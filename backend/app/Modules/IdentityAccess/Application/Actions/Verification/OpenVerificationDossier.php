<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetVerificationDossier;
use App\Modules\IdentityAccess\Application\Queries\ListApplicableVerificationRequirements;
use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class OpenVerificationDossier
{
    private const LEVELS = ['organisation_verifiee', 'partenaire_commercial'];

    public function __construct(
        private CoordinationGuard $coordination,
        private CanDepositVerification $canDeposit,
        private ListApplicableVerificationRequirements $requirements,
        private GetVerificationDossier $dossierQuery,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{niveau: string, organisation_id: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        if ($account->statut !== AccountStatus::Active) {
            throw new IdentityAccessException(
                'compte_ineligible',
                'Le depot KYC n est disponible qu pour un compte actif.',
                403,
            );
        }

        if (! in_array($input['niveau'], self::LEVELS, true)) {
            throw new IdentityAccessException(
                'niveau_inconnu',
                'Ce niveau de verification n est pas disponible.',
                422,
                ['niveau' => ['Niveau inconnu.']],
            );
        }

        if ($input['niveau'] === 'organisation_verifiee' && $input['organisation_id'] === null) {
            throw new IdentityAccessException(
                'sujet_incompatible',
                'Le niveau organisation_verifiee exige une organisation.',
                422,
                ['organisation_id' => ['Organisation requise.']],
            );
        }

        return $this->coordination->withinIdentity(function () use ($account, $input, $idempotencyKey, $intentionFingerprint, $correlationId): array {
            $existingMessage = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

            if ($existingMessage !== null) {
                if ($existingMessage->empreinte_intention !== $intentionFingerprint) {
                    throw new IdentityAccessException(
                        'intention_conflictuelle',
                        'Cette intention entre en conflit avec une demande deja enregistree.',
                        409,
                    );
                }

                return $this->payloadFromMessage($existingMessage);
            }

            $subject = $this->lockSubject($account, $input['organisation_id']);

            if (! ($this->canDeposit)($account, $subject)) {
                throw new IdentityAccessException(
                    'depot_refuse',
                    'Le depot KYC n est pas autorise pour ce sujet.',
                    403,
                );
            }

            $open = VerificationDossier::query()
                ->where('sujet_id', $subject->id)
                ->where('niveau', $input['niveau'])
                ->whereIn('statut', ['draft', 'submitted', 'under_review'])
                ->lockForUpdate()
                ->first();

            if ($open !== null) {
                $payload = $this->draftPayload($open, 200, 'deja_ouvert', 'dossier');
                $this->outbox->record(
                    idempotencyKey: $idempotencyKey,
                    type: 'dossier_kyc_ouvert',
                    operation: 'A1-11',
                    correlation: $correlationId,
                    intentionFingerprint: $intentionFingerprint,
                    payload: $payload,
                    accountId: $account->id,
                    dossierId: $open->id,
                );

                return $payload;
            }

            $requirements = ($this->requirements)($subject, $input['niveau'], $this->countryOf($subject));

            if ($requirements === []) {
                throw new IdentityAccessException(
                    'exigences_indisponibles',
                    'Aucune exigence n est applicable pour ce sujet, ce pays et ce niveau.',
                    422,
                    ['niveau' => ['Exigences indisponibles.']],
                );
            }

            $previous = VerificationDossier::query()
                ->where('sujet_id', $subject->id)
                ->where('niveau', $input['niveau'])
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            $newLineage = $previous === null || in_array($previous->statut, ['approved', 'expired'], true);

            $now = now();
            $dossier = new VerificationDossier;
            $dossier->id = IdentityUlid::make();
            $dossier->sujet_id = $subject->id;
            $dossier->numero = $newLineage ? $this->dossierNumber() : (string) $previous->numero;
            $dossier->version = $newLineage ? 1 : ((int) $previous->version) + 1;
            $dossier->precedent_id = $newLineage ? null : $previous->id;
            $dossier->niveau = $input['niveau'];
            $dossier->statut = 'draft';
            $dossier->deposant_id = $account->id;
            $dossier->cree_a = $now;
            $dossier->save();

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->type = 'ouverture';
            $event->date = $now;
            $event->nouvel_etat = json_encode(['statut' => 'draft', 'version' => $dossier->version], JSON_THROW_ON_ERROR);
            $event->motif = 'Ouverture d un dossier de verification en brouillon.';
            $event->origine_systeme = 'A1-11';
            $event->correlation = $correlationId;
            $event->save();

            $payload = $this->draftPayload($dossier, 201, 'draft', 'completer_dossier');
            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'dossier_kyc_ouvert',
                operation: 'A1-11',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $account->id,
                dossierId: $dossier->id,
                kycEventId: $event->id,
            );

            return $payload;
        });
    }

    private function lockSubject(Account $account, ?string $organizationId): KycSubject
    {
        if ($organizationId === null) {
            $existing = KycSubject::query()->where('profil_compte_id', $account->id)->lockForUpdate()->first();

            if ($existing !== null) {
                return $existing;
            }

            $subject = new KycSubject;
            $subject->id = IdentityUlid::make();
            $subject->profil_compte_id = $account->id;
            $subject->save();

            return $subject;
        }

        $organization = Organization::query()->where('id', $organizationId)->lockForUpdate()->first();

        if ($organization === null || $organization->statut_operationnel === 'closed') {
            throw new IdentityAccessException(
                'organisation_ineligible',
                'Cette organisation n est plus disponible.',
                422,
                ['organisation_id' => ['Organisation indisponible.']],
            );
        }

        $existing = KycSubject::query()->where('organisation_id', $organization->id)->lockForUpdate()->first();

        if ($existing !== null) {
            return $existing;
        }

        $subject = new KycSubject;
        $subject->id = IdentityUlid::make();
        $subject->organisation_id = $organization->id;
        $subject->save();

        return $subject;
    }

    /**
     * @return array<string, mixed>
     */
    private function draftPayload(VerificationDossier $dossier, int $status, string $statut, string $next): array
    {
        $dossier->refresh()->load(['subject.organization', 'subject.profile', 'documents', 'controls.requirement', 'fiscalProfile', 'payoutAccount']);

        return [
            'http_status' => $status,
            'statut' => $statut,
            'prochaine_action' => $next,
            'dossier' => ($this->dossierQuery)->serialize($dossier),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromMessage(OutgoingMessage $message): array
    {
        $payload = is_array($message->donnees_minimales) ? $message->donnees_minimales : [];

        return [
            'http_status' => (int) ($payload['http_status'] ?? 200),
            'statut' => is_string($payload['statut'] ?? null) ? $payload['statut'] : 'draft',
            'prochaine_action' => is_string($payload['prochaine_action'] ?? null) ? $payload['prochaine_action'] : 'dossier',
            'dossier' => is_array($payload['dossier'] ?? null) ? $payload['dossier'] : null,
        ];
    }

    private function countryOf(KycSubject $subject): string
    {
        $subject->loadMissing(['organization', 'profile']);

        if ($subject->isOrganization()) {
            return strtoupper((string) $subject->organization?->pays);
        }

        return strtoupper((string) $subject->profile?->pays);
    }

    private function dossierNumber(): string
    {
        do {
            $numero = 'KV-'.substr(IdentityUlid::make(), -10);
        } while (VerificationDossier::query()->where('numero', $numero)->exists());

        return $numero;
    }
}
