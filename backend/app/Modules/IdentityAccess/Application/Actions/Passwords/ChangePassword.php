<?php

namespace App\Modules\IdentityAccess\Application\Actions\Passwords;

use App\Modules\IdentityAccess\Application\Actions\Messaging\RevokeOpenChallenges;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Exceptions\AuthenticationDenied;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use Illuminate\Support\Facades\Hash;

class ChangePassword
{
    public function __construct(
        private CoordinationGuard $coordination,
        private RevokeAccountSessions $revokeSessions,
        private RevokeOpenChallenges $revokeOpen,
        private IdentityOutboxPublisher $outbox,
        private IdentityNotificationSender $notifications,
    ) {}

    public function __invoke(Account $account, string $currentPassword, string $newPasswordHash, string $correlationId): Account
    {
        if (! Hash::check($currentPassword, $account->getAuthPassword())) {
            throw AuthenticationDenied::generic();
        }

        $updated = $this->coordination->withinIdentity(function () use ($account, $currentPassword, $newPasswordHash, $correlationId): Account {
            $locked = Account::query()->where('id', $account->id)->lockForUpdate()->first();

            if ($locked === null || ! Hash::check($currentPassword, $locked->getAuthPassword())) {
                throw AuthenticationDenied::generic();
            }

            $now = now();
            $locked->hash_mdp = $newPasswordHash;
            $locked->save();

            ($this->revokeOpen)($locked->id, ChallengePurpose::PasswordReset);

            ($this->revokeSessions)($locked->id, 'Changement volontaire du mot de passe.');

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $locked->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'statut' => $locked->statut->value,
                'mot_de_passe' => 'remplace',
            ], JSON_THROW_ON_ERROR);
            $event->motif = 'Changement volontaire du mot de passe.';
            $event->origine_systeme = 'A1-07a';
            $event->correlation = $correlationId;
            $event->save();

            $this->outbox->record(
                idempotencyKey: 'a1-07a:change:'.$event->id,
                type: 'compte_acces_modifie',
                operation: 'A1-07a',
                correlation: $correlationId,
                intentionFingerprint: SecretHasher::hmac($event->id),
                payload: [
                    'compte_id' => $locked->id,
                    'statut' => $locked->statut->value,
                ],
                accountId: $locked->id,
                accountEventId: $event->id,
            );

            return $locked;
        });

        try {
            $this->notifications->sendPasswordChanged($updated);
        } catch (\Throwable) {
        }

        return $updated;
    }
}
