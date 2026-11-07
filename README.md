# Livreaura

Plateforme de découverte, d’achat et de lecture de livres, avec publication auteur et gestion de la conformité.

**Statut : initialisation du projet.** Les commandes ci-dessous préparent le socle technique. Les fonctionnalités A1–A6 ne sont pas encore implémentées ni validées dans ce dépôt par ce README.

## Environnement de développement retenu

Développement **sous Windows natif, sans Docker et sans WSL** pour le moment. Configuration matérielle de référence : 8 Go de RAM et processeur Intel i3-3240.

| Composant | Local | Cible de lancement |
|---|---|---|
| Backend | Laravel / PHP | Laravel / PHP-FPM sur VPS Linux |
| Frontend | Nuxt, lorsque nécessaire | Nuxt / Node.js sur VPS |
| Base de données | MySQL 8.4 | MySQL 8.4 |
| File de tâches | MySQL, driver Laravel `database` | Redis + Horizon |
| Cache | Fichiers | Redis |
| Session technique | Fichiers | Redis |
| Emails | Journal local | Resend |
| Livres | Fichiers privés de test | Cloudflare R2 Standard |

Redis et Horizon seront intégrés et testés dans un environnement Linux avant le lancement. Ils ne sont pas nécessaires pour initialiser Git ou commencer A1. Les règles métier et les outbox/inbox durables restent dans MySQL quel que soit le driver de file.

## 1. Prérequis

Installer les outils suivants si absents :

- [Git for Windows](https://git-scm.com/downloads/win).
- [PHP pour Windows](https://www.php.net/downloads.php?os=windows), version 8.4 ou plus récente compatible avec Laravel 13, avec les extensions requises.
- [Composer](https://getcomposer.org/download/), configuré avec ce PHP.
- [MySQL Community Server 8.4](https://dev.mysql.com/downloads/mysql/8.4.html).
- [Node.js](https://nodejs.org/en/download), version compatible avec Nuxt, au moment de créer le frontend.

Activer notamment les extensions PHP nécessaires à Composer et Laravel : `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl` et `zip`, lorsqu’elles ne sont pas intégrées. Vérifier les autres exigences avec Composer. Ajouter les exécutables au PATH, puis rouvrir le terminal.

**Utiliser l’Invite de commandes Windows (CMD) dans Cursor pour les blocs `bat`.** Ils ne sont pas destinés à Bash. La redirection SQL `<` ne doit pas être copiée telle quelle dans PowerShell.

```bat
git --version
php -v
php --ini
php -m
composer --version
mysql --version
```

MySQL doit être le serveur ciblé ; une installation XAMPP peut fournir MariaDB à la place. Workbench, phpMyAdmin et Apache ne sont pas nécessaires pour ce démarrage.

## 2. Initialiser Git

Placer ce README dans le dossier vide `livreaura`. Ouvrir ce dossier dans Cursor et se placer à sa racine. Adapter le chemin d’exemple :

```bat
cd /d C:\projets\livreaura
git init -b main
git config user.name "Japhet Ntumba"
git config user.email "REMPLACER_PAR_TON_EMAIL"
git config core.autocrlf false
```

Remplacer l’email avant exécution. Si un dépôt existe déjà, vérifier `git status` et `git remote -v` puis le réutiliser.

Créer `.gitignore` à la racine :

```gitignore
# Secrets
.env
.env.*
!.env.example
!.env.testing.example
**/.env
**/.env.*
!**/.env.example
!**/.env.testing.example
**/auth.json
*.pem
*.key

# Dépendances et fichiers générés
**/vendor/
**/node_modules/
**/.nuxt/
**/.output/
**/coverage/
**/.phpunit.cache/
**/.phpunit.result.cache
backend/public/build/
backend/bootstrap/cache/*.php
backend/storage/*.key
backend/storage/logs/**
backend/storage/framework/**
backend/storage/app/private/**
**/database.sqlite*
*.log

# Données privées et sauvegardes
backups/
local-data/
```

Créer `.gitattributes` :

```gitattributes
* text=auto
*.php text eol=lf
*.sh text eol=lf
*.md text eol=lf
*.yml text eol=lf
*.yaml text eol=lf
*.bat text eol=crlf
*.cmd text eol=crlf
```

Versionner `composer.lock`, le fichier de verrouillage Nuxt et le schéma SQL sans données privées. Les dumps contenant des données restent dans `backups/`, exclu du dépôt.

```bat
git add README.md .gitignore .gitattributes
git diff --cached
git commit -m "chore: initialiser Livreaura"
git switch -c feat/a1-foundation
```

## 3. Installer le backend

Depuis la racine, uniquement si `backend/` n’existe pas :

```bat
composer create-project laravel/laravel backend "^13.0" --no-scripts
cd backend
copy .env.example .env
php artisan key:generate
php artisan package:discover --ansi
composer check-platform-reqs
php artisan --version
composer require laravel/sanctum
php artisan config:publish cors
```

`--no-scripts` évite les actions automatiques du squelette, dont l’installation de sa base par défaut. Inspecter les scripts de `composer.json` avant de les exécuter. La clé `APP_KEY` est générée une fois pour ce nouvel environnement : ne pas régénérer une clé qui protège déjà des données.

Ne pas installer Horizon sous Windows natif et ne pas forcer ses exigences de plateforme. Fortify et Spatie Permission ne sont pas installés automatiquement : leur éventuelle utilisation doit respecter A1.

Sauvegarder ce premier socle après inspection :

```bat
cd /d C:\projets\livreaura
git check-ignore backend/.env
git status --short --untracked-files=all
git add backend
git diff --cached --stat
git diff --cached
git commit -m "build: installer Laravel et Sanctum"
```

Ne pas partager une sortie contenant des secrets. Une exclusion Git ne retire pas un fichier déjà suivi de l’historique.

## 4. Préparer MySQL

Ouvrir le client :

```bat
mysql -u root -p
```

Exécuter le SQL suivant après remplacement des secrets :

```sql
SELECT VERSION();
CREATE DATABASE livreaura CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin;
CREATE DATABASE livreaura_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin;
CREATE USER 'livreaura'@'localhost' IDENTIFIED BY 'REMPLACER_SECRET_DEV';
CREATE USER 'livreaura'@'127.0.0.1' IDENTIFIED BY 'REMPLACER_SECRET_DEV';
CREATE USER 'livreaura_test'@'localhost' IDENTIFIED BY 'REMPLACER_SECRET_TEST';
GRANT ALL PRIVILEGES ON livreaura.* TO 'livreaura'@'localhost';
GRANT ALL PRIVILEGES ON livreaura.* TO 'livreaura'@'127.0.0.1';
GRANT ALL PRIVILEGES ON livreaura_test.* TO 'livreaura_test'@'localhost';
EXIT;
```

Ces comptes distincts sont destinés au développement et aux tests locaux. Si les bases existent, les inspecter avant toute création ou modification. Confirmer MySQL 8.4 avec `SELECT VERSION()`.

### Configuration de `backend/.env`

Conserver la clé d’application existante et adapter le port si nécessaire :

```dotenv
APP_NAME=Livreaura
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=livreaura
DB_USERNAME=livreaura
DB_PASSWORD=REMPLACER_SECRET_DEV

QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_COOKIE=livreaura_session
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000

MAIL_MAILER=log
MAIL_FROM_ADDRESS=notifications@example.test
MAIL_FROM_NAME=Livreaura
```

`FRONTEND_URL` est une variable personnalisée à lire dans un fichier de configuration. Les emails de développement restent dans les logs, qui peuvent contenir des liens sensibles.

### Installation du schéma métier

Copier le MPD et les modèles consolidés dans `docs/models/`. Choisir une seule stratégie : migrations Laravel traduites depuis le MPD, ou import SQL initial avec baseline documentée et migrations ultérieures d’évolution.

Pour la seconde option, depuis la racine et uniquement sur la base de développement vide :

```bat
mysql --default-character-set=utf8mb4 --show-warnings -u livreaura -p livreaura < "docs\models\MPD_V1_MySQL84.sql"
```

Ne pas utiliser `--force`. En cas d’erreur, examiner l’état partiel avant une nouvelle tentative. Le MPD doit être testé réellement sur MySQL ; sa validation statique antérieure ne garantit pas un import réussi.

Ne pas exécuter aveuglément les migrations `users`, sessions ou cache du squelette Laravel : les comptes et sessions métier sont déjà définis dans A1. La conversion du MPD en migrations n’est pas fournie par ce README. Ne pas utiliser `migrate:fresh` sur des données à conserver.

### Tables techniques des Jobs

Inspecter d’abord `backend/database/migrations/` : une migration des Jobs existe généralement déjà. Vérifier ses noms de tables et l’absence de conflit avec le MPD. Si aucune migration équivalente n’existe, générer celles qui manquent :

```bat
cd /d C:\projets\livreaura\backend
php artisan make:queue-table
php artisan make:queue-failed-table
```

Ne pas générer de doublons. Après revue, appliquer seulement les migrations choisies en remplaçant le chemin par le vrai nom :

```bat
php artisan migrate --path=database/migrations/NOM_EXACT_MIGRATION.php --pretend
php artisan migrate --path=database/migrations/NOM_EXACT_MIGRATION.php
```

`--pretend` affiche le SQL, mais ne valide pas son comportement. Documenter les tables techniques ajoutées au MPD, notamment `jobs` et `failed_jobs`. `job_batches` n’est nécessaire que si les traitements par lots sont utilisés.

## 5. Organisation métier

Structure cible, à créer progressivement :

```text
livreaura/
  backend/
    app/
      Modules/
        IdentityAccess/          # A1
        Catalog/                 # A2
        Commerce/                # A3
        LicensingReading/        # A4
        AuthorPublishing/        # A5
        ComplianceWithdrawal/    # A6
      Shared/
        Messaging/
          Outbox/
          Inbox/
      Infrastructure/
      Providers/
    config/
    database/
    routes/
    tests/
  frontend/                      # À créer ultérieurement
  docs/
    models/
    architecture/
    decisions/
    operations/
  deploy/                        # Préparation du VPS, ultérieurement
  README.md
```

Chaque module peut contenir `Application`, `Domain`, `Infrastructure` et `Presentation`. Les contrôleurs et Jobs appellent les mêmes Actions métier. Chaque module possède les écritures sur ses données ; les échanges respectent les contrats entre chaînes.

Les dossiers `docs/` et `deploy/` sont à la racine du dépôt, les commandes Artisan se lancent depuis `backend/`.

## 6. Première étape de développement : A1

Créer les premiers squelettes depuis `backend/` :

```bat
php artisan make:provider ModulesServiceProvider
php artisan make:class "App\Modules\IdentityAccess\IdentityAccessServiceProvider"
php artisan make:model "App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account"
php artisan make:model "App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession"
php artisan make:class "App\Modules\IdentityAccess\Application\Actions\AuthenticateAccount"
php artisan make:class "App\Modules\IdentityAccess\Application\Actions\RevokeSession"
php artisan make:class "App\Modules\IdentityAccess\Application\Queries\ResolveEffectivePermissions"
```

Les commandes génèrent des classes à compléter, pas une authentification opérationnelle.

Travaux requis :

- Raccorder `Account` au contrat d’authentification Laravel et aux colonnes réelles du MPD.
- Déclarer explicitement les tables, clés ULID, casts et relations ; ne pas présumer des colonnes Laravel standard.
- Faire hériter `IdentityAccessServiceProvider` de `Illuminate\Support\ServiceProvider` et l’enregistrer dans `ModulesServiceProvider`, lui-même chargé par `bootstrap/providers.php`.
- Configurer `config/auth.php` pour utiliser les comptes A1 ; aucun second modèle d’identité indépendant.
- Relier la session technique Laravel à la session A1 en MySQL. Vérifier expiration, révocation et scopes à chaque accès protégé.
- Utiliser des Gates/Policies qui consultent les rôles, permissions et appartenances d’A1.
- Enregistrer explicitement les routes et middlewares du module.

### Sanctum et Nuxt

Dans le bloc `withMiddleware` de `bootstrap/app.php`, ajouter :

```php
$middleware->statefulApi();
```

Créer `routes/api.php` s’il est absent et ajouter son chargement dans le bloc `withRouting` existant :

```php
api: __DIR__.'/../routes/api.php',
```

Conserver les autres paramètres. Configurer CORS pour `http://localhost:3000`, avec `supports_credentials=true`, et inclure les chemins réels de l’API, de `/sanctum/csrf-cookie` et des routes de session.

Les endpoints métier utilisent `auth:sanctum` puis les contrôles A1. Les routes de connexion doivent disposer des sessions et du CSRF. Nuxt doit demander le cookie CSRF, envoyer les cookies et transmettre le jeton décodé dans `X-XSRF-TOKEN` pour les mutations. Utiliser `localhost` dans les URL du navigateur. Pour le SSR, transmettre les cookies de la requête entrante sans état utilisateur global.

### Lots fonctionnels

| Ordre | Lot | Résultat attendu |
|---|---|---|
| 1 | Données et référentiels | Modèles et relations conformes au MLD |
| 2 | Ouverture et vérification | Défis, expiration et limites de tentatives |
| 3 | Connexion | Contrôle du compte et création des sessions liées |
| 4 | Révocation et récupération | Refus des sessions invalides, jetons à usage unique |
| 5 | Autorisations | Isolation des organisations et respect des scopes |
| 6 | Messages et audit | Outbox/inbox, reprises et traçabilité |
| 7 | Recette A1 | Couverture complète du MOT, y compris les refus |

Conserver les intentions critiques dans les outbox/inbox MySQL. Une nouvelle tentative ne doit pas dupliquer un effet métier ; `afterCommit` seul ne remplace pas l’outbox durable.

## 7. Tests

Créer puis programmer les scénarios :

```bat
php artisan make:test Modules/IdentityAccess/AuthenticationTest
php artisan make:test Modules/IdentityAccess/SessionRevocationTest
php artisan make:test Modules/IdentityAccess/OrganizationPermissionsTest
php artisan make:test Modules/IdentityAccess/AccessScopeTest
php artisan make:test Modules/IdentityAccess/PasswordRecoveryTest
php artisan make:test Integration/Messaging/IdentityOutboxTest
```

Préparer `.env.testing` avec `APP_ENV=testing`, la base et le compte `livreaura_test`, une clé de test, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array` et `QUEUE_CONNECTION=sync`. Vérifier que `phpunit.xml` ne force pas SQLite ou la base de développement.

Installer le schéma de test selon la stratégie retenue. Une baseline SQL nécessite un bootstrap de tests adapté : les migrations Laravel ne recréent pas automatiquement les tables importées. Tester la file `database` séparément avec la base de test ; le mode `sync` ne teste pas les reprises asynchrones.

```bat
php artisan config:clear
php artisan test --filter=IdentityAccess
php artisan test --filter=IdentityOutboxTest
```

Si Pint figure dans les dépendances de développement :

```bat
vendor\bin\pint.bat --test
```

Les tests doivent contenir des assertions métier ; la réussite des exemples du squelette ne valide pas A1. Utiliser MySQL 8.4 pour les contraintes du MPD.

## 8. Lancer le développement local

Terminal CMD 1 — API :

```bat
cd /d C:\projets\livreaura\backend
php artisan serve --host=127.0.0.1 --port=8000
```

Ouvrir `http://localhost:8000`. L’URL `/up` permet de vérifier le démarrage HTTP du squelette, sans valider la base ou A1.

Terminal CMD 2 — un seul worker, une fois les tables techniques prêtes :

```bat
cd /d C:\projets\livreaura\backend
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=3 --memory=128
```

Arrêter puis relancer le worker après modification du code. Le seuil mémoire sert au recyclage du worker ; il ne plafonne pas strictement la mémoire de chaque Job. Sous Windows, ne pas compter sur les interruptions PCNTL : définir les délais des appels externes et tester les timeouts complets sur Linux.

Pour traiter seulement les Jobs en attente puis sortir :

```bat
php artisan queue:work database --queue=notifications,default --stop-when-empty --tries=3
```

Pour contrôler le planificateur ponctuellement :

```bat
php artisan schedule:list
php artisan schedule:run
```

`schedule:run` exécute les tâches dues lors de l’appel ; ce n’est pas une planification continue. Le Planificateur de tâches Windows pourra le déclencher périodiquement avec les chemins absolus appropriés. Le VPS utilisera cron.

Nuxt sera créé et lancé quand le premier parcours API sera prêt. Choisir une version compatible avec Node installé, conserver son lockfile et éviter un dépôt Git imbriqué. Ne pas démarrer tous les services et tests en parallèle sur ce PC.

## 9. Historique Git et dépôt distant

Après un lot terminé, depuis la racine :

```bat
cd /d C:\projets\livreaura
git status --short
git diff
git add backend/app backend/tests backend/config backend/routes backend/bootstrap
git diff --cached
git commit -m "feat(a1): implementer et tester la connexion"
```

Adapter le message et ajouter explicitement les migrations ou documents modifiés. Ne jamais committer `.env`, clés privées, logs ou données personnelles.

Créer un dépôt GitHub privé vide nommé `livreaura`, puis remplacer le nom de compte :

```bat
git remote add origin https://github.com/TON_COMPTE/livreaura.git
git remote -v
git push -u origin main
git push -u origin feat/a1-foundation
```

Configurer l’authentification Git via un gestionnaire d’identifiants ou SSH, sans token dans l’URL. Réutiliser `origin` s’il existe ; ne pas forcer un push sur un historique distant différent.

Ouvrir une pull request, relire le diff et les tests, puis fusionner. Pour le lot suivant :

```bat
git switch main
git pull --ff-only
git switch -c feat/a1-session-revocation
```

Commandes de suivi :

```bat
git log --oneline --graph --decorate -15
git show --stat HEAD
```

Pour annuler un commit partagé, employer `git revert IDENTIFIANT_DU_COMMIT`. Cela n’annule pas une migration déjà exécutée ni un effet externe. Git conserve le code ; MySQL et les fichiers privés nécessitent leurs propres sauvegardes.

## 10. Avant le lancement

- Tester MySQL 8.4, Redis et Horizon dans un environnement Linux isolé.
- Planifier le changement d’environnement Composer : Horizon ajoute des exigences Linux ; ne pas maintenir deux lockfiles divergents ni ignorer les exigences pour forcer Windows.
- Configurer les files, la supervision, les délais, les permissions Horizon et les alertes.
- Raccorder R2 et Resend avec des secrets hors du dépôt.
- Appliquer HTTPS, `APP_DEBUG=false`, cookies sécurisés et accès privés à MySQL/Redis.
- Valider les reprises, révocations, parcours entre chaînes et restauration des sauvegardes.
- Choisir et contractualiser le prestataire de paiement avant l’ouverture des ventes.

Les modèles antérieurs peuvent encore porter le nom Afrique-Educ. Livreaura est le nom de ce projet ; ce changement ne modifie pas implicitement les règles métier ou le schéma.

## Documentation officielle

- [Installation Laravel](https://laravel.com/docs/13.x/installation)
- [Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Queues](https://laravel.com/docs/13.x/queues)
- [Horizon](https://laravel.com/docs/13.x/horizon)
- [Tests Laravel](https://laravel.com/docs/13.x/testing)
- [Git](https://git-scm.com/docs)

Ce README est un guide de mise en place. Les commandes n’ont pas été exécutées dans le projet utilisateur ; les migrations adaptées, Actions et tests métier restent à programmer.
