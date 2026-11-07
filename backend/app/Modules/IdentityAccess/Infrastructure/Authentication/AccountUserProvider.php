<?php

namespace App\Modules\IdentityAccess\Infrastructure\Authentication;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class AccountUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return Account::query()->find($identifier);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        $email = $credentials['email_normalise'] ?? $credentials['email'] ?? null;

        if (! is_string($email) || $email === '') {
            return null;
        }

        return Account::query()
            ->where('email_normalise', mb_strtolower(trim($email)))
            ->first();
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        if (! is_string($password) || ! $user instanceof Account) {
            return false;
        }

        return Hash::check($password, $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
    }
}
