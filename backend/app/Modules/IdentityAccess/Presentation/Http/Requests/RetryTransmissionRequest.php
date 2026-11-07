<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class RetryTransmissionRequest extends BackofficeCorrelationRequest
{
    public function idempotencyKey(Account $account, string $transmissionId): string
    {
        $key = $this->headers->get('Idempotency-Key');

        if (! is_string($key) || strlen($key) < 8 || strlen($key) > 128) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'En-tete Idempotency-Key requis.',
                422,
                ['Idempotency-Key' => ['En-tete Idempotency-Key invalide.']],
            );
        }

        return 'transmissions_messages:retry:idk:'.$account->id.':'.$transmissionId.':'.$key;
    }

    public function intentionFingerprint(string $transmissionId): string
    {
        return SecretHasher::hmac(json_encode([
            'action' => 'reprise',
            'transmission_id' => $transmissionId,
        ], JSON_THROW_ON_ERROR));
    }

    public function transmissionId(string $id): string
    {
        $transmissionId = strtoupper($id);

        if (! IdentityUlid::isValid($transmissionId)) {
            throw new IdentityAccessException('transmission_inconnue', 'Cette transmission n est pas visible.', 404);
        }

        return $transmissionId;
    }
}
