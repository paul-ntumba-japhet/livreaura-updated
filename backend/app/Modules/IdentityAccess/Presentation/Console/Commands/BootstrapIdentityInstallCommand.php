<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Accounts\BootstrapIdentityInstall;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BootstrapIdentityInstallCommand extends Command
{
    protected $signature = 'a1:bootstrap-install
                            {--email= : E-mail du compte d installation}
                            {--prenom= : Prenom}
                            {--nom= : Nom}
                            {--pays=CD : Code pays ISO}
                            {--locale=fr : Locale du compte}
                            {--confirm= : En production, passer INSTALLER}';

    protected $description = 'Amorce localement le compte d installation, les textes requis et les configurations A1.';

    public function handle(BootstrapIdentityInstall $install): int
    {
        if ($this->laravel->environment('production') && $this->option('confirm') !== 'INSTALLER') {
            $this->error('En production, relancer avec --confirm=INSTALLER.');

            return self::FAILURE;
        }

        $email = $this->stringOption('email');
        $prenom = $this->stringOption('prenom');
        $nom = $this->stringOption('nom');
        $pays = strtoupper($this->stringOption('pays', 'CD'));
        $locale = $this->stringOption('locale', 'fr');

        if ($email === null || $prenom === null || $nom === null) {
            $this->error('Les options --email, --prenom et --nom sont requises.');

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caracteres.');

            return self::FAILURE;
        }

        $hash = Hash::make($password);

        try {
            $result = $install([
                'email' => $email,
                'password_hash' => $hash,
                'prenom' => $prenom,
                'nom' => $nom,
                'pays' => $pays,
                'locale' => $locale,
                'ville' => null,
            ]);
        } catch (IdentityAccessException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($result->accountCreated
            ? 'Compte d installation cree : '.$result->email
            : 'Compte d installation deja present : '.$result->email);
        $this->line('Compte id : '.$result->accountId);

        if ($result->createdTextTypes !== []) {
            $this->info('Textes publies : '.implode(', ', $result->createdTextTypes));
        }

        if ($result->createdConfigCodes !== []) {
            $this->info('Configurations approuvees : '.implode(', ', $result->createdConfigCodes));
        }

        if ($result->grantedPermissions !== []) {
            $this->info('Permissions internes : '.implode(', ', $result->grantedPermissions));
        }

        $this->comment('Le mot de passe n a pas ete journalise.');

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        $fromEnvironment = getenv('A1_BOOTSTRAP_PASSWORD');

        if (is_string($fromEnvironment) && $fromEnvironment !== '') {
            return $fromEnvironment;
        }

        $fromLaravel = env('A1_BOOTSTRAP_PASSWORD');

        if (is_string($fromLaravel) && $fromLaravel !== '') {
            return $fromLaravel;
        }

        if ($this->option('no-interaction')) {
            $this->error('Definir A1_BOOTSTRAP_PASSWORD, sans passer le mot de passe en argument.');

            return null;
        }

        $password = $this->secret('Mot de passe du compte d installation');
        $confirmation = $this->secret('Confirmer le mot de passe');

        if (! is_string($password) || $password === '' || $password !== $confirmation) {
            $this->error('Les mots de passe ne correspondent pas.');

            return null;
        }

        return $password;
    }

    private function stringOption(string $name, ?string $default = null): ?string
    {
        $value = $this->option($name);

        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        return trim($value);
    }
}
