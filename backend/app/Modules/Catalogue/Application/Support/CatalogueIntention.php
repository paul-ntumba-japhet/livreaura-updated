<?php

namespace App\Modules\Catalogue\Application\Support;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;

final class CatalogueIntention
{
    /**
     * @return array<string, mixed>|null
     */
    public function replay(string $key, string $fingerprint): ?array
    {
        $existing = OutgoingMessage::query()->where('cle_idempotence', $key)->first();

        if ($existing === null) {
            return null;
        }

        if (! hash_equals((string) $existing->empreinte_intention, $fingerprint)) {
            throw CatalogueConsultationException::conflict('Cette clé correspond déjà à une autre intention.');
        }

        $stored = $existing->donnees_minimales;

        return is_array($stored) ? $stored : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $key,
        string $type,
        string $operation,
        string $fingerprint,
        string $correlation,
        ?string $accountId,
        array $payload,
    ): string {
        $id = IdentityUlid::make();
        $message = new OutgoingMessage([
            'id' => $id,
            'cle_idempotence' => $key,
            'type' => $type,
            'operation' => $operation,
            'correlation' => $correlation,
            'compte_id' => $accountId,
            'producteur' => 'A2',
            'schema_message_version' => 1,
            'empreinte_intention' => $fingerprint,
            'donnees_minimales' => $payload,
        ]);
        $message->cree_a = now();
        $message->save();

        return $id;
    }

    public function key(string $operation, string $actorId, string $resourceId, ?string $clientKey): string
    {
        if (! is_string($clientKey) || ! preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $clientKey)) {
            throw CatalogueConsultationException::invalidInput(
                'Idempotency-Key',
                'La clé d’idempotence est absente ou non recevable.',
            );
        }

        return $operation.':'.$actorId.':'.$resourceId.':'.$clientKey;
    }

    /**
     * @param  array<string, mixed>  $intention
     */
    public function fingerprint(array $intention): string
    {
        return hash('sha256', (string) json_encode($this->canonical($intention), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->canonical(...), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonical($item);
        }

        return $value;
    }
}
