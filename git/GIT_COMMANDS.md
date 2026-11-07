# Livreaura — commandes Git

Guide complémentaire au [README du projet](README.md). À placer à la racine de `livreaura`, à côté de `README.md`.

**Terminal : Windows CMD**, dans Cursor ou l’Invite de commandes. Aucun Docker ni WSL requis. Les chemins sont des exemples à adapter. Ce document est une procédure à suivre par étapes, pas un script à exécuter intégralement.

## 1. Se placer à la racine

```bat
cd /d C:\projets\livreaura
git --version
```

Le dépôt contient `backend/`, puis `frontend/` lorsque Nuxt sera créé. Ne pas initialiser un dépôt supplémentaire dans ces sous-dossiers.

Si tu as déjà commencé le README :

```bat
git rev-parse --show-toplevel
git status
git remote -v
```

Si Git affiche une racine existante, la réutiliser et passer les opérations déjà réalisées. Si Git répond « not a git repository », poursuivre avec l’initialisation.

## 2. Initialiser le dépôt une seule fois

```bat
git init -b main
git config user.name "Japhet Ntumba"
git config user.email "REMPLACER_PAR_TON_EMAIL"
git config core.autocrlf false
```

Remplacer l’email avant exécution, éventuellement par l’adresse privée fournie dans les paramètres GitHub. Ces réglages sont locaux à ce dépôt.

```bat
git config --get user.name
git config --get user.email
git branch --show-current
```

La branche attendue est `main`.

## 3. Préparer les exclusions et le premier commit

Créer `.gitignore` et `.gitattributes` avec les contenus du README **avant d’ajouter le backend**.

À versionner : code source, migrations, schémas sans données privées, tests, documentation, `composer.lock`, lockfile Nuxt et `.env.example` sans secrets.

À exclure : `.env`, `.env.testing`, clés privées, dépendances `vendor/` et `node_modules/`, sorties Nuxt, logs, livres privés et dumps de données.

```bat
git check-ignore -v backend/.env
git check-ignore -v backend/.env.testing
git status --short --untracked-files=all
git add README.md GIT_COMMANDS.md .gitignore .gitattributes
git diff --cached --stat
git diff --cached
git commit -m "chore: initialiser Livreaura et sa documentation"
```

`check-ignore` doit indiquer la règle qui exclut les fichiers sensibles. L’absence de sortie mérite une vérification ; un fichier déjà suivi n’est pas exclu automatiquement. Ne pas employer `git add -f` pour forcer l’ajout d’un secret.

Si le premier commit a déjà été créé avec le README, ajouter seulement ce nouveau guide :

```bat
git add GIT_COMMANDS.md
git diff --cached
git commit -m "docs: ajouter le guide des commandes Git"
```

## 4. Créer la branche du socle A1

```bat
git switch -c feat/a1-foundation
```

Si elle existe déjà :

```bat
git switch feat/a1-foundation
```

Installer Laravel et Sanctum selon le README. Après installation et revue des fichiers :

```bat
git status --short --untracked-files=all
git check-ignore backend/.env
git add backend
git diff --cached --stat
git diff --cached
git commit -m "build: installer Laravel et Sanctum"
```

Le commit conserve les fichiers ajoutés à l’index, pas l’ensemble de ce qui est visible dans l’éditeur. Les fichiers ignorés restent exclus. Vérifier le diff localement avant chaque commit ; ne pas diffuser une sortie qui contiendrait un secret.

## 5. Commits alignés sur les étapes du README

Les exemples ci-dessous s’utilisent **après le travail correspondant**. Ne pas créer les commits avant l’implémentation. Ajouter uniquement les chemins réellement existants et modifiés ; adapter les listes si nécessaire.

### Configuration locale et authentification SPA

Après configuration de Laravel, de CORS et des routes :

```bat
git add backend/config backend/bootstrap backend/routes backend/.env.example
git diff --cached
git commit -m "chore: configurer MySQL sessions fichiers et Sanctum SPA"
```

Ne pas ajouter `backend/.env`. Mettre uniquement des exemples non sensibles dans `.env.example`.

### Schéma et file locale

Après choix de la baseline, revue des migrations et tests réels sur MySQL :

```bat
git add backend/database docs/models
git diff --cached --stat
git diff --cached
git commit -m "build: documenter le schema et preparer les tables de jobs"
```

Créer/remplir `docs/models/` avant cette commande. Un import SQL modifie la base, pas Git : versionner le schéma et sa procédure d’installation. Un dump contenant des données personnelles n’appartient pas au dépôt.

### Module A1

Après création et raccordement effectif des classes :

```bat
git add backend/app/Modules/IdentityAccess backend/app/Providers backend/bootstrap backend/config/auth.php
git diff --cached
git commit -m "feat(a1): raccorder les comptes et sessions au modele"
```

### Fonctionnalités et tests A1

Après programmation et tests d’un lot :

```bat
git status --short
git add backend/app/Modules/IdentityAccess backend/tests
git diff --cached
git commit -m "feat(a1): implementer et tester la connexion"
```

Ajouter aussi les fichiers partagés, routes ou configurations modifiés par ce lot après revue. Exemples de messages pour les lots suivants :

| Lot | Message proposé |
|---|---|
| Ouverture et vérification | `feat(a1): implementer la verification du compte` |
| Révocation | `feat(a1): gerer la revocation des sessions` |
| Récupération | `feat(a1): securiser la recuperation du compte` |
| Permissions | `feat(a1): controler les permissions et organisations` |
| Messages durables | `feat(a1): fiabiliser les notifications par outbox` |
| Correction | `fix(a1): refuser les sessions expirees` |
| Tests seuls | `test(a1): couvrir les restrictions de session` |
| Documentation | `docs: documenter la recette A1` |

## 6. Comprendre les commandes du quotidien

| Commande | Effet |
|---|---|
| `git status --short` | Montre les fichiers modifiés, ajoutés ou supprimés |
| `git diff` | Montre les modifications suivies non préparées |
| `git diff --cached` | Montre les modifications préparées pour le commit |
| `git add CHEMIN` | Prépare l’état courant d’un fichier ou dossier |
| `git add -p CHEMIN` | Sélectionne interactivement des portions d’un diff |
| `git commit -m "message"` | Enregistre les changements préparés dans l’historique local |
| `git push` | Envoie les commits de la branche suivie vers le dépôt distant |

`git diff` ne montre pas le contenu des fichiers non suivis : consulter `git status` et ouvrir ces fichiers avant ajout.

Routine depuis la racine :

```bat
git status --short
git diff
git add backend/app/Modules/IdentityAccess backend/tests
git diff --cached
git commit -m "feat(a1): decrire le lot termine"
```

Les commandes de tests restent celles du README. Leur exécution précède le commit présenté comme validé.

## 7. Connecter GitHub, lorsque souhaité

Git fonctionne localement sans GitHub. Le dépôt distant ajoute une copie du code hors du PC et permet les pull requests.

Créer sur GitHub un dépôt **privé et vide**, nommé `livreaura`, sans README ni `.gitignore` automatiques. Utiliser le gestionnaire d’identifiants Git ou SSH ; ne pas placer de token dans l’URL.

```bat
git remote -v
```

Seulement si `origin` n’existe pas, remplacer `TON_COMPTE` puis exécuter :

```bat
git remote add origin https://github.com/TON_COMPTE/livreaura.git
git remote -v
git push -u origin main
git push -u origin feat/a1-foundation
```

Si un `origin` existe et pointe au bon endroit, le conserver. S’il est incorrect, vérifier la destination puis le modifier :

```bat
git remote set-url origin https://github.com/TON_COMPTE/livreaura.git
```

Si le serveur refuse le push parce qu’il possède déjà un historique :

```bat
git fetch origin
git log --oneline --graph --decorate --all -30
```

Examiner les différences avant intégration. Ne pas forcer le push pour contourner le refus.

Après configuration de la branche distante, les sauvegardes suivantes utilisent :

```bat
git push
```

## 8. Terminer une branche puis commencer le lot suivant

### Parcours recommandé avec GitHub

1. Terminer les tests et committer les changements.
2. Pousser la branche.
3. Ouvrir sur GitHub une pull request vers `main`.
4. Relire le diff et les résultats des tests, puis fusionner.
5. Mettre à jour `main` localement, avec un espace de travail propre.

```bat
git status --short
git switch main
git pull --ff-only
git switch -c feat/a1-session-revocation
```

Noms de branches possibles, à créer un par un après intégration du lot précédent :

```text
feat/a1-foundation
feat/a1-account-verification
feat/a1-authentication
feat/a1-session-revocation
feat/a1-password-recovery
feat/a1-permissions
feat/a1-outbox
```

### Alternative sans dépôt distant

Après tests et commits sur la branche du lot, intégrer localement :

```bat
git status --short
git switch main
git merge --no-ff feat/a1-foundation -m "merge: integrer le socle A1"
git switch -c feat/a1-account-verification
```

Choisir un de ces deux parcours pour un même lot. Ne pas refaire la fusion locale après une fusion déjà réalisée sur GitHub.

### Si une fusion produit des conflits

```bat
git status
```

Corriger les fichiers indiqués dans Cursor, supprimer les marqueurs de conflit et relancer les tests. Ajouter chaque fichier résolu par son vrai chemin :

```bat
git add CHEMIN_DU_FICHIER_RESOLU
git diff --cached
git merge --continue
```

Pour abandonner cette fusion en cours plutôt que la résoudre :

```bat
git merge --abort
```

Ces commandes concernent une fusion Git en cours, pas un rebase. Commencer les fusions avec un espace de travail propre facilite leur annulation.

## 9. Consulter l’historique

```bat
git log --oneline --graph --decorate -20
git log --oneline --all -30
git show --stat HEAD
git diff main...HEAD
```

Pour un fichier précis, remplacer le chemin :

```bat
git log --oneline -- CHEMIN_DU_FICHIER
git diff -- CHEMIN_DU_FICHIER
```

Si Git ouvre une vue paginée dans le terminal, appuyer sur `q` pour quitter.

## 10. Corriger une erreur sans perdre son travail

### Retirer un fichier de la préparation du prochain commit

Après le premier commit, cette commande garde les modifications locales :

```bat
git restore --staged CHEMIN_DU_FICHIER
```

### Retirer un `.env` déjà suivi

Cette commande retire le fichier de Git mais conserve sa copie locale :

```bat
git rm --cached -- backend/.env
git add .gitignore
git diff --cached
git commit -m "fix: retirer le fichier environnement du suivi Git"
```

Elle ne supprime pas les anciennes versions de l’historique. Si un secret a été publié, le remplacer chez son fournisseur et traiter ensuite l’historique si nécessaire.

### Corriger le message du dernier commit non partagé

Vérifier que rien n’est préparé, car un amend inclurait aussi les changements préparés :

```bat
git status --short
git diff --cached
git commit --amend -m "MESSAGE_CORRIGE"
```

À utiliser uniquement sur un commit qui n’a pas été partagé et lorsque l’index ne contient rien d’inattendu. Pour un commit partagé, préférer un nouveau commit correctif.

### Annuler l’effet d’un commit partagé

Avec un espace de travail propre et après identification du commit :

```bat
git log --oneline -10
git revert IDENTIFIANT_DU_COMMIT
```

Git crée un nouveau commit d’annulation. Un commit de fusion exige une décision supplémentaire sur le parent à conserver : ne pas lui appliquer automatiquement cette commande. Si le revert entraîne des conflits, résoudre puis utiliser `git revert --continue`, ou annuler l’opération avec `git revert --abort`.

Le revert du code ne restaure pas les données MySQL et n’annule pas une opération chez un prestataire.

## 11. Faire une pause et reprendre

Pour mettre temporairement de côté les modifications, y compris les nouveaux fichiers non ignorés :

```bat
git stash push -u -m "pause du travail A1"
git stash list
```

Le stash reste local. Il n’inclut pas les fichiers ignorés, comme `.env`, et n’est pas une sauvegarde distante.

Pour récupérer un stash sans le supprimer immédiatement :

```bat
git stash apply "stash@{0}"
git status --short
```

Après vérification de la restauration et résolution des éventuels conflits, supprimer seulement le stash effectivement restauré :

```bat
git stash drop "stash@{0}"
```

Vérifier son identifiant avec `git stash list` si d’autres stashs ont été créés entre-temps.

## 12. Marquer la validation d’A1

Uniquement après intégration dans `main` et réussite de la recette complète A1 :

```bat
git switch main
git status --short
git tag -a a1-v1.0.0 -m "A1 V1 validee par la recette"
```

Si le dépôt distant est configuré et que `main` y est synchronisé :

```bat
git push origin a1-v1.0.0
```

Le tag marque un état précis du code ; il ne prouve pas à lui seul la validation. Conserver le résultat de recette dans la documentation. Ne pas déplacer silencieusement un tag déjà partagé.

## Repères

- `main` : version intégrée et vérifiée.
- `feat/...` : travail d’un lot fonctionnel.
- `fix/...` : correction ciblée.
- Un commit décrit le travail réellement effectué.
- Git conserve le code, pas les bases locales ni les livres privés.
- Ce guide ne lance aucune commande sur le PC ; appliquer chaque section au bon moment.

Documentation officielle : [Git](https://git-scm.com/docs).
