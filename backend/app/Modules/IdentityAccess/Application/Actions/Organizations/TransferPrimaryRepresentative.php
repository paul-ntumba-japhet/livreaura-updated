<?php

namespace App\Modules\IdentityAccess\Application\Actions\Organizations;

use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;

class TransferPrimaryRepresentative
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private RevokeAccountSessions $revokeSessions,
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
    ) {}

    /**
     * @param  array{nouveau_compte_id: string, motif: string, depart: bool, demande_id: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $organizationId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $organizationId,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (
                ! ($this->hasPermission)($actor, 'transferer_principal')
                || ! ($this->hasPermission)($actor, 'verifier_representation')
            ) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

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

            $newAccountId = $input['nouveau_compte_id'];
            $accountIds = collect([$actor->id, $newAccountId])
                ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
                ->unique()
                ->sort()
                ->values();

            foreach ($accountIds as $accountId) {
                Account::query()->where('id', $accountId)->lockForUpdate()->first();
            }

            $organization = Organization::query()->where('id', $organizationId)->lockForUpdate()->first();

            if ($organization === null) {
                throw new IdentityAccessException('organisation_inconnue', 'Cette organisation n est pas visible.', 404);
            }

            if ($organization->statut_operationnel === 'closed') {
                throw new IdentityAccessException(
                    'organisation_ineligible',
                    'Cette organisation n est plus disponible.',
                    422,
                );
            }

            $memberships = OrganizationMembership::query()
                ->with('permissions')
                ->where('organisation_id', $organization->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $oldMembership = $memberships->first(
                fn (OrganizationMembership $membership): bool => $membership->principal && $membership->statut === 'active',
            );

            if ($oldMembership !== null && ! $accountIds->contains($oldMembership->compte_id)) {
                Account::query()->where('id', $oldMembership->compte_id)->lockForUpdate()->first();
            }

            $newAccount = Account::query()->where('id', $newAccountId)->lockForUpdate()->first();

            if ($newAccount === null) {
                throw new IdentityAccessException(
                    'compte_inconnu',
                    'Ce compte n est pas visible.',
                    404,
                    ['nouveau_compte_id' => ['Compte inconnu.']],
                );
            }

            if ($newAccount->statut !== AccountStatus::Active) {
                throw new IdentityAccessException(
                    'compte_ineligible',
                    'Le nouveau representant doit etre un compte actif.',
                    422,
                    ['nouveau_compte_id' => ['Compte ineligible.']],
                );
            }

            if ($oldMembership !== null && $oldMembership->compte_id === $newAccount->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Ce compte est deja le representant principal.',
                    409,
                );
            }

            if ($actor->id === $newAccount->id || ($oldMembership !== null && $actor->id === $oldMembership->compte_id)) {
                throw new IdentityAccessException(
                    'conflit_interet',
                    'Ce transfert est lie a ce compte. Un agent independant est necessaire.',
                    409,
                );
            }

            $demande = $this->lockDemand($input['demande_id'], $organization, $newAccount);
            $permissionIds = $this->permissionIdsToTransfer($oldMembership);
            $now = now();

            if ($oldMembership !== null) {
                $this->demoteOutgoing($oldMembership, $input['depart'], $input['motif'], $now);
            }

            $newMembership = $this->installIncoming(
                $memberships->first(
                    fn (OrganizationMembership $membership): bool => $membership->compte_id === $newAccount->id,
                ),
                $organization,
                $newAccount,
                $permissionIds,
                $now,
            );

            $oldAccount = $oldMembership?->compte_id
                ? Account::query()->with('profile')->where('id', $oldMembership->compte_id)->first()
                : null;
            $newAccount->loadMissing('profile');

            $demande = $this->closeDemand(
                $demande,
                $actor,
                $organization,
                $newAccount,
                $oldAccount,
                $input['motif'],
                $now,
                $correlationId,
            );

            $oldEvent = $oldMembership === null ? null : $this->recordMembershipEvent(
                $oldAccount ?? $actor,
                $oldMembership,
                $actor,
                $organization,
                $input['motif'],
                $now,
                $correlationId,
                [
                    'principal' => true,
                    'statut' => 'active',
                ],
                [
                    'principal' => false,
                    'statut' => $oldMembership->statut,
                    'depart' => $input['depart'],
                ],
            );

            $newEvent = $this->recordMembershipEvent(
                $newAccount,
                $newMembership,
                $actor,
                $organization,
                $input['motif'],
                $now,
                $correlationId,
                [
                    'principal' => false,
                    'statut' => 'incoming',
                ],
                [
                    'principal' => true,
                    'statut' => 'active',
                    'permissions' => $newMembership->permissions
                        ->map(fn (OrganizationPermission $permission): string => $permission->code)
                        ->values()
                        ->all(),
                ],
            );

            $revokedOld = $oldAccount === null ? 0 : ($this->revokeSessions)(
                $oldAccount->id,
                'Transfert de representation principale.',
            );
            $revokedNew = ($this->revokeSessions)(
                $newAccount->id,
                'Transfert de representation principale.',
            );

            $payload = [
                'http_status' => 200,
                'statut' => 'transfere',
                'prochaine_action' => 'compte',
                'organisation' => [
                    'id' => $organization->id,
                    'raison_sociale' => $organization->raison_sociale,
                    'statut_operationnel' => $organization->statut_operationnel,
                ],
                'ancien' => $oldMembership === null || $oldAccount === null ? null : $this->serializeParty(
                    $oldAccount,
                    $oldMembership,
                    $revokedOld,
                ),
                'nouveau' => $this->serializeParty($newAccount, $newMembership, $revokedNew),
                'demande' => ['reference' => $demande->reference],
                'depart' => $input['depart'],
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'representation',
                operation: 'A1-15',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $newAccount->id,
                accountEventId: $newEvent->id,
                demandeId: $demande->id,
            );

            if ($oldAccount !== null && $oldEvent !== null) {
                $this->outbox->record(
                    idempotencyKey: 'a1-15:notify-ancien:'.$idempotencyKey,
                    type: 'representation',
                    operation: 'A1-15',
                    correlation: $correlationId,
                    intentionFingerprint: $intentionFingerprint,
                    payload: $payload,
                    accountId: $oldAccount->id,
                    accountEventId: $oldEvent->id,
                    demandeId: $demande->id,
                );
            }

            if ($revokedOld > 0 && $oldAccount !== null) {
                $this->recordSessionOutbox($oldAccount->id, $revokedOld, $correlationId, $idempotencyKey, 'ancien');
            }

            if ($revokedNew > 0) {
                $this->recordSessionOutbox($newAccount->id, $revokedNew, $correlationId, $idempotencyKey, 'nouveau');
            }

            unset($payload['http_status']);

            return $payload;
        });
    }

    private function lockDemand(?string $demandeId, Organization $organization, Account $newAccount): ?SupportRequest
    {
        if ($demandeId === null) {
            return null;
        }

        $demande = SupportRequest::query()->where('id', $demandeId)->lockForUpdate()->first();

        if ($demande === null) {
            throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
        }

        if ($demande->type !== 'transfert_principal' || $demande->organisation_id !== $organization->id) {
            throw new IdentityAccessException(
                'etat_incompatible',
                'Cette demande ne porte pas sur le transfert de cette organisation.',
                409,
            );
        }

        if ($demande->clos_a !== null) {
            throw new IdentityAccessException(
                'etat_incompatible',
                'Cette demande n est plus modifiable.',
                409,
            );
        }

        if ($demande->identite_etat === 'refusee') {
            throw new IdentityAccessException(
                'etat_incompatible',
                'L identite de cette demande a ete refusee. Le transfert n est pas disponible.',
                409,
            );
        }

        if (
            is_string($demande->compte_concerne_id)
            && $demande->compte_concerne_id !== ''
            && $demande->compte_concerne_id !== $newAccount->id
        ) {
            throw new IdentityAccessException(
                'etat_incompatible',
                'Cette demande ne vise pas le compte indique.',
                409,
            );
        }

        return $demande;
    }

    /**
     * @return list<string>
     */
    private function permissionIdsToTransfer(?OrganizationMembership $oldMembership): array
    {
        $ids = $oldMembership?->permissions
            ->map(fn (OrganizationPermission $permission): string => $permission->id)
            ->values()
            ->all() ?? [];

        if ($ids !== []) {
            return $ids;
        }

        $permission = OrganizationPermission::query()->where('code', 'deposer_kyc')->lockForUpdate()->first();

        if ($permission === null) {
            throw new IdentityAccessException(
                'service_indisponible',
                'La permission de depot organisationnel est indisponible.',
                503,
            );
        }

        return [$permission->id];
    }

    private function demoteOutgoing(OrganizationMembership $membership, bool $depart, string $motif, $now): void
    {
        $membership->principal = 0;

        if ($depart) {
            $membership->statut = 'revoked';
            $membership->revoque_a = $now;
            $membership->motif = $motif;
        }

        $membership->save();
        $membership->permissions()->detach();
    }

    /**
     * @param  list<string>  $permissionIds
     */
    private function installIncoming(
        ?OrganizationMembership $existing,
        Organization $organization,
        Account $newAccount,
        array $permissionIds,
        $now,
    ): OrganizationMembership {
        if ($existing !== null && ! in_array($existing->statut, ['active', 'revoked'], true)) {
            throw new IdentityAccessException(
                'etat_incompatible',
                'Cette adhesion n est pas transferable dans son etat actuel.',
                409,
            );
        }

        if ($existing === null) {
            $existing = new OrganizationMembership;
            $existing->id = IdentityUlid::make();
            $existing->compte_id = $newAccount->id;
            $existing->organisation_id = $organization->id;
            $existing->origine = 'transfert_principal';
            $existing->cree_a = $now;
        }

        $existing->fonction = 'Representant principal';
        $existing->principal = 1;
        $existing->statut = 'active';
        $existing->active_a = $now;
        $existing->revoque_a = null;
        $existing->motif = null;
        $existing->save();
        $existing->permissions()->sync($permissionIds);
        $existing->load('permissions');

        return $existing;
    }

    private function closeDemand(
        ?SupportRequest $demande,
        Account $actor,
        Organization $organization,
        Account $newAccount,
        ?Account $oldAccount,
        string $motif,
        $now,
        string $correlationId,
    ): SupportRequest {
        $calendar = $this->configurations->find('A1_calendrier');
        $delays = $this->configurations->find('A1_delais');

        if ($calendar === null || $delays === null) {
            throw new IdentityAccessException(
                'service_indisponible',
                'La configuration des delais de service est indisponible.',
                503,
            );
        }

        if ($demande === null) {
            $windows = $this->deadlines->forNewTask(
                is_array($calendar->valeurs) ? $calendar->valeurs : [],
                is_array($delays->valeurs) ? $delays->valeurs : [],
                $now,
            );

            $demande = new SupportRequest;
            $demande->id = IdentityUlid::make();
            $demande->reference = $this->transferReference();
            $demande->type = 'transfert_principal';
            $demande->demandeur_compte_id = $oldAccount?->id;
            $demande->compte_concerne_id = $newAccount->id;
            $demande->organisation_id = $organization->id;
            $demande->cree_a = $now;
            $demande->identite_etat = 'non_verifiee';
            $demande->save();

            $task = new ServiceTask;
            $task->id = IdentityUlid::make();
            $task->demande_support_id = $demande->id;
            $task->responsable_id = $actor->id;
            $task->etat_travail = 'a_affecter';
            $task->priorite = 'normale';
            $task->cree_a = $now;
            $task->affecte_a = $now;
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
            $created->origine_systeme = 'A1-15';
            $created->nouvel_etat = 'a_affecter';
            $created->motif = 'Ouverture de la demande de transfert de representation principale.';
            $created->correlation = $correlationId;
            $created->save();
        } else {
            $task = ServiceTask::query()
                ->where('demande_support_id', $demande->id)
                ->lockForUpdate()
                ->first();

            if ($task === null) {
                throw new IdentityAccessException('tache_inconnue', 'La tache de cette demande n est pas visible.', 404);
            }
        }

        $previousState = $task->etat_travail;
        $demande->identite_etat = 'verifiee';
        $demande->identite_verifiee_par = $actor->id;
        $demande->identite_verifiee_a = $now;
        $demande->methode_verification = 'mandat';
        $demande->compte_concerne_id = $newAccount->id;
        $demande->organisation_id = $organization->id;
        $demande->conclusion = $motif;
        $demande->decide_par = $actor->id;
        $demande->clos_a = $now;
        $demande->save();

        $task->responsable_id = $actor->id;
        $task->etat_travail = 'terminee';
        $task->clos_a = $now;
        $task->save();

        $closed = new ServiceTaskEvent;
        $closed->id = IdentityUlid::make();
        $closed->tache_id = $task->id;
        $closed->type = 'cloture';
        $closed->date = $now;
        $closed->auteur_id = $actor->id;
        $closed->ancien_etat = $previousState;
        $closed->nouvel_etat = 'terminee';
        $closed->motif = 'Transfert de representation principale enregistre.';
        $closed->correlation = $correlationId;
        $closed->save();

        return $demande;
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $next
     */
    private function recordMembershipEvent(
        Account $subject,
        OrganizationMembership $membership,
        Account $actor,
        Organization $organization,
        string $motif,
        $now,
        string $correlationId,
        array $previous,
        array $next,
    ): AccountEvent {
        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $subject->id;
        $event->adhesion_id = $membership->id;
        $event->type = 'adhesion_organisation_modifiee';
        $event->date = $now;
        $event->ancien_etat = json_encode($previous, JSON_THROW_ON_ERROR);
        $event->nouvel_etat = json_encode([
            'organisation_id' => $organization->id,
            ...$next,
        ], JSON_THROW_ON_ERROR);
        $event->motif = $motif;
        $event->auteur_compte_id = $actor->id;
        $event->correlation = $correlationId;
        $event->save();

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParty(Account $account, OrganizationMembership $membership, int $revokedSessions): array
    {
        $membership->loadMissing('permissions');

        return [
            'compte_id' => $account->id,
            'email_masque' => DestinationMasker::email($account->email_normalise),
            'prenom' => $account->profile?->prenom,
            'nom' => $account->profile?->nom,
            'adhesion' => [
                'id' => $membership->id,
                'principal' => (bool) $membership->principal,
                'statut' => $membership->statut,
                'fonction' => $membership->fonction,
                'permissions' => $membership->permissions
                    ->map(fn (OrganizationPermission $permission): string => $permission->code)
                    ->values()
                    ->all(),
            ],
            'sessions_revoquees' => $revokedSessions,
        ];
    }

    private function recordSessionOutbox(
        string $accountId,
        int $revoked,
        string $correlationId,
        string $idempotencyKey,
        string $side,
    ): void {
        $this->outbox->record(
            idempotencyKey: 'a1-15:session:'.$side.':'.$idempotencyKey,
            type: 'session_acces_modifie',
            operation: 'A1-15',
            correlation: $correlationId,
            intentionFingerprint: SecretHasher::hmac($idempotencyKey.':'.$side),
            payload: [
                'compte_id' => $accountId,
                'sessions_revoquees' => $revoked,
                'motif' => 'Transfert de representation principale.',
            ],
            accountId: $accountId,
        );
    }

    private function transferReference(): string
    {
        do {
            $reference = 'TP-'.substr(IdentityUlid::make(), -10);
        } while (SupportRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
