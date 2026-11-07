<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListBackofficeSupportDemands
{
    private const KNOWN_TYPES = [
        'recours_suspension',
        'recuperation_canal',
        'revendication_auteur',
        'representation',
        'transfert_principal',
        'reactivation',
        'cloture',
        'incident_technique',
        'examen_compte_conformite',
    ];

    private const AGENT_QUEUE_STATES = ['a_affecter', 'en_cours', 'attente_tiers'];

    public function __construct(
        private HasInternalPermission $hasPermission,
        private GetBackofficeVerificationDossier $reviewQuery,
    ) {}

    /**
     * @param  array{q: ?string, onglet: string, type: ?string, priorite: ?string, page: int, per_page: int}  $filters
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, array $filters): array
    {
        if (! ($this->hasPermission)($actor, 'traiter_support')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $onglet = in_array($filters['onglet'], ['a_traiter', 'mes_demandes', 'attente_demandeur', 'cloturees'], true)
            ? $filters['onglet']
            : 'a_traiter';
        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $query = SupportRequest::query()
            ->with([
                'requester.profile',
                'concerned.profile',
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

        $demandes = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'onglet' => $onglet,
            'totaux' => $this->totals($actor->id),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'demandes' => $demandes
                ->map(fn (SupportRequest $demande): array => $this->serialize($demande))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function totals(string $actorId): array
    {
        $open = fn (): Builder => $this->openQuery();

        return [
            'ouvertes' => (clone $open())->count(),
            'a_traiter' => (clone $open())->where($this->agentQueueConstraint(...))->count(),
            'non_affectes' => (clone $open())
                ->where(function (Builder $query): void {
                    $query
                        ->whereDoesntHave('serviceTask')
                        ->orWhereHas('serviceTask', function ($task): void {
                            $task->where(function ($inner): void {
                                $inner->whereNull('responsable_id')
                                    ->orWhere('etat_travail', 'a_affecter');
                            });
                        });
                })
                ->count(),
            'priorite_haute' => (clone $open())
                ->whereHas('serviceTask', fn ($task) => $task->where('priorite', 'urgente'))
                ->count(),
            'reponses_en_retard' => (clone $open())
                ->whereHas('serviceTask', $this->overdueConstraint(...))
                ->count(),
            'mes_demandes' => (clone $open())
                ->whereHas('serviceTask', fn ($task) => $task->where('responsable_id', $actorId))
                ->count(),
            'attente_demandeur' => (clone $open())
                ->whereHas('serviceTask', fn ($task) => $task->where('etat_travail', 'attente_demandeur'))
                ->count(),
            'cloturees' => $this->closedQuery()->count(),
        ];
    }

    /**
     * @return Builder<SupportRequest>
     */
    private function openQuery(): Builder
    {
        return SupportRequest::query()
            ->whereNull('clos_a')
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('serviceTask')
                    ->orWhereHas('serviceTask', fn ($task) => $task->where('etat_travail', '!=', 'terminee'));
            });
    }

    /**
     * @return Builder<SupportRequest>
     */
    private function closedQuery(): Builder
    {
        return SupportRequest::query()->where(function (Builder $query): void {
            $query
                ->whereNotNull('clos_a')
                ->orWhereHas('serviceTask', fn ($task) => $task->where('etat_travail', 'terminee'));
        });
    }

    /**
     * @param  Builder<SupportRequest>  $query
     */
    private function applyTab(Builder $query, string $onglet, string $actorId): void
    {
        match ($onglet) {
            'mes_demandes' => $query
                ->whereNull('clos_a')
                ->whereHas('serviceTask', function ($task) use ($actorId): void {
                    $task->where('responsable_id', $actorId)->where('etat_travail', '!=', 'terminee');
                }),
            'attente_demandeur' => $query
                ->whereNull('clos_a')
                ->whereHas('serviceTask', fn ($task) => $task->where('etat_travail', 'attente_demandeur')),
            'cloturees' => $query->where(function (Builder $inner): void {
                $inner
                    ->whereNotNull('clos_a')
                    ->orWhereHas('serviceTask', fn ($task) => $task->where('etat_travail', 'terminee'));
            }),
            default => $query
                ->whereNull('clos_a')
                ->where($this->agentQueueConstraint(...)),
        };
    }

    /**
     * @param  Builder<SupportRequest>  $query
     */
    private function agentQueueConstraint(Builder $query): void
    {
        $query->where(function (Builder $inner): void {
            $inner
                ->whereDoesntHave('serviceTask')
                ->orWhereHas('serviceTask', fn ($task) => $task->whereIn('etat_travail', self::AGENT_QUEUE_STATES));
        });
    }

    /**
     * @param  Builder<SupportRequest>  $query
     */
    private function applyOrder(Builder $query, string $onglet): void
    {
        if ($onglet === 'cloturees') {
            $query->orderByDesc('clos_a')->orderByDesc('cree_a')->orderBy('id');

            return;
        }

        $deadline = ServiceTask::query()
            ->selectRaw('COALESCE(echeance_reponse_a, echeance_prise_a)')
            ->whereColumn('demande_support_id', 'demandes_support.id')
            ->limit(1);

        $query->orderBy($deadline)->orderBy('id');
    }

    /**
     * @param  Builder<SupportRequest>  $query
     * @param  array{q: ?string, onglet: string, type: ?string, priorite: ?string, page: int, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $type = $filters['type'];
        if (is_string($type) && in_array($type, self::KNOWN_TYPES, true)) {
            $query->where('type', $type);
        }

        $priorite = $filters['priorite'];
        if (is_string($priorite) && in_array($priorite, ['normale', 'urgente'], true)) {
            $query->whereHas('serviceTask', fn ($task) => $task->where('priorite', $priorite));
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
                ->orWhereRaw('LOWER(reference) like ?', [$needle])
                ->orWhereRaw('LOWER(contact_masque) like ?', [$needle])
                ->orWhereHas('requester', function ($account) use ($needle, $fullNameSql): void {
                    $account
                        ->where('email_normalise', 'like', $needle)
                        ->orWhereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                            $profile
                                ->whereRaw('LOWER(prenom) like ?', [$needle])
                                ->orWhereRaw('LOWER(nom) like ?', [$needle])
                                ->orWhereRaw($fullNameSql, [$needle]);
                        });
                })
                ->orWhereHas('concerned', function ($account) use ($needle, $fullNameSql): void {
                    $account
                        ->where('email_normalise', 'like', $needle)
                        ->orWhereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                            $profile
                                ->whereRaw('LOWER(prenom) like ?', [$needle])
                                ->orWhereRaw('LOWER(nom) like ?', [$needle])
                                ->orWhereRaw($fullNameSql, [$needle]);
                        });
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
    private function serialize(SupportRequest $demande): array
    {
        $task = $demande->serviceTask;
        $assignee = $task?->assignee;
        $deadline = $task?->echeance_reponse_a ?? $task?->echeance_prise_a;

        return [
            'id' => $demande->id,
            'reference' => $demande->reference,
            'type' => $demande->type,
            'identite_etat' => $demande->identite_etat,
            'cree_a' => $demande->cree_a?->toIso8601String(),
            'clos_a' => $demande->clos_a?->toIso8601String() ?? $task?->clos_a?->toIso8601String(),
            'demandeur' => $this->serializeRequester($demande),
            'tache' => $task === null ? null : ($this->reviewQuery)->serializeTask($task),
            'responsable' => $assignee === null ? null : [
                'id' => $assignee->id,
                'prenom' => $assignee->profile?->prenom,
                'nom' => $assignee->profile?->nom,
            ],
            'echeance_a' => $deadline?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: ?string, prenom: ?string, nom: ?string, email_masque: ?string, externe: bool}
     */
    private function serializeRequester(SupportRequest $demande): array
    {
        $account = $demande->requester ?? $demande->concerned;
        if ($account instanceof Account) {
            return [
                'id' => $account->id,
                'prenom' => $account->profile?->prenom,
                'nom' => $account->profile?->nom,
                'email_masque' => DestinationMasker::email($account->email_normalise),
                'externe' => false,
            ];
        }

        return [
            'id' => null,
            'prenom' => null,
            'nom' => null,
            'email_masque' => $demande->contact_masque,
            'externe' => true,
        ];
    }
}
