<?php

namespace App\Modules\IdentityAccess\Application\Actions\Organizations;

use App\Modules\IdentityAccess\Application\Queries\ListAccountOrganizations;
use App\Modules\IdentityAccess\Application\Queries\ListPossibleOrganizations;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;

class CreateOrRecognizeOrganization
{
    public function __construct(
        private CoordinationGuard $coordination,
        private InstallInitialRepresentative $installRepresentative,
        private ListPossibleOrganizations $matches,
        private ListAccountOrganizations $accountOrganizations,
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
    ) {}

    /**
     * @param  array{type_organisation_id: string, raison_sociale: string, pays: string, ville: ?string, email_pro: ?string, site: ?string, organisation_id: ?string, confirmer_nouvelle: bool}  $input
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
                'La creation d une organisation n est disponible qu pour un compte actif.',
                403,
            );
        }

        return $this->coordination->withinIdentity(function () use (
            $account,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            $locked = Account::query()->where('id', $account->id)->lockForUpdate()->first();

            if ($locked === null || $locked->statut !== AccountStatus::Active) {
                throw new IdentityAccessException(
                    'compte_ineligible',
                    'La creation d une organisation n est disponible qu pour un compte actif.',
                    403,
                );
            }

            $existingMessage = OutgoingMessage::query()
                ->where('cle_idempotence', $idempotencyKey)
                ->first();

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

            $type = OrganizationType::query()->where('id', $input['type_organisation_id'])->first();

            if ($type === null) {
                throw new IdentityAccessException(
                    'type_inconnu',
                    'Ce type d organisation est inconnu.',
                    422,
                    ['type_organisation_id' => ['Type d organisation inconnu.']],
                );
            }

            if (is_string($input['organisation_id'])) {
                return $this->recognizeExisting($locked, $input['organisation_id'], $idempotencyKey, $intentionFingerprint, $correlationId);
            }

            $possible = ($this->matches)($type->id, $input['raison_sociale'], $input['pays']);

            if ($possible !== [] && $input['confirmer_nouvelle'] !== true) {
                return [
                    'http_status' => 200,
                    'statut' => 'correspondances_possibles',
                    'prochaine_action' => 'confirmer_ou_revendiquer',
                    'organisation' => null,
                    'adhesion' => null,
                    'demande' => null,
                    'correspondances' => $possible,
                ];
            }

            return $this->createNew($locked, $type, $input, $idempotencyKey, $intentionFingerprint, $correlationId);
        });
    }

    /**
     * @param  array{raison_sociale: string, pays: string, ville: ?string, email_pro: ?string, site: ?string}  $input
     * @return array<string, mixed>
     */
    private function createNew(
        Account $account,
        OrganizationType $type,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        $now = now();
        $organization = new Organization;
        $organization->id = IdentityUlid::make();
        $organization->type_organisation_id = $type->id;
        $organization->raison_sociale = trim($input['raison_sociale']);
        $organization->email_pro = $input['email_pro'];
        $organization->site = $input['site'];
        $organization->pays = strtoupper($input['pays']);
        $organization->ville = $input['ville'];
        $organization->statut_operationnel = 'active';
        $organization->cree_a = $now;
        $organization->save();

        ($this->installRepresentative)($account, $organization, $now, $correlationId);

        $payload = $this->createdPayload($account, $organization, 201, 'creee', 'compte');

        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'organisation_creee',
            operation: 'A1-10',
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $account->id,
        );

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function recognizeExisting(
        Account $account,
        string $organizationId,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        $organization = Organization::query()->where('id', $organizationId)->lockForUpdate()->first();

        if ($organization === null) {
            throw new IdentityAccessException(
                'organisation_inconnue',
                'Cette organisation est inconnue.',
                422,
                ['organisation_id' => ['Organisation inconnue.']],
            );
        }

        if ($organization->statut_operationnel === 'closed') {
            throw new IdentityAccessException(
                'organisation_ineligible',
                'Cette organisation n est plus disponible.',
                422,
                ['organisation_id' => ['Organisation indisponible.']],
            );
        }

        $membership = OrganizationMembership::query()
            ->where('compte_id', $account->id)
            ->where('organisation_id', $organization->id)
            ->lockForUpdate()
            ->first();

        if ($membership !== null && $membership->statut === 'active') {
            $payload = $this->createdPayload($account, $organization, 200, 'deja_membre', 'compte');
            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'organisation_creee',
                operation: 'A1-10',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $account->id,
            );

            return $payload;
        }

        $open = SupportRequest::query()
            ->where('demandeur_compte_id', $account->id)
            ->where('type', 'representation')
            ->where('organisation_id', $organization->id)
            ->whereNull('clos_a')
            ->lockForUpdate()
            ->first();

        if ($open === null) {
            $open = $this->openRepresentation($account, $organization, $correlationId);
        }

        $payload = [
            'http_status' => 202,
            'statut' => 'examen',
            'prochaine_action' => 'attente_examen',
            'organisation' => ($this->matches)->serialize($organization),
            'adhesion' => null,
            'demande' => ['reference' => $open->reference],
            'correspondances' => [],
        ];

        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'representation',
            operation: 'A1-10',
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $account->id,
            demandeId: $open->id,
        );

        return $payload;
    }

    private function openRepresentation(Account $account, Organization $organization, string $correlationId): SupportRequest
    {
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
        $demande->reference = $this->representationReference();
        $demande->type = 'representation';
        $demande->demandeur_compte_id = $account->id;
        $demande->compte_concerne_id = $account->id;
        $demande->organisation_id = $organization->id;
        $demande->cree_a = $now;
        $demande->identite_etat = 'non_verifiee';
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

        $event = new ServiceTaskEvent;
        $event->id = IdentityUlid::make();
        $event->tache_id = $task->id;
        $event->type = 'creation';
        $event->date = $now;
        $event->origine_systeme = 'A1-10';
        $event->nouvel_etat = 'a_affecter';
        $event->motif = 'Ouverture d une demande de representation, sans droit accorde.';
        $event->correlation = $correlationId;
        $event->save();

        return $demande;
    }

    /**
     * @return array<string, mixed>
     */
    private function createdPayload(Account $account, Organization $organization, int $status, string $statut, string $next): array
    {
        $state = ($this->accountOrganizations)($account);
        $current = collect($state['organisations'])
            ->first(fn (array $item): bool => $item['id'] === $organization->id);

        return [
            'http_status' => $status,
            'statut' => $statut,
            'prochaine_action' => $next,
            'organisation' => $current === null ? ($this->matches)->serialize($organization) : [
                'id' => $current['id'],
                'raison_sociale' => $current['raison_sociale'],
                'pays' => $current['pays'],
                'ville' => $current['ville'],
                'statut_operationnel' => $current['statut_operationnel'],
                'type' => $current['type'],
            ],
            'adhesion' => is_array($current) ? ($current['adhesion'] ?? null) : null,
            'demande' => null,
            'correspondances' => [],
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
            'statut' => is_string($payload['statut'] ?? null) ? $payload['statut'] : 'creee',
            'prochaine_action' => is_string($payload['prochaine_action'] ?? null) ? $payload['prochaine_action'] : 'compte',
            'organisation' => is_array($payload['organisation'] ?? null) ? $payload['organisation'] : null,
            'adhesion' => is_array($payload['adhesion'] ?? null) ? $payload['adhesion'] : null,
            'demande' => is_array($payload['demande'] ?? null) ? $payload['demande'] : null,
            'correspondances' => is_array($payload['correspondances'] ?? null) ? $payload['correspondances'] : [],
        ];
    }

    private function representationReference(): string
    {
        do {
            $reference = 'RP-'.substr(IdentityUlid::make(), -10);
        } while (SupportRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
