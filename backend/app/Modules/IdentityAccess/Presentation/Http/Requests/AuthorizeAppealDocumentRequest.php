<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class AuthorizeAppealDocumentRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'compte_id' => ['required', 'string', 'size:26'],
            'facture_vente_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'facture_commission_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'avoir_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'recu_paiement_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'debut' => ['required', 'date'],
            'fin' => ['required', 'date'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{compte_id: string, facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string, debut: string, fin: string, motif: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $accountId = strtoupper(trim((string) $validated['compte_id']));

        if (! IdentityUlid::isValid($accountId)) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
        }

        return [
            'compte_id' => $accountId,
            'facture_vente_id' => $this->optionalId($validated['facture_vente_id'] ?? null),
            'facture_commission_id' => $this->optionalId($validated['facture_commission_id'] ?? null),
            'avoir_id' => $this->optionalId($validated['avoir_id'] ?? null),
            'recu_paiement_id' => $this->optionalId($validated['recu_paiement_id'] ?? null),
            'debut' => trim((string) $validated['debut']),
            'fin' => trim((string) $validated['fin']),
            'motif' => trim((string) $validated['motif']),
        ];
    }

    public function idempotencyKey(Account $account): string
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

        return 'a1-26:grant:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $demandKey): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'demande' => strtoupper($demandKey),
            'action' => 'acces-documents',
            'compte_id' => $payload['compte_id'],
            'facture_vente_id' => $payload['facture_vente_id'],
            'facture_commission_id' => $payload['facture_commission_id'],
            'avoir_id' => $payload['avoir_id'],
            'recu_paiement_id' => $payload['recu_paiement_id'],
            'debut' => $payload['debut'],
            'fin' => $payload['fin'],
            'motif' => $payload['motif'],
        ], JSON_THROW_ON_ERROR));
    }

    private function optionalId(mixed $value): ?string
    {
        $id = strtoupper(trim((string) $value));

        return $id === '' ? null : $id;
    }
}
