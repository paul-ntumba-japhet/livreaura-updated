<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Actions\Configuration\ApproveServiceConfiguration;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListBackofficeTeam
{
    /**
     * @var array<string, list<string>>
     */
    private const FUNCTION_CODES = [
        'kyc' => ['controler_kyc', 'decider_kyc', 'restreindre_habilitation'],
        'commercial' => ['decider_kyc_commercial'],
        'support' => ['traiter_support', 'autoriser_recours'],
        'exploitation' => ['configurer_service', 'reprendre_transmissions', 'affecter_taches'],
    ];

    public function __construct(
        private HasInternalPermission $hasPermission,
        private CurrentServiceConfiguration $current,
    ) {}

    /**
     * @param  array{q: ?string, fonction: ?string, page: int, per_page: int}  $filters
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, array $filters): array
    {
        if (! $this->canAccess($actor)) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $query = Account::query()
            ->where('statut', AccountStatus::Active)
            ->whereHas('internalAttributions', $this->constrainEffective(...))
            ->with([
                'profile',
                'internalAttributions' => function ($attribution): void {
                    $this->constrainEffective($attribution);
                    $attribution->with('permission');
                },
            ]);
        $this->applyFilters($query, $filters);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $agents = $query
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'totaux' => $this->totals(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'agents' => $agents
                ->map(fn (Account $account): array => $this->serializeAgent($account))
                ->values()
                ->all(),
            'suppleances_a_venir' => $this->upcomingSubstitutes(),
            'configurations' => $this->configurationSummaries(),
            'capacites' => [
                'permissions' => ($this->hasPermission)($actor, 'gerer_habilitations_internes'),
                'suppleances' => ($this->hasPermission)($actor, 'affecter_taches'),
                'configurations' => ($this->hasPermission)($actor, 'configurer_service'),
            ],
        ];
    }

    private function canAccess(Account $actor): bool
    {
        return ($this->hasPermission)($actor, 'gerer_habilitations_internes')
            || ($this->hasPermission)($actor, 'affecter_taches')
            || ($this->hasPermission)($actor, 'configurer_service');
    }

    /**
     * @return array<string, int>
     */
    private function totals(): array
    {
        $now = Carbon::now();

        return [
            'agents_actifs' => Account::query()
                ->where('statut', AccountStatus::Active)
                ->whereHas('internalAttributions', $this->constrainEffective(...))
                ->count(),
            'attributions_actives' => InternalAttribution::query()
                ->where($this->constrainEffective(...))
                ->whereHas('account', fn ($account) => $account->where('statut', AccountStatus::Active))
                ->count(),
            'suppleances_planifiees' => ServiceSubstitute::query()
                ->whereNull('annule_a')
                ->whereNotNull('debut_a')
                ->where('debut_a', '>', $now)
                ->where(function ($query) use ($now): void {
                    $query->whereNull('fin_a')->orWhere('fin_a', '>', $now);
                })
                ->count(),
            'configurations_a_approuver' => $this->pendingConfigurationCount(),
        ];
    }

    private function constrainEffective(mixed $query): void
    {
        $now = Carbon::now();
        $query
            ->whereNull('revoque_a')
            ->where(function ($inner) use ($now): void {
                $inner->whereNull('expire_a')->orWhere('expire_a', '>', $now);
            });
    }

    /**
     * @param  Builder<Account>  $query
     * @param  array{q: ?string, fonction: ?string, page: int, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $fonction = $filters['fonction'];
        if (is_string($fonction) && isset(self::FUNCTION_CODES[$fonction])) {
            $codes = self::FUNCTION_CODES[$fonction];
            $query->whereHas('internalAttributions', function ($attribution) use ($codes): void {
                $this->constrainEffective($attribution);
                $attribution->whereHas('permission', fn ($permission) => $permission->whereIn('code', $codes));
            });
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
                ->orWhere('email_normalise', 'like', $needle)
                ->orWhereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                    $profile
                        ->whereRaw('LOWER(prenom) like ?', [$needle])
                        ->orWhereRaw('LOWER(nom) like ?', [$needle])
                        ->orWhereRaw($fullNameSql, [$needle]);
                });
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAgent(Account $account): array
    {
        $codes = $account->internalAttributions
            ->map(fn (InternalAttribution $attribution): ?string => $attribution->permission instanceof InternalPermission
                ? $attribution->permission->code
                : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $expiries = $account->internalAttributions
            ->map(fn (InternalAttribution $attribution) => $attribution->expire_a)
            ->filter()
            ->sort()
            ->values();

        return [
            'id' => $account->id,
            'prenom' => $account->profile?->prenom,
            'nom' => $account->profile?->nom,
            'email_masque' => DestinationMasker::email($account->email_normalise),
            'permissions' => $codes,
            'permissions_actives' => count($codes),
            'expire_a' => $expiries->first()?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingSubstitutes(): array
    {
        $now = Carbon::now();

        return ServiceSubstitute::query()
            ->with(['holder.profile', 'substitute.profile'])
            ->whereNull('annule_a')
            ->whereNotNull('debut_a')
            ->where('debut_a', '>', $now)
            ->where(function ($query) use ($now): void {
                $query->whereNull('fin_a')->orWhere('fin_a', '>', $now);
            })
            ->orderBy('debut_a')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn (ServiceSubstitute $row): array => [
                'id' => $row->id,
                'fonction' => $row->fonction,
                'debut_a' => $row->debut_a?->toIso8601String(),
                'fin_a' => $row->fin_a?->toIso8601String(),
                'titulaire' => $this->serializePerson($row->holder),
                'suppleant' => $this->serializePerson($row->substitute),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{code: string, version: ?int, courante: bool}>
     */
    private function configurationSummaries(): array
    {
        $summaries = [];

        foreach (ApproveServiceConfiguration::CODES as $code) {
            $current = $this->current->find($code);
            $latest = ServiceConfiguration::query()
                ->where('code', $code)
                ->orderByDesc('version')
                ->first();

            $summaries[] = [
                'code' => $code,
                'version' => $latest?->version,
                'courante' => $current !== null && $latest !== null && $current->id === $latest->id,
            ];
        }

        return $summaries;
    }

    private function pendingConfigurationCount(): int
    {
        $count = 0;

        foreach (ApproveServiceConfiguration::CODES as $code) {
            $current = $this->current->find($code);
            $latest = ServiceConfiguration::query()
                ->where('code', $code)
                ->orderByDesc('version')
                ->first();
            if ($latest !== null && ($current === null || $current->id !== $latest->id)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{id: ?string, prenom: ?string, nom: ?string}
     */
    private function serializePerson(?Account $account): array
    {
        return [
            'id' => $account?->id,
            'prenom' => $account?->profile?->prenom,
            'nom' => $account?->profile?->nom,
        ];
    }
}
