<?php

namespace App\Modules\IdentityAccess\Application\Actions\Tasks;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;
use Illuminate\Support\Carbon;

class AssignServiceSubstitute
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{titulaire_id: string, suppleant_id: string, fonction: string, debut_a: string, fin_a: string, motif: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'affecter_taches')) {
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

            $holderId = $input['titulaire_id'];
            $substituteId = $input['suppleant_id'];

            if ($holderId === $substituteId) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Le titulaire et le suppleant doivent etre distincts.',
                    422,
                    ['suppleant_id' => ['Compte identique au titulaire.']],
                );
            }

            $ids = collect([$holderId, $substituteId])->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $holder = Account::query()->where('id', $holderId)->first();
            $substitute = Account::query()->where('id', $substituteId)->first();

            if ($holder === null || $substitute === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($holder->statut !== AccountStatus::Active || $substitute->statut !== AccountStatus::Active) {
                throw new IdentityAccessException(
                    'compte_ineligible',
                    'Le compte indique n est pas eligible a cette operation.',
                    422,
                );
            }

            $startsAt = $this->parseInstant($input['debut_a'], 'debut_a');
            $endsAt = $this->parseInstant($input['fin_a'], 'fin_a');

            if ($endsAt->lte($startsAt)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'La fin doit etre posterieure au debut.',
                    422,
                    ['fin_a' => ['Fin anterieure ou egale au debut.']],
                );
            }

            $overlap = ServiceSubstitute::query()
                ->where('titulaire_id', $holder->id)
                ->where('fonction', $input['fonction'])
                ->whereNull('annule_a')
                ->where('debut_a', '<', $endsAt)
                ->where('fin_a', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Une suppleance effective existe deja pour ce titulaire et cette fonction.',
                    409,
                );
            }

            $now = now();
            $row = new ServiceSubstitute;
            $row->id = IdentityUlid::make();
            $row->titulaire_id = $holder->id;
            $row->suppleant_id = $substitute->id;
            $row->fonction = $input['fonction'];
            $row->debut_a = $startsAt;
            $row->fin_a = $endsAt;
            $row->defini_par = $actor->id;
            $row->defini_a = $now;
            $row->motif = $input['motif'];
            $row->save();

            $payload = [
                'http_status' => 201,
                'statut' => 'enregistree',
                'prochaine_action' => 'aucune_permission_implicite',
                'suppleance' => $this->serialize($row),
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'suppleance_enregistree',
                operation: 'suppleances_service',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $holder->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ServiceSubstitute $substitute): array
    {
        $now = now();
        $state = 'a_venir';

        if ($substitute->annule_a !== null) {
            $state = 'annulee';
        } elseif ($substitute->fin_a !== null && $substitute->fin_a->lte($now)) {
            $state = 'terminee';
        } elseif ($substitute->debut_a !== null && $substitute->debut_a->lte($now) && $substitute->fin_a !== null && $substitute->fin_a->gt($now)) {
            $state = 'en_vigueur';
        }

        return [
            'id' => $substitute->id,
            'titulaire_id' => $substitute->titulaire_id,
            'suppleant_id' => $substitute->suppleant_id,
            'fonction' => $substitute->fonction,
            'debut_a' => $substitute->debut_a?->toIso8601String(),
            'fin_a' => $substitute->fin_a?->toIso8601String(),
            'etat' => $state,
            'motif' => $substitute->motif,
            'permission_implicite' => false,
        ];
    }

    private function parseInstant(string $value, string $field): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La periode est invalide.',
                422,
                [$field => ['Date invalide.']],
            );
        }
    }
}
