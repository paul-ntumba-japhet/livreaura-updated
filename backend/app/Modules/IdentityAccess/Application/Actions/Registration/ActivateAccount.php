<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

use App\Modules\IdentityAccess\Application\Queries\ListRegistrationPaths;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Exceptions\InvalidChallenge;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class ActivateAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private ListRegistrationPaths $paths,
        private IdentityOutboxPublisher $outbox,
    ) {}

    public function __invoke(string $challengeId, string $proof, string $correlationId): Account
    {
        return $this->coordination->withinIdentity(function () use ($challengeId, $proof, $correlationId): Account {
            $challenge = VerificationChallenge::query()
                ->where('id', $challengeId)
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->finalite !== ChallengePurpose::EmailActivation) {
                throw InvalidChallenge::rejected();
            }

            $account = Account::query()->where('id', $challenge->compte_id)->lockForUpdate()->first();

            if ($account === null) {
                throw InvalidChallenge::rejected();
            }

            if (in_array($account->statut, [AccountStatus::Suspended, AccountStatus::Closed], true)) {
                throw InvalidChallenge::ineligibleAccount();
            }

            if ($account->statut !== AccountStatus::Pending) {
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

            $required = $this->paths->applicableRequired($account->locale);
            $accepted = Consent::query()
                ->where('compte_id', $account->id)
                ->whereNull('retire_a')
                ->whereIn('nature', ['acceptation', 'prise_connaissance'])
                ->pluck('texte_version_id');

            foreach ($required as $text) {
                if (! $accepted->contains($text->id)) {
                    throw InvalidChallenge::rejected();
                }
            }

            if ($account->profile === null) {
                throw InvalidChallenge::rejected();
            }

            $now = now();
            $challenge->consomme_a = $now;
            $challenge->save();

            $account->email_verifie_a = $now;
            $account->statut = AccountStatus::Active;
            $account->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $account->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => AccountStatus::Pending->value], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode(['statut' => AccountStatus::Active->value], JSON_THROW_ON_ERROR);
            $event->motif = 'Activation du compte apres verification e-mail.';
            $event->origine_systeme = 'A1-04';
            $event->correlation = $correlationId;
            $event->save();

            $this->outbox->record(
                idempotencyKey: 'a1-04:activation:'.$challenge->id,
                type: 'compte_acces_modifie',
                operation: 'A1-04',
                correlation: $correlationId,
                intentionFingerprint: SecretHasher::hmac($challenge->id),
                payload: [
                    'compte_id' => $account->id,
                    'statut' => AccountStatus::Active->value,
                ],
                accountId: $account->id,
                accountEventId: $event->id,
                challengeId: $challenge->id,
            );

            return $account->fresh() ?? $account;
        });
    }
}
