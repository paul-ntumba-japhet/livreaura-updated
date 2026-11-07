<?php

namespace App\Modules\IdentityAccess\Infrastructure\Authentication;

use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class SessionIdentityBridge
{
    public const SESSION_KEY = 'a1.authentication_session_id';

    public const SESSION_REF_KEY = 'a1.authentication_session_reference';

    public function bind(?string $authenticationSessionId, ?string $reference = null): void
    {
        if ($authenticationSessionId === null) {
            session()->forget(self::SESSION_KEY);
            session()->forget(self::SESSION_REF_KEY);

            return;
        }

        session()->put(self::SESSION_KEY, $authenticationSessionId);

        if ($reference !== null) {
            session()->put(self::SESSION_REF_KEY, $reference);
        }
    }

    public function currentId(): ?string
    {
        $id = session()->get(self::SESSION_KEY);

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function current(): ?AuthenticationSession
    {
        $id = $this->currentId();
        $reference = session()->get(self::SESSION_REF_KEY);

        if ($id === null || ! is_string($reference) || $reference === '') {
            return null;
        }

        $session = AuthenticationSession::query()->find($id);

        if ($session === null) {
            return null;
        }

        if (! hash_equals((string) $session->empreinte_reference, SecretHasher::hmac($reference))) {
            return null;
        }

        return $session;
    }
}
