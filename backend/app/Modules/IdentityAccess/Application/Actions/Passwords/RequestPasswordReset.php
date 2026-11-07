<?php

namespace App\Modules\IdentityAccess\Application\Actions\Passwords;

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

class RequestPasswordReset
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private RevokeOpenChallenges $revokeOpen,
        private RecordChallengeTransmission $recordTransmission,
    ) {}

    public function __invoke(string $email): void
    {
        $account = Account::query()
            ->where('email_normalise', EmailNormalizer::normalize($email))
            ->first();

        if ($account === null || $account->statut === AccountStatus::Closed || $this->limitReached($account)) {
            return;
        }

        $secret = bin2hex(random_bytes(32));

        $this->coordination->withinIdentity(function () use ($account, $secret): void {
            $account->refresh();

            if ($account->statut === AccountStatus::Closed) {
                return;
            }

            ($this->revokeOpen)($account->id, ChallengePurpose::PasswordReset);

            $now = now();
            $ttlMinutes = (int) ($this->configurations->values('A1_delais')['reset_minutes'] ?? 15);

            $challenge = new VerificationChallenge;
            $challenge->id = IdentityUlid::make();
            $challenge->compte_id = $account->id;
            $challenge->finalite = ChallengePurpose::PasswordReset;
            $challenge->canal = 'email';
            $challenge->destination_chiffree = Crypt::encryptString($account->email_normalise);
            $challenge->empreinte_destination = SecretHasher::hmac($account->email_normalise);
            $challenge->destination_masquee = DestinationMasker::email($account->email_normalise);
            $challenge->empreinte_secret = SecretHasher::hmac($secret);
            $challenge->cree_a = $now;
            $challenge->expire_a = $now->copy()->addMinutes(max($ttlMinutes, 1));
            $challenge->nombre_essais = 0;
            $challenge->plafond_essais = 5;
            $challenge->save();

            ($this->recordTransmission)(
                $account,
                $challenge,
                $secret,
                'a1-05:password_reset:'.$challenge->id,
                'defi_password_reset',
                'A1-05',
            );
        });
    }

    private function limitReached(Account $account): bool
    {
        $latest = VerificationChallenge::query()
            ->where('compte_id', $account->id)
            ->where('finalite', ChallengePurpose::PasswordReset)
            ->orderByDesc('cree_a')
            ->first();

        if ($latest !== null && $latest->cree_a !== null && $latest->cree_a->gt(now()->subSeconds(60))) {
            return true;
        }

        $hourly = VerificationChallenge::query()
            ->where('compte_id', $account->id)
            ->where('finalite', ChallengePurpose::PasswordReset)
            ->where('cree_a', '>=', now()->subHour())
            ->count();

        return $hourly >= 5;
    }
}
