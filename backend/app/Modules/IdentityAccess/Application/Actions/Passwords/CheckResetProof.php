<?php

namespace App\Modules\IdentityAccess\Application\Actions\Passwords;

use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Exceptions\InvalidChallenge;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class CheckResetProof
{
    public function __construct(private CoordinationGuard $coordination) {}

    /**
     * @return array{defi_id: string, expire_a: string|null, destination_masquee: string}
     */
    public function __invoke(string $challengeId, string $proof): array
    {
        return $this->coordination->withinIdentity(function () use ($challengeId, $proof): array {
            $challenge = VerificationChallenge::query()
                ->where('id', $challengeId)
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->finalite !== ChallengePurpose::PasswordReset) {
                throw InvalidChallenge::rejected();
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

            return [
                'defi_id' => $challenge->id,
                'expire_a' => $challenge->expire_a?->toIso8601String(),
                'destination_masquee' => (string) $challenge->destination_masquee,
            ];
        });
    }
}
