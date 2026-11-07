<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;

class SendVerificationReminders
{
    private const THRESHOLDS = [30, 7, 1];

    public function __construct(
        private CoordinationGuard $coordination,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $now = now();
        $horizon = $now->copy()->addDays(max(self::THRESHOLDS));
        $ids = VerificationCapability::query()
            ->whereIn('etat', ['valid', 'a_renouveler'])
            ->whereNotNull('valide_jusqua')
            ->where('valide_jusqua', '>', $now)
            ->where('valide_jusqua', '<=', $horizon)
            ->orderBy('valide_jusqua')
            ->orderBy('id')
            ->limit(max($limit, 1))
            ->pluck('id')
            ->all();

        $recorded = [];

        foreach ($ids as $id) {
            $result = $this->coordination->withinIdentity(function () use ($id, $now): ?string {
                $capability = VerificationCapability::query()->where('id', $id)->lockForUpdate()->first();

                if (
                    $capability === null
                    || ! in_array($capability->etat, ['valid', 'a_renouveler'], true)
                    || $capability->valide_jusqua === null
                    || $capability->valide_jusqua->lte($now)
                ) {
                    return null;
                }

                $days = (int) round($now->copy()->startOfDay()->diffInDays($capability->valide_jusqua->copy()->startOfDay(), false));
                if (! in_array($days, self::THRESHOLDS, true)) {
                    return null;
                }

                $deadline = $capability->valide_jusqua->toIso8601String();
                $idempotencyKey = 'a1-14:rappel:'.$capability->id.':'.$capability->valide_jusqua->toDateString().':J-'.$days;

                if (OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->exists()) {
                    return null;
                }

                $etat = $capability->etat;
                $intention = SecretHasher::hmac(json_encode([
                    'habilitation_id' => $capability->id,
                    'action' => 'rappel',
                    'seuil' => 'J-'.$days,
                    'valide_jusqua' => $deadline,
                ], JSON_THROW_ON_ERROR));

                $this->outbox->record(
                    idempotencyKey: $idempotencyKey,
                    type: 'habilitation_commerciale_modifiee',
                    operation: 'A1-14',
                    correlation: IdentityUlid::make(),
                    intentionFingerprint: $intention,
                    payload: [
                        'statut' => $etat,
                        'prochaine_action' => 'nouvelle_version',
                        'seuil' => 'J-'.$days,
                        'habilitation' => [
                            'id' => $capability->id,
                            'niveau' => $capability->niveau,
                            'etat' => $etat,
                            'valide_jusqua' => $deadline,
                        ],
                    ],
                    accountId: $this->notifyAccountId($capability),
                    dossierId: $capability->dossier_courant_id,
                );

                return $capability->id;
            });

            if (is_string($result)) {
                $recorded[] = $result;
            }
        }

        return $recorded;
    }

    private function notifyAccountId(VerificationCapability $capability): ?string
    {
        $subject = $capability->subject instanceof KycSubject
            ? $capability->subject
            : KycSubject::query()->where('id', $capability->sujet_id)->first();

        if ($subject === null) {
            return null;
        }

        if (is_string($subject->profil_compte_id) && $subject->profil_compte_id !== '') {
            return $subject->profil_compte_id;
        }

        if (! $subject->isOrganization()) {
            return null;
        }

        return OrganizationMembership::query()
            ->where('organisation_id', $subject->organisation_id)
            ->where('principal', 1)
            ->where('statut', 'active')
            ->value('compte_id');
    }
}
