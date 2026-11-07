<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

use App\Modules\IdentityAccess\Application\Actions\Messaging\RecordChallengeTransmission;
use App\Modules\IdentityAccess\Application\Actions\Messaging\RevokeOpenChallenges;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\EmailNormalizer;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\Crypt;

class IssueVerificationChallenge
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private RevokeOpenChallenges $revokeOpen,
        private RecordChallengeTransmission $recordTransmission,
    ) {}

    public function __invoke(?Account $account = null, ?string $email = null): void
    {
        $resolved = $account ?? $this->findPendingByEmail($email);

        if ($resolved === null || $this->resendLimitReached($resolved)) {
            return;
        }

        $secret = bin2hex(random_bytes(32));

        $this->coordination->withinIdentity(function () use ($resolved, $secret): void {
            $resolved->refresh();

            if ($resolved->statut !== AccountStatus::Pending) {
                return;
            }

            ($this->revokeOpen)($resolved->id, ChallengePurpose::EmailActivation);

            $now = now();
            $ttlHours = (int) ($this->configurations->values('A1_delais')['activation_heures'] ?? 24);

            $challenge = new VerificationChallenge;
            $challenge->id = IdentityUlid::make();
            $challenge->compte_id = $resolved->id;
            $challenge->finalite = ChallengePurpose::EmailActivation;
            $challenge->canal = 'email';
            $challenge->destination_chiffree = Crypt::encryptString($resolved->email_normalise);
            $challenge->empreinte_destination = SecretHasher::hmac($resolved->email_normalise);
            $challenge->destination_masquee = DestinationMasker::email($resolved->email_normalise);
            $challenge->empreinte_secret = SecretHasher::hmac($secret);
            $challenge->cree_a = $now;
            $challenge->expire_a = $now->copy()->addHours(max($ttlHours, 1));
            $challenge->nombre_essais = 0;
            $challenge->plafond_essais = 5;
            $challenge->save();

            ($this->recordTransmission)(
                $resolved,
                $challenge,
                $secret,
                'a1-03a:email_activation:'.$challenge->id,
                'defi_email_activation',
                'A1-03a',
            );
        });
    }

    public function resendByEmail(string $email): void
    {
        $this($this->findPendingByEmail($email), null);
    }

    private function findPendingByEmail(?string $email): ?Account
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        return Account::query()
            ->where('email_normalise', EmailNormalizer::normalize($email))
            ->where('statut', AccountStatus::Pending)
            ->first();
    }

    private function resendLimitReached(Account $account): bool
    {
        $latest = VerificationChallenge::query()
            ->where('compte_id', $account->id)
            ->where('finalite', ChallengePurpose::EmailActivation)
            ->orderByDesc('cree_a')
            ->first();

        if ($latest !== null && $latest->cree_a !== null && $latest->cree_a->gt(now()->subSeconds(60))) {
            return true;
        }

        $hourly = VerificationChallenge::query()
            ->where('compte_id', $account->id)
            ->where('finalite', ChallengePurpose::EmailActivation)
            ->where('cree_a', '>=', now()->subHour())
            ->count();

        return $hourly >= 5;
    }
}
