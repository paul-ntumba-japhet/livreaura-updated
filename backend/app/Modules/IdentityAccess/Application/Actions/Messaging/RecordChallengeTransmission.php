<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionSecret;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RecordChallengeTransmission
{
    public function __construct(
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $configurations,
    ) {}

    public function __invoke(
        Account $account,
        VerificationChallenge $challenge,
        string $secret,
        string $idempotencyKey,
        string $type,
        string $operation,
    ): ?MessageTransmission {
        $message = $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: $type,
            operation: $operation,
            correlation: $challenge->id,
            intentionFingerprint: SecretHasher::hmac($challenge->id),
            payload: [
                'compte_id' => $account->id,
                'defi_id' => $challenge->id,
                'finalite' => $challenge->finalite->value,
            ],
            accountId: $account->id,
            challengeId: $challenge->id,
        );

        $delayConfig = $this->configurations->find('A1_delais');

        if ($delayConfig === null) {
            Log::error('identity.transmission.skipped', [
                'reason' => 'configuration_absente',
                'code' => 'A1_delais',
                'compte_id' => $account->id,
                'defi_id' => $challenge->id,
            ]);

            return null;
        }

        $existing = MessageTransmission::query()
            ->where('message_id', $message->id)
            ->where('destinataire_cle', 'titulaire')
            ->where('canal', 'email')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $transmission = new MessageTransmission;
        $transmission->id = IdentityUlid::make();
        $transmission->message_id = $message->id;
        $transmission->destinataire_cle = 'titulaire';
        $transmission->canal = 'email';
        $transmission->compte_destinataire_id = $account->id;
        $transmission->destination_masquee = $challenge->destination_masquee;
        $transmission->etat = 'queued';
        $transmission->config_delais_id = $delayConfig->id;
        $transmission->nombre_tentatives = 0;
        $transmission->contenu_chiffre_temporaire = Crypt::encryptString($secret);
        $transmission->cle_chiffrement_ref = 'app.identity.envelope';
        $transmission->contenu_expire_a = $challenge->expire_a;
        $transmission->save();

        $transmissionSecret = new TransmissionSecret;
        $transmissionSecret->transmission_id = $transmission->id;
        $transmissionSecret->defi_id = $challenge->id;
        $transmissionSecret->save();

        $this->scheduleMailDispatch();

        return $transmission;
    }

    private function scheduleMailDispatch(): void
    {
        if (config('identity.notification_driver') !== 'mail') {
            return;
        }

        dispatch(static function (): void {
            app(DispatchDueTransmissions::class)(sendInline: true);
        })->afterResponse();
    }
}
