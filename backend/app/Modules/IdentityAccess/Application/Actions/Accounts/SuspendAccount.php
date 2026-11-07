<?php

namespace App\Modules\IdentityAccess\Application\Actions\Accounts;

use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use Illuminate\Support\Carbon;

class SuspendAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private RevokeAccountSessions $revokeSessions,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{motif: string, fin_prevue: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $accountId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $accountId,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'suspendre_compte')) {
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

            $ids = collect([$actor->id, $accountId])->unique()->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $target = Account::query()->with('profile')->where('id', $accountId)->lockForUpdate()->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($actor->id === $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas suspendre ce compte.',
                    409,
                );
            }

            if ($target->statut === AccountStatus::Closed) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Un compte cloture n est plus suspendable.',
                    409,
                );
            }

            if ($target->statut === AccountStatus::Suspended) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Ce compte est deja suspendu. La levee suit un reexamen distinct.',
                    409,
                );
            }

            $endsAt = $this->parseDeadline($input['fin_prevue']);
            $now = now();
            $previous = $target->statut->value;
            $target->statut = AccountStatus::Suspended;
            $target->save();

            $restricted = $this->restrictPrincipalOrganizations($target);

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => $previous], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode([
                'statut' => AccountStatus::Suspended->value,
                'fin_prevue' => $endsAt?->toIso8601String(),
                'regle' => 'decision_humaine',
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->fin_prevue = $endsAt;
            $event->correlation = $correlationId;
            $event->save();

            $revoked = ($this->revokeSessions)($target->id, 'Suspension du compte.');

            $payload = [
                'http_status' => 200,
                'statut' => 'suspendu',
                'prochaine_action' => 'propagation',
                'propagation' => 'en_cours',
                'compte' => [
                    'id' => $target->id,
                    'statut' => $target->statut->value,
                    'email_masque' => DestinationMasker::email($target->email_normalise),
                    'prenom' => $target->profile?->prenom,
                    'nom' => $target->profile?->nom,
                ],
                'sessions_revoquees' => $revoked,
                'fin_prevue' => $endsAt?->toIso8601String(),
                'organisations_restreintes' => $restricted,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'compte_acces_modifie',
                operation: 'A1-22',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
            );

            if ($revoked > 0) {
                $this->outbox->record(
                    idempotencyKey: 'a1-22:session:'.$idempotencyKey,
                    type: 'session_acces_modifie',
                    operation: 'A1-22',
                    correlation: $correlationId,
                    intentionFingerprint: SecretHasher::hmac($idempotencyKey.':sessions'),
                    payload: [
                        'compte_id' => $target->id,
                        'sessions_revoquees' => $revoked,
                        'motif' => 'Suspension du compte.',
                    ],
                    accountId: $target->id,
                );
            }

            unset($payload['http_status']);

            return $payload;
        });
    }

    private function parseDeadline(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $endsAt = Carbon::parse($value);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La fin prevue est invalide.',
                422,
                ['fin_prevue' => ['Date invalide.']],
            );
        }

        if ($endsAt->lte(now())) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La fin prevue doit etre posterieure a maintenant.',
                422,
                ['fin_prevue' => ['Echeance passee.']],
            );
        }

        return $endsAt;
    }

    /**
     * @return list<array{id: string, raison_sociale: string, statut_operationnel: string}>
     */
    private function restrictPrincipalOrganizations(Account $target): array
    {
        $organizationIds = OrganizationMembership::query()
            ->where('compte_id', $target->id)
            ->where('principal', 1)
            ->where('statut', 'active')
            ->orderBy('organisation_id')
            ->pluck('organisation_id');

        $restricted = [];

        foreach ($organizationIds as $organizationId) {
            $organization = Organization::query()->where('id', $organizationId)->lockForUpdate()->first();

            if ($organization === null || $organization->statut_operationnel !== 'active') {
                continue;
            }

            $organization->statut_operationnel = 'restricted';
            $organization->save();

            $restricted[] = [
                'id' => $organization->id,
                'raison_sociale' => $organization->raison_sociale,
                'statut_operationnel' => $organization->statut_operationnel,
            ];
        }

        return $restricted;
    }
}
