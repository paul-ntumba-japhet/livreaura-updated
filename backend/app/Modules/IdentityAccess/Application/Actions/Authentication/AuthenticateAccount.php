<?php

namespace App\Modules\IdentityAccess\Application\Actions\Authentication;

use App\Modules\IdentityAccess\Application\DTOs\AuthenticationResult;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\AuthenticationDenied;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\EmailNormalizer;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Facades\Hash;

class AuthenticateAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private IdentityOutboxPublisher $outbox,
    ) {}

    public function __invoke(string $email, string $password, string $correlationId): AuthenticationResult
    {
        $normalized = EmailNormalizer::normalize($email);
        $account = Account::query()->where('email_normalise', $normalized)->first();

        if ($account === null || ! Hash::check($password, $account->getAuthPassword())) {
            throw AuthenticationDenied::generic();
        }

        $access = $this->configurations->find('A1_acces');
        $values = $this->configurations->values('A1_acces');

        return $this->coordination->withinIdentity(function () use ($account, $password, $correlationId, $access, $values): AuthenticationResult {
            $locked = Account::query()->where('id', $account->id)->lockForUpdate()->first();

            if ($locked === null || ! Hash::check($password, $locked->getAuthPassword())) {
                throw AuthenticationDenied::generic();
            }

            if ($locked->statut === AccountStatus::Closed) {
                throw AuthenticationDenied::generic();
            }

            if ($locked->statut === AccountStatus::Suspended) {
                return $this->openSupportOnlySession($locked, $access, $values, $correlationId);
            }

            $scope = $locked->statut === AccountStatus::Pending
                ? SessionAccessScope::Limited
                : SessionAccessScope::Normal;

            if ($access === null) {
                throw new IdentityAccessException(
                    'service_indisponible',
                    'La politique d acces n est pas disponible.',
                    503,
                );
            }

            $now = now();
            $inactivity = $scope === SessionAccessScope::Limited
                ? (int) ($values['inactivite_secondes_limited'] ?? 900)
                : (int) ($values['inactivite_secondes_normal'] ?? 3600);
            $absolute = $scope === SessionAccessScope::Limited
                ? (int) ($values['absolue_secondes_limited'] ?? 3600)
                : (int) ($values['absolue_secondes_normal'] ?? 43200);

            $reference = bin2hex(random_bytes(32));
            $session = new AuthenticationSession;
            $session->id = IdentityUlid::make();
            $session->compte_id = $locked->id;
            $session->empreinte_reference = SecretHasher::hmac($reference);
            $session->portee = $scope;
            $session->statut = SessionStatus::Active;
            $session->ouverte_a = $now;
            $session->derniere_activite_a = $now;
            $session->expire_absolue_a = $now->copy()->addSeconds(max($absolute, 60));
            $session->expire_inactivite_a = $now->copy()->addSeconds(max($inactivity, 60));
            $session->config_acces_id = $access->id;
            $session->save();

            $this->recordSessionEvent($locked, $session, $scope, $correlationId, 'A1-20', 'Ouverture de session.');

            return new AuthenticationResult($locked, $session, $scope, $reference);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function openSupportOnlySession(
        Account $locked,
        mixed $access,
        array $values,
        string $correlationId,
    ): AuthenticationResult {
        $appeal = SupportRequest::query()
            ->where('type', 'recours_suspension')
            ->where('identite_etat', 'verifiee')
            ->where('compte_concerne_id', $locked->id)
            ->whereNull('clos_a')
            ->orderByDesc('identite_verifiee_a')
            ->lockForUpdate()
            ->first();

        $authorized = $appeal === null ? null : AuthenticationSession::query()
            ->where('compte_id', $locked->id)
            ->where('demande_support_id', $appeal->id)
            ->where('portee', SessionAccessScope::SupportOnly)
            ->where('statut', SessionStatus::Active)
            ->whereNotNull('autorise_par')
            ->orderByDesc('ouverte_a')
            ->lockForUpdate()
            ->first();

        if ($appeal === null || $authorized === null || $access === null) {
            throw AuthenticationDenied::generic();
        }

        $now = now();
        $reference = bin2hex(random_bytes(32));
        $authorized->empreinte_reference = SecretHasher::hmac($reference);
        $authorized->derniere_activite_a = $now;
        $authorized->expire_inactivite_a = $now->copy()->addSeconds(max((int) ($values['inactivite_secondes_limited'] ?? 900), 60));
        $authorized->save();

        $this->recordSessionEvent(
            $locked,
            $authorized,
            SessionAccessScope::SupportOnly,
            $correlationId,
            'A1-25',
            'Ouverture de session de recours.',
        );

        return new AuthenticationResult($locked, $authorized, SessionAccessScope::SupportOnly, $reference);
    }

    private function recordSessionEvent(
        Account $account,
        AuthenticationSession $session,
        SessionAccessScope $scope,
        string $correlationId,
        string $operation,
        string $motif,
    ): void {
        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $account->id;
        $event->type = 'session_acces_modifie';
        $event->date = now();
        $event->nouvel_etat = json_encode([
            'session_id' => $session->id,
            'portee' => $scope->value,
        ], JSON_THROW_ON_ERROR);
        $event->motif = $motif;
        $event->origine_systeme = $operation;
        $event->correlation = $correlationId;
        $event->save();

        $this->outbox->record(
            idempotencyKey: strtolower($operation).':session:'.$session->id.':'.$correlationId,
            type: 'session_acces_modifie',
            operation: $operation,
            correlation: $correlationId,
            intentionFingerprint: SecretHasher::hmac($session->id.':'.$correlationId),
            payload: [
                'compte_id' => $account->id,
                'session_id' => $session->id,
                'portee' => $scope->value,
            ],
            accountId: $account->id,
            accountEventId: $event->id,
        );
    }
}
