<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;

class RestrictVerificationCapability
{
    private const TARGETS = ['a_renouveler', 'restreint', 'revoked'];

    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private ChangeVerificationCapabilityState $changeState,
    ) {}

    /**
     * @param  array{etat: string, motif: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        string $capabilityId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $account,
            $capabilityId,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($account, 'restreindre_habilitation')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $etat = $input['etat'];
            if (! in_array($etat, self::TARGETS, true)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Cet etat d habilitation n est pas disponible ici.',
                    422,
                    ['etat' => ['Etat inconnu.']],
                );
            }

            $capability = VerificationCapability::query()
                ->with(['subject', 'dossier.serviceTask'])
                ->where('id', $capabilityId)
                ->lockForUpdate()
                ->first();

            if ($capability === null) {
                throw new IdentityAccessException('habilitation_inconnue', 'Cette habilitation n est pas visible.', 404);
            }

            if (in_array($capability->etat, ['expired', 'revoked'], true)) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette habilitation n est plus modifiable. Le retablissement exige une nouvelle version approuvee.',
                    409,
                );
            }

            return ($this->changeState)(
                $capability,
                $etat,
                $input['motif'],
                'restriction',
                $idempotencyKey,
                $intentionFingerprint,
                $correlationId,
                $account,
            );
        });
    }
}
