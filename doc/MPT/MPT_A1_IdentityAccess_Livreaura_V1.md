# Livreaura — MPT A1 : Identité et accès

**Modèle physique des traitements — V1.0 — 9 septembre 2026**  
**Base fonctionnelle : MLD/MOT consolidés V1.3 ; base physique : MPD MySQL 8.4 V1.0.**

## 1. Objet et statut

Ce document traduit les 26 opérations du MOT A1 en traitements Laravel : points d’entrée, contrôles, transactions, stockage, traitements différés et critères de recette. Il constitue une spécification de réalisation. Il ne certifie ni un code déjà développé ni une importation MySQL déjà testée.

Les règles métier et noms de tables viennent des modèles consolidés. Les noms de classes, routes, commandes et paramètres techniques ci-dessous sont des choix proposés pour leur implémentation, pas des éléments déjà présents dans le projet. Les délais T0–T7 restent les valeurs proposées par le MOT, à approuver dans configurations_service avant production. Ce document ne modifie pas le MPD et ne crée aucune nouvelle table métier.

## 2. Implantation et environnement

| Élément | Développement actuel | Production cible |
|---|---|---|
| Poste / système | Windows natif, sans Docker ni WSL | VPS Linux Hostinger |
| Application | Laravel, PHP ; module IdentityAccess | Laravel sous PHP-FPM / Nginx |
| Interface | Nuxt ; back-office soumis aux mêmes API | Nuxt ; origine et cookies configurés avec Laravel |
| Données métier | MySQL 8.4, InnoDB | Même MPD, sauvegardes et restauration vérifiées |
| Authentification | Sanctum SPA par cookie + registre A1 | Même mécanisme, HTTPS |
| File d’exécution | database, un worker | Redis ; Horizon intégré et testé sous Linux |
| Sessions techniques / cache | file / file, poste unique | Redis / Redis ; A1 reste l’autorité métier |
| Notifications | Adaptateur de simulation, données fictives | Resend via adaptateur de transmission |
| Pièces privées | Disque privé de développement | Cloudflare R2 Standard privé, liens contrôlés |
| Planification | schedule:work dans un terminal | schedule:run chaque minute via cron |

Les migrations techniques Laravel nécessaires au driver database (jobs et failed_jobs) sont un complément d’infrastructure à inventorier séparément avant migration. Elles ne remplacent ni taches_service ni messages_sortants. Ne pas recréer users, password_reset_tokens ou un second référentiel de permissions. Le module utilise comptes et defis_verification. Le mode SPA retenu n’exige pas un mécanisme de jetons personnels pour Nuxt.

## 3. Organisation du module

Racine : `backend/app/Modules/IdentityAccess/`. Namespace : `App\Modules\IdentityAccess`.

| Emplacement relatif | Responsabilité |
|---|---|
| Application/Actions/Registration, Authentication, Passwords, Sessions | Inscription, défis, connexion, révocation |
| Application/Actions/Accounts, Consent, Authorization | État du compte, preuves de consentement, habilitations internes |
| Application/Actions/Organizations, Verification, Support | Représentation, KYC, recours et tâches humaines |
| Application/Queries | Lectures filtrées selon titulaire, portée et dossier |
| Application/Jobs et Application/Listeners | Transport et consommation ; appel des mêmes Actions |
| Domain/Rules, Enums, Exceptions | Invariants, états exacts du MPD, refus métier |
| Domain/Contracts | IdentityAccessGateway, IdentityNotificationSender, PrivateEvidenceStore |
| Infrastructure/Authentication | AccountUserProvider et SessionIdentityBridge |
| Infrastructure/Persistence/Models | Modèles Eloquent mappés aux tables existantes |
| Infrastructure/Messaging, Notifications, Storage | Outbox/inbox, mail, stockage privé |
| Presentation/Http/Controllers, Requests, Resources | Validation des entrées, appels d’Actions, réponses filtrées |
| Presentation/Http/Middleware, Policies, Routes | Portées, permissions et routes session.php / api.php |
| Presentation/Console/Commands | Planification, reprise, amorçage contrôlé |
| IdentityAccessServiceProvider.php | Chargement explicite des routes et bindings |

Les migrations restent dans backend/database/migrations ; tests dans backend/tests/{Unit,Feature,Integration}/IdentityAccess. Charger chaque route une seule fois et appliquer explicitement les middlewares de session/CSRF aux parcours SPA, quel que soit le nom du fichier. Le provider personnalisé doit être enregistré. Les autres modules appellent IdentityAccessGateway ; ils n’accordent pas de droits en écrivant directement comptes_roles ou les sessions.

Les modèles déclarent les tables, PK ULID non incrémentales et colonnes exactes ; ne pas laisser Eloquent écrire automatiquement created_at/updated_at absents. AccountUserProvider lit email_normalise et hash_mdp ; exclure ce dernier, les empreintes et les secrets de toute sérialisation. Pas de remember-me tant qu’un cycle conforme au registre A1 n’est pas spécifié.

## 4. Contrat commun d’exécution

### 4.1 Entrées et réponses

Routes proposées sous `/api/v1`, sauf endpoint CSRF Sanctum ; le tableau des fiches omet ce préfixe. Les mutations SPA exigent CSRF, validation de type/longueur et contrôle de la taille du corps. Le compte acteur vient exclusivement de la session vérifiée. Un identifiant de ressource ne prouve pas sa propriété.

Réponse : data ou error.code/message/champs, avec correlation_id et référence métier accessible seulement à son titulaire. Ne pas renvoyer SQL, stack, mot de passe, code, lien secret, pièce KYC ou coordonnées bancaires. 401 = authentification absente/invalide ; 403 = action interdite ; 404 = objet absent ou non visible ; 409 = état/intention concurrente incompatible ; 422 = saisie ou preuve non recevable ; 429 = quota ; 503 = indisponibilité avant engagement. Une panne mail après commit ne transforme pas une création réussie en échec.

### 4.2 Transaction et concurrence

Connexion MySQL en UTC, mode strict du SQL livré, isolation READ COMMITTED. Acquérir les barrières nécessaires dans l’ordre **identite → catalogue → finance → conservation**, par SELECT FOR UPDATE sur verrous_coordination, puis les lignes exactes. Lire UTC_TIMESTAMP(6) après acquisition. Trier les ULID dans une même famille ; sujet/dossier/tâche/contrôles pour KYC. Le code réutilise un coordinateur transactionnel commun, sans acquisition tardive d’une barrière antérieure.

L’état, la preuve, l’événement, le message et ses transmissions doivent appartenir au même commit lorsque requis. Aucun réseau, envoi, upload, analyse antivirus ou calcul coûteux de mot de passe sous verrou. Préparer hors transaction puis vérifier sous verrou que les références/empreintes n’ont pas changé. Les vérifications de permission faites en middleware sont répétées au point d’engagement.

Deadlock ou délai de verrou : rollback intégral, au plus trois tentatives proposées avec temporisation courte aléatoire. Réutiliser la même intention ; au-delà, erreur temporaire et corrélation. Aucun retry aveugle d’un effet externe inconnu.

### 4.3 Idempotence et résultats perdus

Pour créations et décisions sensibles, demander Idempotency-Key et construire une clé bornée à acteur + opération + ressource + clé client. Pour une entrée anonyme, rattacher à un contexte de demande côté serveur ; ne pas utiliser une clé publique pour consulter un compte. La clé de corrélation n’est pas une clé d’idempotence.

Conserver la clé et l’empreinte d’intention dans messages_sortants pour les mutations qui émettent une intention durable ; conserver la référence du résultat dans l’enveloppe minimale autorisée. Ce message d’opération peut rester local, sans destinataire externe. Sous barrière, rechercher la clé : même intention retrouve le résultat, autre intention donne 409. Ajouter ce message lors des implémentations qui n’en ont pas encore ; aucune table générique d’idempotence n’est supposée exister.

Les consommations de défi restent à usage unique : après consommation, jamais un second changement ni une nouvelle authentification sur présentation du même secret. Les mots de passe ne doivent pas entrer dans une empreinte générale de corps HTTP persistée ; une reprise de reset consommé retourne une réponse neutre et propose la connexion. Une déconnexion est naturellement idempotente. Les clés de commande et de message sont conservées selon la politique de preuve, pas seulement en cache.

### 4.4 Authentification et autorisation

Sanctum utilise ici la session par cookie, avec CSRF ; la portée A1 est un contrôle applicatif supplémentaire. Autoriser uniquement ce mode pour les routes de la SPA, sans repli involontaire vers un Bearer token. Configurer origine Nuxt explicitement, credentials, domaine stateful et cookies Secure/HttpOnly/SameSite compatibles ; éviter les origines CORS génériques avec credentials. Aucun token d’accès en localStorage.

SessionIdentityBridge génère une référence aléatoire dédiée, en stocke une empreinte HMAC-SHA-256 dans sessions_authentification.empreinte_reference, et conserve référence + ID A1 uniquement dans la session technique serveur. Le cookie n’est pas l’ID du compte. Après mot de passe vérifié et commit du registre A1, régénérer la session Laravel pour prévenir la fixation. En cas d’échec de stockage technique, refuser la connexion et révoquer au mieux le registre nouvellement créé ; une ligne orpheline ne suffit jamais à authentifier.

À chaque accès protégé : vérifier lien session/compte, statut, révocation, les deux échéances strictes et la portée. Mettre à jour l’inactivité sous verrou, au plus jusqu’à expire_absolue_a. Ne jamais prolonger une session déjà échue ; calcul sur l’heure serveur. Une panne du cache ou du registre entraîne un refus, pas un accès dégradé permissif.

| Portée | Compte / usages admis | Exclusions |
|---|---|---|
| normal | active ; puis rôle, propriété, permission et contexte actuels | Aucun privilège déduit de la portée seule |
| limited | pending ; activation, renvoi, textes, déconnexion | Paiement, publication, lecture, back-office |
| support_only | suspended identifié ; demande liée et autorise_par ; échanges et grants exacts | Paiement, publication, livre, accès documentaire général |

AUTH interne ajoute rôle compatible et attributions_internes datée/non révoquée ; le responsable de tâche doit être courant et indépendant du dossier. Organisation ajoute adhésion, permission et mandat effectifs. Vendeur ajoute les habilitations du même sujet et versions fiscales/reversement cohérentes. Les Guards/Gates/Policies délèguent aux règles A1 ; ni un rôle admin global ni une suppléance n’accorde toutes les permissions.

## 5. Fiches physiques des 26 opérations

Les contrôles, tables et résultats repris du MOT sont normatifs. Les points d’entrée et Actions en anglais ci-dessous sont le contrat de réalisation proposé. Les tables de support citées ne sont pas toutes écrites à chaque branche : la transaction décrite précise les mutations ; le reste est lu pour garde ou contrôle. TXnn renvoie à Regles_transactionnelles_V1.md.

### A1-01 — Choisir son parcours

- **Entrée :** `GET /identity-access/parcours`.
- **Action :** `ListRegistrationPaths`.
- **Accès :** Public, limiteur.
- **Tables et interfaces :** roles, types_organisation.
- **Règle du MOT :** Choisir lecteur, auteur ou représentant ; combiner des rôles, pas des identités incompatibles.
- **Exécution physique :** Lecture seule des référentiels ; exclure les capacités hors V1.
- **Résultat :** Parcours choisi. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-02 — Créer le compte et ses preuves

- **Entrée :** `POST /identity-access/inscriptions`.
- **Action :** `RegisterAccount`.
- **Accès :** Public, CSRF, limiteur.
- **Tables et interfaces :** comptes, profils_particuliers, comptes_roles, consentements, evenements_compte, messages_sortants.
- **Règle du MOT :** Normaliser e-mail/téléphone ; vérifier unicité e-mail, profil et textes requis ; créer atomiquement compte pending, profil, reader et acceptations.
- **Exécution physique :** TX01 : normaliser l’e-mail selon une politique unique, hacher le mot de passe avant transaction puis revalider les entrées ; compte pending, profil, reader, preuves, événement et outbox atomiques. L’UQ e-mail tranche la concurrence.
- **Résultat :** Compte créé ; données invalides ou compte existant : aucune création partielle. **Réponse :** 201.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-03 — Préparer la vérification obligatoire

- **Entrée :** `Consommateur interne du message compte_cree`.
- **Action :** `PrepareAccountVerification`.
- **Accès :** SYS, événement authentifié.
- **Tables et interfaces :** messages_sortants, transmissions_messages.
- **Règle du MOT :** Préparer la vérification obligatoire.
- **Exécution physique :** TX06/24 : relire le compte et les défis ; déléguer A1-03a. Si l’étape est différée, conserver son intention dans l’outbox. Aucun HTTP public autonome.
- **Résultat :** Demande de défi A1-03a. **Réponse :** Résultat interne durable.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-03a — Émettre ou renvoyer le défi

- **Entrée :** `POST /identity-access/defis/renvoi ; appel interne A1-03`.
- **Action :** `IssueVerificationChallenge`.
- **Accès :** Public ou limited selon finalité, CSRF, quotas.
- **Tables et interfaces :** defis_verification, messages_sortants, transmissions_messages, tentatives_transmission, configurations_service.
- **Règle du MOT :** Émettre un défi à finalité précise et échéance configurée ; limiter les renvois, invalider l’ancien défi équivalent ; tracer l’échec d’envoi sans activation.
- **Exécution physique :** TX06 : compte puis défis ; révoquer les anciens ouverts équivalents, annuler leurs transmissions en attente, écrire nouveau défi et transmissions avec secret chiffré et secrets_transmission. Renvoi et reprise technique sont distincts.
- **Résultat :** Défi envoyé, envoi à reprendre ou limite atteinte. **Réponse :** 202 générique.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-04 — Vérifier et activer le compte

- **Entrée :** `POST /identity-access/activation`.
- **Action :** `ActivateAccount`.
- **Accès :** Preuve de défi, CSRF, limiteur.
- **Tables et interfaces :** defis_verification, comptes, consentements, evenements_compte, transmissions_messages.
- **Règle du MOT :** Contrôler finalité, destination, expiration, essais, usage unique et compte pending ; consommer atomiquement ; exiger textes requis et profil ; marquer e-mail vérifié et active.
- **Exécution physique :** TX06 : recontrôler finalité email_activation, destinataire, secret et délais sous verrou ; consommer et activer seulement si toutes conditions remplies. GET affiche un formulaire sans consommer. Pas de connexion automatique.
- **Résultat :** Compte actif ; invalide/expiré/consommé : refus ; jamais de réactivation d’un suspended/closed. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-05 — Demander une récupération

- **Entrée :** `POST /identity-access/recuperations`.
- **Action :** `RequestPasswordReset`.
- **Accès :** Public, CSRF, limiteur.
- **Tables et interfaces :** defis_verification, messages_sortants, transmissions_messages ; demandes_support si perte du canal.
- **Règle du MOT :** Réponse identique que le compte existe ou non ; si éligible, défi password_reset envoyé à l’e-mail enregistré ; limitation des demandes.
- **Exécution physique :** TX06 : réponse et forme identiques pour adresse absente ou inéligible ; seul compte éligible déclenche un défi password_reset. Ne jamais créer de compte à cette occasion.
- **Résultat :** Message générique ; aucune divulgation du compte. **Réponse :** 202 générique.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-06 — Présenter la preuve de récupération

- **Entrée :** `POST /identity-access/recuperations/verification`.
- **Action :** `CheckResetProof`.
- **Accès :** Preuve, CSRF, limiteur.
- **Tables et interfaces :** defis_verification.
- **Règle du MOT :** Vérifier finalité, compte, destination, validité et essais ; ne pas confondre avec un défi d’activation.
- **Exécution physique :** TX06 : contrôler la preuve et comptabiliser les essais invalides sans consommer le défi. Cette étape ouvre uniquement le formulaire ; A1-07 contrôle de nouveau la preuve originale.
- **Résultat :** Formulaire autorisé ou refus ; contrôle préalable non suffisant seul. **Réponse :** 200 ou 422 générique.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-07 — Réinitialiser le mot de passe

- **Entrée :** `POST /identity-access/recuperations/confirmation`.
- **Action :** `ResetPassword`.
- **Accès :** Preuve password_reset, CSRF, limiteur.
- **Tables et interfaces :** comptes, defis_verification, sessions_authentification, evenements_compte, messages_sortants.
- **Règle du MOT :** Recontrôler et consommer atomiquement le défi ; remplacer empreinte du mot de passe ; révoquer toutes sessions et autres défis de récupération ; notifier.
- **Exécution physique :** TX06 : consommation unique, hash_mdp remplacé, toutes sessions révoquées, autres défis reset révoqués, événement/outbox. Le statut du compte reste inchangé. Une preuve consommée ne permet pas une seconde mutation.
- **Résultat :** Mot de passe changé ; statut du compte conservé ; nouvelle connexion requise. **Réponse :** 200, reconnexion.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-07a — Changer volontairement son mot de passe

- **Entrée :** `PUT /identity-access/moi/mot-de-passe`.
- **Action :** `ChangePassword`.
- **Accès :** AUTH, réauthentification récente.
- **Tables et interfaces :** comptes, sessions_authentification, defis_verification, evenements_compte, messages_sortants.
- **Règle du MOT :** Réauthentifier le titulaire puis appliquer remplacement, révocations et notification comme A1-07.
- **Exécution physique :** TX06 : vérifier le mot de passe courant, puis recontrôler sous verrou que son empreinte n’a pas changé depuis cette vérification. Remplacement et révocations comme A1-07 ; invalider aussi la session technique courante après commit.
- **Résultat :** Nouvelle connexion requise. **Réponse :** 200, reconnexion.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-08 — Activer le parcours auteur

- **Entrée :** `POST /identity-access/moi/parcours-auteur`.
- **Action :** `EnableAuthorPath`.
- **Accès :** AUTH ; verifier_representation pour revendication.
- **Tables et interfaces :** comptes_roles ; interface auteurs (§4.1 MLD) ; demandes_support, pieces_support, taches_service si revendication.
- **Règle du MOT :** Attribuer author idempotemment ; créer Auteur draft ou rattacher après preuve ; pas de publication publique.
- **Exécution physique :** Identite puis catalogue : rôle author idempotent ; création ou rattachement de l’identité auteurs par service propriétaire A2 dans la transaction commune. Pour revendication, demande support et preuves avant rattachement ; aucun published implicite.
- **Résultat :** Auteur habilité au dépôt ; fiche brouillon. **Réponse :** 200 ou 202 si examen.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-09 — Enregistrer la réponse à un texte versionné

- **Entrée :** `POST /identity-access/moi/consentements`.
- **Action :** `RecordConsentResponse`.
- **Accès :** AUTH ou limited propre compte ; contexte inscription lié côté serveur.
- **Tables et interfaces :** textes_legaux_versions, consentements, evenements_consentement.
- **Règle du MOT :** Présenter version immuable et enregistrer réponse ; ne pas forcer un choix optionnel pour activer le compte.
- **Exécution physique :** Identite puis conservation : vérifier version applicable et immuable ; conserver consentements et événements successifs. Aucun compte_id libre dans une requête publique ; intégrer l’inscription dans TX01. Refus requis bloque seulement la fonction conditionnée.
- **Résultat :** Preuve versionnée ; fonctionnalité nécessitant acceptation bloquée si texte requis non accepté. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-10 — Créer ou reconnaître une organisation

- **Entrée :** `POST /identity-access/organisations`.
- **Action :** `CreateOrRecognizeOrganization`.
- **Accès :** AUTH ; verifier_representation pour dossier existant.
- **Tables et interfaces :** organisations, adhesions_organisation ; demandes_support, pieces_support, taches_service si structure existante.
- **Règle du MOT :** Vérifier type et données ; rechercher structure existante pour ne pas attribuer un accès sur simple correspondance de nom.
- **Exécution physique :** TX02 : nouvelle organisation et A1-10a dans un seul commit. Correspondance possible : demande support et tâche, sans droit accordé. Ne jamais dédupliquer ou fusionner par seul nom.
- **Résultat :** Organisation créée avec A1-10a atomiquement ; structure existante : traitement de représentation par support. **Réponse :** 201 ou 202.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-10a — Installer le représentant principal déclarant

- **Entrée :** `Appel interne de CreateOrRecognizeOrganization`.
- **Action :** `InstallInitialRepresentative`.
- **Accès :** SYS, transaction A1-10.
- **Tables et interfaces :** adhesions_organisation, adhesions_permissions, evenements_compte.
- **Règle du MOT :** Créer adhésion active du déclarant comme principal avec permissions de dépôt ; pas de badge de représentant vérifié à ce stade.
- **Exécution physique :** TX02 : adhésion active principale et deposer_kyc, événement ; aucun endpoint indépendant. Échec annule aussi l’organisation. Principal déclarant ne signifie pas représentant vérifié.
- **Résultat :** Espace de dépôt accessible. **Réponse :** Résultat de A1-10.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-11 — Constituer et soumettre une version KYC

- **Entrée :** `POST /identity-access/kyc/dossiers ; POST /identity-access/kyc/dossiers/{id}/soumission`.
- **Action :** `SubmitVerificationDossier`.
- **Accès :** AUTH, propriétaire individuel ou deposer_kyc organisationnel.
- **Tables et interfaces :** dossiers_verification, documents_justificatifs, controles_verification, profils_fiscaux, comptes_reversement, taches_service, evenements_taches.
- **Règle du MOT :** Vérifier droit de déposer ; créer dossier draft, pièces privées, contrôles attendus et versions de données ; figer puis soumettre si exigences obligatoires complètes.
- **Exécution physique :** TX04 avec conservation lors d’inscription de preuves : sujet, dossier ; draft éditable via PUT dédié, pièces via POST dédié. Vérifier stockage et analyse de fichier avant transaction de soumission, puis références/empreintes inchangées sous verrou. Figer exigences, pièces et versions fiscales/reversement ; tâche, événement/outbox atomiques.
- **Résultat :** submitted ou liste des éléments manquants ; aucune pièce publique. **Réponse :** 201 draft ; 200 submitted ; 422 incomplet.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-12 — Contrôler l’organisation et décider

- **Entrée :** `POST /backoffice/identity-access/kyc/{id}/decision`.
- **Action :** `DecideOrganizationVerification`.
- **Accès :** AUTH interne, controler_kyc + decider_kyc, responsable indépendant.
- **Tables et interfaces :** taches_service, evenements_taches, controles_verification, decisions_verification, habilitations_verification, attributions_internes, messages_sortants.
- **Règle du MOT :** Passer under_review ; contrôler existence, mandat et canal ; tracer chaque résultat ; décider sans auto-approbation de son propre dossier.
- **Exécution physique :** TX05 : sujet, dossier, tâche, contrôles ; exiger under_review, contrôles complets et habilitations actuelles. Décision unique, statut, habilitation organisation_verifiee, événements, tâche terminée et outbox. Prise et contrôles via points d’entrée auxiliaires.
- **Résultat :** approved : organisation_verifiee valid ; needs_fix/rejected : motif notifié et aucune nouvelle habilitation. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-13 — Activer le partenaire commercial

- **Entrée :** `POST /backoffice/identity-access/kyc/{id}/decision-commerciale`.
- **Action :** `DecideCommercialVerification`.
- **Accès :** AUTH interne, controler_kyc + decider_kyc_commercial, indépendant.
- **Tables et interfaces :** sujets_kyc, profils_fiscaux, comptes_reversement, decisions_verification, habilitations_verification, taches_service, attributions_internes ; interface vendeurs (§4.2 MLD).
- **Règle du MOT :** Vérifier identité individuelle ou niveau organisation préalable ; exiger volets fiscal, destination de reversement et droits ; décider via contrôleur habilité ; associer Vendeur au sujet réel.
- **Exécution physique :** TX05 : mêmes garanties ; sujet réel, fiscal et reversement de ce sujet, identité préalable. Écrire décision/habilitation partenaire_commercial et vendeurs cohérent. La capacité commerciale ne prouve pas les droits d’un ouvrage.
- **Résultat :** partenaire_commercial valid ; sinon blocage commercial motivé. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-14 — Surveiller et renouveler les habilitations

- **Entrée :** `Commande a1:review-expiries ; POST /backoffice/identity-access/habilitations/{id}/restriction`.
- **Action :** `ReviewVerificationValidity`.
- **Accès :** SYS versionné ou restreindre_habilitation.
- **Tables et interfaces :** habilitations_verification, evenements_verification, configurations_service, messages_sortants ; dossiers_verification et nouvelle ligne taches_service pour renouvellement.
- **Règle du MOT :** Tracer raison ; passer habilitation à a_renouveler/restreint/expired/revoked selon événement ; notifier ; nouvelle version via A1-11 puis A1-12/A1-13.
- **Exécution physique :** Identite, conservation si preuve : chaque sujet en transaction courte, relecture échéance après verrou. Rappel sans retrait de valid ; restriction/expiration avec événement et outbox. Renouvellement via nouvelle version A1-11. Vérification de date à chaque acte même sans scheduler.
- **Résultat :** Accès commerciaux recalculés ; rétablissement uniquement après approbation valide. **Réponse :** Résultat batch ou 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-15 — Transférer la représentation principale

- **Entrée :** `POST /backoffice/identity-access/organisations/{id}/transfert-principal`.
- **Action :** `TransferPrimaryRepresentative`.
- **Accès :** AUTH interne, transferer_principal + verifier_representation.
- **Tables et interfaces :** demandes_support, pieces_support, taches_service, adhesions_organisation, evenements_compte, sessions_authentification, attributions_internes.
- **Règle du MOT :** Vérifier mandat du nouveau compte actif ; transférer principal et permissions atomiquement ; révoquer ancienne adhésion si départ ; pas de période à deux principaux.
- **Exécution physique :** TX02 : comptes triés, organisation, adhésions ; preuve indépendante et nouveau titulaire actif. Retirer l’ancien principal puis installer le nouveau dans la même transaction ; permissions, révocations de sessions et événements. Aucune période visible avec deux principaux.
- **Résultat :** Nouveau représentant ; ancien contexte organisationnel invalidé, sessions révoquées. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-20 — Ouvrir une session de portée contrôlée

- **Entrée :** `POST /identity-access/connexion`.
- **Action :** `OpenAccountSession`.
- **Accès :** Public, CSRF, limiteur et mot de passe.
- **Tables et interfaces :** comptes, comptes_roles, adhesions_organisation, habilitations_verification, sessions_authentification, configurations_service.
- **Règle du MOT :** Contrôler identifiants et état ; créer session normal si active, limited si pending ; quotas d’essais ; contrôle des droits à chaque opération.
- **Exécution physique :** TX06 : recontrôler hash et état après vérification coûteuse ; active donne normal, pending donne limited. Créer registre A1 avec référence secrète ; ouvrir/régénérer la session technique après commit. suspended suit A1-25, closed refusé.
- **Résultat :** Session authentifiée de portée adaptée ; échec générique. **Réponse :** 200 ou 401 générique.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-21 — Fermer ou expirer les sessions

- **Entrée :** `DELETE /identity-access/moi/sessions/courante ; DELETE /identity-access/moi/sessions`.
- **Action :** `RevokeAccountSessions`.
- **Accès :** Session propre compte, portée explicite ; SYS pour expiration.
- **Tables et interfaces :** sessions_authentification, configurations_service.
- **Règle du MOT :** Révoquer session courante ou toutes à la demande ; expirer selon inactivité/durée absolue.
- **Exécution physique :** TX06 : révoquer côté serveur session courante ou toutes ; effacer contexte technique après commit. Nettoyage via a1:expire-sessions. Rejouer une déconnexion ne restaure rien ; absence de session peut rendre 204.
- **Résultat :** Accès de la session fermé. **Réponse :** 204.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-22 — Suspendre un compte et couper ses accès

- **Entrée :** `POST /backoffice/identity-access/comptes/{id}/suspension`.
- **Action :** `SuspendAccount`.
- **Accès :** AUTH interne, suspendre_compte ; SYS uniquement règle approuvée.
- **Tables et interfaces :** comptes, evenements_compte, sessions_authentification, attributions_internes, messages_sortants, transmissions_messages ; receptions_messages côté A4.
- **Règle du MOT :** Tracer motif, auteur/origine et fin prévue ; passer suspended ; révoquer toutes sessions et accès de lecture actifs ; empêcher nouvelles actions sensibles.
- **Exécution physique :** TX06/24 : compte suspended, événement motivé, sessions révoquées et messages A4 atomiques. A4 traite ensuite ses sessions/jetons ; état A1 courant interdit immédiatement toute nouvelle unité protégée. Conserver acquisitions.
- **Résultat :** Compte suspendu ; droits de propriété et documents conservés. **Réponse :** 200 avec propagation en cours si nécessaire.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-23 — Réexaminer et réactiver un compte

- **Entrée :** `POST /backoffice/identity-access/comptes/{id}/reactivation`.
- **Action :** `ReactivateAccount`.
- **Accès :** AUTH interne, reactiver_compte, dossier vérifié.
- **Tables et interfaces :** demandes_support, echanges_support, taches_service, comptes, evenements_compte, attributions_internes.
- **Règle du MOT :** Examiner motif ; vérifier e-mail/profil/textes ; passer active si conditions réunies, sinon pending ; ne pas rétablir automatiquement KYC révoqué.
- **Exécution physique :** Identite : réexaminer suspension et avis sécurité requis ; active si conditions complètes sinon pending ; événement/outbox, aucune ancienne session restaurée. closed n’entre pas dans ce parcours. Les habilitations KYC ne sont pas rétablies automatiquement.
- **Résultat :** Réactivation tracée ; nouvelle connexion. **Réponse :** 200.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-24 — Clôturer le compte

- **Entrée :** `POST /backoffice/identity-access/comptes/{id}/cloture`.
- **Action :** `CloseAccount`.
- **Accès :** AUTH interne, cloturer_compte, demande vérifiée.
- **Tables et interfaces :** demandes_support, pieces_support, taches_service, comptes, adhesions_organisation, sessions_authentification, defis_verification, messages_sortants.
- **Règle du MOT :** Résoudre représentation principale ; tracer closed ; révoquer sessions et défis ; préserver historiques sans suppression en cascade.
- **Exécution physique :** Identite et conservation pour preuve : résoudre principal, statut closed et ferme_a, révocations sessions/défis, événement et outbox A4. Pas de DELETE compte, cascade, remboursement ou purge implicite. Rejeu retrouve la clôture.
- **Résultat :** Compte fermé ; conservation selon politique à définir. **Réponse :** 200 ou 409 représentation non résolue.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-25 — Recevoir un recours après suspension

- **Entrée :** `POST /identity-access/recours ; POST /backoffice/identity-access/recours/{id}/autorisation-session`.
- **Action :** `OpenSuspensionAppeal`.
- **Accès :** Dépôt public limité ; traiter_support + autoriser_recours pour agent.
- **Tables et interfaces :** demandes_support, echanges_support, pieces_support, taches_service, sessions_authentification, attributions_internes.
- **Règle du MOT :** Vérifier identité par circuit dédié ; ouvrir au besoin session support_only ; limiter aux dossiers, documents commerciaux autorisés et support.
- **Exécution physique :** TX07 : demande même anonyme + tâche atomiques ; échanges/pièces privées. Après vérification du titulaire suspended, créer support_only lié à demande_support_id et autorise_par. Remise dans canal authentifié dédié, jamais token dans e-mail libre ou réponse publique.
- **Résultat :** Recours accessible ; ni paiement, ni publication, ni lecture normale. **Réponse :** 202 dépôt ; 200 autorisation.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-26 — Autoriser un document de recours

- **Entrée :** `POST /backoffice/identity-access/recours/{id}/acces-documents`.
- **Action :** `AuthorizeAppealDocument`.
- **Accès :** AUTH interne, traiter_support + autoriser_recours.
- **Tables et interfaces :** acces_documents_support, demandes_support, comptes, sessions_authentification, evenements_compte ; références documentaires A3.
- **Règle du MOT :** Propriétaire ou mandat vérifié, session support_only valide et bornes du grant..
- **Exécution physique :** TX07 : propriétaire/mandat, demande et document A3 exact ; créer acces_documents_support borné et événement de compte lié au recours. Téléchargement via A3-21s, avec AUTH_SUPPORT à chaque usage. XOR des quatre références documentaires.
- **Résultat :** Grant exact, usage tracé et refus après expiration/révocation.. **Réponse :** 201.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

### A1-27 — Recevoir un examen de compte A6

- **Entrée :** `Consommateur interne d’instruction A6-22`.
- **Action :** `ReceiveAccountReviewInstruction`.
- **Accès :** Source A6 vérifiée, schéma et références valides.
- **Tables et interfaces :** receptions_messages, demandes_support, taches_service, evenements_taches, messages_sortants, transmissions_messages.
- **Règle du MOT :** Source, intention, compte et références exacts ; aucun chargeback ne vaut suspension..
- **Exécution physique :** TX07/24 : inbox dédupliquée avec comparaison d’empreinte ; demande examen_compte_conformite, tâche et événement atomiques. ACK engage_local référence la demande ; décision humaine A1-22/23 distincte. Aucune suspension automatique sur chargeback.
- **Résultat :** Demande et tâche durables ; résultat de décision distinct de la réception.. **Réponse :** ACK durable, puis résultat métier.
- **Reprise :** appliquer §4.3 ; notification rejouée séparément, aucun contournement d’une garde en cas de panne.

## 6. Points d’entrée auxiliaires nécessaires

Ces traitements permettent d’exécuter les opérations du MOT ; ils ne constituent pas une nouvelle chaîne.

| Point d’entrée proposé | Action / contrôle | Persistance |
|---|---|---|
| GET /identity-access/moi | ReadCurrentIdentity ; session et projection filtrée | Lecture comptes, profil, rôles et contexte autorisé |
| GET /identity-access/moi/sessions | ListOwnSessions ; compte propre | Lecture sessions_authentification sans empreinte |
| PUT /identity-access/kyc/dossiers/{id} | SaveVerificationDraft ; même sujet, draft uniquement | TX04 ; nouvelle version après décision |
| POST /identity-access/kyc/dossiers/{id}/pieces | AttachVerificationEvidence ; dépôt autorisé | documents_justificatifs, inscription conservation |
| POST /backoffice/identity-access/taches/{id}/affectation | AssignServiceTask ; affecter_taches | responsable_id + evenements_taches atomiques |
| POST /backoffice/identity-access/kyc/{id}/prise-en-charge | StartVerificationReview ; affectation actuelle | submitted vers under_review + événement |
| POST /backoffice/identity-access/kyc/{id}/controles | RecordVerificationControl ; controler_kyc, indépendance | controles_verification, controles_documents, événements |
| POST /identity-access/support/{id}/echanges | ReplyToSupport ; propriétaire normal ou AUTH_SUPPORT | echanges_support ; notes internes jamais exposées au titulaire |
| POST /identity-access/support/{id}/pieces | AttachSupportEvidence ; même périmètre | pieces_support et conservation |
| POST /backoffice/identity-access/permissions | GrantInternalPermission ; gerer_habilitations_internes | attributions_internes + événement, contrôle des périodes |
| POST /backoffice/identity-access/permissions/{id}/revocation | RevokeInternalPermission ; même permission | Révocation datée + événement ; droits recalculés immédiatement |
| POST /backoffice/identity-access/configurations | ApproveServiceConfiguration ; configurer_service | configurations_service versionnée, schéma typé |
| POST /backoffice/identity-access/suppleances | AssignServiceSubstitute ; affecter_taches | suppleances_service ; aucune permission implicite |
| POST /backoffice/identity-access/transmissions/{id}/reprise | RetryTransmission ; reprendre_transmissions | Même transmission/intention, tentative supplémentaire tracée |

Pour les listes back-office et les téléchargements de pièces, appliquer les mêmes périmètres par requête SQL : les droits sur une liste ne donnent pas accès à toutes les pièces. La création initiale d’un administrateur et de ses permissions passe par une commande d’installation locale contrôlée et journalisée, jamais une route publique. Ne pas placer de mot de passe dans un argument de commande ou un seeder versionné.

## 7. Traitements différés, outbox et ordonnanceur

### 7.1 Cycle de livraison

1. Le service métier écrit messages_sortants et chaque transmissions_messages dans son commit. Le payload contient références et données minimales ; jamais mot de passe ni secret. Pour les défis, le contenu chiffré temporaire appartient à la transmission et sa source exacte à secrets_transmission.
2. Un dispatcher balaie les transmissions éligibles queued/retry_wait et les processing dont le bail est expiré. Il sélectionne les lignes sous verrou, relit prochaine_tentative_a et réserve un bail court avec pris_par. Plusieurs jobs peuvent être livrés, mais un seul bail courant autorise l’envoi.
3. Le job ne contient que l’ID de transmission. Avant envoi, il recontrôle le bail, la source, les échéances et révocations, écrit une tentative started puis commit. Déchiffrement en mémoire et appel externe hors transaction.
4. Une transaction courte enregistre le résultat avec comparaison du bail et de pris_par. Un worker dépassé ne remplace pas le résultat d’un propriétaire plus récent. Un bail expiré ou un crash pendant l’appel peut néanmoins provoquer un doublon externe : rapprocher avec la référence stable du prestataire.
5. Une acceptation mail donne sent ; elle ne prouve pas une lecture. Résultat inconnu : tentative unknown et transmission retry_wait avec rapprochement. Ne pas écrire unknown dans etat de transmissions_messages : cette valeur appartient au résultat de tentative.
6. En interne, le consommateur écrit receptions_messages et effet local atomiquement. Même producteur/événement/consommateur et même empreinte retrouve la référence ; empreinte différente déclenche incident/conflit. Réception non corrélée reste durable en attente.
7. L’ACK recu_durable, engage_local ou effet_confirme conserve son sens précis. Une livraison acked au niveau engage_local n’autorise pas à afficher un effet distant terminé.

Le polling MySQL reprend l’intention même si le dispatch Laravel après commit est perdu. Les retries de queue ne sont pas l’historique métier : tentatives_transmission fait foi. Une transmission critique non acquittée crée ou rattache un incident_technique et une tâche support, sans en créer une nouvelle à chaque essai. Reprendre une notification ne rejoue jamais la décision KYC.

### 7.2 Planification proposée

| Commande à implémenter | Fréquence proposée | Effet et reprise |
|---|---|---|
| a1:dispatch-transmissions | Chaque minute | Envoi et reprises échues ; bail par transmission |
| a1:resume-inbox | Chaque minute | Réceptions non traitées/corrélations maintenant disponibles |
| a1:expire-sessions | Chaque minute | active vers expired si échéance atteinte ; garde immédiate indépendante |
| a1:expire-challenges | Chaque minute | Annulation des transmissions devenues inutiles et purge des secrets ; expiration lue depuis expire_a |
| a1:review-expiries | Toutes les 15 minutes | Habilitations échues, restrictions et notifications idempotentes |
| a1:send-verification-reminders | Quotidien, calendrier configuré | J-30/J-7/J-1, clé sujet/habilitation/échéance/seuil ; ne pas répéter |
| a1:monitor-service-tasks | Toutes les 15 minutes | Retards, suppléances et escalades ; aucun rejet automatique |
| a1:purge-transient-secrets | Chaque minute | Mise à NULL après envoi certain, consommation, révocation ou échéance |

Les noms de commandes sont à développer. Utiliser des traitements par lots bornés et des verrous par objet. Un verrou de scheduler limite les chevauchements mais ne remplace pas le bail SQL. Les files A5/A6 ne sont pas stockées dans taches_service A1.

### 7.3 Paramètres initiaux à approuver

| Paramètre du MOT | Valeur proposée |
|---|---|
| Activation / reset / OTP optionnel | 24 h / 15 min / 10 min ; téléphone désactivé initialement |
| Essais par défi | 5 ; incrément des échecs validé même si la réponse HTTP est un refus |
| Renvoi | Au moins 60 s ; maximum 5/h compte et destination + limiteur réseau |
| Session normal | 60 min d’inactivité, 12 h absolues |
| Session limited / support_only | 15 min d’inactivité, 1 h absolue |
| Envoi initial / reprises | Objectif 1 min ; puis +1 min et +5 min seulement si source encore valide |
| File KYC | Prise 8 h ouvrées ; décision/complément 24 h ouvrées |
| Support | Accusé immédiat ; prise 8 h ouvrées ; première réponse 16 h ouvrées |
| Calendrier proposé | Jours ouvrés 09–17 h, Africa/Kinshasa ; jours non travaillés configurés |
| Propagation de coupure A4 | Objectif 1 min ; accès refusé sur état courant dès le commit A1 |

Les configurations A1_calendrier, A1_delais, A1_acces et A1_regles_suspension sont typées, versionnées et approuvées. Les tâches et sessions référencent leur version ; ne pas réécrire silencieusement des échéances passées. Le temps total continue pendant attente_demandeur/attente_tiers. Une nouvelle tentative de vérification n’efface pas les échecs précédents.

Valeurs techniques distinctes, proposées pour les petits jobs A1 : timeout réseau 10 s, timeout worker 30 s, bail 60 s, retry_after 90 s ; vérifier ces relations et la configuration Horizon avant déploiement. Les tâches longues de fichier n’utilisent pas ces seuils. Une limite de trois essais automatiques mène à une reprise supervisée documentée ; elle ne supprime pas une intention critique non accomplie.

## 8. Secrets, pièces privées et conservation

Mots de passe : hachage adaptatif du framework, jamais chiffrement réversible. Défis : secret aléatoire à forte entropie, HMAC-SHA-256 avec clé extérieure à la base ; comparaison constante. Appliquer également l’HMAC de destination prévu au MPD avec normalisation identique. La clé de chiffrement des transmissions et sa référence ont un cycle documenté ; ne pas placer la clé dans donnees_minimales.

Le mode mail log de Laravel peut écrire des liens secrets. Pour ce module, l’adaptateur local doit masquer codes et liens avant journalisation ; tester les notifications avec fakes et comptes fictifs. Même règle dans failed_jobs : payload par ID seulement, sans objet sérialisé contenant un secret.

Une pièce uploadée reçoit une clé opaque privée ; extension, signature MIME, taille et contenu sont contrôlés. Stocker l’empreinte et la référence prévues par le MPD. Le contrôle de stockage avant soumission doit être associé à une référence immuable/protégée, afin qu’un remplacement après vérification ne change pas la preuve. Les téléchargements passent par un contrôleur autorisé ; pas de lien public permanent ni de cache partagé. Une URL signée reste utilisable jusqu’à son expiration : pour une révocation immédiate, servir par le contrôleur qui revalide chaque requête.

Création et rattachement de preuve appellent l’inscription de conservation A6 sous la barrière conservation (CT-07). Les fichiers orphelins de staging sont rapprochés avant purge. Aucune destruction de preuve avant politique approuvée, vérification de tous gels et primitive propriétaire de protection/exclusion du stockage. La clôture A1 conserve les preuves ; ne pas lui attacher un effacement automatique. Les secrets temporaires sont purgés selon leur cycle spécifique ; leur révocation ne promet pas l’effacement immédiat de toutes les sauvegardes.

## 9. Raccordements aux autres chaînes

| Contrat / événement canonique | Producteur ou consommateur A1 | Responsabilité de la chaîne distante |
|---|---|---|
| compte_acces_modifie | A1-04/22/23/24, états courants relus | A2 projections ; A3 éligibilité ; A4 accès ; A5 dépôt/activation |
| session_acces_modifie | A1-20/21 et révocations | A3 fusion panier idempotente ; A4 sessions/accès selon état courant |
| permission_interne_modifiee | Administration des attributions A1 | Chaque chaîne contrôle attribution et périmètre à l’acte |
| auteur_compte_lie | A1-08 via identité auteurs A2 | A2 garde identité catalogue ; A5 vérifie droit au dépôt |
| habilitation_commerciale_modifiee | A1-13/14 | A3/A5 recontrôlent sujet et habilitation actuels ; pas de publication automatique |
| Document de recours A1-26 → A3-21s | Grant exact, temporaire et révocable | A3 contrôle AUTH_SUPPORT, propriétaire, document et usage ; aucun livre |
| Examen A6-22 → A1-27 | Inbox et demande examen_compte_conformite | A6 reçoit une référence de travail puis une réponse métier distincte |
| Conservation CT-07 | A1 inscrit et rattache ses preuves | A6 gère politiques/gels ; A1 reste propriétaire de ses destructions autorisées |

L’enveloppe indique producteur, événement stable, type, opération, version de schéma, corrélation, cible/version et empreinte d’intention. Un ancien message active/valid ne réactive rien : le consommateur relit la situation actuelle. A1 n’attribue pas de licence, ne rembourse pas, ne publie pas un ouvrage et ne transforme pas un chargeback en suspension automatique.

## 10. Exploitation locale et production

Configuration locale proposée dans backend/.env :

```dotenv
QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
MAIL_MAILER=log
```

Le mail log exige le masquage décrit au §8. Ajouter les variables MySQL et domaines locaux correspondant au README ; aucun secret réel dans Git. Avant queue:work, installer seulement les migrations techniques manquantes, après comparaison à l’inventaire du MPD ; ne pas importer le MPD puis relancer des migrations créant les mêmes tables.

Commandes depuis backend, dans des terminaux Windows distincts après implémentation des commandes A1 :

```bat
php artisan serve
php artisan queue:work database --queue=a1-critical,a1-default --sleep=3 --tries=3 --timeout=30
php artisan schedule:work
```

Nuxt se lance dans son propre terminal selon le README. Le limiteur file est réservé au poste unique ; en production, les compteurs de quota utilisent Redis partagé. L’indisponibilité du limiteur d’authentification bloque l’opération sensible. Horizon n’est pas requis pour développer A1 localement. Son intégration Linux doit être testée avant la production et ne doit pas rendre l’installation Composer Windows impossible sans stratégie de développement adaptée.

Production : workers supervisés, redémarrage après déploiement, cron chaque minute, séparation des accès MySQL d’installation et applicatifs. Le compte applicatif n’a ni DDL ni suppression générale. Superviser retard de la plus ancienne transmission, refus d’authentification, baux bloqués, réceptions sans corrélation, tâches hors délai et échec de purge des secrets. Les alertes vont à l’exploitation ; elles n’autorisent aucune décision métier automatique.

Sauvegarder base et fichiers privés, protéger les clés séparément et tester une restauration. Après restauration, rapprocher messages déjà envoyés et références prestataires avant reprise massive. Les droits continuent à dépendre de leur état courant, pas de l’âge d’une sauvegarde de cache.

## 11. Recette d’acceptation à exécuter

Ces tests sont spécifiés ici ; ils ne sont pas déclarés exécutés. Utiliser MySQL 8.4 réel pour les contraintes et courses, pas seulement SQLite. Les tests de mail/stockage utilisent des adaptateurs simulés ; les tests d’intégration production contrôlent ensuite les prestataires.

| Test | Critère de réussite |
|---|---|
| R01 Inscription concurrente même e-mail | Un compte cohérent, reader unique, preuves et outbox présentes ; aucun profil orphelin |
| R02 Double consommation de défi | Une seule activation/reset ; autres appels sans seconde mutation |
| R03 Essais invalides et quota concurrent | Compteurs committés, plafond respecté ; aucune remise à zéro par rollback métier |
| R04 Renvoi contre ancien lien | Ancien défi inutilisable ; transmission annulée ou mail déjà parti sans effet |
| R05 Reset contre suspension/clôture | Mot de passe ne réactive jamais suspended/closed ; sessions anciennes refusées |
| R06 Fixation et vol de référence | ID technique régénéré ; ID A1 seul inutilisable ; mauvais lien compte/session refusé |
| R07 Expiration avec scheduler arrêté | Refus dès chacune des échéances ; aucune prolongation rétroactive |
| R08 Portées et accès direct | limited/support_only refusés pour paiement, publication, lecture et back-office |
| R09 Suspension concurrente à une unité A4 | Barrières respectées, aucune nouvelle admission après suspension validée |
| R10 Transfert principal concurrent | Un seul principal final, preuve/permissions cohérentes, anciennes sessions invalidées |
| R11 Soumission KYC concurrente | Version et tâche uniques ; pièces/exigences figées ; fichier absent refuse soumission |
| R12 Décision KYC et permission révoquée | Droits et affectation relus sous verrou ; auto-approbation impossible |
| R13 Mauvais sujet fiscal/reversement | Décision commerciale refusée, aucun vendeur incohérent |
| R14 KYC expiré sans batch | Nouvel acte commercial refusé ; rappel seul ne retire pas valid |
| R15 Grant documentaire expiré ou tiers | Téléchargement A3 refusé ; aucune ouverture d’un livre A4 |
| R16 A6-22 livré deux fois | Une demande/tâche ; autre intention avec même clé en conflit ; pas de suspension implicite |
| R17 Crash après commit avant dispatch | Le balayage outbox retrouve la transmission ; décision métier non rejouée |
| R18 Timeout mail et worker dépassé | État unknown dans tentative, rapprochement ; résultat récent non écrasé |
| R19 ACK local sans effet distant | Interface affiche propagation en cours ; pas de faux succès global |
| R20 Secret et journal | Aucun secret dans logs/failed_jobs/JSON minimal ; purge temporaire et trace conservée |
| R21 Clôture avec principal non remplacé | Refus contrôlé ; aucune clôture partielle ni suppression de licence |
| R22 Consentement facultatif refusé | Compte non arbitrairement bloqué ; historique de choix conservé |
| R23 Rattachement auteur homonyme | Aucun rattachement sans preuve ; identité unique, fiche draft |
| R24 Conservation et nouvelle liaison | Enregistrement/liaison atomique ; purge refusée en présence d’un gel applicable |
| R25 Réponse HTTP perdue après décision | Même intention retrouve résultat autorisé ; autre contenu donne conflit |
| R26 Affectation et suppléance | Historique/délais initiaux conservés ; suppléant sans permission ne décide pas |

La validation finale exige aussi : import du SQL complet sur base de recette, intégration A1–A6, vérification des modèles Eloquent contre le dictionnaire, contrôle des migrations techniques et essai de restauration. La revue documentaire seule ne remplace pas ces essais.

## 12. Ordre de réalisation

1. Installer la base de recette et le mapping Eloquent ; règles UTC/verrous, référentiels et amorçage contrôlé.
2. Implémenter registre A1, provider, bridge, scopes et politiques ; tests de révocation/expiration.
3. Réaliser inscription, défis, activation, connexion, reset, consentements et outbox.
4. Ajouter organisations, représentation, dossiers KYC et décisions indépendantes.
5. Ajouter support, restrictions, réactivation, clôture et accès documentaire de recours.
6. Brancher A2–A6, planification, alertes et reprises ; exécuter la recette sur MySQL et les essais Linux avant lancement.

## 13. Sources et traçabilité

Les sources suivantes ont servi à la génération ; leurs versions et empreintes permettent de détecter une modification ultérieure. Le nom Afrique-Educ conservé dans leurs titres désigne les modèles sources utilisés pour Livreaura.

- `MOT_A1_V1_3_Consolide.md` — SHA-256 : `36f9e90b8caa891c783d6775646d7410f16e988f0dff683a8f2ffe799888634c`.
- `MLD_A1_V1_3_Consolide.md` — SHA-256 : `a99c2c419d7ac8918cb3e1f9506db5ea02f24024a6b7f9603610f6d8cfce9d78`.
- `Contrats_inter_chaines_V1_3.md` — SHA-256 : `eb7b42c23a7e14a6eec2ed2e74c840937d58d35b4c47ba60f002aa4d9bc09d36`.
- `MPD_A1_V1.md` — SHA-256 : `3c6dfeb33ae02300ba0234a67a47525714ef69deaad94a29b283ca1c50b14602`.
- `Regles_transactionnelles_V1.md` — SHA-256 : `c8b416b72bf968cb9d650eadd6614f81434324a7918ba1cc3220a7e09ae2a997`.

Documentation technique officielle consultée le 9 septembre 2026 :

- [Laravel Sanctum : authentification SPA](https://laravel.com/docs/13.x/sanctum#spa-authentication).
- [Laravel Queues : workers et transactions](https://laravel.com/docs/13.x/queues).
- [Laravel Scheduling : ordonnanceur](https://laravel.com/docs/13.x/scheduling).

La documentation du framework étaye les mécanismes techniques ; les contrôles et transitions propres à Livreaura sont ceux des modèles sources et les choix de réalisation explicités dans ce MPT.
