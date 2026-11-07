<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessKycBackoffice;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListBackofficeKycDossiers
{
    private const OPEN_STATUSES = ['submitted', 'under_review'];

    private const HISTORY_STATUSES = ['approved', 'rejected', 'expired'];

    public function __construct(
        private CanAccessKycBackoffice $canAccess,
        private IsIndependentReviewer $independent,
        private GetBackofficeVerificationDossier $reviewQuery,
    ) {}

    /**
     * @param  array{q: ?string, onglet: string, niveau: ?string, responsable_id: ?string, page: int, per_page: int}  $filters
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, array $filters): array
    {
        if (! ($this->canAccess)($actor)) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $onglet = in_array($filters['onglet'], ['a_traiter', 'mes_dossiers', 'a_corriger', 'historique'], true)
            ? $filters['onglet']
            : 'a_traiter';
        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $query = $this->baseQuery()
            ->with([
                'subject.organization',
                'subject.profile',
                'serviceTask.assignee.profile',
            ]);
        $this->applyTab($query, $onglet, $actor->id);
        $this->applyFilters($query, $filters);
        $this->applyOrder($query, $onglet);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $dossiers = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'onglet' => $onglet,
            'totaux' => $this->totals($actor->id),
            'responsables' => $this->assignees(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'dossiers' => $dossiers
                ->map(fn (VerificationDossier $dossier): array => $this->serialize($actor, $dossier))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Builder<VerificationDossier>
     */
    private function baseQuery(): Builder
    {
        return VerificationDossier::query()
            ->where('statut', '!=', 'draft')
            ->whereHas('serviceTask');
    }

    /**
     * @return array<string, int>
     */
    private function totals(string $actorId): array
    {
        $open = fn (): Builder => $this->baseQuery()->whereIn('statut', self::OPEN_STATUSES);

        return [
            'a_traiter' => (clone $open())->count(),
            'non_affectes' => (clone $open())
                ->whereHas('serviceTask', function ($task): void {
                    $task->where(function ($inner): void {
                        $inner->whereNull('responsable_id')
                            ->orWhere('etat_travail', 'a_affecter');
                    });
                })
                ->count(),
            'en_retard' => (clone $open())
                ->whereHas('serviceTask', $this->overdueConstraint(...))
                ->count(),
            'a_corriger' => $this->baseQuery()->where('statut', 'needs_fix')->count(),
            'mes_dossiers' => (clone $open())
                ->whereHas('serviceTask', fn ($task) => $task->where('responsable_id', $actorId))
                ->count(),
            'historique' => $this->baseQuery()->whereIn('statut', self::HISTORY_STATUSES)->count(),
        ];
    }

    /**
     * @return list<array{id: string, prenom: ?string, nom: ?string}>
     */
    private function assignees(): array
    {
        $ids = ServiceTask::query()
            ->whereNotNull('dossier_kyc_id')
            ->whereNotNull('responsable_id')
            ->distinct()
            ->pluck('responsable_id')
            ->all();

        if ($ids === []) {
            return [];
        }

        return Account::query()
            ->with('profile')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'prenom' => $account->profile?->prenom,
                'nom' => $account->profile?->nom,
            ])
            ->sortBy(fn (array $person): string => mb_strtolower(trim(($person['prenom'] ?? '').' '.($person['nom'] ?? ''))))
            ->values()
            ->all();
    }

    /**
     * @param  Builder<VerificationDossier>  $query
     */
    private function applyTab(Builder $query, string $onglet, string $actorId): void
    {
        match ($onglet) {
            'mes_dossiers' => $query
                ->whereIn('statut', self::OPEN_STATUSES)
                ->whereHas('serviceTask', fn ($task) => $task->where('responsable_id', $actorId)),
            'a_corriger' => $query->where('statut', 'needs_fix'),
            'historique' => $query->whereIn('statut', self::HISTORY_STATUSES),
            default => $query->whereIn('statut', self::OPEN_STATUSES),
        };
    }

    /**
     * @param  Builder<VerificationDossier>  $query
     */
    private function applyOrder(Builder $query, string $onglet): void
    {
        if ($onglet === 'a_corriger' || $onglet === 'historique') {
            $query->orderByDesc('clos_a')->orderByDesc('soumis_a')->orderBy('id');

            return;
        }

        $deadline = ServiceTask::query()
            ->select('echeance_prise_a')
            ->whereColumn('dossier_kyc_id', 'dossiers_verification.id')
            ->limit(1);

        $query->orderBy($deadline)->orderBy('id');
    }

    /**
     * @param  Builder<VerificationDossier>  $query
     * @param  array{q: ?string, onglet: string, niveau: ?string, responsable_id: ?string, page: int, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $niveau = $filters['niveau'];
        if (is_string($niveau) && in_array($niveau, ['organisation_verifiee', 'partenaire_commercial'], true)) {
            $query->where('niveau', $niveau);
        }

        $responsableId = $filters['responsable_id'];
        if (is_string($responsableId) && $responsableId !== '') {
            $query->whereHas('serviceTask', fn ($task) => $task->where('responsable_id', strtoupper($responsableId)));
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search === '') {
            return;
        }

        $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
        $fullNameSql = DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(prenom || ' ' || nom) like ?"
            : "LOWER(CONCAT(prenom, ' ', nom)) like ?";

        $query->where(function (Builder $builder) use ($search, $needle, $fullNameSql): void {
            $builder
                ->where('id', strtoupper($search))
                ->orWhereRaw('LOWER(numero) like ?', [$needle])
                ->orWhereHas('subject.organization', function ($organization) use ($needle): void {
                    $organization->whereRaw('LOWER(raison_sociale) like ?', [$needle]);
                })
                ->orWhereHas('subject.profile', function ($profile) use ($needle, $fullNameSql): void {
                    $profile
                        ->whereRaw('LOWER(prenom) like ?', [$needle])
                        ->orWhereRaw('LOWER(nom) like ?', [$needle])
                        ->orWhereRaw($fullNameSql, [$needle]);
                });
        });
    }

    /**
     * @param  Builder<ServiceTask>  $task
     */
    private function overdueConstraint($task): void
    {
        $now = Carbon::now();
        $task->where(function ($inner) use ($now): void {
            $inner
                ->where(function ($prise) use ($now): void {
                    $prise->whereNotNull('echeance_prise_a')->where('echeance_prise_a', '<', $now);
                })
                ->orWhere(function ($reponse) use ($now): void {
                    $reponse->whereNotNull('echeance_reponse_a')->where('echeance_reponse_a', '<', $now);
                });
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Account $actor, VerificationDossier $dossier): array
    {
        $task = $dossier->serviceTask;
        $assignee = $task?->assignee;
        $deadline = $task?->echeance_prise_a ?? $task?->echeance_reponse_a;

        return [
            'id' => $dossier->id,
            'numero' => $dossier->numero,
            'version' => $dossier->version,
            'niveau' => $dossier->niveau,
            'statut' => $dossier->statut,
            'sujet' => [
                'type' => $dossier->subject?->isOrganization() ? 'organisation' : 'particulier',
                'libelle' => $this->subjectLabel($dossier),
            ],
            'tache' => $task === null ? null : ($this->reviewQuery)->serializeTask($task),
            'responsable' => $assignee === null ? null : [
                'id' => $assignee->id,
                'prenom' => $assignee->profile?->prenom,
                'nom' => $assignee->profile?->nom,
            ],
            'echeance_a' => $deadline?->toIso8601String(),
            'clos_a' => $task?->clos_a?->toIso8601String() ?? $dossier->clos_a?->toIso8601String(),
            'conflit_interet' => ! ($this->independent)($actor, $dossier),
        ];
    }

    private function subjectLabel(VerificationDossier $dossier): string
    {
        $subject = $dossier->subject;
        if ($subject?->isOrganization()) {
            $name = trim((string) $subject->organization?->raison_sociale);

            return $name !== '' ? $name : 'Organisation';
        }

        $prenom = trim((string) $subject?->profile?->prenom);
        $nom = trim((string) $subject?->profile?->nom);
        if ($prenom !== '' && $nom !== '') {
            return $prenom.' '.$nom;
        }

        return $prenom !== '' ? $prenom : ($nom !== '' ? $nom : 'Personne');
    }
}
