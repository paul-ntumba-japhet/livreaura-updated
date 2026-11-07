<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\EvaluateDossierCompleteness;
use App\Modules\IdentityAccess\Application\Queries\GetVerificationDossier;
use App\Modules\IdentityAccess\Application\Queries\ListApplicableVerificationRequirements;
use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;

class SubmitVerificationDossier
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CanDepositVerification $canDeposit,
        private ListApplicableVerificationRequirements $requirements,
        private EvaluateDossierCompleteness $completeness,
        private GetVerificationDossier $dossierQuery,
        private PrivateEvidenceStore $evidence,
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        string $dossierId,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentityThenConservation(function () use (
            $account,
            $dossierId,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
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

                return [
                    'http_status' => (int) ($payload['http_status'] ?? 200),
                    'statut' => is_string($payload['statut'] ?? null) ? $payload['statut'] : 'submitted',
                    'prochaine_action' => is_string($payload['prochaine_action'] ?? null) ? $payload['prochaine_action'] : 'attente_examen',
                    'dossier' => is_array($payload['dossier'] ?? null) ? $payload['dossier'] : null,
                    'manques' => is_array($payload['manques'] ?? null) ? $payload['manques'] : [],
                ];
            }

            $dossier = VerificationDossier::query()->where('id', $dossierId)->lockForUpdate()->first();

            if ($dossier === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            $dossier->load(['subject.organization', 'subject.profile', 'documents', 'fiscalProfile', 'payoutAccount']);

            if ($dossier->subject === null || ! ($this->canDeposit)($account, $dossier->subject)) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            if (! $dossier->isDraft()) {
                throw new IdentityAccessException(
                    'dossier_fige',
                    'Ce dossier n est plus modifiable.',
                    409,
                );
            }

            $country = $dossier->subject->isOrganization()
                ? strtoupper((string) $dossier->subject->organization?->pays)
                : strtoupper((string) $dossier->subject->profile?->pays);
            $requirements = ($this->requirements)($dossier->subject, $dossier->niveau, $country);
            $missing = ($this->completeness)($dossier, $requirements);

            if ($missing !== []) {
                throw new IdentityAccessException(
                    'dossier_incomplet',
                    'Le dossier n est pas complet.',
                    422,
                    ['manques' => array_map(fn (array $item): string => $item['motif'].':'.$item['code'], $missing)],
                );
            }

            foreach ($dossier->documents as $document) {
                if (! $this->evidence->exists($document->reference_privee) || $this->evidence->hashOf($document->reference_privee) !== $document->empreinte) {
                    throw new IdentityAccessException(
                        'fichier_inaccessible',
                        'Une piece n est plus accessible. Le dossier reste en brouillon.',
                        503,
                    );
                }
            }

            $calendar = $this->configurations->find('A1_calendrier');
            $delays = $this->configurations->find('A1_delais');

            if ($calendar === null || $delays === null) {
                throw new IdentityAccessException(
                    'service_indisponible',
                    'La configuration des delais de service est indisponible.',
                    503,
                );
            }

            $now = now();
            $dossier->statut = 'submitted';
            $dossier->soumis_a = $now;
            $dossier->save();

            foreach ($requirements as $requirement) {
                $control = new VerificationControl;
                $control->id = IdentityUlid::make();
                $control->dossier_id = $dossier->id;
                $control->exigence_id = $requirement->id;
                $control->resultat = 'pending';
                $control->save();

                if ($requirement->requiresDocument()) {
                    $matches = $dossier->documents->filter(
                        fn (VerificationDocument $document): bool => $document->type_doc === $requirement->code,
                    );
                    foreach ($matches as $document) {
                        $control->documents()->attach($document->id, ['dossier_id' => $dossier->id]);
                    }
                }
            }

            $windows = $this->deadlines->forNewTask(
                is_array($calendar->valeurs) ? $calendar->valeurs : [],
                is_array($delays->valeurs) ? $delays->valeurs : [],
                $now,
            );

            $task = new ServiceTask;
            $task->id = IdentityUlid::make();
            $task->dossier_kyc_id = $dossier->id;
            $task->etat_travail = 'a_affecter';
            $task->priorite = 'normale';
            $task->cree_a = $now;
            $task->echeance_prise_a = $windows['prise'];
            $task->echeance_reponse_a = $windows['reponse'];
            $task->calendrier_config_id = $calendar->id;
            $task->delais_config_id = $delays->id;
            $task->save();

            $taskEvent = new ServiceTaskEvent;
            $taskEvent->id = IdentityUlid::make();
            $taskEvent->tache_id = $task->id;
            $taskEvent->type = 'creation';
            $taskEvent->date = $now;
            $taskEvent->origine_systeme = 'A1-11';
            $taskEvent->nouvel_etat = 'a_affecter';
            $taskEvent->motif = 'Ouverture de la tache de controle KYC, sans decision.';
            $taskEvent->correlation = $correlationId;
            $taskEvent->save();

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->type = 'soumission';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => 'draft'], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode(['statut' => 'submitted', 'version' => $dossier->version], JSON_THROW_ON_ERROR);
            $event->motif = 'Soumission du dossier. Les preuves sont figees.';
            $event->origine_systeme = 'A1-11';
            $event->correlation = $correlationId;
            $event->save();

            $dossier->refresh()->load(['subject.organization', 'subject.profile', 'documents', 'controls.requirement', 'fiscalProfile', 'payoutAccount']);
            $payload = [
                'http_status' => 200,
                'statut' => 'submitted',
                'prochaine_action' => 'attente_examen',
                'dossier' => ($this->dossierQuery)->serialize($dossier),
                'manques' => [],
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'dossier_kyc_soumis',
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
}
