<?php

namespace App\Modules\IdentityAccess\Application\Actions\Accounts;

use App\Modules\IdentityAccess\Application\Actions\Authorization\AssignRole;
use App\Modules\IdentityAccess\Application\Queries\ListAuthorPathState;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;
use Illuminate\Support\Str;

class EnableAuthorPath
{
    public function __construct(
        private CoordinationGuard $coordination,
        private AssignRole $assignRole,
        private ListAuthorPathState $authorPath,
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        ?string $auteurId,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        if ($account->statut !== AccountStatus::Active) {
            throw new IdentityAccessException(
                'compte_ineligible',
                'Le parcours auteur n est disponible qu pour un compte actif.',
                403,
            );
        }

        return $this->coordination->withinIdentityThenCatalog(function () use (
            $account,
            $auteurId,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            $locked = Account::query()->where('id', $account->id)->lockForUpdate()->first();

            if ($locked === null || $locked->statut !== AccountStatus::Active) {
                throw new IdentityAccessException(
                    'compte_ineligible',
                    'Le parcours auteur n est disponible qu pour un compte actif.',
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

            $locked->load(['profile', 'accountRoles.role']);

            if ($auteurId !== null) {
                return $this->claimExistingFiche($locked, $auteurId, $idempotencyKey, $intentionFingerprint, $correlationId);
            }

            return $this->enableDraft($locked, $idempotencyKey, $intentionFingerprint, $correlationId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function enableDraft(
        Account $account,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        $now = now();
        $assignment = ($this->assignRole)($account, 'author', 'parcours_auteur', $now);
        $fiche = CatalogAuthor::query()->where('compte_id', $account->id)->lockForUpdate()->first();
        $eventId = null;

        if ($fiche === null) {
            $fiche = $this->createDraftFiche($account);

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $account->id;
            $event->compte_role_id = $assignment->id;
            $event->type = 'auteur_compte_lie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'auteur_id' => $fiche->auteur_id,
                'statut_publication' => $fiche->statut_publication,
                'role' => 'author',
            ], JSON_THROW_ON_ERROR);
            $event->motif = 'Activation du parcours auteur et preparation d une fiche brouillon.';
            $event->origine_systeme = 'A1-08';
            $event->correlation = $correlationId;
            $event->save();
            $eventId = $event->id;
        }

        $payload = $this->successPayload($account, 200, 'active', 'compte');

        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'auteur_compte_lie',
            operation: 'A1-08',
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $account->id,
            accountEventId: $eventId,
        );

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function claimExistingFiche(
        Account $account,
        string $auteurId,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        $fiche = CatalogAuthor::query()->where('auteur_id', $auteurId)->lockForUpdate()->first();

        if ($fiche === null) {
            throw new IdentityAccessException(
                'fiche_inconnue',
                'Cette fiche auteur est inconnue.',
                422,
                ['auteur_id' => ['Fiche auteur inconnue.']],
            );
        }

        if ($fiche->compte_id === $account->id) {
            return $this->enableDraft($account, $idempotencyKey, $intentionFingerprint, $correlationId);
        }

        $open = SupportRequest::query()
            ->where('demandeur_compte_id', $account->id)
            ->where('type', 'revendication_auteur')
            ->where('reference_auteur_externe', $auteurId)
            ->whereNull('clos_a')
            ->lockForUpdate()
            ->first();

        if ($open === null) {
            $open = $this->openClaim($account, $auteurId, $correlationId);
        }

        $payload = $this->successPayload($account, 202, 'examen', 'attente_examen', $open);

        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'revendication_auteur',
            operation: 'A1-08',
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $account->id,
            demandeId: $open->id,
        );

        return $payload;
    }

    private function openClaim(Account $account, string $auteurId, string $correlationId): SupportRequest
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
        $demande->reference = $this->claimReference();
        $demande->type = 'revendication_auteur';
        $demande->demandeur_compte_id = $account->id;
        $demande->compte_concerne_id = $account->id;
        $demande->reference_auteur_externe = $auteurId;
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
        $event->origine_systeme = 'A1-08';
        $event->nouvel_etat = 'a_affecter';
        $event->motif = 'Ouverture d une revendication d identite auteur, sans rattachement.';
        $event->correlation = $correlationId;
        $event->save();

        return $demande;
    }

    private function createDraftFiche(Account $account): CatalogAuthor
    {
        $id = IdentityUlid::make();
        $displayName = $this->displayName($account);

        $fiche = new CatalogAuthor;
        $fiche->auteur_id = $id;
        $fiche->compte_id = $account->id;
        $fiche->slug = $this->uniqueSlug($displayName, $id);
        $fiche->nom_affiche = $displayName;
        $fiche->bio = null;
        $fiche->pays = $account->profile?->pays;
        $fiche->role_affiche = null;
        $fiche->verified = 0;
        $fiche->mis_en_avant = 0;
        $fiche->actif = 1;
        $fiche->statut_publication = 'draft';
        $fiche->save();

        return $fiche;
    }

    private function displayName(Account $account): string
    {
        $profile = $account->profile;
        $penName = trim((string) ($profile?->nom_plume ?? ''));

        if ($penName !== '') {
            return mb_substr($penName, 0, 512);
        }

        $full = trim(trim((string) ($profile?->prenom ?? '')).' '.trim((string) ($profile?->nom ?? '')));

        if ($full !== '') {
            return mb_substr($full, 0, 512);
        }

        return 'Auteur';
    }

    private function uniqueSlug(string $displayName, string $id): string
    {
        $base = Str::slug($displayName);
        $base = $base !== '' ? $base : 'auteur';
        $suffix = strtolower(substr($id, -8));
        $slug = mb_substr($base.'-'.$suffix, 0, 191);

        if (! CatalogAuthor::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return mb_substr($base.'-'.strtolower($id), 0, 191);
    }

    private function claimReference(): string
    {
        do {
            $reference = 'RA-'.substr(IdentityUlid::make(), -10);
        } while (SupportRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return array<string, mixed>
     */
    private function successPayload(
        Account $account,
        int $httpStatus,
        string $statut,
        string $nextAction,
        ?SupportRequest $demande = null,
    ): array {
        $state = ($this->authorPath)($account);

        return [
            'http_status' => $httpStatus,
            'statut' => $statut,
            'prochaine_action' => $nextAction,
            'fiche' => $state['parcours_auteur']['fiche'],
            'demande' => $demande === null
                ? $state['parcours_auteur']['demande']
                : ['reference' => $demande->reference],
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
            'statut' => is_string($payload['statut'] ?? null) ? $payload['statut'] : 'active',
            'prochaine_action' => is_string($payload['prochaine_action'] ?? null) ? $payload['prochaine_action'] : 'compte',
            'fiche' => is_array($payload['fiche'] ?? null) ? $payload['fiche'] : null,
            'demande' => is_array($payload['demande'] ?? null) ? $payload['demande'] : null,
        ];
    }
}
