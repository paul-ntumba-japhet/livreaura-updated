# Livreaura — démarrage Windows sans Docker et développement A1

Version 1.1 • 8 septembre 2026

Ce guide remplace la proposition WSL/Docker précédente. Point de départ : un dossier vide nommé `livreaura`, un PC Windows avec 8 Go de RAM et un processeur i3-3240. Il décrit les commandes à exécuter sur le PC ; aucun dépôt local utilisateur n’a été créé par la génération de ce document.

## 1. Configuration retenue

| Élément | Développement local | Préproduction et production |
|---|---|---|
| Backend | Laravel, PHP natif Windows | Laravel, PHP-FPM sur Linux |
| Interface | Nuxt, à lancer lorsque nécessaire | Nuxt / Node.js sur VPS |
| Base | MySQL 8.4 natif Windows | MySQL 8.4 sur VPS |
| Tâches | Driver `database`, un worker | Redis + Horizon |
| Cache | Fichiers | Redis |
| Session technique | Fichiers | Redis, raccordé à A1 |
| Emails | Journal local | Resend |
| Livres | Fichiers de test privés | Cloudflare R2 Standard |

La carte graphique n’est pas un facteur déterminant pour ce backend. La RAM, le processeur et le disque influencent davantage le confort de travail. Commencer par Laravel et MySQL, puis lancer Nuxt seulement pour les parcours d’interface. Ne pas installer Docker, WSL ou un serveur Redis local pour ce premier lot.

Les Jobs restent écrits avec les mécanismes Laravel. Le changement de driver ne garantit pas une équivalence complète : reprises, concurrence, verrous et supervision seront testés sur Redis/Linux avant lancement. Les outbox/inbox métier restent dans MySQL dans les deux environnements.

## 2. Installer les outils natifs

Installer séparément, à partir des sites officiels :

1. Git for Windows : https://git-scm.com/downloads/win
2. PHP 8.4 x64 ou une version plus récente compatible avec Laravel 13 : https://www.php.net/downloads.php?os=windows
3. Composer pour Windows, en sélectionnant ce `php.exe` : https://getcomposer.org/download/
4. MySQL Community Server 8.4 : https://dev.mysql.com/downloads/mysql/8.4.html
5. Node.js LTS compatible avec la version Nuxt choisie : https://nodejs.org/en/download

Pour PHP, suivre les prérequis Windows officiels, notamment le runtime Visual C++ requis. Copier `php.ini-development` en `php.ini`, définir correctement `extension_dir` et activer les extensions nécessaires telles que `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql` et `zip`, lorsqu’elles ne sont pas déjà intégrées. Ne pas charger deux fois une extension. Composer signalera les exigences manquantes.

Ajouter au PATH Windows les dossiers de Git, PHP, Composer, Node.js et le dossier `bin` de MySQL si leurs installateurs ne l’ont pas fait. Fermer puis rouvrir le terminal.

Ne pas installer un second serveur MySQL si MySQL 8.4 fonctionne déjà. XAMPP peut fournir MariaDB : ce n’est pas le moteur ciblé par notre MPD. Apache, phpMyAdmin et MySQL Workbench ne sont pas nécessaires pour commencer ; le client `mysql` suffit.

**Tous les blocs `bat` ci-dessous se lancent dans l’Invite de commandes Windows (CMD), y compris dans Cursor.** La redirection `<` n’est pas à copier telle quelle dans PowerShell.

```bat
where git
where php
where composer
where mysql
where node
git --version
php -v
php --ini
php -m
composer --version
mysql --version
node --version
npm --version
```

`mysql --version` indique la version du client ; la version du serveur sera contrôlée plus loin. Ne pas utiliser `--ignore-platform-reqs` pour forcer une installation incompatible.

## 3. Créer Livreaura et initialiser Git

Chemin d’exemple : `C:\projets\livreaura`. Si le dossier vide existe ailleurs, adapter le chemin, sans recréer un second projet.

```bat
mkdir C:\projets\livreaura
cd /d C:\projets\livreaura
git init -b main
git config user.name "Japhet Ntumba"
git config user.email "REMPLACER_PAR_TON_EMAIL"
git config core.autocrlf false
```

Remplacer l’adresse email avant exécution. L’identité est configurée pour ce dépôt seulement.

Créer `.gitignore` dans Cursor avec ce contenu :

```gitignore
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
**/vendor/
**/node_modules/
**/.nuxt/
**/.output/
**/coverage/
**/.phpunit.cache/
**/.phpunit.result.cache
backend/public/build/
backend/storage/*.key
backend/storage/app/private/**
backend/storage/logs/**
backend/storage/framework/**
backend/bootstrap/cache/*.php
**/database.sqlite*
backups/
local-data/
*.log
```

Créer `.gitattributes` :

```gitattributes
* text=auto
*.php text eol=lf
*.sh text eol=lf
*.yml text eol=lf
*.yaml text eol=lf
*.md text eol=lf
*.bat text eol=crlf
*.cmd text eol=crlf
```

Créer `README.md` et y inscrire le nom Livreaura, l’objectif du projet et le fait que le backend est modulaire A1–A6. Versionner les schémas SQL sans données, mais pas les dumps privés. Les fichiers `composer.lock` et de verrouillage Nuxt doivent être conservés dans Git.

```bat
git add .gitignore .gitattributes README.md
git diff --cached
git commit -m "chore: initialiser le projet Livreaura"
git switch -c feat/a1-foundation
```

## 4. Installer un nouveau Laravel

Depuis la racine Livreaura :

```bat
composer create-project laravel/laravel backend "^13.0" --no-scripts
cd backend
copy .env.example .env
php artisan key:generate
php artisan package:discover --ansi
composer check-platform-reqs
php artisan --version
```

`--no-scripts` évite notamment l’exécution automatique des migrations du squelette. Nous préparons une base alignée sur notre MPD. Inspecter `composer.json` avant de lancer d’autres scripts. `APP_KEY` est générée une fois pour ce nouvel environnement : ne pas la régénérer sur une base utilisant déjà des données chiffrées.

Installer Sanctum sans les migrations automatiques de jetons personnels :

```bat
composer require laravel/sanctum
php artisan config:publish cors
```

Ne pas installer Horizon sur ce Windows natif : ses exigences Linux de gestion de processus ne sont pas satisfaites. Redis, Predis, Fortify et Spatie Permission ne sont pas requis pour ce premier lot.

```bat
cd /d C:\projets\livreaura
git check-ignore backend/.env
git status --short --untracked-files=all
git add backend
git diff --cached --stat
git diff --cached
git commit -m "build: installer Laravel et Sanctum sans Docker"
```

Contrôler les fichiers préparés avant le commit ; ne pas partager de sortie contenant des secrets.

## 5. Créer les bases MySQL locales

```bat
mysql -u root -p
```

Dans le client MySQL, exécuter ce SQL, en remplaçant les deux mots de passe par des valeurs locales distinctes :

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

Ces droits conviennent à des comptes locaux de développement et de migration ; ils ne définissent pas les droits minimaux de production. Si les bases ou comptes existent déjà, les inspecter au lieu de supprimer et recréer leurs données.

La commande `SELECT VERSION()` doit confirmer MySQL 8.4. Si elle affiche MariaDB ou une autre version, résoudre cette différence avant d’importer le MPD.

## 6. Configurer Laravel pour un PC peu puissant

Modifier `backend/.env` sans écraser `APP_KEY` :

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

Adapter le port si le serveur utilise un autre port. La variable `FRONTEND_URL` est personnalisée : la lire dans un fichier de configuration, puis accéder à sa valeur via `config(...)` dans le code.

La session en fichier est une session technique Laravel. Elle doit être reliée à la session A1 en base : A1 conserve l’autorité sur les restrictions, expirations et révocations. La même règle restera valable avec Redis sur le VPS.

Dans le bloc `withMiddleware` existant de `bootstrap/app.php`, activer :

```php
$middleware->statefulApi();
```

Créer `routes/api.php` s’il est absent et ajouter son chargement dans le bloc `withRouting` existant :

```php
api: __DIR__.'/../routes/api.php',
```

Conserver les autres paramètres du fichier. Les routes d’authentification doivent bénéficier des sessions et de la protection CSRF. Les routes métier seront protégées par `auth:sanctum` puis les contrôles A1.

Dans `config/cors.php`, configurer notamment :

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

Ajouter les autres routes de session lorsqu’elles seront créées. Dans le navigateur, employer `localhost` pour Nuxt et Laravel, sans alterner avec `127.0.0.1`.

Nuxt doit d’abord demander `/sanctum/csrf-cookie`, envoyer les cookies et transmettre le cookie `XSRF-TOKEN` décodé dans l’en-tête `X-XSRF-TOKEN` pour les mutations. Le SSR exige de transmettre les cookies de la requête entrante ; ne pas conserver un état utilisateur global sur le serveur Nuxt.

```bat
cd /d C:\projets\livreaura\backend
php artisan config:clear
php artisan route:list
```

## 7. Installer le schéma : deux étapes distinctes

Le MPD vise les données métier ; le driver local `database` utilise aussi une table technique de Jobs. Aucun `migrate` global ne doit être lancé avant revue des migrations par défaut.

### 7.1 Tables métier

Copier le SQL de référence et les MLD/MOT dans `docs/models/` à la racine du dépôt, sans données privées. Choisir une stratégie unique :

- Traduire le MPD en migrations Laravel ordonnées puis exécuter celles-ci ; ou
- Importer le MPD dans la base vide, documenter la baseline et n’utiliser ensuite les migrations que pour les évolutions.

La conversion du MPD en migrations n’est pas fournie par ce guide. Pour importer le SQL initial, uniquement sur la base `livreaura` vide, depuis la racine du dépôt :

```bat
mysql --default-character-set=utf8mb4 --show-warnings -u livreaura -p livreaura < "docs\models\MPD_V1_MySQL84.sql"
```

Ne pas ajouter `--force`. À la première erreur, arrêter, relever le message et examiner l’état partiel. Ne pas rejouer le fichier sur les tables déjà créées. Le MPD doit encore réussir une recette réelle MySQL ; une validation statique ne garantit pas l’import.

### 7.2 File de tâches locale

Inspecter les fichiers dans `backend/database/migrations/`. Le squelette Laravel contient généralement déjà une migration des tables de Jobs. Elle doit être revue pour vérifier les noms de `jobs`, `job_batches` et `failed_jobs` ainsi que l’absence de conflit avec le MPD.

Si aucune migration équivalente n’existe, générer seulement celles qui manquent :

```bat
php artisan make:queue-table
php artisan make:queue-failed-table
```

Ne pas générer de doublons. Il n’est pas nécessaire d’ajouter `job_batches` tant qu’aucun traitement par lots n’en a besoin. Pour exécuter uniquement la migration revue, remplacer le chemin d’exemple par le nom exact du fichier :

```bat
php artisan migrate --path=database/migrations/NOM_EXACT_MIGRATION_JOBS.php --pretend
php artisan migrate --path=database/migrations/NOM_EXACT_MIGRATION_JOBS.php
```

Répéter pour la migration des échecs si elle est distincte. `--pretend` montre les requêtes, mais ne teste pas le comportement du schéma. Documenter ces tables comme compléments techniques ; ce sont des ajouts au décompte du MPD initial.

Ne pas exécuter les migrations génériques `users`/sessions/cache sans les avoir alignées sur A1. Ne pas employer `migrate:fresh` sur une base dont les données doivent être conservées.

## 8. Créer le module A1

Depuis `backend/`, créer les dossiers :

```bat
mkdir app\Modules\IdentityAccess\Application\Actions
mkdir app\Modules\IdentityAccess\Application\Queries
mkdir app\Modules\IdentityAccess\Application\Jobs
mkdir app\Modules\IdentityAccess\Domain\Contracts
mkdir app\Modules\IdentityAccess\Domain\Rules
mkdir app\Modules\IdentityAccess\Infrastructure\Authentication
mkdir app\Modules\IdentityAccess\Infrastructure\Persistence\Models
mkdir app\Modules\IdentityAccess\Presentation\Http\Controllers
mkdir app\Modules\IdentityAccess\Presentation\Http\Middleware
mkdir app\Modules\IdentityAccess\Presentation\Http\Policies
mkdir app\Shared\Messaging\Outbox
mkdir app\Shared\Messaging\Inbox
```

Générer les premiers squelettes :

```bat
php artisan make:provider ModulesServiceProvider
php artisan make:class "App\Modules\IdentityAccess\IdentityAccessServiceProvider"
php artisan make:model "App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account"
php artisan make:model "App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession"
php artisan make:class "App\Modules\IdentityAccess\Application\Actions\AuthenticateAccount"
php artisan make:class "App\Modules\IdentityAccess\Application\Actions\RevokeSession"
php artisan make:class "App\Modules\IdentityAccess\Application\Queries\ResolveEffectivePermissions"
```

Ces commandes ne programment pas A1. Compléter les classes et leurs interfaces :

- `Account` doit supporter le contrat d’authentification Laravel et utiliser les colonnes réelles du MPD ; ne pas présumer de champs `password`, `email`, `remember_token` ou des timestamps standards.
- Déclarer les noms de tables, clés ULID, relations et casts conformément au modèle. Ne pas générer des migrations métier à partir de conventions supposées.
- `IdentityAccessServiceProvider` doit hériter de `Illuminate\Support\ServiceProvider`.
- `ModulesServiceProvider` enregistre le provider A1 ; vérifier sa présence dans `bootstrap/providers.php`.
- `config/auth.php` utilise le modèle de compte A1 et le provider adapté. Aucun second utilisateur indépendant ne doit contourner A1.
- Les Policies interrogent A1 et appliquent les règles de la ressource. Les routes du module sont enregistrées explicitement avec les middlewares nécessaires.

Exemple dans `ModulesServiceProvider::register()` :

```php
$this->app->register(
    \App\Modules\IdentityAccess\IdentityAccessServiceProvider::class
);
```

Après implémentation de l’enregistrement :

```bat
composer dump-autoload
php artisan route:list
```

## 9. Développer et tester par lots

| Lot A1 | Résultat à programmer et vérifier |
|---|---|
| Données | Modèles, relations, référentiels et contraintes alignés |
| Ouverture de compte | Parcours et défis A1, unicité et limites de tentatives |
| Connexion | Mot de passe, état du compte, régénération de session, session A1 |
| Révocation | Expiration, déconnexion, révocation et refus des sessions invalides |
| Récupération | Usage unique des jetons et conséquences prévues sur les sessions |
| Autorisations | Rôles, permissions, organisations et scopes restreints |
| Messages durables | Outbox/inbox, emails et absence d’effets dupliqués |
| Recette | Couverture de tous les cas restants du MOT A1, y compris refus et audit |

Créer les tests puis remplacer les assertions exemples par de vrais scénarios :

```bat
php artisan make:test Modules/IdentityAccess/AuthenticationTest
php artisan make:test Modules/IdentityAccess/SessionRevocationTest
php artisan make:test Modules/IdentityAccess/OrganizationPermissionsTest
php artisan make:test Modules/IdentityAccess/AccessScopeTest
php artisan make:test Modules/IdentityAccess/PasswordRecoveryTest
php artisan make:test Integration/Messaging/IdentityOutboxTest
```

Créer `.env.testing` avec la connexion `livreaura_test` et son utilisateur distinct, une clé de test, `APP_ENV=testing`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array` et `QUEUE_CONNECTION=sync`. Ne pas copier le mot de passe de développement par inadvertance. Vérifier que `phpunit.xml` ne force pas SQLite ou la base de développement.

Préparer le schéma de test suivant la même stratégie que la base de développement. Une baseline SQL importée exige un bootstrap de tests adapté ; les traits de migrations Laravel ne recréent pas automatiquement le MPD importé. Les tests d’intégration de la file utilisent le driver `database` dans une base de test, puis Redis dans l’environnement Linux.

```bat
php artisan config:clear
php artisan test --filter=IdentityAccess
php artisan test --filter=IdentityOutboxTest
vendor\bin\pint.bat --test
```

Pint est utilisé s’il figure dans les dépendances de développement ; sinon l’installer avec `composer require --dev laravel/pint`. Les tests SQLite ne valident pas les contraintes MySQL 8.4 de notre MPD.

Après chaque lot terminé :

```bat
cd /d C:\projets\livreaura
git status --short
git add backend/app backend/tests backend/config backend/routes backend/bootstrap
git diff --cached
git commit -m "feat(a1): implementer et tester la connexion"
```

Adapter le message au travail réel et ajouter explicitement les migrations ou documents modifiés après revue.

## 10. Lancer seulement ce qui est nécessaire

### Premier terminal : API

```bat
cd /d C:\projets\livreaura\backend
php artisan serve --host=127.0.0.1 --port=8000
```

Ouvrir `http://localhost:8000`. Il s’agit d’un serveur de développement.

### Deuxième terminal : un worker, lorsque les tables techniques sont prêtes

```bat
cd /d C:\projets\livreaura\backend
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=3 --memory=128
```

Arrêter avec Ctrl+C et relancer après modification du code des Jobs. `--memory=128` est un seuil de recyclage du worker, pas une limite mémoire garantie pour chaque Job. Sous Windows, les interruptions par PCNTL ne sont pas disponibles : configurer des délais explicites sur les appels HTTP et réserver les tests de timeout et de concurrence complets à Linux.

Pour traiter uniquement les Jobs actuellement disponibles, puis arrêter le worker :

```bat
php artisan queue:work database --queue=notifications,default --stop-when-empty --tries=3
```

La priorité ci-dessus est adaptée au démarrage A1 ; elle sera révisée avec les files de paiement, licence et retrait. Ne pas multiplier les workers sur ce PC. `QUEUE_CONNECTION=sync` peut servir temporairement pour déboguer un Job, mais ne teste pas une exécution asynchrone.

### Scheduler : exécution ponctuelle

```bat
php artisan schedule:list
php artisan schedule:run
```

`schedule:run` exécute les tâches dues au moment de l’appel ; ce n’est pas une planification continue. Pour tester un fonctionnement périodique, configurer ultérieurement le Planificateur de tâches Windows avec le chemin absolu de `php.exe`, celui d’`artisan` et le bon répertoire de travail. Sur le VPS, utiliser cron.

### Nuxt : deuxième phase

Créer le frontend seulement lorsque le premier parcours API A1 est prêt :

```bat
cd /d C:\projets\livreaura
npm create nuxt@latest frontend
cd frontend
npm run dev -- --host localhost --port 3000
```

La commande Nuxt est interactive ; choisir npm et ne pas créer un dépôt Git imbriqué. Si les dépendances ne sont pas installées par l’assistant de création, lancer `npm install`. Versionner ensuite `package-lock.json` et utiliser `npm ci` pour restaurer ces versions. Le générateur `latest` évolue : contrôler la version Node requise au moment de cette étape et consigner les versions retenues.

Fermer les applications et onglets inutilisés. Reporter les tests parallèles et les traitements de fichiers volumineux à une machine plus adaptée. Un SSD, si le PC n’en possède pas, peut améliorer le confort sans changer l’architecture.

## 11. GitHub et suivi quotidien

Créer un dépôt privé vide nommé `livreaura` sur GitHub, puis remplacer le nom de compte :

```bat
cd /d C:\projets\livreaura
git remote add origin https://github.com/TON_COMPTE/livreaura.git
git remote -v
git push -u origin main
git push -u origin feat/a1-foundation
```

Utiliser le gestionnaire d’identifiants Git ou SSH ; aucun token dans l’URL. Vérifier que `origin` n’existe pas avant `remote add`. Si le dépôt distant n’est pas vide, récupérer et examiner son historique au lieu de forcer un push.

```bat
git status --short
git diff
git log --oneline --graph --decorate -15
```

Faire un commit cohérent par lot. Pousser régulièrement la branche puis ouvrir une pull request vers `main`. Après fusion :

```bat
git switch main
git pull --ff-only
git switch -c feat/a1-session-revocation
```

Pour annuler un commit partagé :

```bat
git revert IDENTIFIANT_DU_COMMIT
```

L’annulation du code ne restaure pas automatiquement une base ou un effet chez un prestataire. Git ne sauvegarde ni les données MySQL ni les livres privés.

## 12. Passer à Redis et Horizon sur Linux

Effectuer ce lot sur un environnement de développement ou préproduction Linux isolé, avec la même version MySQL que la cible. Ne pas tester directement sur les données de production.

**Point de compatibilité Composer :** ajouter Horizon au dépôt introduit des exigences Linux dans `composer.lock`. À partir de cette étape, un `composer install` complet peut ne plus fonctionner sur Windows natif. Prévoir de poursuivre le backend à distance sur Linux (éditeur connecté en SSH), ou d’adopter alors un environnement Linux compatible. Ne pas maintenir deux fichiers lock divergents ni contourner les exigences avec `--ignore-platform-reqs`.

Depuis la copie du projet sur Linux, dans une branche dédiée :

```bash
git switch -c feat/redis-horizon
cd backend
composer require laravel/horizon predis/predis
php artisan horizon:install
```

Configurer les connexions Redis, les files et l’environnement Horizon. Passer les variables de cet environnement à `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis` et `REDIS_CLIENT=predis`, sans changer les règles métier A1. Configurer les permissions du tableau de bord et la supervision avant la production.

```bash
php artisan config:clear
php artisan horizon
```

Tester la révocation des sessions, la concurrence, les délais, les redémarrages, les messages doublonnés et les reprises des outbox/inbox. Redis n’est pas la seule mémoire des intentions métier. Configurer aussi sauvegardes et restauration de MySQL.

## 13. Périmètre du livrable

Ce fichier fournit un parcours de démarrage adapté à Livreaura, sans Docker et sans création d’un ancien projet à reprendre. Les modèles historiques peuvent encore porter le nom Afrique-Educ : leur changement de titre ne doit pas modifier les règles ou tables métier sans décision explicite.

Les commandes sont proposées pour Windows CMD, avec une section Linux clairement séparée. Elles n’ont pas été exécutées sur ton ordinateur. Les Actions, interfaces, migrations adaptées et tests A1 restent à programmer à partir des modèles consolidés.

Sources :

- Laravel : https://laravel.com/docs/13.x/installation
- Sanctum : https://laravel.com/docs/13.x/sanctum
- Queues : https://laravel.com/docs/13.x/queues
- Horizon : https://laravel.com/docs/13.x/horizon
- Exigences du package Horizon : https://github.com/laravel/horizon/blob/5.x/composer.json
- PHP Windows : https://www.php.net/downloads.php?os=windows
- Composer Windows : https://getcomposer.org/download/
- MySQL 8.4 : https://dev.mysql.com/doc/refman/8.4/en/windows-installation.html
- Nuxt : https://nuxt.com/docs/4.x/getting-started/installation
