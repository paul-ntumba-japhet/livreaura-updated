<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessAccountBackoffice;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListBackofficeAccounts
{
    public function __construct(
        private CanAccessAccountBackoffice $canAccess,
    ) {}

    /**
     * @param  array{q: ?string, statut: ?string, role: ?string, email_verifie: ?string, page: int, per_page: int}  $filters
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

        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $query = Account::query()
            ->with([
                'profile',
                'accountRoles.role',
                'organizationMemberships.organization',
            ]);

        $this->applyFilters($query, $filters);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $accounts = $query
            ->orderByDesc('cree_a')
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $lastSeen = $this->lastSeenByAccount($accounts->pluck('id')->all());

        return [
            'totaux' => [
                'total' => Account::query()->count(),
                'active' => Account::query()->where('statut', AccountStatus::Active)->count(),
                'pending' => Account::query()->where('statut', AccountStatus::Pending)->count(),
                'suspended' => Account::query()->where('statut', AccountStatus::Suspended)->count(),
                'closed' => Account::query()->where('statut', AccountStatus::Closed)->count(),
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'comptes' => $accounts
                ->map(fn (Account $account): array => $this->serialize($account, $lastSeen[$account->id] ?? null))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Builder<Account>  $query
     * @param  array{q: ?string, statut: ?string, role: ?string, email_verifie: ?string, page: int, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $statut = $filters['statut'];
        if (is_string($statut) && AccountStatus::tryFrom($statut) !== null) {
            $query->where('statut', $statut);
        }

        $verified = $filters['email_verifie'];
        if ($verified === 'oui') {
            $query->whereNotNull('email_verifie_a');
        }
        if ($verified === 'non') {
            $query->whereNull('email_verifie_a');
        }

        $role = $filters['role'];
        if (is_string($role) && in_array($role, ['reader', 'author', 'admin'], true)) {
            $query->whereHas('accountRoles', function ($assignment) use ($role): void {
                $assignment
                    ->where('statut', 'active')
                    ->whereHas('role', fn ($roleQuery) => $roleQuery->where('code', $role));
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
     * @param  list<string>  $accountIds
     * @return array<string, string>
     */
    private function lastSeenByAccount(array $accountIds): array
    {
        if ($accountIds === []) {
            return [];
        }

        return AuthenticationSession::query()
            ->select(['compte_id', 'derniere_activite_a'])
            ->whereIn('compte_id', $accountIds)
            ->orderByDesc('derniere_activite_a')
            ->orderByDesc('id')
            ->get()
            ->unique('compte_id')
            ->mapWithKeys(function (AuthenticationSession $session): array {
                $value = $session->derniere_activite_a;

                return [
                    $session->compte_id => $value instanceof \DateTimeInterface
                        ? Carbon::instance($value)->toIso8601String()
                        : null,
                ];
            })
            ->filter()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Account $account, ?string $lastSeenAt): array
    {
        $roles = $account->accountRoles
            ->filter(fn (AccountRole $assignment): bool => $assignment->statut === 'active' && $assignment->role instanceof Role)
            ->map(fn (AccountRole $assignment): string => $assignment->role->code)
            ->unique()
            ->values()
            ->all();

        $organization = $account->organizationMemberships
            ->filter(fn (OrganizationMembership $membership): bool => $membership->statut === 'active')
            ->sortByDesc(fn (OrganizationMembership $membership): int => $membership->principal ? 1 : 0)
            ->map(fn (OrganizationMembership $membership): ?string => $membership->organization?->raison_sociale)
            ->filter()
            ->first();

        return [
            'id' => $account->id,
            'statut' => $account->statut->value,
            'email_masque' => DestinationMasker::email($account->email_normalise),
            'email_verifie' => $account->email_verifie_a !== null,
            'prenom' => $account->profile?->prenom,
            'nom' => $account->profile?->nom,
            'roles' => $roles,
            'organisation' => is_string($organization) ? $organization : null,
            'derniere_connexion_a' => $lastSeenAt,
        ];
    }
}
