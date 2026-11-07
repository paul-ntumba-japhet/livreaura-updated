<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionAttempt;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionSecret;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverMessageTransmission
{
    public const int MAX_AUTOMATIC_ATTEMPTS = 3;

    public function __construct(private IdentityNotificationSender $notifications) {}

    public function __invoke(string $transmissionId): void
    {
        $prepared = $this->beginAttempt($transmissionId);

        if ($prepared === null) {
            return;
        }

        $result = 'accepted';
        $maskedError = null;

        try {
            $prepared['secret'] = Crypt::decryptString($prepared['ciphertext']);
            unset($prepared['ciphertext']);
            $this->send($prepared);
        } catch (Throwable $exception) {
            $result = $this->isUnknown($exception) ? 'unknown' : 'failed';
            $maskedError = $this->maskedError($exception);
            Log::warning('identity.transmission.failed', [
                'transmission_id' => $transmissionId,
                'result' => $result,
                'code' => $maskedError,
                'exception' => $exception::class,
            ]);
        }

        $this->completeAttempt(
            $transmissionId,
            $prepared['owner'],
            $prepared['attempt_id'],
            $prepared['numero'],
            $result,
            $maskedError,
        );
    }

    /**
     * @return array{owner: string, attempt_id: string, numero: int, account: Account, challenge: VerificationChallenge, ciphertext: string, type: string}|null
     */
    private function beginAttempt(string $transmissionId): ?array
    {
        return DB::transaction(function () use ($transmissionId): ?array {
            $transmission = MessageTransmission::query()
                ->where('id', $transmissionId)
                ->lockForUpdate()
                ->first();

            if ($transmission === null || $transmission->etat !== 'processing') {
                return null;
            }

            if ($transmission->bail_jusqua === null || $transmission->bail_jusqua->isPast()) {
                return null;
            }

            $owner = (string) $transmission->pris_par;

            if ($owner === '') {
                return null;
            }

            $this->closeStaleStartedAttempts($transmission);

            $source = $this->resolveSource($transmission);

            if ($source === null) {
                $this->writeAttempt($transmission, 'cancelled', now());
                $this->release($transmission, 'cancelled');

                return null;
            }

            $numero = $transmission->nombre_tentatives + 1;
            $attempt = $this->writeAttempt($transmission, 'started', null, $numero);
            $transmission->nombre_tentatives = $numero;
            $transmission->save();

            return [
                'owner' => $owner,
                'attempt_id' => $attempt->id,
                'numero' => $numero,
                'account' => $source['account'],
                'challenge' => $source['challenge'],
                'ciphertext' => (string) $transmission->contenu_chiffre_temporaire,
                'type' => $source['type'],
            ];
        });
    }

    /**
     * @param  array{owner: string, attempt_id: string, numero: int, account: Account, challenge: VerificationChallenge, secret: string, type: string, ciphertext?: string}  $prepared
     */
    private function send(array $prepared): void
    {
        match ($prepared['type']) {
            'defi_email_activation' => $this->notifications->sendEmailActivation(
                $prepared['account'],
                $prepared['challenge']->id,
                $prepared['secret'],
            ),
            'defi_password_reset' => $this->notifications->sendPasswordReset(
                $prepared['account'],
                $prepared['challenge']->id,
                $prepared['secret'],
            ),
            default => null,
        };
    }

    private function completeAttempt(
        string $transmissionId,
        string $owner,
        string $attemptId,
        int $numero,
        string $result,
        ?string $maskedError,
    ): void {
        DB::transaction(function () use ($transmissionId, $owner, $attemptId, $numero, $result, $maskedError): void {
            $transmission = MessageTransmission::query()
                ->where('id', $transmissionId)
                ->lockForUpdate()
                ->first();

            if ($transmission === null || (string) $transmission->pris_par !== $owner) {
                return;
            }

            $attempt = TransmissionAttempt::query()
                ->where('id', $attemptId)
                ->lockForUpdate()
                ->first();

            if ($attempt === null || $attempt->termine_a !== null) {
                return;
            }

            $now = now();
            $attempt->resultat = $result;
            $attempt->termine_a = $now;
            $attempt->code_erreur_masque = $maskedError;
            $attempt->save();

            if ($result === 'accepted') {
                $transmission->etat = 'sent';
                $transmission->envoye_a = $now;
                $transmission->prochaine_tentative_a = null;
                $transmission->pris_par = null;
                $transmission->bail_jusqua = null;
                $transmission->save();

                return;
            }

            if ($numero >= self::MAX_AUTOMATIC_ATTEMPTS) {
                $this->release($transmission, 'failed');

                return;
            }

            $transmission->etat = 'retry_wait';
            $transmission->prochaine_tentative_a = $numero === 1
                ? $now->copy()->addMinutes(1)
                : $now->copy()->addMinutes(5);
            $transmission->pris_par = null;
            $transmission->bail_jusqua = null;
            $transmission->save();
        });
    }

    /**
     * @return array{account: Account, challenge: VerificationChallenge, type: string}|null
     */
    private function resolveSource(MessageTransmission $transmission): ?array
    {
        if (
            $transmission->canal !== 'email'
            || $transmission->contenu_chiffre_temporaire === null
            || $transmission->contenu_expire_a === null
            || $transmission->contenu_expire_a->isPast()
            || $transmission->contenu_purge_a !== null
        ) {
            return null;
        }

        $secretRow = TransmissionSecret::query()
            ->where('transmission_id', $transmission->id)
            ->first();

        if ($secretRow === null || $secretRow->defi_id === null) {
            return null;
        }

        $challenge = VerificationChallenge::query()
            ->where('id', $secretRow->defi_id)
            ->first();

        if (
            $challenge === null
            || $challenge->consomme_a !== null
            || $challenge->revoque_a !== null
            || $challenge->expire_a <= now()
        ) {
            return null;
        }

        $message = $transmission->message;

        if ($message === null || $message->defi_id !== $challenge->id) {
            return null;
        }

        if (! in_array($message->type, ['defi_email_activation', 'defi_password_reset'], true)) {
            return null;
        }

        $account = Account::query()->where('id', $transmission->compte_destinataire_id)->first();

        if ($account === null) {
            return null;
        }

        return [
            'account' => $account,
            'challenge' => $challenge,
            'type' => (string) $message->type,
        ];
    }

    private function closeStaleStartedAttempts(MessageTransmission $transmission): void
    {
        TransmissionAttempt::query()
            ->where('transmission_id', $transmission->id)
            ->where('resultat', 'started')
            ->whereNull('termine_a')
            ->update([
                'resultat' => 'unknown',
                'termine_a' => now(),
                'code_erreur_masque' => 'worker_interrompu',
            ]);
    }

    private function writeAttempt(
        MessageTransmission $transmission,
        string $result,
        $finishedAt,
        ?int $numero = null,
    ): TransmissionAttempt {
        $attempt = new TransmissionAttempt;
        $attempt->id = IdentityUlid::make();
        $attempt->transmission_id = $transmission->id;
        $attempt->numero = $numero ?? ($transmission->nombre_tentatives + 1);
        $attempt->commence_a = now();
        $attempt->termine_a = $finishedAt;
        $attempt->resultat = $result;
        $attempt->save();

        return $attempt;
    }

    private function release(MessageTransmission $transmission, string $etat): void
    {
        $transmission->etat = $etat;
        $transmission->pris_par = null;
        $transmission->bail_jusqua = null;

        if ($etat !== 'retry_wait') {
            $transmission->prochaine_tentative_a = null;
        }

        $transmission->save();
    }

    private function isUnknown(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out');
    }

    private function maskedError(Throwable $exception): string
    {
        return $this->isUnknown($exception) ? 'timeout' : 'envoi_refuse';
    }
}
