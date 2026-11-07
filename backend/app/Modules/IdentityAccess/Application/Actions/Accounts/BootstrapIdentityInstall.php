<?php

namespace App\Modules\IdentityAccess\Application\Actions\Accounts;

use App\Modules\IdentityAccess\Application\DTOs\BootstrapInstallResult;
use App\Modules\IdentityAccess\Application\Support\EnsureOrganizationTypes;
use App\Modules\IdentityAccess\Application\Support\EnsureVerificationRequirements;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\EmailNormalizer;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ConsentEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PersonalProfile;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;

class BootstrapIdentityInstall
{
    public const ORIGIN = 'amorcage_installation';

    public const SYSTEM = 'A1-install';

    public function __construct(
        private CoordinationGuard $coordination,
        private CurrentServiceConfiguration $configurations,
        private EnsureOrganizationTypes $types,
        private EnsureVerificationRequirements $requirements,
    ) {}

    /**
     * @param  array{email: string, password_hash: string, prenom: string, nom: string, pays: string, locale: string, ville: ?string}  $input
     */
    public function __invoke(array $input): BootstrapInstallResult
    {
        $email = EmailNormalizer::normalize($input['email']);
        $correlation = IdentityUlid::make();

        return $this->coordination->withinIdentity(function () use ($input, $email, $correlation): BootstrapInstallResult {
            $now = now();
            $createdTextTypes = [];
            $createdConfigCodes = [];
            $grantedPermissions = [];

            $accountCreated = false;
            $account = $this->resolveAccount($email, $input, $now, $correlation, $accountCreated);

            $createdTextTypes = $this->ensureLegalTexts($now);
            ($this->types)();
            ($this->requirements)();
            $this->ensureConsents($account, $now);
            $grantedPermissions = $this->ensureInternalPermissions($account, $now, $correlation);
            $createdConfigCodes = $this->ensureServiceConfigurations($account, $now);

            return new BootstrapInstallResult(
                accountId: $account->id,
                email: $account->email_normalise,
                accountCreated: $accountCreated,
                createdTextTypes: $createdTextTypes,
                createdConfigCodes: $createdConfigCodes,
                grantedPermissions: $grantedPermissions,
            );
        });
    }

    /**
     * @param  array{email: string, password_hash: string, prenom: string, nom: string, pays: string, locale: string, ville: ?string}  $input
     */
    private function resolveAccount(string $email, array $input, $now, string $correlation, bool &$created): Account
    {
        $existing = Account::query()->where('email_normalise', $email)->first();

        if ($existing !== null) {
            if (! $this->isInstallAccount($existing)) {
                throw new IdentityAccessException(
                    'compte_existant',
                    'Cet e-mail appartient deja a un compte qui n est pas le compte d installation.',
                    409,
                );
            }

            return $existing;
        }

        $account = new Account;
        $account->id = IdentityUlid::make();
        $account->email_normalise = $email;
        $account->hash_mdp = $input['password_hash'];
        $account->statut = AccountStatus::Active;
        $account->locale = $input['locale'];
        $account->email_verifie_a = $now;
        $account->cree_a = $now;
        $account->ferme_a = null;
        $account->save();

        $profile = new PersonalProfile;
        $profile->compte_id = $account->id;
        $profile->prenom = $input['prenom'];
        $profile->nom = $input['nom'];
        $profile->pays = strtoupper($input['pays']);
        $profile->ville = $input['ville'];
        $profile->save();

        foreach (['reader', 'admin'] as $roleCode) {
            $role = Role::query()->where('code', $roleCode)->first();

            if ($role === null) {
                throw new IdentityAccessException(
                    'service_indisponible',
                    'Le referentiel des roles est indisponible.',
                    503,
                );
            }

            $assignment = new AccountRole;
            $assignment->id = IdentityUlid::make();
            $assignment->compte_id = $account->id;
            $assignment->role_id = $role->id;
            $assignment->statut = 'active';
            $assignment->origine = self::ORIGIN;
            $assignment->attribue_a = $now;
            $assignment->save();
        }

        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $account->id;
        $event->type = 'compte_cree';
        $event->date = $now;
        $event->nouvel_etat = json_encode([
            'statut' => AccountStatus::Active->value,
            'origine' => self::ORIGIN,
        ], JSON_THROW_ON_ERROR);
        $event->motif = 'Amorcage local du compte d installation.';
        $event->origine_systeme = self::SYSTEM;
        $event->correlation = $correlation;
        $event->save();

        $created = true;

        return $account;
    }

    private function isInstallAccount(Account $account): bool
    {
        $admin = Role::query()->where('code', 'admin')->first();

        if ($admin === null) {
            return false;
        }

        return AccountRole::query()
            ->where('compte_id', $account->id)
            ->where('role_id', $admin->id)
            ->where('statut', 'active')
            ->where('origine', self::ORIGIN)
            ->exists();
    }

    /**
     * @return list<string>
     */
    private function ensureLegalTexts($now): array
    {
        $created = [];

        foreach ($this->legalTexts() as $definition) {
            $exists = LegalTextVersion::query()
                ->where('type', $definition['type'])
                ->where('version', $definition['version'])
                ->where('langue', $definition['langue'])
                ->exists();

            if ($exists) {
                continue;
            }

            $text = new LegalTextVersion;
            $text->id = IdentityUlid::make();
            $text->type = $definition['type'];
            $text->version = $definition['version'];
            $text->langue = $definition['langue'];
            $text->contenu = $definition['contenu'];
            $text->reference_immuable = null;
            $text->empreinte = hash('sha256', $definition['contenu']);
            $text->publie_a = $now;
            $text->requis_pour_activation = 1;
            $text->save();

            $created[] = $definition['type'];
        }

        return $created;
    }

    private function ensureConsents(Account $account, $now): void
    {
        $texts = LegalTextVersion::query()
            ->where('requis_pour_activation', 1)
            ->where('langue', $account->locale)
            ->get();

        foreach ($texts as $text) {
            $exists = Consent::query()
                ->where('compte_id', $account->id)
                ->where('texte_version_id', $text->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $consent = new Consent;
            $consent->id = IdentityUlid::make();
            $consent->compte_id = $account->id;
            $consent->texte_version_id = $text->id;
            $consent->nature = 'acceptation';
            $consent->accepte_a = $now;
            $consent->source = self::ORIGIN;
            $consent->canal = 'cli';
            $consent->save();

            $consentEvent = new ConsentEvent;
            $consentEvent->id = IdentityUlid::make();
            $consentEvent->consentement_id = $consent->id;
            $consentEvent->type = 'acceptation';
            $consentEvent->date = $now;
            $consentEvent->source = self::ORIGIN;
            $consentEvent->canal = 'cli';
            $consentEvent->save();
        }
    }

    /**
     * @return list<string>
     */
    private function ensureInternalPermissions(Account $account, $now, string $correlation): array
    {
        $granted = [];

        foreach (['configurer_service', 'gerer_habilitations_internes', 'affecter_taches', 'controler_kyc', 'decider_kyc', 'decider_kyc_commercial', 'restreindre_habilitation', 'transferer_principal', 'verifier_representation', 'suspendre_compte', 'traiter_support', 'autoriser_recours', 'reactiver_compte', 'cloturer_compte', 'reprendre_transmissions'] as $code) {
            $permission = InternalPermission::query()->where('code', $code)->first();

            if ($permission === null) {
                throw new IdentityAccessException(
                    'service_indisponible',
                    'Le referentiel des permissions internes est indisponible.',
                    503,
                );
            }

            $current = InternalAttribution::query()
                ->where('compte_id', $account->id)
                ->where('permission_id', $permission->id)
                ->whereNull('revoque_a')
                ->where(function ($query) use ($now): void {
                    $query->whereNull('expire_a')->orWhere('expire_a', '>', $now);
                })
                ->first();

            if ($current !== null) {
                continue;
            }

            $attribution = new InternalAttribution;
            $attribution->id = IdentityUlid::make();
            $attribution->compte_id = $account->id;
            $attribution->permission_id = $permission->id;
            $attribution->attribue_par = $account->id;
            $attribution->attribue_a = $now;
            $attribution->motif = 'Amorcage local.';
            $attribution->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $account->id;
            $event->attribution_interne_id = $attribution->id;
            $event->type = 'permission_interne_modifiee';
            $event->date = $now;
            $event->nouvel_etat = json_encode(['permission' => $code], JSON_THROW_ON_ERROR);
            $event->motif = 'Attribution d amorcage.';
            $event->origine_systeme = self::SYSTEM;
            $event->correlation = $correlation;
            $event->save();

            $granted[] = $code;
        }

        return $granted;
    }

    /**
     * @return list<string>
     */
    private function ensureServiceConfigurations(Account $account, $now): array
    {
        $created = [];

        foreach ($this->serviceConfigurations() as $code => $values) {
            if ($this->configurations->find($code) !== null) {
                continue;
            }

            $nextVersion = (int) ServiceConfiguration::query()->where('code', $code)->max('version');
            $encoded = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $config = new ServiceConfiguration;
            $config->id = IdentityUlid::make();
            $config->code = $code;
            $config->version = $nextVersion + 1;
            $config->schema_version = 1;
            $config->valeurs = $values;
            $config->empreinte = hash('sha256', $encoded);
            $config->cree_par = $account->id;
            $config->cree_a = $now;
            $config->approuve_par = $account->id;
            $config->approuve_a = $now;
            $config->effet_a = $now;
            $config->save();

            $created[] = $code;
        }

        return $created;
    }

    /**
     * @return list<array{type: string, version: string, langue: string, contenu: string}>
     */
    private function legalTexts(): array
    {
        return [
            [
                'type' => 'cgu',
                'version' => '1.0',
                'langue' => 'fr',
                'contenu' => 'Livreaura — Conditions generales d utilisation, version 1.0. Texte d amorcage local a remplacer par la version juridique approuvee.',
            ],
            [
                'type' => 'confidentialite',
                'version' => '1.0',
                'langue' => 'fr',
                'contenu' => 'Livreaura — Politique de confidentialite, version 1.0. Texte d amorcage local a remplacer par la version juridique approuvee.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function serviceConfigurations(): array
    {
        return [
            'A1_acces' => [
                'inactivite_secondes_normal' => 3600,
                'absolue_secondes_normal' => 43200,
                'inactivite_secondes_limited' => 900,
                'absolue_secondes_limited' => 3600,
                'inactivite_secondes_support_only' => 900,
                'absolue_secondes_support_only' => 3600,
            ],
            'A1_delais' => [
                'activation_heures' => 24,
                'reset_minutes' => 15,
                'otp_minutes' => 10,
                'telephone_active' => false,
                'essais_defi' => 5,
                'renvoi_minimum_secondes' => 60,
                'renvoi_maximum_par_heure' => 5,
            ],
            'A1_calendrier' => [
                'timezone' => 'Africa/Kinshasa',
                'plages' => [
                    ['jours' => [1, 2, 3, 4, 5], 'debut' => '09:00', 'fin' => '17:00'],
                ],
                'jours_non_travailles' => [],
            ],
            'A1_regles_suspension' => [
                'regles_autorisees' => ['securite', 'conformite', 'decision_humaine'],
            ],
        ];
    }
}
