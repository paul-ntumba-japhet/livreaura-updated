<?php

namespace App\Modules\IdentityAccess\Application\Actions\Passwords;

use App\Modules\IdentityAccess\Application\Actions\Messaging\CancelPendingTransmissions;
use App\Modules\IdentityAccess\Application\Actions\Messaging\RevokeOpenChallenges;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Exceptions\InvalidChallenge;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class ResetPassword
{
    public function __construct(
        private CoordinationGuard $coordination,
        private RevokeAccountSessions $revokeSessions,
        private RevokeOpenChallenges $revokeOpen,
        private CancelPendingTransmissions $cancelPending,
        private IdentityOutboxPublisher $outbox,
        private IdentityNotificationSender $notifications,
    ) {}

    public function __invoke(string $challengeId, string $proof, string $passwordHash, string $correlationId): Account
    {
        $account = $this->coordination->withinIdentity(function () use ($challengeId, $proof, $passwordHash, $correlationId): Account {
            $challenge = VerificationChallenge::query()
                ->where('id', $challengeId)
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->finalite !== ChallengePurpose::PasswordReset) {
                throw InvalidChallenge::rejected();
            }

            $account = Account::query()->where('id', $challenge->compte_id)->lockForUpdate()->first();

            if ($account === null || $account->statut === AccountStatus::Closed) {
                throw InvalidChallenge::ineligibleAccount();
            }

            if (
                $challenge->consomme_a !== null
                || $challenge->revoque_a !== null
                || $challenge->expire_a <= now()
                || $challenge->nombre_essais >= $challenge->plafond_essais
            ) {
                throw InvalidChallenge::rejected();
            }

            if (! SecretHasher::equals((string) $challenge->empreinte_secret, $proof)) {
                $challenge->nombre_essais++;
                $challenge->save();

                throw InvalidChallenge::rejected();
            }

            $now = now();
            $challenge->consomme_a = $now;
            $challenge->save();

            ($this->revokeOpen)($account->id, ChallengePurpose::PasswordReset, $challenge->id);
            $this->cancelPending->forChallenges([$challenge->id]);

            $account->hash_mdp = $passwordHash;
            $account->save();

            ($this->revokeSessions)($account->id, 'Reinitialisation du mot de passe.');

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $account->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'statut' => $account->statut->value,
                'mot_de_passe' => 'remplace',
            ], JSON_THROW_ON_ERROR);
            $event->motif = 'Reinitialisation du mot de passe.';
            $event->origine_systeme = 'A1-07';
            $event->correlation = $correlationId;
            $event->save();

            $this->outbox->record(
                idempotencyKey: 'a1-07:reset:'.$challenge->id,
                type: 'compte_acces_modifie',
                operation: 'A1-07',
                correlation: $correlationId,
                intentionFingerprint: SecretHasher::hmac($challenge->id),
                payload: [
                    'compte_id' => $account->id,
                    'statut' => $account->statut->value,
                ],
                accountId: $account->id,
                accountEventId: $event->id,
                challengeId: $challenge->id,
            );

            return $account;
        });

        try {
            $this->notifications->sendPasswordChanged($account);
        } catch (\Throwable) {
        }

        return $account;
    }
}
