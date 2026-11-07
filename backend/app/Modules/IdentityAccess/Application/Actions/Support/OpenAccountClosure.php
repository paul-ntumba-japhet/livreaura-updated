<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\EmailNormalizer;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\ServiceDeadlineCalculator;
use Illuminate\Support\Facades\Crypt;

class OpenAccountClosure
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private ServiceDeadlineCalculator $deadlines,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{email: string, message: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $input,
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
                unset($payload['http_status']);

                return $payload;
            }

            $email = EmailNormalizer::normalize($input['email']);
            $masked = DestinationMasker::email($email);

            if ($this->quotaReached($masked)) {
                throw new IdentityAccessException(
                    'quota_atteint',
                    'Le nombre de demandes pour cette periode est atteint. Reessayez plus tard.',
                    429,
                );
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
            $demande->reference = $this->closureReference();
            $demande->type = 'cloture';
            $demande->contact_chiffre = Crypt::encryptString($email);
            $demande->contact_masque = $masked;
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

            $created = new ServiceTaskEvent;
            $created->id = IdentityUlid::make();
            $created->tache_id = $task->id;
            $created->type = 'creation';
            $created->date = $now;
            $created->origine_systeme = 'cloture_demande';
            $created->nouvel_etat = 'a_affecter';
            $created->motif = 'Ouverture d une demande de cloture.';
            $created->correlation = $correlationId;
            $created->save();

            $exchange = new SupportExchange;
            $exchange->id = IdentityUlid::make();
            $exchange->demande_id = $demande->id;
            $exchange->origine = 'demandeur';
            $exchange->visibilite = 'demandeur';
            $exchange->contenu_prive = $input['message'];
            $exchange->date = $now;
            $exchange->canal = 'formulaire_public';
            $exchange->save();

            $payload = [
                'http_status' => 202,
                'statut' => 'recu',
                'prochaine_action' => 'conserver_la_reference',
                'reference' => $demande->reference,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'demande_cloture_ouverte',
                operation: 'cloture_demande',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                demandeId: $demande->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    private function quotaReached(string $maskedContact): bool
    {
        $count = SupportRequest::query()
            ->where('type', 'cloture')
            ->where('contact_masque', $maskedContact)
            ->where('cree_a', '>=', now()->subHour())
            ->count();

        return $count >= 5;
    }

    private function closureReference(): string
    {
        do {
            $reference = 'CL-'.substr(IdentityUlid::make(), -10);
        } while (SupportRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
