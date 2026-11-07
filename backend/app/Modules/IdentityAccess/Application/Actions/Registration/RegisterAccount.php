<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

use App\Modules\IdentityAccess\Application\DTOs\RegistrationData;
use App\Modules\IdentityAccess\Application\Queries\ListRegistrationPaths;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ConsentEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PersonalProfile;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use Illuminate\Database\QueryException;

class RegisterAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private ListRegistrationPaths $paths,
        private IdentityOutboxPublisher $outbox,
        private PrepareAccountVerification $prepareVerification,
    ) {}

    public function __invoke(RegistrationData $data): Account
    {
        $created = false;

        try {
            $account = $this->coordination->withinIdentity(function () use ($data, &$created): Account {
                $existingMessage = OutgoingMessage::query()
                    ->where('cle_idempotence', $data->idempotencyKey)
                    ->first();

                if ($existingMessage !== null) {
                    if ($existingMessage->empreinte_intention !== $data->intentionFingerprint) {
                        throw new IdentityAccessException(
                            'intention_conflictuelle',
                            'Cette intention entre en conflit avec une demande deja enregistree.',
                            409,
                        );
                    }

                    $existing = Account::query()->find($existingMessage->compte_id);

                    if ($existing === null) {
                        throw new IdentityAccessException(
                            'inscription_incoherente',
                            'La demande precedente n\'est pas recuperable.',
                            409,
                        );
                    }

                    return $existing;
                }

                if (Account::query()->where('email_normalise', $data->email)->exists()) {
                    throw new IdentityAccessException(
                        'compte_existant',
                        'Un compte existe deja pour cet e-mail. Connectez-vous ou recuperez l acces.',
                        409,
                    );
                }

                $required = $this->paths->applicableRequired($data->locale);
                $acceptedIds = collect($data->consents)
                    ->whereIn('nature', ['acceptation', 'prise_connaissance'])
                    ->pluck('texte_version_id')
                    ->all();

                foreach ($required as $text) {
                    if (! in_array($text->id, $acceptedIds, true)) {
                        throw new IdentityAccessException(
                            'textes_requis',
                            'Les textes requis pour l activation n ont pas tous ete acceptes.',
                            422,
                            ['consentements' => ['Textes requis manquants.']],
                        );
                    }
                }

                $now = now();
                $account = new Account;
                $account->id = IdentityUlid::make();
                $account->email_normalise = $data->email;
                $account->telephone_normalise = $data->phone;
                $account->hash_mdp = $data->passwordHash;
                $account->statut = AccountStatus::Pending;
                $account->locale = $data->locale;
                $account->email_verifie_a = null;
                $account->telephone_verifie_a = null;
                $account->cree_a = $now;
                $account->ferme_a = null;
                $account->save();

                $profile = new PersonalProfile;
                $profile->compte_id = $account->id;
                $profile->prenom = $data->firstName;
                $profile->nom = $data->lastName;
                $profile->nom_plume = $data->penName;
                $profile->pays = $data->country;
                $profile->ville = $data->city;
                $profile->langue_ecriture = $data->writingLanguage;
                $profile->save();

                $reader = Role::query()->where('code', 'reader')->first();

                if ($reader === null) {
                    throw new IdentityAccessException(
                        'service_indisponible',
                        'Le referentiel des roles est indisponible.',
                        503,
                    );
                }

                $accountRole = new AccountRole;
                $accountRole->id = IdentityUlid::make();
                $accountRole->compte_id = $account->id;
                $accountRole->role_id = $reader->id;
                $accountRole->statut = 'active';
                $accountRole->origine = 'inscription_publique';
                $accountRole->attribue_a = $now;
                $accountRole->save();

                foreach ($data->consents as $consentInput) {
                    $text = LegalTextVersion::query()->find($consentInput['texte_version_id']);

                    if ($text === null) {
                        throw new IdentityAccessException(
                            'saisie_invalide',
                            'Un texte legal est inconnu.',
                            422,
                            ['consentements' => ['Texte legal inconnu.']],
                        );
                    }

                    $consent = new Consent;
                    $consent->id = IdentityUlid::make();
                    $consent->compte_id = $account->id;
                    $consent->texte_version_id = $text->id;
                    $consent->nature = $consentInput['nature'];
                    $consent->accepte_a = $now;
                    $consent->source = 'inscription';
                    $consent->canal = 'web';
                    $consent->save();

                    $consentEvent = new ConsentEvent;
                    $consentEvent->id = IdentityUlid::make();
                    $consentEvent->consentement_id = $consent->id;
                    $consentEvent->type = 'acceptation';
                    $consentEvent->date = $now;
                    $consentEvent->source = 'inscription';
                    $consentEvent->canal = 'web';
                    $consentEvent->save();
                }

                $event = new AccountEvent;
                $event->id = IdentityUlid::make();
                $event->compte_sujet_id = $account->id;
                $event->compte_role_id = $accountRole->id;
                $event->type = 'compte_cree';
                $event->date = $now;
                $event->nouvel_etat = json_encode([
                    'statut' => AccountStatus::Pending->value,
                    'parcours' => $data->path,
                    'role' => 'reader',
                ], JSON_THROW_ON_ERROR);
                $event->motif = 'Ouverture de compte personnel.';
                $event->origine_systeme = 'A1-02';
                $event->correlation = $data->correlationId;
                $event->save();

                $this->outbox->record(
                    idempotencyKey: $data->idempotencyKey,
                    type: 'compte_cree',
                    operation: 'A1-02',
                    correlation: $data->correlationId,
                    intentionFingerprint: $data->intentionFingerprint,
                    payload: [
                        'compte_id' => $account->id,
                        'statut' => AccountStatus::Pending->value,
                        'parcours' => $data->path,
                    ],
                    accountId: $account->id,
                    accountEventId: $event->id,
                );

                $created = true;

                return $account;
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueEmailViolation($exception)) {
                throw new IdentityAccessException(
                    'compte_existant',
                    'Un compte existe deja pour cet e-mail. Connectez-vous ou recuperez l acces.',
                    409,
                );
            }

            throw $exception;
        }

        if ($created) {
            try {
                ($this->prepareVerification)($account);
            } catch (\Throwable) {
                // Une panne mail apres commit ne transforme pas une creation reussie en echec.
            }
        }

        return $account->fresh() ?? $account;
    }

    private function isUniqueEmailViolation(QueryException $exception): bool
    {
        return $exception->errorInfo[0] === '23000'
            && str_contains($exception->getMessage(), 'email_normalise');
    }
}
