<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessKycBackoffice;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListBackofficeCapabilities
{
    private const CURRENT_STATES = ['valid', 'a_renouveler'];

    private const OPEN_RENEWAL_STATUSES = ['draft', 'submitted', 'under_review', 'needs_fix'];

    private const HORIZON_DAYS = 30;

    public function __construct(
        private CanAccessKycBackoffice $canAccess,
    ) {}

    /**
     * @param  array{q: ?string, onglet: string, niveau: ?string, echeance: ?string, page: int, per_page: int}  $filters
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

        $onglet = in_array($filters['onglet'], ['toutes', 'valides', 'restreintes', 'expirees', 'revoquees'], true)
            ? $filters['onglet']
            : 'toutes';
        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $query = VerificationCapability::query()
            ->with(['subject.organization', 'subject.profile', 'dossier']);
        $this->applyTab($query, $onglet);
        $this->applyFilters($query, $filters);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $capabilities = $query
            ->orderByRaw('valide_jusqua is null')
            ->orderBy('valide_jusqua')
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $renewals = $this->openRenewals($capabilities);

        return [
            'onglet' => $onglet,
            'totaux' => $this->totals(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'habilitations' => $capabilities
                ->map(fn (VerificationCapability $capability): array => $this->serialize(
                    $capability,
                    $renewals->get($capability->sujet_id.'|'.$capability->niveau),
                ))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function totals(): array
    {
        $now = Carbon::now();
        $horizon = $now->copy()->addDays(self::HORIZON_DAYS);
        $current = fn (): Builder => VerificationCapability::query()->whereIn('etat', self::CURRENT_STATES);

        return [
            'toutes' => VerificationCapability::query()->count(),
            'valides' => (clone $current())->count(),
            'expirent_30j' => (clone $current())
                ->whereNotNull('valide_jusqua')
                ->where('valide_jusqua', '>', $now)
                ->where('valide_jusqua', '<=', $horizon)
                ->count(),
            'restreintes' => VerificationCapability::query()->where('etat', 'restreint')->count(),
            'expirees' => VerificationCapability::query()->where('etat', 'expired')->count(),
            'revoquees' => VerificationCapability::query()->where('etat', 'revoked')->count(),
        ];
    }

    /**
     * @param  Builder<VerificationCapability>  $query
     */
    private function applyTab(Builder $query, string $onglet): void
    {
        match ($onglet) {
            'valides' => $query->whereIn('etat', self::CURRENT_STATES),
            'restreintes' => $query->where('etat', 'restreint'),
            'expirees' => $query->where('etat', 'expired'),
            'revoquees' => $query->where('etat', 'revoked'),
            default => null,
        };
    }

    /**
     * @param  Builder<VerificationCapability>  $query
     * @param  array{q: ?string, onglet: string, niveau: ?string, echeance: ?string, page: int, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $niveau = $filters['niveau'];
        if (is_string($niveau) && in_array($niveau, ['organisation_verifiee', 'partenaire_commercial'], true)) {
            $query->where('niveau', $niveau);
        }

        $echeance = $filters['echeance'];
        $now = Carbon::now();
        if ($echeance === '30j') {
            $query
                ->whereIn('etat', self::CURRENT_STATES)
                ->whereNotNull('valide_jusqua')
                ->where('valide_jusqua', '>', $now)
                ->where('valide_jusqua', '<=', $now->copy()->addDays(self::HORIZON_DAYS));
        } elseif ($echeance === 'depassee') {
            $query->whereNotNull('valide_jusqua')->where('valide_jusqua', '<', $now);
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
     * @param  Collection<int, VerificationCapability>  $capabilities
     * @return Collection<string, VerificationDossier>
     */
    private function openRenewals(Collection $capabilities): Collection
    {
        if ($capabilities->isEmpty()) {
            return collect();
        }

        $subjectIds = $capabilities->pluck('sujet_id')->unique()->values()->all();

        return VerificationDossier::query()
            ->whereIn('sujet_id', $subjectIds)
            ->whereIn('statut', self::OPEN_RENEWAL_STATUSES)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (VerificationDossier $dossier): string => $dossier->sujet_id.'|'.$dossier->niveau)
            ->keyBy(fn (VerificationDossier $dossier): string => $dossier->sujet_id.'|'.$dossier->niveau);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(VerificationCapability $capability, ?VerificationDossier $renewal): array
    {
        $subject = $capability->subject;
        $isOrganization = $subject?->isOrganization() === true;
        $libelle = $this->subjectLabel($capability);

        return [
            'id' => $capability->id,
            'niveau' => $capability->niveau,
            'etat' => $capability->etat,
            'valide_jusqua' => $capability->valide_jusqua?->toIso8601String(),
            'obtenue_a' => $capability->obtenue_a?->toIso8601String(),
            'dossier_id' => $renewal?->id ?? $capability->dossier_courant_id,
            'beneficiaire' => [
                'type' => $isOrganization ? 'organisation' : 'particulier',
                'libelle' => $libelle,
            ],
            'renouvellement' => $this->serializeRenewal($capability, $renewal),
        ];
    }

    /**
     * @return array{code: string, jours_restants: ?int, dossier_id: ?string}
     */
    private function serializeRenewal(VerificationCapability $capability, ?VerificationDossier $renewal): array
    {
        if ($renewal !== null) {
            $code = match ($renewal->statut) {
                'needs_fix' => 'a_corriger',
                'submitted', 'under_review' => 'en_examen',
                default => 'en_examen',
            };

            return [
                'code' => $code,
                'jours_restants' => $this->daysRemaining($capability),
                'dossier_id' => $renewal->id,
            ];
        }

        if (in_array($capability->etat, self::CURRENT_STATES, true)) {
            return [
                'code' => 'aucun',
                'jours_restants' => $this->daysRemaining($capability),
                'dossier_id' => $capability->dossier_courant_id,
            ];
        }

        return [
            'code' => 'non_demande',
            'jours_restants' => null,
            'dossier_id' => $capability->dossier_courant_id,
        ];
    }

    private function daysRemaining(VerificationCapability $capability): ?int
    {
        if ($capability->valide_jusqua === null) {
            return null;
        }

        return (int) round(
            Carbon::now()->copy()->startOfDay()->diffInDays($capability->valide_jusqua->copy()->startOfDay(), false),
        );
    }

    private function subjectLabel(VerificationCapability $capability): string
    {
        $subject = $capability->subject;
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
