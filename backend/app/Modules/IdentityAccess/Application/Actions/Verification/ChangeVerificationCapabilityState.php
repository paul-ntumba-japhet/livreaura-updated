<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Vendor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class ChangeVerificationCapabilityState
{
    public function __construct(
        private GetBackofficeVerificationDossier $reviewQuery,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        VerificationCapability $capability,
        string $etat,
        string $motif,
        string $eventType,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
        ?Account $actor,
    ): array {
        $existingMessage = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

        if ($existingMessage !== null) {
            if ($existingMessage->empreinte_intention !== $intentionFingerprint) {
                throw new IdentityAccessException(
                    'intention_conflictuelle',
                    'Cette intention entre en conflit avec une demande deja enregistree.',
                    409,
                );
            }

            $payload = is_array($existingMessage->donnees_minimales) ? $existingMessage->donnees_minimales : [];
            unset($payload['http_status']);

            return $payload;
        }

        $previous = $capability->etat;
        $now = now();
        $capability->etat = $etat;
        $capability->motif_restriction = $etat === 'valid' ? null : $motif;
        $capability->save();

        if ($capability->niveau === 'partenaire_commercial' && in_array($etat, ['restreint', 'expired', 'revoked'], true)) {
            Vendor::query()
                ->where('sujet_id', $capability->sujet_id)
                ->update(['actif' => false]);
        }

        $event = new VerificationEvent;
        $event->id = IdentityUlid::make();
        $event->dossier_id = $capability->dossier_courant_id;
        $event->habilitation_id = $capability->id;
        $event->decision_id = $capability->decision_courante_id;
        $event->type = $eventType;
        $event->date = $now;
        $event->ancien_etat = json_encode(['etat' => $previous], JSON_THROW_ON_ERROR);
        $event->nouvel_etat = json_encode(['etat' => $etat, 'niveau' => $capability->niveau], JSON_THROW_ON_ERROR);
        $event->motif = $motif;
        $event->auteur_compte_id = $actor?->id;
        $event->origine_systeme = $actor === null ? 'A1-14' : null;
        $event->correlation = $correlationId;
        $event->save();

        $payload = $this->payload($capability, $actor, $etat);
        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'habilitation_commerciale_modifiee',
            operation: 'A1-14',
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $this->notifyAccountId($capability),
            dossierId: $capability->dossier_courant_id,
            kycEventId: $event->id,
        );

        unset($payload['http_status']);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(VerificationCapability $capability, ?Account $actor, string $etat): array
    {
        $capability->loadMissing(['dossier.subject.organization', 'dossier.subject.profile', 'dossier.documents', 'dossier.controls.requirement', 'dossier.fiscalProfile', 'dossier.payoutAccount', 'dossier.decision', 'dossier.serviceTask']);
        $dossier = $capability->dossier;

        $base = [
            'http_status' => 200,
            'statut' => $etat,
            'prochaine_action' => 'nouvelle_version',
            'habilitation' => [
                'id' => $capability->id,
                'niveau' => $capability->niveau,
                'etat' => $capability->etat,
                'obtenue_a' => $capability->obtenue_a?->toIso8601String(),
                'valide_jusqua' => $capability->valide_jusqua?->toIso8601String(),
                'motif_restriction' => $capability->motif_restriction,
            ],
        ];

        if ($actor instanceof Account && $dossier instanceof VerificationDossier && $dossier->serviceTask !== null) {
            return [
                ...$base,
                ...($this->reviewQuery)->serialize($actor, $dossier),
            ];
        }

        return $base;
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
