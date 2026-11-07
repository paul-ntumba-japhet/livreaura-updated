# MOT A1 — Identité et accès — Afrique-Educ

Version 1.3 — 7 septembre 2026 — lot V1 consolidé pour préparation du MPD.

Modèle associé : [MLD_A1_V1_3_Consolide.md](MLD_A1_V1_3_Consolide.md). Contrat normatif commun : [Contrats V1.3](Contrats_inter_chaines_V1_3.md). [Guide du MPD](Guide_MPD_V1_3.md).

Base : fichier A1 du ZIP audité, révisé selon F01–F15 et recommandations approuvées. Provenance et empreintes dans le manifeste. Les compléments de données et procédures V1.3 sont intégrés ci-dessous ; la recette historique est complétée par les scénarios transversaux.

## 1. Base, portée et mode de lecture

Source normative de cette traduction : `mct_v1_1_corrige_A1.md` joint, §3.1–3.7 et interfaces/messages associés. Le MOT conserve ses 24 opérations historiques A1, leurs décisions D1–D8 et leurs états. Il précise les postes, responsabilités, moments d’exécution, sorties et reprises. Les chaînes A2 à A6 ne sont décrites qu’aux points de transmission avec A1.

Les procédures et objectifs chiffrés absents du MCT sont des **propositions d’organisation pour la V1**, et non des décisions précédemment validées, délais légaux ou engagements de service déjà pris. Ils peuvent être paramétrés sans changer les règles métier. Le MOT ne suppose aucun personnel déjà recruté et ne confond pas rôle organisationnel et personne nominative.

Les références P01–P05 suivent le MCT. Les extensions récupération, clôture et supervision gardent leurs identifiants A1 ; leur rattachement à la liste maître sera reporté ultérieurement sans inventer de Pnn.

## 2. Postes, acteurs et responsabilité

| Poste | Lieu fonctionnel et accès | Acteurs et usage |
|---|---|---|
| P1 | Site public, navigateur mobile ou ordinateur | VIS, titulaire : parcours, inscription, connexion, activation et récupération. Aucun accès aux pièces KYC. |
| P2 | Espace personnel ou espace organisation, contexte clairement affiché | Titulaire actif ou pending selon portée : sécurité, profil, textes, dépôt et suivi KYC ; représentant limité à sa structure. |
| P3 | Back-office protégé avec permissions nominatives | MOD contrôle KYC ; SUP gère représentation/recours ; ADM décide sécurité et rôles privilégiés. Chaque action sensible journalisée. |
| P4 | Services serveurs, file de travaux et ordonnanceur | SYS valide, persiste, calcule les accès, révoque et notifie ; EXP surveille les incidents techniques. |
| P5 | Service d’envoi e-mail ; canal téléphone facultatif inactif par défaut | MAIL reçoit uniquement le contenu nécessaire à l’envoi ; retour technique de livraison au système. |
| P6 | Point de contact support accessible même sans session normale | Titulaire suspendu ou privé d’accès : demande de recours/récupération ; pièces sensibles déposées par canal sécurisé après prise en charge. |

MOD = agent autorisé à contrôler/décider ; SUP = support habilité ; ADM = responsable des décisions d’accès sensibles ; EXP = exploitation technique. Le rôle « MOD habilité commerce » requiert la compétence et la permission de vérifier les volets fiscaux/de reversement. EXP corrige l’exécution et les files, jamais le résultat métier d’une décision.

### 2.1 Répartition des décisions

| Activité | Exécutant | Responsable de la décision | Consulté / destinataire |
|---|---|---|---|
| Inscription, activation, session, reset | Titulaire + SYS | Responsable du service identité (fonction ADM) pour les règles | SUP en assistance ; EXP si incident |
| Organisation et KYC standard | Déposant + MOD | MOD habilité sur le dossier | Représentant, SUP ; expert compétent si preuve ambiguë |
| Activation commerciale | MOD habilité commerce + SYS | MOD habilité commerce | Vendeur et responsable métier si cas non prévu |
| Transfert principal, recours, clôture | SUP habilité + SYS | SUP habilité | MOD pour preuve de mandat ; ADM si sécurité |
| Suspension | ADM ou SYS sur règle explicitement autorisée | ADM propriétaire de la règle/décision | SUP et titulaire ; EXP pour propagation |
| Réactivation | SUP habilité | SUP habilité, après avis ADM si motif sécurité | Titulaire et gestionnaire KYC si impact |
| Incidents d’envoi/propagation | EXP | Responsable d’exploitation | SUP pour informer, responsable métier si blocage durable |

Une personne peut tenir plusieurs fonctions dans une petite équipe, mais ne traite pas sa propre demande KYC ou celle d’une organisation qu’elle représente. Un suppléant habilité est désigné pour les absences et conflits ; sinon le dossier reste en attente avec information du demandeur. Aucun délai dépassé ne déclenche d’approbation implicite. Une double validation systématique n’est pas imposée par le MCT ; une seconde expertise peut être sollicitée en cas de conflit ou d’incertitude.

### 2.2 Habilitations opérationnelles et affectation

La matrice MLD §10.2 lie chaque opération sensible à une permission explicite. MOD/SUP/ADM désignent des fonctions ; le rôle général seul n’autorise pas toutes les actions. Affectation et réaffectation passent par affecter_taches ; le décideur est responsable courant du dossier après éventuel transfert tracé. Le compte est actif, ses attributions valides, sans conflit d’intérêt. EXP peut reprendre les transmissions sans décider un KYC. Toute règle automatique de suspension porte sa version de configuration approuvée.

## 3. Modes de traitement et calendrier proposés

**I : interactif** — saisie/demande humaine avec contrôle automatique en ligne. **A : automatique** — sans décision humaine au cas par cas, immédiat, différé ou périodique selon le déclencheur. **H : humain assisté** — un agent analyse et décide dans le back-office, puis SYS applique la décision. Une fiche peut combiner plusieurs modes pour un même traitement conceptuel.

| Réf. | Cadence / objectif initial proposé | Départ, arrêt et dépassement |
|---|---|---|
| T0 | Réponse interactive cible ≤ 3 s pour validation courante, hors téléversement et livraison externe | De la réception serveur à l’accusé ; si travail différé, afficher « en cours » et référence, jamais un succès métier non confirmé. Les expirations sont vérifiées à chaque utilisation. |
| T1 | Envoi initial en file immédiate, cible de prise en charge ≤ 1 min ; deux reprises à +1 puis +5 min si le défi reste valable | Après transaction confirmée ; passé les reprises, file d’échecs EXP et renvoi utilisateur sous quota. Sent n’atteste pas réception ni activation. |
| T2 | Lien d’activation e-mail : 24 h ; reset : 15 min ; OTP si activé : 10 min ; 5 essais par défi ; renvoi au plus toutes les 60 s et 5/h par compte/destination | Paramètres de démarrage proposés. Limites complémentaires par origine réseau pour les demandes anonymes ; quota atteint : message et délai de nouvel essai, sans révéler l’existence du compte. |
| T3 | Back-office proposé : lundi–vendredi, 9 h–17 h, heure de Kinshasa, hors jours non travaillés configurés ; prise en charge ≤ 8 heures ouvrées ; première décision ou demande de complément ≤ 24 heures ouvrées | Compter depuis submitted pour chaque version. Attente d’un tiers documentée : informer de la nouvelle échéance, sans prétendre qu’une décision finale est garantie. Dépassement : escalade au responsable métier/suppléant. |
| T4 | Support : accusé immédiat ; prise en charge ≤ 8 heures ouvrées ; réponse motivée ou demande de complément ≤ 16 heures ouvrées | Depuis réception ; ne pas confondre première réponse et résolution. Reprise sur nouvelle preuve ; durée totale depuis la demande conservée, même en attente du titulaire. |
| T5 | Rappel KYC quotidien, proposé à J−30/J−7/J−1 si échéance connue ; balayage expiration toutes les 15 min | Rappel ne change pas valid. À l’heure d’échéance, accès bloqué par contrôle courant, indépendamment du balayage. Pas d’échéance connue : aucun rappel artificiel. |
| T6 | Session normal : inactivité 60 min, absolu 12 h ; limited/support_only : inactivité 15 min, absolu 1 h | Paramètres proposés pour authentification seulement ; ne définissent pas la durée d’une SessionLecture A4. Renouvellement ne dépasse pas l’échéance absolue ; nouvelle preuve à expiration. |
| T7 | Suspension et révocation d’accès immédiates après décision ; transmission A4 cible ≤ 1 min, alerte EXP si non acquittée au-delà | Aucun accès accordé pendant une reprise. Qualification de l’alerte à la prochaine plage couverte ; aucune astreinte 24/7 humaine présumée. Si une astreinte est requise, la doter avant de promettre ce délai de prise en charge. |

Les horodatages sont conservés sur une référence commune UTC et affichés dans la zone métier appropriée. Les services automatiques visent un fonctionnement continu ; cette organisation ne constitue pas une garantie de disponibilité. Les fichiers volumineux affichent une progression et un état de reprise, sans les soumettre à la cible T0 de réponse courte.

## 4. Fiches organisationnelles des 24 opérations historiques

Pour chaque fiche, les données et résultats reprennent le MCT ; la rubrique « Déroulement » précise l’organisation proposée. Les journaux mentionnent opération, acteur ou origine système, date, compte/sujet/dossier/version concernés, résultat et corrélation, sans mot de passe, code ou lien secret.

### 4.1. A1-01 — Choisir son parcours

**Support MLD V1.1 :** roles, types_organisation. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Parcours choisi / visiteur |
| Responsable opérationnel et poste | VIS · P1 |
| Mode / échéance | I ; T0 selon §3 |
| Préconditions et contrôle métier | Choisir lecteur, auteur ou représentant ; combiner des rôles, pas des identités incompatibles |
| Informations et preuves | Role, TypeOrganisation (lecture) |
| Sortie attendue | Parcours choisi |

**Déroulement :** Le visiteur choisit lecteur, auteur ou représentant. L’écran explique qu’un même compte personnel peut cumuler ces usages. SYS prépare les champs adaptés.

**Exceptions et reprise :** Changement de parcours avant envoi : actualiser le formulaire ; aucune création de compte ni rôle privilégié.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.2. A1-02 — Créer le compte et ses preuves

**Support MLD V1.1 :** comptes, profils_particuliers, comptes_roles, consentements, evenements_compte, messages_sortants. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Formulaire soumis / visiteur |
| Responsable opérationnel et poste | VIS puis SYS · P1/P4 |
| Mode / échéance | I ; T0 selon §3 |
| Préconditions et contrôle métier | Normaliser e-mail/téléphone ; vérifier unicité e-mail, profil et textes requis ; créer atomiquement compte pending, profil, reader et acceptations |
| Informations et preuves | Compte, ProfilParticulier, CompteRole, TexteLegalVersion, Consentement, EvenementCompte |
| Sortie attendue | Compte créé ; données invalides ou compte existant : aucune création partielle |

**Déroulement :** Le visiteur saisit son identité et répond aux textes. SYS valide, crée compte pending, profil, reader, acceptations et événement dans une seule transaction. Le succès affiche la prochaine action de vérification.

**Exceptions et reprise :** Erreur de saisie : signaler les champs. Doublon ou réponse réseau perdue : proposer connexion/récupération et vérifier le résultat existant avant une nouvelle création ; aucune activation manuelle par le support.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.3. A1-03 — Préparer la vérification obligatoire

**Support MLD V1.1 :** messages_sortants, transmissions_messages. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Compte créé / système |
| Responsable opérationnel et poste | SYS · P4 |
| Mode / échéance | A ; T0 selon §3 |
| Préconditions et contrôle métier | Préparer la vérification obligatoire |
| Informations et preuves | Compte, TexteLegalVersion, Consentement |
| Sortie attendue | Demande de défi A1-03a |

**Déroulement :** Après confirmation de création, SYS vérifie le compte et les textes puis transmet une demande corrélée à A1-03a. Cette opération n’est pas un deuxième formulaire utilisateur.

**Exceptions et reprise :** Échec de préparation : demande conservée pour reprise technique ; le compte reste pending. EXP traite l’échec sans modifier la preuve e-mail.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.4. A1-03a — Émettre ou renvoyer le défi

**Support MLD V1.1 :** defis_verification, messages_sortants, transmissions_messages, tentatives_transmission, configurations_service. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Demande initiale ou renvoi / compte ou système |
| Responsable opérationnel et poste | SYS et MAIL · P4/P5 ; titulaire P1/P2 |
| Mode / échéance | A ; T1 selon §3 |
| Préconditions et contrôle métier | Émettre un défi à finalité précise et échéance configurée ; limiter les renvois, invalider l’ancien défi équivalent ; tracer l’échec d’envoi sans activation |
| Informations et preuves | DefiVerification, Compte |
| Sortie attendue | Défi envoyé, envoi à reprendre ou limite atteinte |

**Déroulement :** SYS applique quotas, crée le défi lié à sa finalité et invalide le précédent équivalent. MAIL reçoit le message. SYS trace chaque tentative d’envoi et son résultat ; l’interface affiche le délai de renvoi.

**Exceptions et reprise :** Échec de livraison : reprise T1 puis file EXP ; ne jamais marquer e-mail vérifié. Un renvoi utilisateur crée un nouveau défi ; un message ancien n’est plus utilisable. Les journaux et écrans support ne révèlent aucun secret.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.5. A1-04 — Vérifier et activer le compte

**Support MLD V1.1 :** defis_verification, comptes, consentements, evenements_compte, transmissions_messages. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Code/lien présenté / titulaire |
| Responsable opérationnel et poste | Titulaire puis SYS · P1/P4 |
| Mode / échéance | I ; T0/T2 selon §3 |
| Préconditions et contrôle métier | Contrôler finalité, destination, expiration, essais, usage unique et compte pending ; consommer atomiquement ; exiger textes requis et profil ; marquer e-mail vérifié et active |
| Informations et preuves | DefiVerification, Compte, Consentement, TexteLegalVersion, EvenementCompte |
| Sortie attendue | Compte actif ; invalide/expiré/consommé : refus ; jamais de réactivation d’un suspended/closed |

**Déroulement :** Le titulaire présente le lien/code. SYS recontrôle finalité, destination, échéance, essais, statut pending, profil et textes requis. Consommation et passage active sont atomiques. L’écran confirme et oriente vers A1-20.

**Exceptions et reprise :** Lien consommé/expiré ou mauvaise finalité : refus compréhensible et renvoi sous quota ; suspended/closed reste inchangé. Pas de session normal créée du seul fait de la confirmation.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.6. A1-05 — Demander une récupération

**Support MLD V1.1 :** defis_verification, messages_sortants, transmissions_messages ; demandes_support si perte du canal. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Mot de passe oublié / demandeur |
| Responsable opérationnel et poste | Demandeur puis SYS · P1/P4 |
| Mode / échéance | I ; T0/T2 selon §3 |
| Préconditions et contrôle métier | Réponse identique que le compte existe ou non ; si éligible, défi password_reset envoyé à l’e-mail enregistré ; limitation des demandes |
| Informations et preuves | Compte (lecture), DefiVerification |
| Sortie attendue | Message générique ; aucune divulgation du compte |

**Déroulement :** Le demandeur indique son e-mail. SYS fournit le même accusé dans tous les cas ; si le compte est éligible, un défi password_reset est envoyé par P5.

**Exceptions et reprise :** Compte inconnu ou fermé : même message public ; aucun compte créé. Canal inaccessible : SUP reçoit une demande via P6, vérifie l’identité par procédure distincte sans transférer arbitrairement le compte.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.7. A1-06 — Présenter la preuve de récupération

**Support MLD V1.1 :** defis_verification. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Lien/code reçu / titulaire |
| Responsable opérationnel et poste | Titulaire puis SYS · P1/P4 |
| Mode / échéance | I ; T0/T2 selon §3 |
| Préconditions et contrôle métier | Vérifier finalité, compte, destination, validité et essais ; ne pas confondre avec un défi d’activation |
| Informations et preuves | DefiVerification |
| Sortie attendue | Formulaire autorisé ou refus ; contrôle préalable non suffisant seul |

**Déroulement :** SYS contrôle le défi et ouvre le formulaire si éligible. L’autorisation d’afficher le formulaire ne remplace pas le contrôle final d’A1-07.

**Exceptions et reprise :** Preuve invalide : formulaire refusé, retour à A1-05. Aucun mot de passe ne doit être communiqué au support.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.8. A1-07 — Réinitialiser le mot de passe

**Support MLD V1.1 :** comptes, defis_verification, sessions_authentification, evenements_compte, messages_sortants. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Nouveau mot de passe / titulaire |
| Responsable opérationnel et poste | Titulaire puis SYS · P1/P4 |
| Mode / échéance | I ; T0 selon §3 |
| Préconditions et contrôle métier | Recontrôler et consommer atomiquement le défi ; remplacer empreinte du mot de passe ; révoquer toutes sessions et autres défis de récupération ; notifier |
| Informations et preuves | Compte, DefiVerification, SessionAuthentification, EvenementCompte |
| Sortie attendue | Mot de passe changé ; statut du compte conservé ; nouvelle connexion requise |

**Déroulement :** Le titulaire saisit le nouveau mot de passe. SYS recontrôle et consomme le défi, remplace l’empreinte, révoque toutes les sessions et autres défis de récupération, journalise puis notifie. Nouvelle connexion obligatoire.

**Exceptions et reprise :** Échec avant validation : aucune modification partielle. Notification échouée après validation : reprendre seulement la notification. Suspension et clôture ne sont jamais levées par ce traitement.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.9. A1-07a — Changer volontairement son mot de passe

**Support MLD V1.1 :** comptes, sessions_authentification, defis_verification, evenements_compte, messages_sortants. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Changement volontaire / compte authentifié |
| Responsable opérationnel et poste | Titulaire puis SYS · P2/P4 |
| Mode / échéance | I ; T0 selon §3 |
| Préconditions et contrôle métier | Réauthentifier le titulaire puis appliquer remplacement, révocations et notification comme A1-07 |
| Informations et preuves | Compte, DefiVerification, SessionAuthentification, EvenementCompte |
| Sortie attendue | Nouvelle connexion requise |

**Déroulement :** Dans Sécurité du compte, demander une réauthentification avant modification. Appliquer ensuite remplacement, révocation globale et notification comme A1-07.

**Exceptions et reprise :** Réauthentification échouée : conserver l’ancien mot de passe ; proposer récupération. Aucun maintien silencieux d’une ancienne session après succès.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.10. A1-08 — Activer le parcours auteur

**Support MLD V1.1 :** comptes_roles ; interface auteurs (§4.1 MLD) ; demandes_support, pieces_support, taches_service si revendication. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Activation du parcours auteur / compte actif |
| Responsable opérationnel et poste | Compte actif puis SYS ; MOD si revendication · P2/P3/P4 |
| Mode / échéance | I puis H si nécessaire ; T0 ou T4 selon §3 |
| Préconditions et contrôle métier | Attribuer author idempotemment ; créer Auteur draft ou rattacher après preuve ; pas de publication publique |
| Informations et preuves | CompteRole, Role, Auteur, EvenementCompte |
| Sortie attendue | Auteur habilité au dépôt ; fiche brouillon |

**Déroulement :** SYS attribue author une seule fois et prépare une fiche draft. S’il faut rattacher une fiche existante, une demande avec preuves est confiée à MOD ; celui-ci vérifie la propriété avant rattachement.

**Exceptions et reprise :** Homonymie ou conflit : conserver demande en attente et dossier de preuve ; ne pas rattacher par nom. Un rôle author ou une fiche draft ne publie pas un ouvrage en A5.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.11. A1-09 — Enregistrer la réponse à un texte versionné

**Support MLD V1.1 :** textes_legaux_versions, consentements, evenements_consentement. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Nouvelle version de texte ou retrait optionnel / compte |
| Responsable opérationnel et poste | Titulaire puis SYS · P1/P2/P4 |
| Mode / échéance | I ; T0 selon §3 |
| Préconditions et contrôle métier | Présenter version immuable et enregistrer réponse ; ne pas forcer un choix optionnel pour activer le compte |
| Informations et preuves | TexteLegalVersion, Consentement |
| Sortie attendue | Preuve versionnée ; fonctionnalité nécessitant acceptation bloquée si texte requis non accepté |

**Déroulement :** Présenter le texte applicable et sa nature ; enregistrer version, réponse, date, canal et source. Un retrait optionnel conserve la preuve antérieure.

**Exceptions et reprise :** Refus d’un texte requis : expliquer la fonction conditionnée ; ne pas confondre avec un choix optionnel ni suspendre arbitrairement le compte. Publier un nouveau texte relève d’une administration habilitée, pas du titulaire.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.12. A1-10 — Créer ou reconnaître une organisation

**Support MLD V1.1 :** organisations, adhesions_organisation ; demandes_support, pieces_support, taches_service si structure existante. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Création organisation / compte actif |
| Responsable opérationnel et poste | Compte actif puis SYS ; SUP si existante · P2/P3/P4 |
| Mode / échéance | I puis H si nécessaire ; T0 ou T4 selon §3 |
| Préconditions et contrôle métier | Vérifier type et données ; rechercher structure existante pour ne pas attribuer un accès sur simple correspondance de nom |
| Informations et preuves | Compte, Organisation, TypeOrganisation |
| Sortie attendue | Organisation créée avec A1-10a atomiquement ; structure existante : traitement de représentation par support |

**Déroulement :** Le déclarant saisit type et raison sociale. SYS recherche une structure potentiellement existante. Nouvelle structure : création avec A1-10a atomiquement. Structure existante : demande de représentation au support.

**Exceptions et reprise :** Une correspondance de nom ne donne aucun accès et ne prouve pas un doublon. SUP examine les identifiants et preuves ; EXP ne fusionne pas les organisations sur simple demande technique.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.13. A1-10a — Installer le représentant principal déclarant

**Support MLD V1.1 :** adhesions_organisation, adhesions_permissions, evenements_compte. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Organisation créée / système |
| Responsable opérationnel et poste | SYS · P4 |
| Mode / échéance | A ; T0 selon §3 |
| Préconditions et contrôle métier | Créer adhésion active du déclarant comme principal avec permissions de dépôt ; pas de badge de représentant vérifié à ce stade |
| Informations et preuves | CompteOrganisation, EvenementCompte |
| Sortie attendue | Espace de dépôt accessible |

**Déroulement :** Créer l’adhésion active, le principal et les seules permissions de dépôt prévues, puis journaliser. Le titulaire accède à son espace de dossier.

**Exceptions et reprise :** Échec d’adhésion : annuler aussi la création de l’organisation. Le principal déclarant n’est pas encore un représentant vérifié ; aucun badge ni droit commercial implicite.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.14. A1-11 — Constituer et soumettre une version KYC

**Support MLD V1.1 :** dossiers_verification, documents_justificatifs, controles_verification, profils_fiscaux, comptes_reversement, taches_service, evenements_taches. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Dépôt/soumission / représentant ou vendeur individuel |
| Responsable opérationnel et poste | Représentant ou vendeur individuel puis SYS · P2/P4 |
| Mode / échéance | I ; T0 puis file T3 selon §3 |
| Préconditions et contrôle métier | Vérifier droit de déposer ; créer dossier draft, pièces privées, contrôles attendus et versions de données ; figer puis soumettre si exigences obligatoires complètes |
| Informations et preuves | DossierVerification, ExigenceVerification, DocumentJustificatif, ControleVerification, ProfilFiscal?, CompteReversement?, EvenementVerification |
| Sortie attendue | submitted ou liste des éléments manquants ; aucune pièce publique |

**Déroulement :** SYS vérifie le droit de dépôt et affiche les exigences du sujet/pays/niveau. Le déposant sauvegarde un draft, charge ses pièces privées puis soumet. SYS contrôle complétude, fige pièces/exigences/données et accuse réception avec numéro et version.

**Exceptions et reprise :** Dossier incomplet : rester draft avec éléments manquants. Double clic : une seule soumission. needs_fix/rejected : nouvelle version, jamais édition de la décision antérieure. Fichier inaccessible : blocage de soumission et signalement EXP.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.15. A1-12 — Contrôler l’organisation et décider

**Support MLD V1.1 :** taches_service, evenements_taches, controles_verification, decisions_verification, habilitations_verification, attributions_internes, messages_sortants. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Dossier submitted / modérateur habilité |
| Responsable opérationnel et poste | MOD · P3 ; SYS P4 |
| Mode / échéance | H ; T3 selon §3 |
| Préconditions et contrôle métier | Passer under_review ; contrôler existence, mandat et canal ; tracer chaque résultat ; décider sans auto-approbation de son propre dossier |
| Informations et preuves | ControleVerification, DecisionVerification, DossierVerification, HabilitationVerification, EvenementVerification |
| Sortie attendue | approved : organisation_verifiee valid ; needs_fix/rejected : motif notifié et aucune nouvelle habilitation |

**Déroulement :** MOD prend en charge le dossier submitted et le passe under_review. Il contrôle existence, identité/mandat et canal officiel, consigne les résultats puis décide approved, needs_fix ou rejected. SYS historise et, si approved, crée/actualise le niveau organisation_verifiee valid.

**Exceptions et reprise :** Dossier lié au contrôleur : réaffecter. Pièce manquante ou non probante : décision motivée needs_fix/rejected ; pas d’approbation automatique par dépassement du délai. Le décideur vérifie les contrôles obligatoires avant confirmation.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.16. A1-13 — Activer le partenaire commercial

**Support MLD V1.1 :** sujets_kyc, profils_fiscaux, comptes_reversement, decisions_verification, habilitations_verification, taches_service, attributions_internes ; interface vendeurs (§4.2 MLD). Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Activation vente/versement / sujet commercial |
| Responsable opérationnel et poste | MOD habilité commerce · P3 ; SYS P4 |
| Mode / échéance | H ; T3 selon §3 |
| Préconditions et contrôle métier | Vérifier identité individuelle ou niveau organisation préalable ; exiger volets fiscal, destination de reversement et droits ; décider via contrôleur habilité ; associer Vendeur au sujet réel |
| Informations et preuves | DossierVerification, ControleVerification, DecisionVerification, ProfilFiscal, CompteReversement, HabilitationVerification, Vendeur, EvenementVerification |
| Sortie attendue | partenaire_commercial valid ; sinon blocage commercial motivé |

**Déroulement :** Vérifier identité du vendeur individuel ou habilitation organisation préalable. Examiner fiscal, titulaire/destination de reversement et capacité/droits. Associer Vendeur au même sujet ; approuver le niveau commercial seulement après contrôles satisfaits.

**Exceptions et reprise :** Sujet ou titulaire incohérent : bloquer, demander correction motivée. Un prestataire indisponible ne vaut pas validation. L’approbation générale ne remplace pas les droits vérifiés par ouvrage dans A5.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.17. A1-14 — Surveiller et renouveler les habilitations

**Support MLD V1.1 :** habilitations_verification, evenements_verification, configurations_service, messages_sortants ; dossiers_verification et nouvelle ligne taches_service pour renouvellement. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Échéance, changement sensible ou restriction / système ou modérateur |
| Responsable opérationnel et poste | SYS puis MOD · P4/P3 ; titulaire P2 |
| Mode / échéance | A puis H ; T5/T3 selon §3 |
| Préconditions et contrôle métier | Tracer raison ; passer habilitation à a_renouveler/restreint/expired/revoked selon événement ; notifier ; nouvelle version via A1-11 puis A1-12/A1-13 |
| Informations et preuves | HabilitationVerification, DossierVerification, EvenementVerification |
| Sortie attendue | Accès commerciaux recalculés ; rétablissement uniquement après approbation valide |

**Déroulement :** SYS envoie des rappels avant échéance. À échéance, changement sensible ou décision motivée, il trace l’état bloquant applicable et notifie. Le titulaire soumet une nouvelle version via A1-11 ; MOD réexamine via A1-12/13.

**Exceptions et reprise :** Un rappel seul ne retire pas valid. Dès échéance dépassée, les actions commerciales sont refusées même si le traitement planifié est en retard. Le renouvellement défaillant ne prolonge pas l’ancienne autorisation.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.18. A1-15 — Transférer la représentation principale

**Support MLD V1.1 :** demandes_support, pieces_support, taches_service, adhesions_organisation, evenements_compte, sessions_authentification, attributions_internes. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Remplacement principal / support habilité |
| Responsable opérationnel et poste | SUP habilité · P3 ; MOD consulté |
| Mode / échéance | H ; T4 selon §3 |
| Préconditions et contrôle métier | Vérifier mandat du nouveau compte actif ; transférer principal et permissions atomiquement ; révoquer ancienne adhésion si départ ; pas de période à deux principaux |
| Informations et preuves | CompteOrganisation, DocumentJustificatif via dossier, EvenementCompte, SessionAuthentification |
| Sortie attendue | Nouveau représentant ; ancien contexte organisationnel invalidé, sessions révoquées |

**Déroulement :** SUP vérifie identité et mandat du nouveau titulaire actif, résout les contestations puis confirme le transfert. SYS remplace le principal atomiquement, ajuste les permissions, révoque l’ancienne adhésion si départ et invalide les sessions prévues.

**Exceptions et reprise :** Aucun second principal simultané. En cas de mandat contesté, conserver le traitement en attente et restreindre les actions organisationnelles si plus de principal utilisable. L’absence de l’ancien principal ne dispense pas de preuve indépendante.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.19. A1-20 — Ouvrir une session de portée contrôlée

**Support MLD V1.1 :** comptes, comptes_roles, adhesions_organisation, habilitations_verification, sessions_authentification, configurations_service. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Connexion / titulaire |
| Responsable opérationnel et poste | Titulaire puis SYS · P1/P4 |
| Mode / échéance | I ; T0/T6 selon §3 |
| Préconditions et contrôle métier | Contrôler identifiants et état ; créer session normal si active, limited si pending ; quotas d’essais ; contrôle des droits à chaque opération |
| Informations et preuves | Compte, CompteRole, CompteOrganisation, HabilitationVerification, SessionAuthentification |
| Sortie attendue | Session authentifiée de portée adaptée ; échec générique |

**Déroulement :** SYS vérifie identifiants, quotas et état courant. Compte active : normal ; pending : limited. Les permissions et habilitations sont relues lors de chaque action. L’utilisateur est dirigé vers la fonction accessible.

**Exceptions et reprise :** Échec générique ; aucune session normale pour suspended/closed. Le recours d’un suspended suit A1-25. La session n’est ni une licence ni un droit de représenter toute organisation.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.20. A1-21 — Fermer ou expirer les sessions

**Support MLD V1.1 :** sessions_authentification, configurations_service. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Déconnexion / titulaire ou expiration / système |
| Responsable opérationnel et poste | Titulaire ou SYS · P2/P4 |
| Mode / échéance | I ou A ; T0/T6 selon §3 |
| Préconditions et contrôle métier | Révoquer session courante ou toutes à la demande ; expirer selon inactivité/durée absolue |
| Informations et preuves | SessionAuthentification |
| Sortie attendue | Accès de la session fermé |

**Déroulement :** Le titulaire ferme la session courante ou toutes ses sessions. SYS invalide côté serveur. L’inactivité ou l’échéance absolue interdit la réutilisation et provoque une nouvelle connexion.

**Exceptions et reprise :** La suppression locale du cookie seule ne suffit pas. Une tâche de nettoyage retardée ne rend pas une session expirée valable ; ne pas révoquer les licences acquises.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.21. A1-22 — Suspendre un compte et couper ses accès

**Support MLD V1.1 :** comptes, evenements_compte, sessions_authentification, attributions_internes, messages_sortants, transmissions_messages ; receptions_messages côté A4. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Suspension / administrateur habilité ou règle système autorisée |
| Responsable opérationnel et poste | ADM ou SYS selon règle autorisée · P3/P4 |
| Mode / échéance | H ou A ; T0/T7 selon §3 |
| Préconditions et contrôle métier | Tracer motif, auteur/origine et fin prévue ; passer suspended ; révoquer toutes sessions et accès de lecture actifs ; empêcher nouvelles actions sensibles |
| Informations et preuves | Compte, EvenementCompte, SessionAuthentification ; SessionLecture et JetonAcces par interface A4 |
| Sortie attendue | Compte suspendu ; droits de propriété et documents conservés |

**Déroulement :** Tracer motif, auteur/règle et fin prévue. Passer suspended, révoquer sessions d’authentification et accès de lecture ; bloquer nouvelles actions sensibles. SUP reçoit le suivi et l’utilisateur une information adaptée avec voie de recours.

**Exceptions et reprise :** Incident A4 : maintenir le refus d’accès par état courant, créer incident EXP et reprendre la propagation jusqu’à acquittement. Conserver les licences et preuves ; ne pas suspendre automatiquement tous les membres pour un problème d’organisation.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.22. A1-23 — Réexaminer et réactiver un compte

**Support MLD V1.1 :** demandes_support, echanges_support, taches_service, comptes, evenements_compte, attributions_internes. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Réactivation / support habilité |
| Responsable opérationnel et poste | SUP habilité · P3 ; ADM consulté si sécurité |
| Mode / échéance | H ; T4 selon §3 |
| Préconditions et contrôle métier | Examiner motif ; vérifier e-mail/profil/textes ; passer active si conditions réunies, sinon pending ; ne pas rétablir automatiquement KYC révoqué |
| Informations et preuves | Compte, EvenementCompte, HabilitationVerification (lecture) |
| Sortie attendue | Réactivation tracée ; nouvelle connexion |

**Déroulement :** Examiner la cause, les preuves et les conditions e-mail/profil/textes. Décider active ou pending selon les conditions remplies, historiser et demander une nouvelle connexion.

**Exceptions et reprise :** Ne pas rétablir une habilitation KYC révoquée. Pour une suspension de sécurité, obtenir l’avis habilité prévu avant levée. L’arrivée d’une fin prévue déclenche un réexamen, pas une réactivation aveugle.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.23. A1-24 — Clôturer le compte

**Support MLD V1.1 :** demandes_support, pieces_support, taches_service, comptes, adhesions_organisation, sessions_authentification, defis_verification, messages_sortants. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Clôture / support habilité après demande vérifiée |
| Responsable opérationnel et poste | SUP habilité · P3 ; titulaire P2/P6 |
| Mode / échéance | H ; T4 selon §3 |
| Préconditions et contrôle métier | Résoudre représentation principale ; tracer closed ; révoquer sessions et défis ; préserver historiques sans suppression en cascade |
| Informations et preuves | Compte, CompteOrganisation, EvenementCompte, SessionAuthentification, DefiVerification |
| Sortie attendue | Compte fermé ; conservation selon politique à définir |

**Déroulement :** Vérifier la demande et son auteur, résoudre le remplacement du principal si nécessaire puis confirmer closed. SYS révoque sessions/défis et propage fermeture de lecture, conserve les relations et historiques.

**Exceptions et reprise :** Si représentation non résolue, informer et suivre la demande sans clôture partielle. Clôture ne signifie ni suppression physique, ni remboursement automatique, ni effacement des licences ; aucune réouverture par reset.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

### 4.24. A1-25 — Recevoir un recours après suspension

**Support MLD V1.1 :** demandes_support, echanges_support, pieces_support, taches_service, sessions_authentification, attributions_internes. Dictionnaire du socle §2 ; compléments et règles §10.1–10.4.

| Rubrique | Organisation |
|---|---|
| Déclencheur source | Demande de recours / titulaire suspended |
| Responsable opérationnel et poste | Titulaire et SUP habilité · P6/P3 ; SYS P4 |
| Mode / échéance | H puis I ; T4/T6 selon §3 |
| Préconditions et contrôle métier | Vérifier identité par circuit dédié ; ouvrir au besoin session support_only ; limiter aux dossiers, documents commerciaux autorisés et support |
| Informations et preuves | Compte, SessionAuthentification, EvenementCompte |
| Sortie attendue | Recours accessible ; ni paiement, ni publication, ni lecture normale |

**Déroulement :** Le titulaire utilise le point de contact dédié accessible sans session normale. SUP vérifie identité et périmètre, consigne le dossier de recours puis, si nécessaire, autorise une nouvelle session support_only.

**Exceptions et reprise :** Preuves insuffisantes : demander complément sans exposer documents du compte. Le support ne demande jamais le mot de passe. support_only ne permet paiement/publication/lecture ; une éventuelle levée relève d’A1-23.

**Clôture de l’opération :** le résultat est enregistré et consultable avec sa référence avant notification de succès. Une reprise retrouve cette référence et l’état courant ; elle ne crée pas une deuxième décision ou action sensible. Les contrôles refusés restent distingués d’un incident technique.

## 5. Circulation des dossiers, notifications et transmissions

### 5.1 File KYC

Le déposant prépare draft ; la soumission complète produit submitted et un accusé. MOD s’attribue la version, la passe under_review et trace les contrôles. Un seul agent décide sur cette version à un instant donné ; réaffectation par suppléant tracée, sans perdre l’historique. L’agent voit sujet, niveau, version, âge de la demande, pièces, règles applicables et éventuels conflits d’intérêt.

| Décision | État et transmission | Action du destinataire |
|---|---|---|
| approved | Décision immuable ; habilitation du niveau accordée sous conditions ; notification au sujet/représentant | Utiliser les fonctions autorisées ; une vérification d’organisation seule n’autorise pas la vente |
| needs_fix | Motifs précis, éléments attendus et ancienne version conservée | Ouvrir une nouvelle version puis soumettre ; pas d’édition de la preuve décidée |
| rejected | Motif et canal de contact ; aucune nouvelle habilitation | Demander un réexamen avec éléments nouveaux via support ; ne pas recycler la décision |
| Absence de réponse du demandeur | Ancienne décision conservée, aucune habilitation accordée automatiquement | Relance proposée après 5 jours ouvrés ; aucune clôture/rejet automatique non prévu au MCT |

Les preuves fiscales, bancaires et de représentation restent privées. L’e-mail de notification donne numéro, version, décision, résumé du motif et lien vers l’espace protégé, jamais les pièces ni coordonnées complètes. Le contexte de l’organisation concernée est affiché pour éviter d’agir au nom d’une autre structure.

### 5.2 Contrat de transmission entre acteurs et chaînes

Persistance de référence : MLD §10.1.10–13 et §10.3. La transaction métier écrit messages_sortants ; chaque destinataire possède sa transmission et ses tentatives. Les acquittements internes précisent le niveau d’engagement local durable (CT-02), tandis qu’un e-mail sent confirme seulement l’acceptation par le prestataire. Un timeout peut laisser un résultat unknown ; rapprocher/reprendre avec déduplication, sans promettre une livraison unique.

Pour un défi, le message exact est conservé chiffré temporairement par transmission, clé externe à la base, et purgé au succès définitif, consommation, révocation ou expiration. Les tentatives réutilisent ce contenu tant qu’il est valide. Un renvoi utilisateur crée un nouveau défi et annule les transmissions encore en attente de l’ancien. Aucune reprise à partir d’une empreinte seule, aucun secret dans les journaux. Le worker recontrôle l’état avant envoi. Les messages retardés éventuellement déjà partis restent inutilisables après révocation du défi.


| Message | Producteur | Destinataire / action | En cas d’échec |
|---|---|---|---|
| Compte créé | A1-02 | SYS A1-03/03a : demande de vérification | Reprendre envoi ; compte pending |
| Compte actif | A1-04/23 | Titulaire puis A1-20 ; précondition d’achat/gratuit A3 | Reprendre notification ; aucun login implicite |
| Session normal ouverte | A1-20 | A3 fusion du panier ; A4 bibliothèque selon licences | Reprendre tâche de fusion/affichage sans doubler le panier ni attribuer une licence |
| Auteur habilité au dépôt | A1-08 | Titulaire/A5 | Conserver draft ; reprise du rattachement autorisé |
| Organisation vérifiée | A1-12 | Représentant, contrôles d’accès organisation | Recalculer à partir de l’habilitation actuelle ; pas de rôle admin |
| Partenaire commercial OK | A1-13 | Vendeur, A5 mise en vente et reversements | Refuser tant que les préconditions courantes ne peuvent pas être établies ; reprise corrélée |
| Habilitation restreinte/expirée | A1-14 | A5/reversements, représentant, SUP | Blocage courant indépendant du message ; incident EXP si propagation incomplète |
| Principal transféré | A1-15 | Ancien et nouveau représentants, SUP | Reprendre notifications ; le principal enregistré reste unique |
| Compte suspendu/fermé | A1-22/24 | A4 ferme sessions de lecture et révoque jetons ; SUP et titulaire | Refus des nouveaux accès immédiat ; reprise des fermetures jusqu’à acquittement |
| Mot de passe changé | A1-07/07a | Titulaire et registre de sessions | Révocation prioritaire ; notification reprise séparément |

Un message comporte identifiant de corrélation, opération, objet concerné, version éventuelle, date et résultat. Chaque consommateur traite une même transition une fois et relit l’état courant. Une ancienne notification « valid » ne peut réactiver une autorisation devenue restreinte. Les traitements sensibles attendent la validation de la transaction avant d’émettre une confirmation.

## 6. Organisation des incidents et des recours

### 6.1 Trois circuits distincts

| Nature | Responsable | Traitement |
|---|---|---|
| Erreur de saisie ou preuve manquante | Titulaire, assisté de SUP | Correction guidée ; nouvelle version si dossier déjà décidé ; conserver numéro de demande |
| Refus ou conflit métier | MOD/SUP/ADM selon décision | Motif consultable, preuves et réexamen par personne habilitée ; EXP n’accorde jamais un accès pour débloquer le cas |
| Incident technique | EXP ; SUP informe | Identifier étape validée, reprendre seulement étape échouée ; surveiller les effets d’accès et conserver traçabilité |

Pour une réponse réseau perdue après validation, l’interface consulte le résultat connu au lieu d’afficher un échec définitif ou de répéter la création. Les envois et notifications peuvent être repris ; une décision KYC ou un transfert validé ne sont pas rejoués aveuglément.

### 6.2 Support d’un compte suspendu ou privé de son canal

P6 permet de déposer une demande sans session normale ; l’accusé ne révèle aucune donnée du compte. SUP vérifie le lien entre demandeur et compte par preuves adaptées, sans exiger de mot de passe. Si la preuve est insuffisante, demander complément ; si elle est contestée, escalader au responsable habilité. Le MOT ne crée pas de procédure automatique de changement d’e-mail sur simple déclaration.

Pour un suspended identifié, SYS peut émettre une nouvelle support_only limitée aux échanges et documents autorisés. Les contrôles de propriété des dossiers s’appliquent toujours. Une levée de suspension suit A1-23 ; elle ne réactive pas automatiquement un KYC. Un compte closed reste closed, y compris après présentation d’un ancien lien de récupération.

### 6.3 Hors heures de présence

Inscription, connexion et expirations restent automatiques. Les demandes humaines sont accusées puis placées en file avec prochaine plage annoncée. Les restrictions de sécurité sont maintenues ; aucun délai d’attente n’autorise le service à ignorer un contrôle. Une absence prolongée du responsable déclenche la réaffectation au suppléant, pas une approbation par le demandeur.

## 7. Supports de travail alignés sur le MLD V1.1

| Support MOT | Relations MLD | Référence / usage |
|---|---|---|
| File KYC et suppléance | taches_service, evenements_taches, suppleances_service | §10.1.4, §10.1.8–9 : responsable, attentes, reports, délais et réaffectations |
| Recours, auteur, représentation, clôture | demandes_support, echanges_support, pieces_support | §10.1.5–7 : demande initiale anonyme possible ; lien au compte seulement après contrôle |
| Permissions du back-office | permissions_internes, attributions_internes | §10.1.1–2 et §10.2 : pouvoirs explicites, sans auto-approbation |
| Journaux métier | evenements_compte, evenements_verification, decisions_verification | §2 : preuves immuables ; les journaux ne sont pas la file de livraison |
| Envois, notifications et propagation | messages_sortants, transmissions_messages, tentatives_transmission, receptions_messages | §10.1.10–13 et §10.3 : outbox, acquittements, reprises ; envois_defis devient une vue |
| Paramètres T0–T7 et calendrier | configurations_service | §10.1.3/10.4 : version approuvée référencée par tâche/session/transmission ; valeurs proposées conservées |
| Justification support_only | demande_support_id, autorise_par sur sessions_authentification | §2.29 : demande identifiée, agent autorisé, périmètre limité |

Une tâche de travail distincte est créée à la soumission d’une version KYC ou à la réception d’une demande support. La réaffectation conserve premières dates et échéances ; elle ajoute un événement. Une décision needs_fix termine la tâche de cette version ; le dépôt suivant crée une nouvelle tâche. Les états attente_demandeur/attente_tiers ne remplacent pas les statuts métier du dossier.

Les délais T0–T7 sont proposés, pas déjà approuvés. Avant activation, un compte disposant de configurer_service approuve la version des règles et du calendrier. Les suppléants sont des comptes liés par suppleances_service et doivent posséder leurs propres permissions valides ; cette nomination ne donne aucun privilège supplémentaire.

## 8. Recette organisationnelle et suivi de service

| Scénario | Résultat observable attendu |
|---|---|
| Inscription réussie, e-mail retardé | Compte pending, message clair, renvoi limité et incident consultable ; aucune licence ni activation anticipée |
| Compte pending se connecte | Session limited et parcours de vérification ; paiement refusé |
| Défi expiré ou présenté deux fois | Refus contrôlé sans seconde activation ; nouvelle demande possible sous quota |
| Reset d’un compte suspendu | Mot de passe changé selon éligibilité, sessions anciennes invalides, suspension maintenue |
| Revendication d’un auteur homonyme | Aucun rattachement automatique ; décision humaine appuyée par preuve |
| Dossier incomplet ou agent absent | Guidage du déposant ou réaffectation à suppléant ; pas d’approbation tacite |
| MOD impliqué dans le sujet KYC | Conflit détecté et dossier réaffecté à un agent habilité indépendant |
| Correction après needs_fix | Ancienne version intacte, nouvelle version identifiable, nouvelle échéance de première réponse |
| Renouvellement en retard | Droits commerciaux bloqués à échéance, lecteur personnel non suspendu automatiquement |
| Transfert de principal contesté | Aucun accès accordé sur simple demande ; demande suivie, principal unique |
| A4 ne reçoit pas une suspension | Accès refusé par état courant ; incident visible et propagation reprise sans effacer les licences |
| Recours support_only | Documents limités au titulaire, aucune publication/paiement/lecture normale |
| Clôture du principal d’une organisation | Représentation résolue avant clôture ; accès révoqué et historique préservé |

Indicateurs proposés : délai de première prise en charge KYC, délai de première décision par version, âge des dossiers en attente de tiers, taux de demandes de correction, envois en échec après reprises, nombre de transmissions critiques non acquittées et âge du plus ancien incident. Distinguer temps ouvré, temps calendaire et attente du demandeur. Revue des files chaque début de plage ; bilan hebdomadaire par le responsable du service. Ces indicateurs suivent la qualité de service, sans imposer de quota d’approbation.

## 9. État et prochaines actions

Le MOT couvre les 24 opérations historiques du MCT A1 et ajoute A1-26/A1-27, soit 26 opérations. Ses règles métier proviennent de la source ; horaires, délais, répartition des fonctions et supports d’exploitation sont des propositions explicites. Aucun test opérationnel ni recrutement n’est présumé réalisé.

Avant lancement : affecter responsables et suppléants, confirmer T0–T7, renseigner les exigences documentaires par pays/service, prévoir P6 et l’accès privé aux preuves, implémenter les supports désormais référencés au §7, puis exécuter les scénarios du §8. Le MPD traduira les structures d’affectation, de reprise et de contrôle définies dans le MLD V1.1, en les adaptant au projet existant.

## Annexe — Traçabilité des opérations

| Opération source | Fiche MOT | Poste principal | Mode |
|---|---|---|---|
| A1-01 | §4.1 — Choisir son parcours | VIS · P1 | I |
| A1-02 | §4.2 — Créer le compte et ses preuves | VIS puis SYS · P1/P4 | I |
| A1-03 | §4.3 — Préparer la vérification obligatoire | SYS · P4 | A |
| A1-03a | §4.4 — Émettre ou renvoyer le défi | SYS et MAIL · P4/P5 ; titulaire P1/P2 | A |
| A1-04 | §4.5 — Vérifier et activer le compte | Titulaire puis SYS · P1/P4 | I |
| A1-05 | §4.6 — Demander une récupération | Demandeur puis SYS · P1/P4 | I |
| A1-06 | §4.7 — Présenter la preuve de récupération | Titulaire puis SYS · P1/P4 | I |
| A1-07 | §4.8 — Réinitialiser le mot de passe | Titulaire puis SYS · P1/P4 | I |
| A1-07a | §4.9 — Changer volontairement son mot de passe | Titulaire puis SYS · P2/P4 | I |
| A1-08 | §4.10 — Activer le parcours auteur | Compte actif puis SYS ; MOD si revendication · P2/P3/P4 | I puis H si nécessaire |
| A1-09 | §4.11 — Enregistrer la réponse à un texte versionné | Titulaire puis SYS · P1/P2/P4 | I |
| A1-10 | §4.12 — Créer ou reconnaître une organisation | Compte actif puis SYS ; SUP si existante · P2/P3/P4 | I puis H si nécessaire |
| A1-10a | §4.13 — Installer le représentant principal déclarant | SYS · P4 | A |
| A1-11 | §4.14 — Constituer et soumettre une version KYC | Représentant ou vendeur individuel puis SYS · P2/P4 | I |
| A1-12 | §4.15 — Contrôler l’organisation et décider | MOD · P3 ; SYS P4 | H |
| A1-13 | §4.16 — Activer le partenaire commercial | MOD habilité commerce · P3 ; SYS P4 | H |
| A1-14 | §4.17 — Surveiller et renouveler les habilitations | SYS puis MOD · P4/P3 ; titulaire P2 | A puis H |
| A1-15 | §4.18 — Transférer la représentation principale | SUP habilité · P3 ; MOD consulté | H |
| A1-20 | §4.19 — Ouvrir une session de portée contrôlée | Titulaire puis SYS · P1/P4 | I |
| A1-21 | §4.20 — Fermer ou expirer les sessions | Titulaire ou SYS · P2/P4 | I ou A |
| A1-22 | §4.21 — Suspendre un compte et couper ses accès | ADM ou SYS selon règle autorisée · P3/P4 | H ou A |
| A1-23 | §4.22 — Réexaminer et réactiver un compte | SUP habilité · P3 ; ADM consulté si sécurité | H |
| A1-24 | §4.23 — Clôturer le compte | SUP habilité · P3 ; titulaire P2/P6 | H |
| A1-25 | §4.24 — Recevoir un recours après suspension | Titulaire et SUP habilité · P6/P3 ; SYS P4 | H puis I |

Empreinte SHA-256 de la source examinée : `b7c41652c741f077d6fb5455b2e051d76e95655602ea85c6b6e28cf05822534e`.

## 10. Vérification de l’alignement V1.1

Les 24 fiches A1 conservent leurs déclencheurs et résultats et disposent de références au MLD. Les six besoins organisationnels relevés (affectation, recours, permissions internes, configuration, notifications/transmissions, reprise du secret des défis) ont un support défini. Les fonctions différées du MCT restent différées.

Recette additionnelle : attribution concurrente, réaffectation sans effacer l’échéance, demande anonyme puis identité vérifiée, session support_only liée au dossier, suppléant sans privilège implicite, notification répétée sans second effet métier, reprise de mail chiffré et purge. Voir MLD §10.7. Cette vérification est documentaire ; ni migrations ni tests de service n’ont été exécutés.


## A1-26 — Autoriser un document de recours

**Mode :** H puis I. **Poste :** A1-P3 SUP / A1-P6 titulaire.

**Déclencheur :** Identité de recours vérifiée et besoin documentaire précis.

**Contrôles :** traiter_support + autoriser_recours ; propriétaire ou mandat vérifié ; session support_only valide et bornes de grant.

**Transaction / données :** Créer acces_documents_support et événement, pointer le document exact ; lecture via A3-21s.

**Résultat et reprise :** Grant seul, pas de paiement/publication/lecture. Accès refusé après expiration/révocation ; chaque usage est tracé.

**Contrat :** Contrats_inter_chaines_V1_3.md ; recette transversale dans Guide_MPD_V1_3.md.


## A1-27 — Recevoir un examen de compte A6

**Mode :** A puis H. **Poste :** A1-P4 puis A1-P3.

**Déclencheur :** Instruction A6-22 authentifiée.

**Contrôles :** Source, intention, compte et références exacts ; aucun chargeback ne vaut suspension.

**Transaction / données :** Déduplication receptions_messages + demande support examen_compte_conformite + tache_service et événement ; décision habilitée via A1-22/23 si nécessaire.

**Résultat et reprise :** Réponse stable liée à instruction ; résultat distinct d’une réception, reprise sans seconde décision.

**Contrat :** Contrats_inter_chaines_V1_3.md ; recette transversale dans Guide_MPD_V1_3.md.


## Contrôles inter-chaînes obligatoires V1.3

Les règles CT-01…CT-08 sont des parties normatives de ce modèle. Propriétaire fonctionnel : A1. Base logique V1 commune, identités partagées et références canoniques du lot.

- CT-01 : contrôle actuel de session, permission, rôle et portée ; exceptions documentaires/phase nommées seulement.
- CT-02 : état métier + message atomiques ; déduplication intention/référence ; acquittement local distinct du résultat propriétaire.
- CT-03 : chaque lecteur/écrivain de cibles consulte les causes communes et leurs dates ; l’index n’est jamais l’autorité.
- CT-04/05 : acquisitions, extensions, argent et compensations restent propriétaires A4/A3 ; autre origine préservée.
- CT-06 : droits d’acte/version actuels ; offre sous restriction géographique non applicable reste en attente V1.
- CT-07 : création/rattachement de preuve appelle inscription conservation ; propriétaire ne détruit qu’après recontrôle sérialisé de tous gels/politiques.
- CT-08 : titre privé minimal autorisé ; secrets temporaires hors journaux ; aucun grant de recours ne permet de lire le livre.

Les tables support A1 taches_service restent réservées KYC/support : files des autres chaînes fondées sur leurs propres états. Les postes numérotés sont locaux, qualifiés A1-P… dans le MOT global. Les nouveaux traitements V1.3 complètent la couverture du MCT historique ; la recette transversale X01…X24 et ses adaptations figure au Guide MPD.
