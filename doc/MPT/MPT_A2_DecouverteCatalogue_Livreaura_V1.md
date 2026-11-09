# Livreaura — MPT A2 : Découverte du catalogue

**Modèle physique des traitements — V1.0 — 7 novembre 2026**  
**Base fonctionnelle : MCT A2, MOT et MLD A2 V1.3, contrats inter-chaînes V1.3. Base physique : `livreaura.sql`.**

## 1. Objet et statut

Ce document traduit les tâches T01 à T15 du MOT A2 en traitements Laravel : points d’entrée, prédicats, transactions, stockage et critères de recette. Il constitue une spécification de réalisation. Il ne certifie ni un module déjà développé ni une exécution des scénarios R01 à R24.

Les règles métier viennent du MOT, du MLD et des contrats CT-01 à CT-08. Les noms de tables et de colonnes viennent de `livreaura.sql`. Les noms de classes, routes et commandes sont des choix proposés pour l’implémentation. Ils ne sont pas déjà présents dans `backend/app/Modules`. Ce document ne crée aucune table et ne modifie pas le schéma.

Le guide `Guide_MPD_V1_3.md`, cité par le MOT pour la recette transversale X01 à X24, n’est pas dans le dépôt. Cette recette n’est pas reprise ici.

Le lot `doc/chaineA2` (classeurs et médias) sert de jeu de recette. Il n’est pas une source de règles nouvelles et il n’est pas importé par les traitements décrits.

## 2. Implantation et environnement

L’environnement est celui du MPT A1 : Windows natif en développement, Laravel 13, Nuxt consommateur des mêmes API, MySQL du fichier `livreaura.sql`, Sanctum SPA par cookie, file `database` puis Redis en production. A2 ne crée pas un second registre de sessions, de permissions ni de messages.

| Élément | Choix A2 |
|---|---|
| Module | `backend/app/Modules/Catalogue/`, namespace `App\Modules\Catalogue` |
| Autorité de session | Module IdentityAccess déjà branché : `a1.session.authorized`, `a1.account.active`, `a1.session.scope` |
| Écritures catalogue | Barrière `catalogue` de `verrous_coordination`, après `identite` lorsque l’acte dépend du compte |
| Remise A1 → A2 | `messages_sortants`, `transmissions_messages`, `tentatives_transmission`, `receptions_messages` |
| Travaux internes | File Laravel `a2-catalogue`, payload limité à un identifiant. Pas de table métier de travaux, pas d’usage de `taches_service` |
| Index de recherche | Aucune table et aucun index `FULLTEXT` dans `livreaura.sql`. La requête relationnelle est l’autorité tant qu’une projection T13 n’est pas basculée |
| Stock affiché | `offres_stock` et A3 font autorité. `offre.stock` est une colonne présente ; A2 ne la décrémente pas |
| Plafonds | Version approuvée de `configurations_service`, code proposé `A2_consultation` |

Les quatre codes de format `epub`, `pdf`, `audio` et `paper` sont déjà insérés dans `format_technique`. Les permissions `gerer_vitrine`, `moderer_avis`, `reprendre_traitements_catalogue` et `reprendre_transmissions` sont déjà des lignes de `permissions_internes`. Aucune attribution n’est créée par ce document.

## 3. Organisation du module

| Emplacement relatif | Responsabilité |
|---|---|
| Application/Actions/Discovery | Recherche, navigation, fiches, médias |
| Application/Actions/Showcase | Sélections, badge, mise en avant |
| Application/Actions/Reviews | Dépôt, décision, retrait, agrégats |
| Application/Actions/Handoff | Demandes panier, gratuit et lecture |
| Application/Actions/Projection | Synchronisation, reprise, restriction |
| Application/Queries | Lectures filtrées par les prédicats du §4 |
| Domain/Rules | V-O, V-E, V-F, V-A, V-W et `bloque` |
| Domain/Contracts | Passerelles A3 et A4, aucun accès SQL direct à leurs tables d’écriture |
| Infrastructure/Persistence/Models | Modèles Eloquent des tables existantes, PK non incrémentales |
| Presentation/Http | Requêtes, ressources filtrées, routes |
| Presentation/Console | Reprise des projections, agrégats et restrictions |
| CatalogueServiceProvider.php | Routes et liaisons, chargées une seule fois |

Les modèles déclarent les colonnes exactes. `created_at` et `updated_at` ne sont pas ajoutés : ces tables ne les portent pas. Les clés primaires sont des `char(26)`. Les réponses ne sérialisent ni secret de session, ni pièce privée, ni fichier intégral.

Les autres modules n’écrivent pas `oeuvre`, `edition`, `offre`, `avis`, `selection_editoriale` ni `media_catalogue` pour contourner A2. A5 reste propriétaire de la publication. A6 reste propriétaire des causes. A3 reste propriétaire du panier, du prix encaissé et du stock. A4 reste propriétaire du droit de lecture.

## 4. Contrat commun d’exécution

### 4.1 Entrées et réponses

Routes proposées sous `/api/v1`. Les fiches omettent ce préfixe. Les mutations de la SPA exigent CSRF. Le compte acteur vient de la session A1. Un identifiant d’œuvre ou d’offre ne prouve pas un droit.

Codes de réponse, identiques au contrat A1 : 401 session absente ou invalide ; 403 action interdite pour une session présente ; 404 objet absent ou non visible, avec le même corps pour inconnu, brouillon, archivé et retiré ; 409 conflit d’état ou d’intention ; 422 critère ou saisie non recevable ; 429 quota ; 503 autorité distante ou configuration approuvée absente. 202 signifie demande transmise, pas effet confirmé par A3 ou A4.

Une œuvre `soon` visible sans offre V-F n’utilise pas 401 pour un dépôt refusé : la réponse est 403, code stable `avis_indisponible`. Une œuvre non visible reste 404, y compris pour ses avis et ses médias.

### 4.2 Prédicats physiques

L’instant `t` est `UTC_TIMESTAMP(6)` lu après prise des barrières lorsqu’il y a écriture, sinon au début de la lecture. Aucune donnée de `livreaura.sql` n’est filtrée seulement en mémoire après pagination.

| Prédicat | Expression sur le schéma actuel |
|---|---|
| V-O | `oeuvre.statut = 'published'` et `oeuvre.retire_a` NULL et non `bloque(œuvre, retrait_catalogue, t)` |
| V-E | V-O et `edition.statut = 'active'` et `edition.consultable = 1` et non `bloque(édition, retrait_catalogue, t)` |
| V-F | V-E et `offre.actif = 1` et `offre.retire_a` NULL et `format_technique.actif = 1` et `offre.modalite_commerciale = 'achat'` et non `bloque(offre, retrait_catalogue, t)` et non `bloque(offre, arret_ventes, t)` |
| V-A | `auteur.statut_publication = 'published'` et `auteur.actif = 1` |
| V-W | V-O et (il existe une offre V-F, ou `oeuvre.availability = 'soon'` et le contexte est la fiche directe ou la sélection active de code `a_paraitre`) |

`location` et `editeur_label` restent des valeurs réservées. Une offre `location` n’est pas V-F en V1, même si la colonne `modalite_commerciale` peut la stocker. `offre.prix = 0` est la gratuité. `oeuvre.badge` ne la remplace pas. Il n’existe pas de valeur `availability = 'free'`.

`bloque(cible, effet, t)` est vrai lorsqu’une cause opposable vise cette cible ou un parent applicable. Parent œuvre : ses éditions, offres, médias et avis publics. Parent édition : ses offres. Un masque d’avis ne retire pas l’œuvre.

Cause opposable : ligne `restrictions_conformite` avec `debut <= t`, `fin` NULL ou `t < fin`, et aucune ligne `levees_restrictions` de cette restriction dont `effet_a <= t`. La cible et l’effet ne sont pas des colonnes de `restrictions_conformite`. Les lire ainsi :

- chemin A6 : `restrictions_conformite.ligne_creatrice_id` vers `lignes_ordres_retrait`, puis `resolution_id` vers `resolutions_cibles` (`oeuvre_id`, `edition_id`, `offre_id`, `media_catalogue_id`, `avis_id`) et `portee_id` vers `portees_decisions_mesures.effet` ;
- chemin A5 : `restrictions_conformite.instruction_publication_id` vers `instructions_retrait_publication` (`effet`, `oeuvre_id`, `edition_id`, `offre_id`, `version_id`).

Effets lus par A2 : `retrait_catalogue`, `arret_ventes`, `masquer_media_public`, `masquer_avis`. `interdiction_diffusion` et `masquer_titre_prive` restent à A4. `actif`, `consultable`, `statut` et `retire_a` ne sont pas écrasés pour simuler une cause. `developpements_portee` trace des enfants connus ; il n’autorise pas à ignorer le parent.

`confirmations_effet.ligne_id` référence seulement `lignes_ordres_retrait`. Une restriction qui n’a pas cette ligne est acquittée dans `receptions_messages`, sans ligne de confirmation inventée.

Le dump actuel ne contient pas de contrainte `CHECK` pour la note, le prix, le volume d’aperçu ni le rattachement exclusif d’un média. Ces invariants du MLD sont portés par les Actions. Le déclencheur `bu_avis_010a4e39` interdit déjà de faire sortir un avis de `rejected`. L’unicité d’un avis non rejeté est déjà portée par la colonne générée `uq_avis_non_rejete` et l’index `uq_avis_45b94da625` sur `(compte_id, oeuvre_id, uq_avis_non_rejete)`.

### 4.3 Transaction, ordre de lecture et idempotence

Isolement `READ COMMITTED`, connexion en UTC. Ordre des barrières : `identite`, puis `catalogue`, puis `finance`, puis `conservation`. A2 ne prend `finance` ni `conservation` que si un traitement ultérieur d’une autre chaîne l’exige dans la même transaction. Ne pas reprendre une barrière antérieure après coup.

Lectures publiques : pas de verrou. Écritures : `SELECT … FOR UPDATE` sur `verrous_coordination`, puis sur les lignes triées par identifiant dans la famille concernée. Aucun appel A3, A4 ou lecture de fichier sous verrou. Préparer hors transaction, recontrôler sous verrou.

Pour le dépôt d’avis, la décision, la vitrine et les passages authentifiés, relire le compte et la session dans la transaction. Une suspension concurrente empêche l’écriture.

Idempotence des mutations qui émettent une intention : en-tête `Idempotency-Key`, clé bornée à acteur, opération, ressource et clé client, conservée dans `messages_sortants.cle_idempotence`. Même empreinte : le résultat déjà engagé est renvoyé. Empreinte différente : 409. Le dépôt d’avis s’appuie en plus sur l’unicité SQL. Une violation de `uq_avis_45b94da625` devient 409, jamais une seconde ligne.

La pagination s’applique à l’ensemble déjà filtré par V-W et par les critères. Un index ou un cache périmé peut proposer des candidats. Il ne remplit pas une page et n’autorise pas une diffusion. Les compteurs et les facettes utilisent le même filtre.

### 4.4 Habilitations

| Acte | Condition |
|---|---|
| Recherche, navigation, fiches, aperçu, avis publiés | Public. Le prédicat suffit |
| Dépôt d’avis, obtention gratuite, demande de lecture | IA-02 : compte `active`, session `normal`, `active`, non révoquée, avant les deux échéances |
| Ajout au panier | Public. L’offre doit être V-F. A3 revalide |
| Vitrine | IA-02, rôle `moderator` ou `admin` actif, attribution courante `gerer_vitrine` |
| Modération d’avis | IA-02, rôle `moderator` ou `admin` actif, attribution courante `moderer_avis` |
| Reprise des projections | IA-02, rôle `support` ou `admin` actif, attribution courante `reprendre_traitements_catalogue` |
| Reprise d’une transmission A1 → A2 | Attribution courante `reprendre_transmissions`. Elle ne reconstruit pas le catalogue et ne décide pas un avis |

Le rôle seul ne donne pas la permission. Une attribution expirée ou révoquée est refusée au moment de l’écriture. L’autorité A1 indisponible rend l’acte protégé indisponible et laisse la consultation publique si ses tables répondent.

## 5. Fiches physiques

Les contrôles du MOT sont normatifs. Les Actions sont le contrat de réalisation proposé.

### T01 — Rechercher et filtrer

Opérations MCT : A2-01, A2-02, A2-03, A2-04.

- **Entrée :** `GET /catalogue/recherche`.
- **Action :** `SearchCatalogue`.
- **Accès :** Public.
- **Tables :** `oeuvre`, `edition`, `offre`, `format_technique`, `vendeurs`, `oeuvre_auteur`, `auteur`, `oeuvre_categorie`, `categorie`, `oeuvre_langue`, `langue`, `niveau_educatif`, `editeur`, `restrictions_conformite`, `levees_restrictions`, `lignes_ordres_retrait`, `resolutions_cibles`, `portees_decisions_mesures`, `instructions_retrait_publication`.
- **Règle du MOT :** Normaliser le texte, les bornes, la pagination et le tri. Appliquer les prédicats avant déduplication, tri et page. Un critère invalide est une erreur explicite.
- **Exécution physique :** Le DTO `RequeteCatalogue` porte le texte, les catégories par identifiant ou chemin complet, le format, la langue, le niveau, le prix minimum et maximum, la devise, la modalité, la gratuité, l’état, la disponibilité, le badge, `africain`, l’auteur, l’éditeur, la sélection, le tri, la page et la taille. Devise initiale `USD`. `CDF` est accepté. Aucune conversion et aucune somme entre devises. Le tri `popular` répond 422. Les tris acceptés sont `recent` (`publie_a` décroissant, puis `oeuvre_id` croissant), `price_asc`, `price_desc` et l’ordre de sélection. Format, modalité, état, prix, devise et gratuité se jugent sur la même offre V-F. Le filtre éditeur exige que cette offre appartienne à une édition V-E de cet éditeur actif. Le filtre ou le tri de prix exclut toute œuvre sans offre correspondante dans la devise choisie. Hors filtre et hors tri de prix, chaque devise réelle reste affichable. Le texte interroge `oeuvre.titre`, `auteur.nom_affiche` des seuls auteurs V-A, `edition.isbn` des seules éditions V-E et le nom des catégories actives. Un ISBN d’édition non V-E ne fait pas remonter l’œuvre. `availability=soon` dans une recherche générale ne réintroduit pas une œuvre sans offre V-F. Les œuvres sont dédupliquées. Le prix de référence est le minimum des offres qui satisfont les critères, dans la devise choisie. Une offre hors critère peut être montrée comme alternative seulement si elle est distinguée. La page est prise sur cet ensemble.
- **Résultat :** Liste paginée ou 422. **Réponse :** 200 ou 422.
- **Reprise :** Lecture seule. Si la projection T13 est indisponible ou périmée, exécuter la requête relationnelle. Ne pas compléter une page avec des lignes rejetées par le prédicat.

### T02 — Naviguer dans les catégories et les sélections

Opérations MCT : A2-10, A2-11, A2-15.

- **Entrée :** `GET /catalogue/categories`, `GET /catalogue/categories/{categorie_id}`, `GET /catalogue/selections/{code}`.
- **Action :** `BrowseCatalogue`.
- **Accès :** Public.
- **Tables :** `categorie`, `selection_editoriale`, `selection_oeuvre`, puis les tables de T01.
- **Règle du MOT :** Menu des branches actives. Une sélection active est soit un filtre, soit une liste manuelle, jamais les deux. `a_paraitre` reste le filtre `soon`. `tendances` reste l’ordre manuel.
- **Exécution physique :** Le menu charge `categorie` avec `actif = 1`, parent actif jusqu’à la racine, `sort_order` puis `categorie_id`. La résolution se fait par `categorie_id` ou par le chemin complet de slugs. Un slug local seul est refusé. Si un cycle est déjà présent, le parcours s’arrête à l’identifiant déjà vu et la branche n’est pas publiée comme navigable. A2 ne déplace pas les catégories. L’option de descendance, si elle est demandée, ajoute les descendants actifs. La sélection est lue par `selection_editoriale.code`. Si `actif = 0`, la réponse est 404. Mode `filtre` : `parametres` est un document de critères déjà validé, pas une requête SQL ; il alimente T01. Mode `manuelle` : ordre `selection_oeuvre.sort_order` puis `oeuvre_id`, et T01 ne conserve que les œuvres V-W. `a_paraitre` est le seul contexte de liste autorisé à montrer une œuvre `soon` sans offre V-F. `tendances` ne calcule aucune popularité.
- **Résultat :** Menu, liste filtrée ou 404. **Réponse :** 200 ou 404.
- **Reprise :** Lecture seule. Une sélection inactive ou un code inconnu ne tombe pas sur une recherche générale.

### T03 — Consulter une fiche œuvre

Opérations MCT : A2-20, A2-21, A2-22, A2-23, A2-24.

- **Entrée :** `GET /catalogue/oeuvres/{slug}`.
- **Action :** `ShowWork`.
- **Accès :** Public. Le bloc « Lire » dépend d’un appel A4 hors transaction, seulement pour une session IA-02.
- **Tables :** `oeuvre`, `edition`, `editeur`, `offre`, `format_technique`, `vendeurs`, `oeuvre_auteur`, `auteur`, `oeuvre_categorie`, `categorie`, `oeuvre_langue`, `langue`, `niveau_educatif`, `collection`, `media_catalogue`, `avis`.
- **Règle du MOT :** Contrôler l’état avant toute projection. Brouillon, archivage, retrait et slug inconnu produisent le même 404. La fiche directe d’une œuvre `soon` sans offre V-F est autorisée, sans prix inventé et sans action transactionnelle.
- **Exécution physique :** Résoudre `oeuvre.slug`. Appliquer V-W avec le contexte fiche directe. Charger les éditions V-E, chacune avec son éditeur, et les offres V-F. Plusieurs offres restent distinctes : vendeur, modalité, format, devise et prix. Charger les contributions dont l’auteur est V-A, tri `ordre_affichage` renseigné croissant puis `auteur_id`, les rôles non renseignés après. Charger les catégories, langues et niveau actifs, et la collection si `collection.actif = 1`. Une collection `kind = 'bundle'` ne porte ni prix ni droit groupé. Charger les avis `published` non masqués par `masquer_avis`, paginés. `note_moyenne` et `avis_count` ne sont renvoyés que s’ils viennent du dernier recalcul T12 ou d’un recalcul de lecture lorsque ce recalcul n’est pas garanti. Ne jamais exposer un avis `pending`. Le CTA « Lire » n’est proposé qu’après une réponse positive d’A4 pour le contenu et le support demandés. L’absence de réponse A4 produit un état « vérification indisponible », distinct de « aucun droit ». Les autres modalités proposent leurs offres V-F sans déduire un droit universel. `offres_contenu` peut fournir l’identifiant de version à transmettre à A4. A2 ne décide pas le droit.
- **Résultat :** Fiche autorisée ou 404 uniforme. **Réponse :** 200 ou 404. A4 indisponible : la fiche reste 200 et le CTA de lecture est indisponible.
- **Reprise :** Relecture de l’état courant. Aucune fiche servie depuis un cache qui échoue le prédicat.

### T04 — Consulter un média public

Opération MCT : A2-25.

- **Entrée :** `GET /catalogue/medias/{media_catalogue_id}`.
- **Action :** `ServeCatalogueMedia`.
- **Accès :** Public, avec recontrôle à chaque requête.
- **Tables :** `media_catalogue`, parent `oeuvre` ou `edition`, et les tables de `bloque`.
- **Règle du MOT :** Servir le dérivé public limité. Ne jamais envoyer l’original ni masquer un fichier intégral dans le navigateur.
- **Exécution physique :** Exiger exactement une des deux clés `oeuvre_id` et `edition_id`. Le parent doit satisfaire le prédicat de sa fiche. `retire_a` NULL, `actif = 1`, et non `bloque(média, masquer_media_public, t)`. Pour `type = 'apercu'`, `volume_apercu` et `unite_volume` sont obligatoires, le volume est strictement positif et inférieur ou égal au plafond de l’unité dans la configuration approuvée `A2_consultation`. Sans cette version approuvée, la réponse est 503. Pour `couverture` et `galerie`, ces deux champs doivent être nuls. `reference_publique` est une clé de stockage du dérivé, pas une URL permanente. Le contrôleur relit le prédicat puis envoie les octets. Une édition contextualisée prend d’abord les médias de cette édition, sinon ceux de l’œuvre. Elle ne prend pas la couverture d’une autre édition. Aucun média `actif = 0` n’est servi. L’absence de couverture autorisée est une absence : le substitut visuel appartient à l’interface.
- **Résultat :** Dérivé autorisé ou refus. **Réponse :** 200, 404 ou 503.
- **Reprise :** Chaque requête recontrôle. Un retrait confirme la barrière de diffusion avant la fin d’une reconstruction d’index. Les octets déjà transmis ne sont pas repris.

### T05 — Consulter les auteurs et les éditeurs

Opérations MCT : A2-30 à A2-33 et A2-35 à A2-37.

- **Entrée :** `GET /catalogue/auteurs`, `GET /catalogue/auteurs/{slug}`, `GET /catalogue/editeurs/{slug}`.
- **Action :** `ShowContributor`.
- **Accès :** Public.
- **Tables :** `auteur`, `auteur_tag`, `tag_auteur`, `oeuvre_auteur`, `editeur`, `edition`, `collection`, `oeuvre`.
- **Règle du MOT :** Auteur visible seulement s’il est V-A. Éditeur visible seulement s’il est `actif = 1`. Les bibliographies ne contiennent que des œuvres V-W du contexte bibliographie, donc sans les œuvres `soon` dépourvues d’offre V-F.
- **Exécution physique :** Fiche auteur : `slug`, prédicat V-A, sinon 404. Profil public : nom, biographie, pays, rôle affiché, indicateurs `verified` et `mis_en_avant`, tags. `verified` n’est pas une habilitation commerciale. Bibliographie paginée par `oeuvre_auteur` et V-W. Annuaire : auteurs V-A, filtre public éventuel par tag, tri stable et page. Fiche éditeur : deux ensembles séparés et dédupliqués, éditions V-E de cet éditeur et collections actives qu’il porte, avec leurs œuvres V-W. L’éditeur public n’est pas le vendeur. Aucune statistique de vente n’est renvoyée.
- **Résultat :** Fiche, annuaire ou 404. **Réponse :** 200 ou 404.
- **Reprise :** Lecture seule. Un auteur passé `draft` ou `actif = 0` disparaît de l’annuaire, des métadonnées et des termes de recherche.

### T06 — Administrer la vitrine

Opération MCT : A2-16.

- **Entrée :** `PUT /backoffice/catalogue/selections/{selection_id}` et `PUT /backoffice/catalogue/oeuvres/{oeuvre_id}/mise-en-avant`.
- **Action :** `UpdateShowcase`.
- **Accès :** `gerer_vitrine`.
- **Tables :** `selection_editoriale`, `selection_oeuvre`, `oeuvre`.
- **Règle du MOT :** Modifier le badge, la mise en avant ou une sélection selon son mode. Ne pas écrire un badge de gratuité indépendant du prix. Ne pas contourner un retrait. Activer une sélection n’active ni une œuvre ni une offre.
- **Exécution physique :** Barrières `identite` puis `catalogue`. Relire l’attribution. Verrouiller la sélection ou l’œuvre. La lecture préalable renvoie une empreinte de l’état (`regle_ou_manuelle`, `parametres`, appartenances et ordres, ou badge et `mis_en_avant`). L’écriture renvoie cette empreinte. Un écart donne 409 et aucune modification. `tendances` reste `manuelle`. `a_paraitre` reste `filtre` avec le critère `soon`. Un changement de mode est atomique : le passage à `filtre` supprime les lignes `selection_oeuvre` ; le passage à `manuelle` met `parametres` à NULL. Le badge n’est pas interprété comme un prix nul. Une œuvre non V-O peut figurer dans une sélection préparée ; elle ne devient pas visible. Après commit, enregistrer un travail durable T13.
- **Résultat :** Vitrine modifiée ou conflit. **Réponse :** 200 ou 409.
- **Reprise :** Même clé d’idempotence et même empreinte : renvoyer l’état engagé. Le travail de projection se rejoue sans réappliquer une décision déjà commitée.

### T07 — Lire et déposer un avis

Opérations MCT : A2-40, A2-41.

- **Entrée :** `GET /catalogue/oeuvres/{slug}/avis` et `POST /catalogue/oeuvres/{slug}/avis`.
- **Action :** `SubmitReview` pour le dépôt. La lecture publique est une requête.
- **Accès :** Lecture publique sur œuvre V-W. Dépôt IA-02.
- **Tables :** `avis`, `oeuvre`, `offre`, `comptes`, `sessions_authentification`.
- **Règle du MOT :** Note entière de 1 à 5, corps borné par la configuration, au plus un avis `pending` ou `published` par compte et œuvre. Aucune preuve d’achat. Refus sur une œuvre `soon` sans offre V-F. Le dépôt crée `pending`.
- **Exécution physique :** La lecture liste `statut = 'published'` non masqués, sur une fiche autorisée. Le dépôt recontrôle IA-02 sous les barrières `identite` puis `catalogue`, puis V-W et l’exclusion `soon` sans offre V-F. Sans configuration approuvée pour la longueur du corps, 503. Insérer `avis_id`, `compte_id` de la session, `oeuvre_id`, `note`, `corps`, `statut = 'pending'`, `soumis_a`, modérateur, date de modération et motif nuls. La clé unique existante tranche la concurrence. Aucun message e-mail n’est émis. `note_moyenne` et `avis_count` ne changent pas à ce stade.
- **Résultat :** Avis en attente, ou refus distinct selon le cas du MOT. **Réponse :** 201, 401, 403, 404, 409, 422 ou 503.
- **Reprise :** La contrainte unique empêche le doublon. Une réponse perdue avec la même clé d’idempotence retrouve l’avis créé. Elle n’en crée pas un second.

### T08 — Modérer ou retirer un avis

Opérations MCT : A2-42, A2-44.

- **Entrée :** `POST /backoffice/catalogue/avis/{avis_id}/publication`, `POST /backoffice/catalogue/avis/{avis_id}/rejet`, `POST /backoffice/catalogue/avis/{avis_id}/retrait`.
- **Action :** `DecideReview`.
- **Accès :** `moderer_avis`.
- **Tables :** `avis`, `oeuvre`, `messages_sortants`.
- **Règle du MOT :** Publication depuis `pending` avec modérateur et date, motif nul. Rejet depuis `pending` avec motif non vide. Retrait de `published` vers `rejected` avec motif. Un `rejected` ne redevient pas `published`. Le déclencheur SQL le garantit déjà. Une cause `masquer_avis` ne change pas ce statut.
- **Exécution physique :** Barrières `identite` puis `catalogue`. Verrouiller la ligne `avis`. L’état attendu est envoyé par le client. S’il diffère de la ligne verrouillée, 409 et aucune écriture. Renseigner `moderateur_compte_id` et `modere_a >= soumis_a`. Dans la même transaction, recalculer les agrégats T12 et écrire le message de synchronisation. Le motif vide est 422. Une permission disparue entre l’ouverture du formulaire et l’enregistrement est 403.
- **Résultat :** Décision motivée et datée. **Réponse :** 200, 403, 409 ou 422.
- **Reprise :** Même intention : résultat déjà engagé. Intention différente sur la même clé : 409. Le recalcul n’est pas une seconde décision.

### T09 — Demander un ajout au panier

Opération MCT : A2-26.

- **Entrée :** `POST /catalogue/offres/{offre_id}/panier`.
- **Action :** `RequestCartAddition`.
- **Accès :** Public. La quantité est un entier strictement positif.
- **Tables lues :** `offre`, `edition`, `oeuvre`, `format_technique`. Aucune écriture dans `paniers` ni dans une ligne de panier.
- **Règle du MOT :** Recharger l’offre. Transmettre son identifiant, la quantité, `modalite_commerciale`, `devise` et une corrélation. A3-01 confirme ou refuse. « Demande transmise » n’est pas « ajouté au panier ».
- **Exécution physique :** Recontrôler V-F hors de tout verrou long. Refuser `location`. Appeler la passerelle A3 avec la clé d’idempotence, après avoir lu le prix courant. Ne pas conserver le prix affiché par le navigateur. La réponse utilisateur de succès n’est produite qu’avec la confirmation A3. A3 indisponible : 503, sans succès local.
- **Résultat :** Demande acceptée par A3, refus A3, ou indisponibilité. **Réponse :** celle du contrat A3, ou 404, 422, 503.
- **Reprise :** Même clé transmise à A3. Pas de second envoi aveugle tant que le résultat de cette clé est inconnu.

### T10 — Demander une obtention gratuite

Opération MCT : A2-27.

- **Entrée :** `POST /catalogue/offres/{offre_id}/obtention-gratuite`.
- **Action :** `RequestFreeAcquisition`.
- **Accès :** IA-02.
- **Tables lues :** `offre` et session A1. Aucun droit écrit par A2.
- **Règle du MOT :** Offre V-F de prix nul. La modalité transmise reste celle de l’offre. A3-20 revalide. La gratuité d’une location ne crée pas un droit permanent. Cette modalité est inactive en V1.
- **Exécution physique :** Barrière `identite` pour relire la session, sans écrire le catalogue. Exiger `prix = 0`, V-F et `modalite_commerciale = 'achat'`. Appeler A3-20. Le succès affiché attend sa confirmation.
- **Résultat :** Demande A3 ou refus. **Réponse :** contrat A3, ou 401, 403, 404, 422, 503.
- **Reprise :** Idempotence A3. A2 ne crée pas de licence de compensation.

### T11 — Demander la lecture

Opération MCT : A2-28.

- **Entrée :** `POST /catalogue/oeuvres/{slug}/lecture`.
- **Action :** `RequestReading`.
- **Accès :** IA-02.
- **Tables lues :** fiche V-W et, pour identifier la cible, `offres_contenu`. Aucun fichier privé lu par A2.
- **Règle du MOT :** A4 recontrôle le droit du compte pour le contenu et le support. A2 ne fournit aucune URL privée. Service indisponible et absence de droit sont deux résultats distincts.
- **Exécution physique :** Recontrôler la session et la fiche. Transmettre le compte de la session et la cible convenue avec A4. Ne pas déduire le droit d’une offre, d’un prix nul ou d’un rôle `reader`. Réponse positive : l’interface suit le parcours A4. Réponse négative : refus sans fichier. Absence de réponse : état indisponible et possibilité de réessayer.
- **Résultat :** Autorisation, refus ou indisponibilité A4. **Réponse :** contrat A4, ou 401, 403, 404, 503.
- **Reprise :** Nouvel essai après indisponibilité. Pas de jeton de lecture fabriqué localement.

### T12 — Recalculer les agrégats

Opération MCT : A2-43.

- **Entrée :** Même transaction que T08, et travail durable après un masque ou une levée T15.
- **Action :** `RecalculateReviewAggregates`.
- **Accès :** SYS. Aucune route publique.
- **Tables :** `avis`, `oeuvre`.
- **Règle du MOT :** Moyenne et compteur des seuls avis `published` non masqués. Aucun avis retenu : `note_moyenne` NULL et `avis_count = 0`. Un calcul plus ancien ne remplace pas un calcul plus récent.
- **Exécution physique :** Barrière `catalogue`, verrou de la ligne `oeuvre`, relecture des avis courants, écriture des deux colonnes. La sérialisation par œuvre tient lieu de version : il n’y a pas de colonne de version à ajouter. Le calcul est idempotent.
- **Résultat :** Agrégats égaux à la source. **Réponse :** effet interne.
- **Reprise :** Rejouer le calcul depuis les avis. Ne pas réécrire une moyenne reçue dans un message.

### T13 — Synchroniser les projections

Opération MCT : A2-50.

- **Entrée :** Après commit d’une mutation catalogue, et à la consommation des événements A1 du §9.
- **Action :** `SynchronizeCatalogueProjection`.
- **Accès :** SYS.
- **Tables :** sources relationnelles du §5. Aucune table d’index n’est lue comme autorité.
- **Règle du MOT :** Recharger l’état courant. Un événement ancien ne republie pas un contenu retiré. Le retrait est effectif dès le contrôle de diffusion, sans attendre la fin de l’index.
- **Exécution physique :** Le travail porte l’identifiant de la cible et l’identifiant d’événement. Il relit les prédicats. S’il n’existe pas encore de projection séparée, le succès du travail est cette relecture : les lectures T01 à T05 interrogent déjà la source. Le jour où une projection est introduite, sa bascule n’a lieu qu’après contrôle des prédicats, et chaque lecture continue de pouvoir rejeter un candidat. Le travail aboutit dans `receptions_messages` lorsqu’il consomme un message, avec `traite_a` seulement après effet local durable.
- **Résultat :** Projections cohérentes avec la source, ou source relationnelle seule. **Réponse :** acquittement interne.
- **Reprise :** Même producteur, même événement et même consommateur : un seul effet. Empreinte différente : conflit, sans remplacement silencieux.

### T14 — Reprendre un traitement en échec

- **Entrée :** `POST /backoffice/catalogue/travaux/reprise` et commande `a2:resume-catalogue`.
- **Action :** `ResumeCatalogueWork`.
- **Accès :** `reprendre_traitements_catalogue` pour la reconstruction. `reprendre_transmissions` seulement pour une transmission A1 déjà enregistrée.
- **Tables :** `receptions_messages` et sources métier. Pas de modification directe d’un prix, d’un avis ou d’un statut éditorial.
- **Règle du MOT :** Reconstruire depuis l’état courant. Ne pas modifier arbitrairement les données métier.
- **Exécution physique :** Relancer le calcul T12 d’une œuvre ou la synchronisation T13 d’une cible. Vérifier le prédicat avant de considérer la reprise réussie. Une reprise de transmission réutilise l’action A1 `RetryTransmission` et la même intention. Elle ne rejoue pas une décision de vitrine ou d’avis.
- **Résultat :** Reprise vérifiée ou refus d’habilitation. **Réponse :** 202, 403 ou 409.
- **Reprise :** Une seconde demande de la même cible retrouve le travail en cours ou son résultat. Elle ne lance pas une écriture concurrente contradictoire.

### T15 — Appliquer une restriction ou une levée

Opération : A2-90.

- **Entrée :** Consommateur des causes A6 et des instructions A5, selon CT-03.
- **Action :** `ApplyCatalogueRestriction`.
- **Accès :** SYS, message authentifié. Pas de route publique.
- **Tables :** `receptions_messages`, `restrictions_conformite`, `levees_restrictions`, `lignes_ordres_retrait`, `resolutions_cibles`, `portees_decisions_mesures`, `instructions_retrait_publication`, `confirmations_effet` lorsque `ligne_id` existe.
- **Règle du MOT :** Authentifier la source, relire la cause et les autres causes, y compris le parent. Confirmer l’effet à date. Un rejeu ou une levée anticipée ne réactive pas un contenu. Aucune cause n’est copiée dans un booléen d’œuvre.
- **Exécution physique :** Barrière `catalogue`. Insérer ou retrouver la réception par `(producteur, evenement_externe_id, consommateur)`. Même empreinte : retrouver le résultat. Empreinte différente : conflit. Lire l’opposabilité au sens du §4.2. Écrire le travail T12 ou T13 dans la même transaction. Si la cause passe par `lignes_ordres_retrait`, écrire `confirmations_effet` avec `autorite = 'A2'`, unique par `(ligne_id, autorite)`. Renseigner `applique_a` seulement lorsque la barrière de lecture est effective. Ne pas attendre la reconstruction complète d’une projection future. Ne pas modifier `avis.statut` pour un masque : l’avis `published` masqué reste non rejeté et conserve son unicité. La levée d’un masque ne restaure pas un avis `rejected`.
- **Résultat :** Cause et visibilité confirmées à date. **Réponse :** acquittement `engage_local` puis effet confirmé par la lecture d’autorité.
- **Reprise :** Le worker reprend les réceptions dont `traite_a` est nul. Un message ancien après retrait ne republie rien.

## 6. Points d’entrée auxiliaires

Ces lectures permettent d’exécuter T06 et T08. Elles ne sont pas de nouvelles opérations MCT.

| Point d’entrée proposé | Contrôle | Persistance |
|---|---|---|
| `GET /backoffice/catalogue/selections` | `gerer_vitrine` | Lecture des sélections et de leur empreinte |
| `GET /backoffice/catalogue/avis?statut=pending` | `moderer_avis` | File fondée sur `avis.statut`, pas sur `taches_service` |
| `GET /backoffice/catalogue/travaux` | `reprendre_traitements_catalogue` | Réceptions et échecs de la file `a2-catalogue` |

Une liste autorisée ne donne pas le droit de décider. La décision repasse par T06 ou T08.

## 7. Traitements différés

| Commande proposée | Effet |
|---|---|
| `a2:resume-catalogue` | Reprend les réceptions A2 non traitées et les travaux dont le bail est échu |
| `a2:recalculate-aggregates` | Recalcule une œuvre ou un lot borné depuis `avis` |
| `a2:dispatch-catalogue` | Réserve les travaux durables déjà commités et non encore exécutés |

Le balayage retrouve un travail perdu entre le commit et le dispatch. Le payload du job contient un identifiant, jamais un avis complet ni une session. Un worker dépassé ne remplace pas le résultat d’un verrou plus récent. Aucune limite numérique de délai n’est fixée ici : elle suivra la configuration d’exploitation approuvée, comme pour A1.

`messages_sortants` continue de porter les événements A1 déjà produits. A2 ne crée pas un second registre d’envoi pour ces événements. Les mutations propres au catalogue qui doivent survivre à un crash écrivent aussi leur intention dans `messages_sortants` lorsque la clé d’idempotence est exigée au §4.3. Le suivi d’exécution interne reste la file Laravel et `receptions_messages`.

## 8. Médias, agrégats et configuration

`media_catalogue.reference_publique` désigne un dérivé déjà plafonné. Le module ne stocke pas l’original et ne le dérive pas à la volée devant l’utilisateur. Les fichiers de `doc/chaineA2/medias` sont des pièces de recette locales. Les activer en base exige un contrôle de format, de volume et de rattachement, puis `actif = 1`. Ce document ne réalise pas cet import.

Tant que `configurations_service` n’a pas de version approuvée du code `A2_consultation`, le dépôt d’avis et la diffusion d’aperçu répondent 503. La recherche et les fiches ne dépendent pas de ce plafond. Les valeurs du jeu de test ne sont pas recopiées comme limites de production.

`oeuvre.note_moyenne` et `oeuvre.avis_count` restent des projections. Aucune Action de vitrine ne les modifie directement.

## 9. Raccordements

| Événement ou contrat | Producteur | Effet A2 |
|---|---|---|
| `compte_acces_modifie` | A1 | Invalider le contexte du compte. Recontrôler IA-02. Ne pas supprimer ses avis ni son auteur |
| `session_acces_modifie` | A1 | Le contrôle courant ignore une session révoquée même si le message arrive plus tard |
| `permission_interne_modifiee` | A1 | Retirer la capacité de vitrine, de modération ou de reprise. Ne pas réécrire une décision passée |
| `auteur_compte_lie` | A1-08 | Réutiliser `auteur.auteur_id` et `auteur.compte_id`. Ne pas passer `statut_publication` à `published` |
| `habilitation_commerciale_modifiee` | A1 | Invalider l’affichage d’éligibilité. A3 et A5 décident. Pas de retrait public automatique |
| Demande panier et gratuit | A2 | A3 écrit le panier, le stock et le droit d’obtention |
| Demande de lecture | A2 | A4 répond du droit effectif et sert le fichier privé |
| Publication d’œuvre, d’édition ou d’offre | A5 | A2 relit l’état. Il ne publie pas à la place d’A5 |
| Cause et levée | A6 ou instruction A5 | T15 |

Charge minimale consommée : identifiant de message, type, opération source, corrélation, date, compte ou identifiant d’objet, version si elle existe. L’effet est obtenu en relisant la source. Une charge ancienne `active` ne réactive rien. Aucun secret de session et aucune pièce KYC ne sont acceptés dans `donnees_minimales`.

`vendeurs.id` est la cible de `offre.vendeur_id`. `vendeurs.actif` et le rôle éditeur ne prouvent pas l’habilitation commerciale. La consultation publique ne demande pas de KYC. Toute transaction reste revalidée par A3, y compris le droit de vente CT-06. Une offre dont le périmètre territorial n’est pas activable en V1 n’est pas rendue transactionnelle par A2.

## 10. Exploitation

Le module se branche sur les workers déjà prévus par le MPT A1. Ajouter la file `a2-catalogue` au worker de développement. Ne pas importer `livreaura.sql` une seconde fois pour obtenir les tables A2 : elles y sont déjà.

La mise en service publique des avis et des aperçus attend une version approuvée de `A2_consultation` et l’attribution nominative des trois permissions A2 par un compte `gerer_habilitations_internes`. Le rôle `admin` ne reçoit aucune de ces permissions par ce document.

Surveiller les réceptions A2 sans `traite_a`, les travaux en échec, les agrégats qui ne correspondent plus au compte des avis `published` non masqués, et les médias servis après un retrait. Une alerte ne republie pas un contenu et ne modère pas un avis.

## 11. Recette d’acceptation à exécuter

Ces tests sont spécifiés. Ils ne sont pas déclarés exécutés. Les courses s’exécutent sur MySQL, avec le schéma de `livreaura.sql`.

| Test | Critère de réussite |
|---|---|
| R01 Brouillon sans édition ni offre | Invisible dans T01 et T03 |
| R02 Auteur publié inactif | Absent de l’annuaire, des métadonnées et du texte de recherche |
| R03 Œuvre retirée encore présente dans une projection | Aucune liste, fiche ou média nouveau |
| R04 Deux vendeurs, même édition et même format | Deux offres, une seule œuvre dans la page |
| R05 Achat et location | La location n’est pas proposée comme offre V1 |
| R06 PDF payant et EPUB gratuit, filtre PDF gratuit | Œuvre exclue |
| R07 Passage USD vers CDF | Aucun montant converti |
| R08 Aucune offre dans la devise du tri de prix | Œuvre exclue de ce tri, encore affichable hors tri de prix |
| R09 Soon sans offre visible | Visible seulement dans `a_paraitre` et par slug direct. Pas d’achat, de gratuit ni d’avis |
| R10 Ordre de `tendances` | Ordre manuel conservé. `popular` refusé |
| R11 Même slug de catégorie sous deux parents | Résolution par chemin ou identifiant. Pas de doublon de racine |
| R12 ISBN d’édition non consultable | Aucune révélation par la recherche |
| R13 Aperçu au-dessus du plafond ou lien vers l’original | Diffusion refusée |
| R14 Compte actif sans achat | Dépôt `pending` sur œuvre éligible |
| R15 Deux dépôts simultanés | Une seule ligne non rejetée |
| R16 Rejet sans motif ou sans permission | État précédent conservé |
| R17 Retrait du dernier avis publié | Compteur 0 et moyenne NULL. Aucune moyenne ancienne affichée |
| R18 Deux décisions ou deux recalculs | Pas d’écrasement silencieux ni de retour à un agrégat ancien |
| R19 Compte suspendu ou session non normale | Dépôt, gratuit et lecture refusés. Consultation publique conservée |
| R20 A4 absent ou sans droit | Indisponible distinct du refus. Aucun fichier privé |
| R21 Prix changé avant le panier | A3 décide sur l’offre rechargée. A2 n’annonce pas le succès |
| R22 Location gratuite | Demande refusée en V1. Aucun droit permanent |
| R23 Rejeu après retrait | État courant conservé. Aucune republication |
| R24 Nouveau dépôt après rejet | Nouvelle ligne `pending`. L’ancienne reste `rejected` |
| IA-R01 à IA-R09 | Résultats du MOT §9, sur le registre A1 réel |
| CAT-01 à CAT-14 | Scénarios du classeur catalogue, après import de recette séparé et mapping vendeur explicite |

La validation finale exige aussi le rapprochement des modèles Eloquent avec `livreaura.sql`, les courses sur l’unicité des avis et la preuve qu’un candidat d’index périmé ne franchit pas le prédicat.

## 12. Ordre de réalisation

1. Mapper les dix-neuf relations A2 et les tables de restriction lues par `bloque`, sans migration de structure.
2. Implémenter les prédicats et les lectures T01, T02, T03 et T05.
3. Implémenter T04 derrière la configuration approuvée.
4. Implémenter T07, T08 et T12, puis les courses R15, R17, R18 et R24.
5. Implémenter T06 et l’empreinte de conflit.
6. Brancher les passerelles T09, T10 et T11 sans écriture locale de panier ni de droit.
7. Consommer les événements A1 et réaliser T13, T14 et T15.
8. Exécuter la recette sur MySQL avant toute déclaration de service connecté.

## 13. Sources et traçabilité

Les empreintes permettent de voir si une source change après cette rédaction.

- `doc/chaineA2/MOT_A2_V1_3_Consolide.md` — SHA-256 : `31fa423efbbf301484b776dec19bc2be1ea913a45b24d4acbfe5cfe1d1025fe9`.
- `doc/chaineA2/MLD_A2_V1_3_Consolide.md` — SHA-256 : `4c2becdd345ad3988882fa54d67b332ca67d1538e9fbe343c667b1ab19a9734e`.
- `doc/chaineA2/mct_v1_A2_Decouverte_Catalogue.md` — SHA-256 : `ef1b45f175b7232ca172524c1eae524842ce9b50ae01991b444075bd600e9676`.
- `doc/chaineA2/Contrats_inter_chaines_V1_3.md` — SHA-256 : `eb7b42c23a7e14a6eec2ed2e74c840937d58d35b4c47ba60f002aa4d9bc09d36`.
- `livreaura.sql` — SHA-256 : `b5d052c78e1617d79ac1a9914651792e7174eb0e2f79ecbe1f3ab1020c62773b`.

## 14. Écarts retenus entre le MLD et la base actuelle

| Sujet | Constat dans `livreaura.sql` | Traitement retenu |
|---|---|---|
| Noms logiques | Les dix-neuf relations existent en minuscules, avec les clés prévues | Aucun renommage |
| Contrôles de domaine | Pas de `CHECK` sur la note, le prix, les pages, le volume ni le rattachement exclusif du média | Contrôle dans les Actions |
| Avis | Unicité partielle et interdiction de quitter `rejected` déjà installées | Les Actions s’appuient dessus |
| Catégories racines | Unicité des racines déjà portée par `uq_racine` | Aucune table supplémentaire |
| Stock | `offre.stock` coexiste avec `offres_stock` | A2 n’écrit ni l’une ni l’autre |
| Recherche | Pas de `FULLTEXT` ni de table d’index | La requête relationnelle est l’autorité |
| Confirmation d’effet | `confirmations_effet` ne référence que `lignes_ordres_retrait` | Une cause sans cette ligne s’acquitte dans `receptions_messages` |
| File technique | Pas de table `jobs` dans le dump métier | Réutiliser la file Laravel prévue par le MPT A1 |
| Permissions | Les trois codes A2 existent, sans attribution | L’attribution reste un acte A1 |
| Guide MPD | Fichier absent | Recette X01 à X24 non spécifiée ici |
