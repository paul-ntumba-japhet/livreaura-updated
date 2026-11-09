<?php

namespace App\Modules\Catalogue\Application\Support;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use Illuminate\Support\Facades\DB;

final class CatalogueActor
{
    public function __construct(private HasInternalPermission $permissions) {}

    public function assertSession(Account $actor, ?string $sessionId): Account
    {
        return $this->lockAccount($actor, $sessionId);
    }

    /**
     * @param  list<string>  $roles
     */
    public function assert(Account $actor, ?string $sessionId, string $permission, array $roles): Account
    {
        $account = $this->lockAccount($actor, $sessionId);

        InternalAttribution::query()
            ->where('compte_id', $account->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if (! $this->hasRole($account->id, $roles) || ! ($this->permissions)($account, $permission)) {
            throw CatalogueConsultationException::forbidden(
                'permission_insuffisante',
                'Cette action n’est pas autorisée.',
            );
        }

        return $account;
    }

    private function lockAccount(Account $actor, ?string $sessionId): Account
    {
        $account = Account::query()->where('id', $actor->id)->lockForUpdate()->first();
        $session = $sessionId === null
            ? null
            : AuthenticationSession::query()->where('id', $sessionId)->lockForUpdate()->first();

        if ($account === null || $session === null || $session->compte_id !== $account->id) {
            throw CatalogueConsultationException::unauthenticated();
        }

        if ($session->statut !== SessionStatus::Active || $session->revoque_a !== null) {
            throw CatalogueConsultationException::unauthenticated();
        }

        $now = now();

        if ($session->expire_absolue_a <= $now || $session->expire_inactivite_a <= $now) {
            throw CatalogueConsultationException::unauthenticated();
        }

        if ($account->statut !== AccountStatus::Active || $session->portee !== SessionAccessScope::Normal) {
            throw CatalogueConsultationException::forbidden(
                'session_restreinte',
                'Cette session ne permet pas cette action.',
            );
        }

        return $account;
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasRole(string $accountId, array $roles): bool
    {
        return DB::table('comptes_roles')
            ->join('roles', 'roles.id', '=', 'comptes_roles.role_id')
            ->where('comptes_roles.compte_id', $accountId)
            ->where('comptes_roles.statut', 'active')
            ->whereNull('comptes_roles.revoque_a')
            ->whereIn('roles.code', $roles)
            ->exists();
    }
}
