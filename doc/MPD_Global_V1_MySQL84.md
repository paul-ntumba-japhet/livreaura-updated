# Afrique-Educ — Modèle physique de données global — V1.0

**Cible : MySQL 8.4, InnoDB. Sources : MLD/MOT A1–A6 consolidés V1.3.**

MPD de conception et SQL d'installation initiale sur base vide. Vérification statique réussie ; installation et tests sur un serveur MySQL non exécutés dans cet environnement. Ce dossier ne constitue pas une migration d'une base existante ni une validation applicative des MOT.

## 1. Périmètre livré

| Chaîne | Tables | Modèle physique détaillé |
|---|---:|---|
| A1 — Identité et accès | 44 propres + vendeurs | [MPD_A1_V1.md](MPD_A1_V1.md) |
| A2 — Découverte et catalogue | 19 | [MPD_A2_V1.md](MPD_A2_V1.md) |
| A3 — Commande et paiement B2C | 45 | [MPD_A3_V1.md](MPD_A3_V1.md) |
| A4 — Licence et lecture sécurisée | 26 | [MPD_A4_V1.md](MPD_A4_V1.md) |
| A5 — Publication auteur | 45 | [MPD_A5_V1.md](MPD_A5_V1.md) |
| A6 — Conformité et retrait | 63 | [MPD_A6_V1.md](MPD_A6_V1.md) |
| **Total** | **243** | [Dictionnaire physique complet](Dictionnaire_physique_V1.md) |

Une seule table `vendeurs`, une seule fiche `auteur`, un seul compte et un seul registre de sessions font autorité. La vue `envois_defis` ne s'ajoute pas aux 243 tables. L'installation contient 1976 colonnes stockées et 10 colonnes virtuelles, 587 FK simples/composites, 226 contraintes uniques hors PK, 748 CHECK, 448 index secondaires non uniques et 56 triggers BEFORE UPDATE.

Les clés candidates ajoutées pour garantir des rattachements composites expliquent qu'il y ait davantage de FK et d'index que dans le simple inventaire lexical du MLD. Aucun module de location, de royalties ou de logistique détaillée n'est inventé. Les structures papier sont présentes ; la capacité transactionnelle reste fermée tant que le service logistique n'est pas disponible.

## 2. Décisions physiques retenues

| Élément | Décision V1 |
|---|---|
| Architecture | Une base relationnelle commune ; modules applicatifs A1–A6 propriétaires de leurs écritures |
| SGBD | MySQL 8.4 / InnoDB ; pas de promesse de compatibilité MariaDB ou MySQL 5.7 |
| Noms | snake_case minuscule ; noms A2 convertis à l'identique en minuscules, sans double table ni pluriel implicite |
| Identifiants ID | ULID canonique de 26 caractères, `CHAR(26) CHARACTER SET ascii COLLATE ascii_bin`, généré dans l'application ; aucune colonne AUTO_INCREMENT |
| Clés composées | Conservées sur les pivots ; les clés partagées réutilisent la valeur du parent, sans créer un nouvel ID |
| Texte | UTF-8 complet `utf8mb4`, collation exacte `utf8mb4_0900_bin` ; normalisation explicite des clés métier avant écriture |
| Instants | `DATETIME(6)` UTC ; précision microseconde ; horloge DB au point d'engagement, pas une échéance dépendante d'un worker |
| Dates civiles | `DATE`, sans conversion de fuseau |
| Montants | `DECIMAL(20,4)` non négatif ; aucun FLOAT/DOUBLE ; une devise par agrégat |
| Ratios/bornes | `DECIMAL(20,8)` ; CHECK supplémentaires pour pourcentage, progression, volume ou bornes connus |
| Entiers/booléens | `BIGINT` avec borne explicite ; `TINYINT UNSIGNED` et CHECK 0/1 |
| Documents structurés | JSON natif, contrôle du conteneur en SQL ; 87 contrats déclarés dans le registre JSON, validation métier au service propriétaire |
| Secrets | Mot de passe haché adaptativement ; empreintes SHA-256 ou HMAC-SHA-256 selon finalité en hexadécimal, 64 caractères ; contenu chiffré dans MEDIUMBLOB, clés hors base |
| Suppressions | FK sans action de propagation : comportement RESTRICT par défaut ; aucun ON DELETE CASCADE ni suppression automatique d'historique |
| Transactions | READ COMMITTED, UTC, mode strict ; barrières ordonnées et verrous de lignes ; réseau après commit |
| Partitionnement | Aucun à cette version ; éviter d'introduire un partitionnement incompatible avec l'intégrité référentielle |

ULID est ici une convention de génération, pas une preuve d'identité ou un secret d'accès. Les identifiants fictifs de tests sont isolés et ne représentent aucun utilisateur réel. Les identifiants externes PSP restent des références textuelles avec leur propre unicité, pas des ULID arbitrairement reformatés.

Montants : 16 chiffres entiers et 4 décimales conservés. Les règles validées d'arrondi final de la devise/document sont appliquées avant persistance ; enregistrer la règle/version dans les snapshots prévus. L'échelle de stockage n'autorise pas un montant de paiement non accepté par le prestataire. L'application travaille avec des chaînes décimales ou un type décimal exact ; ne pas recalculer les snapshots avec des nombres flottants JavaScript/PHP.

Les valeurs obligatoires sont fournies explicitement par les services ; pas de défaut implicite `active`, `approved`, `succeeded`, montant nul ou heure de décision. Le dictionnaire précise chaque longueur. Les clés textuelles indexées sont bornées à 191 caractères sauf e-mail à 320 ; références de stockage non indexées jusqu'à 1024, libellés/titres 512, texte long TEXT. Rejeter les dépassements, ne jamais tronquer une référence pour contourner une unicité. Les références externes trop longues exigent un contrat de canonicalisation documenté avant intégration. Les unités DECIMAL et les formats de pays/langue restent ceux des référentiels validés, sans conversion inventée.

## 3. Transformation du MLD

La correspondance complète est fournie dans [Correspondance_MLD_MPD.csv](Correspondance_MLD_MPD.csv). Les colonnes et nullabilités du MLD sont conservées. Deux compléments physiques :

1. `remboursements.configuration_id`, obligatoire, répète la configuration du paiement. La FK `(paiement_id, configuration_id)` vers `paiements(id, configuration_id)` empêche une configuration erronée. UQ `(configuration_id, ref_externe)` interdit de rattacher le même remboursement PSP à deux paiements de cette configuration, tout en permettant plusieurs NULL avant connaissance de la référence.
2. Dix indicateurs virtuels de présence dans un sous-ensemble unique. Le marqueur vaut 1 lorsque la ligne appartient au sous-ensemble contraint, NULL sinon. Ils ne sont jamais renseignés par l'application.

| Invariant | Réalisation physique |
|---|---|
| Principal actif unique par organisation | UQ organisation + marqueur principal actif |
| Dossier KYC en cours unique par sujet/niveau | UQ sujet/niveau + marqueur draft/submitted/under_review |
| Catégories racines de même slug | UQ slug + marqueur racine ; UQ parent/slug pour les enfants |
| Un avis non rejeté par lecteur/œuvre | UQ compte/œuvre + marqueur pending/published |
| Panier actif unique par compte | UQ compte + marqueur actif ; jeton garde son UQ du MLD |
| Fusion de panier | Un marqueur de fusion ouverte et un marqueur de résultat final par source ; transaction contrôle qu'une source déjà finalisée n'est pas fusionnée de nouveau |
| Version de publication courante | UQ manuscrit + marqueur draft/submitted/under_review ; pipeline d'exécution accepted contrôlé sous verrou manuscrit |
| Langue principale du dossier | UQ dossier + marqueur principale |
| Application de conservation courante | UQ objet + marqueur fin_application NULL |

Les unicités dépendant de périodes effectives, de l'heure courante ou de plusieurs tables sont des contrats transactionnels précis dans [Regles_transactionnelles_V1.md](Regles_transactionnelles_V1.md), pas des index contenant NOW(). Les FK composites couvrent entre autres KYC/sujet, compte/licence/appareil/session, paiements/devise/configuration et phase/partie/notification. Lorsqu'une composante d'une FK est nullable, les conditions de présence nécessaires sont ajoutées en CHECK ou imposées au point d'engagement ; une FK composite seule n'impose pas les minima métier.

## 4. Fichiers SQL et installation

| Fichier | Contenu |
|---|---|
| [MPD_V1_MySQL84.sql](MPD_V1_MySQL84.sql) | Installation complète dans l'ordre des quatre fichiers suivants ; syntaxe client mysql incluant DELIMITER |
| [01_tables.sql](01_tables.sql) | 243 tables, PK, UQ, CHECK, marqueurs et index |
| [02_cles_etrangeres.sql](02_cles_etrangeres.sql) | Deuxième passe de FK, après création de toutes les structures |
| [03_protections_et_vues.sql](03_protections_et_vues.sql) | 56 triggers et la vue envois_defis |
| [04_referentiels.sql](04_referentiels.sql) | Quatre barrières, six rôles, quatre formats et les permissions canoniques ; aucun compte, privilège attribué ou contrat approuvé |

Créer une **base de recette vide** sur MySQL 8.4 avec encodage utf8mb4. Configurer un profil de connexion local `ae_test` avec le client MySQL, sans mot de passe dans un script ou une commande partagée. Depuis le dossier livré :

```bash
python3 outils/verifier_mysql.py --database afrique_educ_mpd_test --login-path ae_test --installer
```

Le script exige MySQL 8.4, refuse d'installer si des tables/vues existent déjà, exécute le DDL puis ses tests de contraintes. Pour retester une installation de recette déjà en place, retirer `--installer`. Il ne crée ni ne supprime de base, et les fixtures sont annulées par rollback. Ne pas exécuter ce script sur une base contenant des données réelles.

Le DDL n'est pas une transaction couvrant tout le fichier : une erreur laisse les instructions précédentes installées. Ne pas utiliser `--force`, `FOREIGN_KEY_CHECKS=0`, `INSERT IGNORE` ou `REPLACE` pour masquer les erreurs. Repartir d'une nouvelle base de recette vide après correction, puis versionner la migration réellement validée. Les SQL sont une installation initiale, sans mécanisme d'upgrade d'un schéma préexistant.

Les dépendances circulaires sont résolues **à la création du schéma** en posant les FK après les tables. **À l'insertion des données**, MySQL vérifie immédiatement les FK ; créer les parents avant les enfants, utiliser les références nullables prévues, puis compléter sous transaction. Aucune vérification différée jusqu'au commit n'est présumée.

## 5. Intégration Laravel

Mapper explicitement `$table`, `$primaryKey`, le type string et l'absence d'auto-incrément. Les pivots conservent leurs PK composées : utiliser les relations/Query Builder avec toutes les composantes, sans supposer un id numérique fictif. Les clés partagées de profil et autres extensions reprennent l'ULID du parent.

Aucun dépôt Laravel, schéma Sanctum/Spatie ou migrations existantes n'a été fourni pour cette production. Ce MPD garde donc l'autorité `comptes`/`roles`/`sessions_authentification` définie par les modèles ; ne pas ajouter en parallèle des tables framework faisant autorité sur les mêmes accès. L'adaptateur d'authentification et les permissions doivent consulter le registre retenu. Les éventuelles tables de jobs/cache/migrations du framework sont hors décompte métier et à ajouter seulement lorsqu'elles servent réellement.

Le script global utilise DELIMITER pour le client mysql. Pour le convertir en migrations Laravel, exécuter chaque CREATE/ALTER/CREATE TRIGGER séparément et retirer les directives DELIMITER ; ne pas passer le fichier complet aveuglément à DB::unprepared. Les services des MOT, autorisations, workers, PSP et stockage ne sont pas du code généré par ce MPD.

## 6. Sécurité, indexation et exploitation

Chiffrement applicatif authentifié par enveloppe pour les secrets ; clés dans un gestionnaire de secrets externe. Aucun master de livre, mot de passe en clair, token réutilisable ou pièce KYC dans les traces. Les champs JSON contenant des données privées utilisent des valeurs chiffrées/références privées selon leur propriétaire. Sauvegardes chiffrées et restaurations testées ; politique de conservation et droits de purge distincts du parcours de clôture de compte.

Les index secondaires couvrent FK, files d'attente, statuts/échéances, propriétaires, versions, décisions et cibles de restriction. Ils forment une base de travail à mesurer avec les requêtes applicatives et leur volumétrie ; aucun débit ou temps de réponse n'est certifié. La recherche textuelle du catalogue devra être raccordée au moteur retenu et aux prédicats publics A2 ; les index proposés ne prétendent pas remplacer un moteur de recherche ni autoriser une fiche retirée.

Pas de durée de conservation, prestataire de paiement, taux fiscal ou configuration de sécurité approuvés fictivement. Avant mise en service, activer les adaptateurs/protections externes, valider les paramètres puis exécuter [Recette_transversale_V1.md](Recette_transversale_V1.md). Les restrictions territoriales selection restent hold en V1 ; la bibliothèque privée garde seulement le titre historique autorisé ou le libellé neutre en cas de masque.

## 7. Vérifications et limites

**Réalisé ici :** couverture des 243 relations et de leurs attributs ; existence et compatibilité de toutes les références SQL ; clés candidates des FK composites ; absence de doublons de noms ; expressions locales référant à des colonnes réelles ; contrôle statique des longueurs d'index et des marqueurs déterministes ; compilation Python des outils. Résultat détaillé : [Rapport_validation_statique.json](Rapport_validation_statique.json).

**À exécuter sur le serveur cible :** installation complète, tests de contraintes du script de recette, concurrence et parcours X01–X24, EXPLAIN et mesures, sauvegarde/restauration et droits SQL. Le serveur MySQL n'étant pas disponible dans cet environnement, aucun rapport de tests MySQL réussi n'est fourni. Les protections locales ne remplacent pas les transactions TX01–TX24.

## 8. Références techniques

Les FK sont vérifiées immédiatement par InnoDB : [documentation MySQL 8.4 sur les clés étrangères](https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html). Les références composites ciblent ici des clés candidates explicites.

Les CHECK sont limités à la ligne et aux expressions autorisées : [contraintes CHECK](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html). Les invariants multi-tables restent donc des transactions.

Les colonnes calculées utilisées pour l'unicité sont déterministes et ne sont pas des cibles de FK : [colonnes générées](https://dev.mysql.com/doc/refman/8.4/en/create-table-generated-columns.html).
