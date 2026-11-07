<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;

class ReceiveAccountReviewInstruction
{
    public const CONSUMER = 'A1-27';

    public const PRODUCER = 'A6';

    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{evenement_externe_id: string, producteur: string, correlation: string, schema_message_version: int, compte_id: string, instruction_id: string, type: string, cause: ?string, motif: string}  $instruction
     * @return array<string, mixed>
     */
    public function __invoke(array $instruction, string $correlationId): array
    {
        $this->assertSchema($instruction);
        $instruction['compte_id'] = strtoupper(trim((string) $instruction['compte_id']));
        $instruction['cause'] = isset($instruction['cause']) && trim((string) $instruction['cause']) !== ''
            ? trim((string) $instruction['cause'])
            : null;
        $instruction['motif'] = trim((string) $instruction['motif']);
        $instruction['instruction_id'] = trim((string) $instruction['instruction_id']);
        $instruction['evenement_externe_id'] = trim((string) $instruction['evenement_externe_id']);
        $instruction['correlation'] = trim((string) ($instruction['correlation'] ?? ''));
        $instruction['schema_message_version'] = (int) $instruction['schema_message_version'];

        $fingerprint = SecretHasher::hmac(json_encode([
            'compte_id' => $instruction['compte_id'],
            'instruction_id' => $instruction['instruction_id'],
            'type' => $instruction['type'],
            'cause' => $instruction['cause'],
            'motif' => $instruction['motif'],
        ], JSON_THROW_ON_ERROR));

        return $this->coordination->withinIdentity(function () use ($instruction, $fingerprint, $correlationId): array {
            $existing = MessageReception::query()
                ->where('producteur', self::PRODUCER)
                ->where('evenement_externe_id', $instruction['evenement_externe_id'])
                ->where('consommateur', self::CONSUMER)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->empreinte_intention !== $fingerprint) {
                    throw new IdentityAccessException(
                        'intention_conflictuelle',
                        'Cette intention entre en conflit avec une instruction deja recue.',
                        409,
                    );
                }

                if ($existing->traite_a !== null) {
                    return [
                        'niveau_acquittement' => $existing->niveau_acquittement,
                        'reference_resultat' => $existing->reference_resultat,
                        'demande_id' => is_array($existing->donnees_minimales) ? ($existing->donnees_minimales['demande_id'] ?? null) : null,
                    ];
                }

                return $this->completePending($existing, $instruction, $fingerprint, $correlationId);
            }

            $account = Account::query()->where('id', $instruction['compte_id'])->lockForUpdate()->first();

            if ($account === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
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
            $windows = $this->deadlines->forNewTask(
                is_array($calendar->valeurs) ? $calendar->valeurs : [],
                is_array($delays->valeurs) ? $delays->valeurs : [],
                $now,
            );

            $demande = new SupportRequest;
            $demande->id = IdentityUlid::make();
            $demande->reference = $this->examReference();
            $demande->type = 'examen_compte_conformite';
            $demande->compte_concerne_id = $account->id;
            $demande->cree_a = $now;
            $demande->identite_etat = 'non_requise';
            $demande->save();

            $task = new ServiceTask;
            $task->id = IdentityUlid::make();
            $task->demande_support_id = $demande->id;
            $task->etat_travail = 'a_affecter';
            $task->priorite = 'normale';
            $task->cree_a = $now;
            $task->echeance_prise_a = $windows['prise'];
            $task->echeance_reponse_a = $windows['reponse'];
            $task->calendrier_config_id = $calendar->id;
            $task->delais_config_id = $delays->id;
            $task->save();

            $created = new ServiceTaskEvent;
            $created->id = IdentityUlid::make();
            $created->tache_id = $task->id;
            $created->type = 'creation';
            $created->date = $now;
            $created->origine_systeme = 'A1-27';
            $created->nouvel_etat = 'a_affecter';
            $created->motif = $instruction['motif'];
            $created->correlation = $correlationId;
            $created->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $account->id;
            $event->type = 'examen_compte_recu';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'demande_id' => $demande->id,
                'instruction_id' => $instruction['instruction_id'],
                'cause' => $instruction['cause'],
                'statut_compte' => $account->statut->value,
                'suspension_automatique' => false,
            ], JSON_THROW_ON_ERROR);
            $event->motif = $instruction['motif'];
            $event->origine_systeme = 'A1-27';
            $event->correlation = $correlationId;
            $event->save();

            $ack = [
                'niveau_acquittement' => 'engage_local',
                'reference_resultat' => $demande->reference,
                'demande_id' => $demande->id,
                'tache_id' => $task->id,
            ];

            $reception = new MessageReception;
            $reception->id = IdentityUlid::make();
            $reception->evenement_externe_id = $instruction['evenement_externe_id'];
            $reception->producteur = self::PRODUCER;
            $reception->consommateur = self::CONSUMER;
            $reception->correlation = $instruction['correlation'] !== '' ? $instruction['correlation'] : $correlationId;
            $reception->recu_a = $now;
            $reception->traite_a = $now;
            $reception->empreinte_intention = $fingerprint;
            $reception->schema_message_version = $instruction['schema_message_version'];
            $reception->donnees_minimales = [
                'demande_id' => $demande->id,
                'tache_id' => $task->id,
                'compte_id' => $account->id,
                'instruction_id' => $instruction['instruction_id'],
                'type' => $instruction['type'],
                'cause' => $instruction['cause'],
                'motif' => $instruction['motif'],
            ];
            $reception->niveau_acquittement = 'engage_local';
            $reception->reference_resultat = $demande->reference;
            $reception->save();

            $this->outbox->record(
                idempotencyKey: 'a1-27:'.$instruction['evenement_externe_id'],
                type: 'examen_compte_recu',
                operation: 'A1-27',
                correlation: $correlationId,
                intentionFingerprint: $fingerprint,
                payload: $ack,
                accountId: $account->id,
                accountEventId: $event->id,
                demandeId: $demande->id,
            );

            return $ack;
        });
    }

    /**
     * @param  array{evenement_externe_id: string, producteur: string, correlation: string, schema_message_version: int, compte_id: string, instruction_id: string, type: string, cause: ?string, motif: string}  $instruction
     * @return array<string, mixed>
     */
    private function completePending(
        MessageReception $existing,
        array $instruction,
        string $fingerprint,
        string $correlationId,
    ): array {
        $data = is_array($existing->donnees_minimales) ? $existing->donnees_minimales : [];

        if (isset($data['demande_id']) && is_string($data['demande_id']) && $data['demande_id'] !== '') {
            $existing->traite_a = now();
            $existing->save();

            return [
                'niveau_acquittement' => $existing->niveau_acquittement,
                'reference_resultat' => $existing->reference_resultat,
                'demande_id' => $data['demande_id'],
            ];
        }

        $account = Account::query()->where('id', $instruction['compte_id'])->lockForUpdate()->first();

        if ($account === null) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
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
        $windows = $this->deadlines->forNewTask(
            is_array($calendar->valeurs) ? $calendar->valeurs : [],
            is_array($delays->valeurs) ? $delays->valeurs : [],
            $now,
        );

        $demande = new SupportRequest;
        $demande->id = IdentityUlid::make();
        $demande->reference = $this->examReference();
        $demande->type = 'examen_compte_conformite';
        $demande->compte_concerne_id = $account->id;
        $demande->cree_a = $now;
        $demande->identite_etat = 'non_requise';
        $demande->save();

        $task = new ServiceTask;
        $task->id = IdentityUlid::make();
        $task->demande_support_id = $demande->id;
        $task->etat_travail = 'a_affecter';
        $task->priorite = 'normale';
        $task->cree_a = $now;
        $task->echeance_prise_a = $windows['prise'];
        $task->echeance_reponse_a = $windows['reponse'];
        $task->calendrier_config_id = $calendar->id;
        $task->delais_config_id = $delays->id;
        $task->save();

        $created = new ServiceTaskEvent;
        $created->id = IdentityUlid::make();
        $created->tache_id = $task->id;
        $created->type = 'creation';
        $created->date = $now;
        $created->origine_systeme = 'A1-27';
        $created->nouvel_etat = 'a_affecter';
        $created->motif = $instruction['motif'];
        $created->correlation = $correlationId;
        $created->save();

        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $account->id;
        $event->type = 'examen_compte_recu';
        $event->date = $now;
        $event->nouvel_etat = json_encode([
            'demande_id' => $demande->id,
            'instruction_id' => $instruction['instruction_id'],
            'cause' => $instruction['cause'],
            'statut_compte' => $account->statut->value,
            'suspension_automatique' => false,
        ], JSON_THROW_ON_ERROR);
        $event->motif = $instruction['motif'];
        $event->origine_systeme = 'A1-27';
        $event->correlation = $correlationId;
        $event->save();

        $ack = [
            'niveau_acquittement' => 'engage_local',
            'reference_resultat' => $demande->reference,
            'demande_id' => $demande->id,
            'tache_id' => $task->id,
        ];

        $existing->traite_a = $now;
        $existing->niveau_acquittement = 'engage_local';
        $existing->reference_resultat = $demande->reference;
        $existing->donnees_minimales = [
            'demande_id' => $demande->id,
            'tache_id' => $task->id,
            'compte_id' => $account->id,
            'instruction_id' => $instruction['instruction_id'],
            'type' => $instruction['type'],
            'cause' => $instruction['cause'],
            'motif' => $instruction['motif'],
        ];
        $existing->save();

        $this->outbox->record(
            idempotencyKey: 'a1-27:'.$instruction['evenement_externe_id'],
            type: 'examen_compte_recu',
            operation: 'A1-27',
            correlation: $correlationId,
            intentionFingerprint: $fingerprint,
            payload: $ack,
            accountId: $account->id,
            accountEventId: $event->id,
            demandeId: $demande->id,
        );

        return $ack;
    }

    /**
     * @param  array<string, mixed>  $instruction
     */
    private function assertSchema(array $instruction): void
    {
        if (($instruction['producteur'] ?? '') !== self::PRODUCER) {
            throw new IdentityAccessException('source_invalide', 'La source de cette instruction n est pas verifiee.', 422);
        }

        if (($instruction['type'] ?? '') !== 'demander_examen_compte') {
            throw new IdentityAccessException('saisie_invalide', 'Cette instruction n est pas un examen de compte.', 422);
        }

        $eventId = trim((string) ($instruction['evenement_externe_id'] ?? ''));
        $instructionId = trim((string) ($instruction['instruction_id'] ?? ''));
        $accountId = strtoupper(trim((string) ($instruction['compte_id'] ?? '')));
        $motif = trim((string) ($instruction['motif'] ?? ''));
        $version = (int) ($instruction['schema_message_version'] ?? 0);

        if ($eventId === '' || $instructionId === '' || ! IdentityUlid::isValid($accountId) || $motif === '' || $version < 1) {
            throw new IdentityAccessException('saisie_invalide', 'L instruction A6 est incomplete.', 422);
        }
    }

    private function examReference(): string
    {
        do {
            $reference = 'EX-'.substr(IdentityUlid::make(), -10);
        } while (SupportRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
