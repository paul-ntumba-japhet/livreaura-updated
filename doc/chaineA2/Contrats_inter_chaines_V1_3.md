# Contrats inter-chaînes — V1.3 consolidée — Afrique-Educ

7 septembre 2026. Partie normative des douze MLD/MOT V1.3. Les recommandations de l’audit F01–F15 et Q1/Q2 sont appliquées sur demande du porteur. Le lot prépare le MPD ; les critères applicatifs restent à implémenter et tester.

## CT-00 — Architecture logique et périmètre de livraison

V1 : application modulaire avec base relationnelle commune A1–A6. Les identités et registres ci-dessous sont partagés ; les services restent responsables de leurs écritures. Ce choix de consolidation évite les copies concurrentes d’autorité. La distribution en bases séparées serait une évolution d’architecture nécessitant d’autres contrats, hors du présent MPD V1. Stockage de fichiers et PSP sont des systèmes externes et gardent des confirmations distinctes.

Noms de relations logiques conservés ; un renommage physique éventuel exigera une table de correspondance sans double identité. FK SQL inter-chaînes possibles dans cette base ; RESTRICT/NO ACTION sur références historiques, suppression selon CT-07. Les valeurs courantes ne réécrivent pas les preuves.

Achat papier/numérique/audio, gratuit direct numérique/audio, publication et lecture en ligne sont modélisés. Location, hors-ligne, institutionnel complet, label éditeur AE, coédition multi-vendeurs et royalties détaillées ne sont pas activés. Les anciennes valeurs de catalogue réservées ne créent aucune capacité transactionnelle. Les périmètres géographiques limités sont déclarables mais non activables dans cette V1 (CT-06).

Logistique papier détaillée hors A1–A6 : besoins_preparation constitue l’interface. Avant activation du papier, un service de préparation/livraison et ses règles de retour doivent être disponibles ; sinon capacités papier transactionnelles désactivées, sans inventer une livraison depuis le seul paiement. Le schéma papier peut être créé au MPD avant activation de ce service.

## CT-01 — Identités, autorisations et portée

AUTH : session du compte présenté, comptes.statut=active, session.portee=normal, session.statut=active, revoque_a NULL, maintenant strictement avant expire_absolue_a ET expire_inactivite_a. L’identité vient de la session vérifiée. Les contrôles sont répétés au point d’engagement ; contrôle antérieur et rôle général ne suffisent pas.

Interne : AUTH, comptes_roles active compatible, attributions_internes attribue_a ≤ maintenant, non révoquée et non expirée, portée de dossier/objet actuelle et indépendance si décision concernée. Organisation : AUTH, adhésion et organisation opérationnelles, permission organisationnelle actuelle, mandat et sujet réels. Vendeur : même sujets_kyc, habilitation partenaire_commercial valid non échue, organisation_verifiee si nécessaire, fiscal/reversement conformes au dossier courant. KYC expiré bloque les nouvelles opérations commerciales sans dépublier automatiquement ni révoquer les acquisitions.

AUTEUR.auteur_id reste une identité catalogue A2 ; compte_id nullable unique. A1-08 retrouve/crée la même fiche draft après contrôle, jamais published implicitement. Auteur, éditeur public et vendeur contractuel sont distincts.

### Catalogue canonique des permissions

Codes stockés dans permissions_internes ou permissions_organisation A1 ; les lignes suivantes sont des capacités à configurer, aucune attribution effective n’est créée par les documents. Une ligne comportant plusieurs codes signifie des permissions distinctes, pas une permission composée implicite.

| Codes | Contexte | Rôles compatibles | Portée et usage |
|---|---|---|---|
| affecter_taches | Interne | moderator, support, admin | Affectation A1 et règles d’affectation A5/A6 ; aucune décision ni accès à toutes pièces par ce seul code |
| controler_kyc, decider_kyc, decider_kyc_commercial | Interne | moderator, admin | Dossier KYC attribué, indépendance et contrôles de niveau |
| restreindre_habilitation | Interne | moderator, admin | Sujet KYC et cause motivée |
| verifier_representation, transferer_principal | Interne | support, moderator, admin | Sujet/organisation de demande vérifiée |
| suspendre_compte | Interne | admin | Compte exact, décision motivée |
| reactiver_compte, cloturer_compte | Interne | support, admin | Compte/demande vérifiés et conditions A1 |
| traiter_support, autoriser_recours | Interne | support, admin | Demande et ressources exactes ; aucun droit commercial |
| gerer_habilitations_internes, configurer_service | Interne | admin | Gestion tracée, aucune auto-promotion publique |
| reprendre_transmissions | Interne | support, admin | Livraison seulement, même référence/intention |
| gerer_vitrine, moderer_avis | Interne | moderator, admin | Sélections/avis ; pas de licence ni KYC implicite |
| reprendre_traitements_catalogue | Interne | support, admin | Reconstruire les projections autorisées, pas modifier les décisions |
| decider_remboursement, executer_remboursement | Interne | support, admin | Dossier/paiement/lignes autorisés, montant borné |
| gerer_facturation | Interne | moderator, admin | Documents/circuit ; pouvoir légal indépendant |
| attribuer_droit, revoquer_origine_droit, gerer_blocages_lecture, traiter_alertes_lecture, revoquer_appareil_tiers | Interne | moderator, support, admin | Cible A4 exacte ; chaque mesure exige son code |
| consulter_publications, controler_publication, decider_publication, gerer_retraits_publication, approuver_extension_contenu | Interne | moderator, admin | Dossier/objet A5 autorisé et indépendance ; pas de levée d’une cause A6 par simple permission A5 |
| consulter_conformite, instruire_signalement, decider_mesure_conservatoire, decider_retrait | Interne | moderator, admin | Dossier/phase A6, indépendance à décision |
| instruire_contestation_paiement, coordonner_compensation | Interne | moderator, support, admin | Dossier financier/conformité explicite ; A3 reste autorité des fonds |
| demander_examen_compte | Interne | moderator, support, admin | Demande A6 vers A1 ; aucune suspension présumée |
| gerer_gels_conservation, gerer_politiques_conservation | Interne | moderator, admin | Causes/politiques explicites, validation et traçabilité |
| deposer_kyc | Organisation | Rôle compatible avec organisation et adhésion | Dossier de sa structure |
| deposer_publication, consulter_publication, soumettre_publication, retirer_publication | Organisation | publisher ou autre rôle d’organisation explicitement habilité | Projet appartenant à la structure, action séparée par code |
| repondre_conformite | Organisation | Rôle compatible avec mandat fournisseur | Partie/phase/cible concernées ; aucun montant ni droit de lecteur |
| publier_offre, demander_reversement | Organisation, réserve de capacité | Selon chaîne propriétaire et habilitation commerciale | Aucun chemin d’activation autonome : publication via A5, reversement détaillé hors lot |

L’ancien exemple deposer_manuscrit n’est pas un code V1 actif : reprise vers deposer_publication seulement après vérification de l’attribution existante, sans accorder soumettre/retirer. Personnel auteur : rôle author et propriété, sans adhésion fictive. Une suppléance ne donne aucune permission additionnelle.

Affectation A5/A6 : affecter_taches et périmètre autorisé du responsable d’affectation, ou règle technique approuvée versionnée. Écrire affectation et accès de dossier ensemble ; attribuer uniquement à un compte ayant les permissions nécessaires. Réaffectation ferme la période précédente. La permission attribuée reste indépendante de cette portée. Pas de détournement de taches_service A1 hors KYC/support.

AUTH_SUPPORT : session A1 support_only du titulaire vérifié de demande_support, active/non révoquée, deux échéances valides ; statut du compte compatible avec ce canal de recours (suspended), jamais restauration normal. Pour document A3, acces_documents_support doit en plus identifier exactement ressource, titulaire, période et autorisation. Une page ouverte n’est pas une autorisation de téléchargement ultérieur.

ACCES_REPONSE : accès A6 vérifié de partie/phase, valide/non révoqué et secret prouvé si requis ; qualité/mandat actuel et action permise. Fonctionne indépendamment d’une session normale A1 et n’est ni un accès documentaire A3 ni un droit de lecture A4. Fermeture de phase révoque ses grants ; recours recevable exige nouveau grant de la phase suivante. Les anciennes preuves restent conservées.

## CT-02 — Messages, réceptions et résultats

Enveloppe commune : producteur, identifiant stable d’événement/instruction, type, operation, schema_message_version, corrélation, objet/cible, version/révision pertinente, empreinte d’intention, dates d’effet/fin si applicables et données minimales vérifiées. Même clé avec autre intention = conflit ; jamais remplacer silencieusement. La corrélation peut regrouper plusieurs messages et ne sert pas de clé unique d’effet.

État métier et messages_sortants dans la même transaction. receptions_messages est unique par producteur/événement/consommateur ; conserve l’empreinte et le minimum de données versionnées nécessaire au traitement différé. Une réception technique incomplète n’est pas marquée traitée. Une étape locale validée ou un travail durable identifiable permet de renseigner traite_a et son niveau. Les réceptions PSP restent authentifiées par leur connecteur et leur modèle A3.

| Niveau | Sens | Persistance et confirmation |
|---|---|---|
| recu_durable | Instruction conservée, non encore exécutée | receptions_messages ; reçu, traite_a NULL tant qu’aucun travail/traitement local n’est engagé |
| engage_local | Mutation locale ou travail durable créé, transaction validée | receptions_messages.niveau_acquittement, reference_resultat ; transmission interne acked admissible |
| effet_confirme | Propriétaire a constaté l’effet à sa date sur la cible | confirmations_effet, suivis_instructions_externes, résultats A3/A4/A5/A6 ; message de confirmation séparé |

transmissions_messages.niveau_acquittement reprend le niveau prouvé. acked sans confirmation propriétaire ne signifie pas que l’effet distant est terminé. Une simple acceptation e-mail ne prouve ni livraison ni lecture ; A6 faits_notification porte les preuves de livraison/accusé et ses échéances. Les anciennes confirmations ne régressent pas un état plus récent.

Messages A1 produits par transitions existantes : compte_acces_modifie, session_acces_modifie, permission_interne_modifiee, auteur_compte_lie, habilitation_commerciale_modifiee. A2 invalide les projections concernées ; A3 recontrôle éligibilité ; A4 coupe accès selon état courant ; A5 recontrôle activation. Aucun ancien message active/valid ne rouvre un état révoqué. Chaque consommateur nommé a sa propre transmission/réception, sans pièce KYC ni secret de session dans la charge.

Le récepteur conserve en attente les résultats dont les références métier ne sont pas encore rapprochées et dispose d’un travail durable à reprendre. Un worker consulte ces réceptions ou l’état métier correspondant ; pas de payload uniquement en mémoire. Les tentatives et refus ne sont pas effacés pour simuler un nouveau résultat.

## CT-03 — Causes partagées, levées et concurrence

restrictions_conformite A6 est le registre partagé des causes catalogue/diffusion/titre. Une cause provient exactement d’une ligne restrictive A6 ou d’une instruction autonome A5. Elle est créée dans la transaction d’autorisation métier ; la création porte l’empreinte et les cibles de sa source. Les relais A5 utilisent restriction_source_id, sans seconde cause. Compte/licence bloqués pour sécurité restent dans blocages_acces A4 et s’ajoutent aux autres conditions, sans conversion en retrait catalogue.

La cause est opposable si source validée, debut ≤ t, fin absente ou t < fin, et aucune levees_restrictions.effet_a ≤ t. La date de décision de levée seule ne termine rien avant sa date d’effet. Une cause arrivée à fin ou levée n’est pas réouverte ; toute nouvelle mesure reçoit une autre identité. Une levée enregistrée avant livraison de restriction est déjà visible dans la base commune et reste opposable à tout rejeu.

| Effet canonique | Cible admissible | Propriétaire du contrôle et récepteur |
|---|---|---|
| arret_ventes | Offre ou parent Œuvre/Édition | A3 à checkout/initiation/gratuit ; A2 V-F ; A5 activation |
| retrait_catalogue | Œuvre, Édition ou Offre | A2-90, toutes projections/accès publics dépendants |
| masquer_media_public | MEDIA_CATALOGUE | A2-90, endpoint et métadonnées du média |
| masquer_avis | AVIS | A2-90 ; liste et agrégats excluent le masque, statut éditorial conservé |
| interdiction_diffusion | VersionContenu exacte | A4-43 ; MEDIA à chaque nouvelle unité autorisée |
| masquer_titre_prive | Œuvre, Édition, Offre ou VersionContenu | A4 bibliothèque ; libellé neutre CT-08 |

A6 lignes utilisent arreter_ventes/retirer_catalogue/interdire_diffusion : ces verbes sont mappés aux codes ci-dessus. A5 utilise arret_ventes/retrait_catalogue/interdiction_diffusion. Une levée n’est pas un septième effet de restriction : elle référence la cause. Pour une mesure provisoire fin obligatoire et cause réversible ; aucune révocation terminale.

bloque(cible,effet,t) = existence d’une cause opposable de cet effet visant la cible ou un parent applicable. Parent Œuvre couvre ses Éditions/Offres ; parent Édition ses Offres. Le retrait d’un parent empêche aussi les médias/avis publics dépendants de sa fiche. Une interdiction de diffusion est développée en versions exactes autorisées, jamais déduite d’un simple retrait public. L’activation d’un nouvel objet sous parent encore restreint doit consulter le parent ; developpements_portee trace les enfants connus, pas une autorisation d’échapper au parent. Masquer un avis n’interdit pas toute l’Œuvre.

actif, consultable, statut et retire_a de base ne sont pas écrasés par une cause temporaire. Une levée rend seulement cette cause inopposable ; publication/KYC/état technique et autres causes sont encore vérifiés. Avis masqué published reste non rejeté et occupe son unicité ; une nouvelle soumission est refusée comme doublon. Un avis rejected par modération ne redevient pas published à la levée d’un masque.

### Coordination transactionnelle V1

verrous_coordination A1 fixe un ordre de barrières : identite, catalogue, finance, conservation. Prendre les domaines nécessaires dans cet ordre avant les lignes métier, puis les lignes par clé stable. Tous chemins qui écrivent ou valident la même autorité utilisent la barrière correspondante : compte/permissions → identite ; publication/retrait/départ de délivrance et droits → catalogue ; encaissement/refund/gel → finance ; inscription/rattachement/gel/destruction → conservation. Une transaction nécessitant plusieurs domaines les prend dès son début dans l’ordre, puis revalide après acquisition. Ce mécanisme logique peut être raffiné au MPD si la même sérialisation est prouvée.

Aucun verrou de transaction longue pendant conversion, notification, PSP ou streaming. L’engagement d’une nouvelle unité de diffusion est autorisé sous barrière catalogue ; les octets déjà autorisés/transmis ne sont pas récupérables. Une unité suivante exige un nouveau contrôle. Le départ irréversible de conservation suit CT-07 et sa primitive propriétaire ; il ne se réduit pas à un message de suppression en file.

Confirmation : A2/A4/A3 lisent le registre au point d’effet, confirment cause/cible/instant et état effectif. A5 relaie sans devenir seconde autorité de catalogue ou licence. Réception d’une instruction future = attente jusqu’à date ; l’autorité temporelle s’applique à date sans dépendre du worker. Succès global seulement lorsque toutes confirmations requises sont connues ; état actuel d’une cible reste recalculé.

## CT-04 — Acquisitions, résultats et extensions A4

Acquisition A3 : référence canonique = producteur A3 + identifiant demandes_attribution_droit.id sérialisé de manière stable. La clé d’idempotence demeure celle de la demande et l’empreinte porte compte/offre/ligne/origine/preuve. Ne pas utiliser licence_id comme identité d’acquisition. A6 révocation cible ce producteur/référence ; A4-40 retrouve references_acquisition même avant attribution.

A3 conserve reference_acquisition_a4_id, origine_a4_id et licence_a4_id séparément ; concordance contrôlée. Le premier peut exister sans les deux autres pour annulation préalable. L’ancienne reference_droit_a4 n’est qu’une donnée à rapprocher lors d’une migration d’existant, jamais une identité ambiguë à recopier dans ces trois champs.

| Réponse A4 | Demande attribution A3 | Demande révocation A3 | Résultat métier |
|---|---|---|---|
| Reçue / dépendance technique en attente | transmise/en_attente | transmise/en_attente | recu / en_attente ; pas de droit confirmé |
| Attribution réellement active | confirmee | Sans objet | attribution_active, origine/licence exactes |
| Attribution déjà annulée | annulee | Sans objet | acquisition_annulee ; pas d’activation fugace |
| Révocation future reçue | Sans objet | en_attente | future_enregistree, date d’effet |
| Origine révoquée ou déjà révoquée | Sans objet | confirmee | origine_revoquee |
| Annulation préalable durable | annulee si concerne attribution reçue ensuite | confirmee | annulation_prealable, référence sans origine possible |
| Refus métier | refusee | refusee | refusee + motif, pas de boucle automatique de panne |
| Incident technique | en_erreur ou attente de rapprochement | en_erreur ou attente | incident ; reprise même identité après diagnostic |

confirme_a date seulement une issue réellement confirmée ; repondu_a trace aussi les retours non terminaux/refus. resultats métier et retours passés sont conservés dans messages/réceptions/événements, sans régression après une réponse ancienne. Gratuit direct et achat à zéro restent deux origines source distinctes ; la révocation est terminale pour l’origine, pas pour toutes les acquisitions d’une offre.

Extension A5-04e → A4-01e : décision + référence d’acquisition + version figées. A4 sérialise avec révocation, vérifie origine existante active, source approuvée, concordance édition/format et droits CT-06. Insérer/retrouver receptions_extension_contenu ; autorisation origine/version + événement + réponse atomiques. Déjà autorisée de manière concordante = succès sans doublon. Origine absente, expirée, révoquée ou source non recevable = refus motivé ; panne technique = attente, pas refus inventé. L’extension ne crée pas l’origine. A5 agrège les résultats de toutes cibles : attente si réponse manquante, succès total/mixte/refus total seulement quand complet.

## CT-05 — Compensations, gels et autorité financière

A6-30/32 → A3-30c : demande immuable, initiateur réel, commande/lignes, justification, effet droit demandé, référence stable, empreinte et éventuel dossier_reutilisation_id. A6 n’autorise aucun montant souverain. A3 commence par receptions_compensation sous unicité avant ouverture. Rejeu identique retrouve le dossier et le résultat, même si la réponse a été perdue ; intention différente refusée.

Règle de rapprochement : si une demande identique est connue, même résultat. Si un dossier de réutilisation est indiqué, vérifier même commande et opération/autorisation/lignes ; un agent decider_remboursement consigne decisions_rattachement_compensation avant liaison. Si une nouvelle référence concerne des lignes ayant déjà une compensation ouverte, autorisée, inconnue ou réussie, maintenir en_examen pour rapprochement humain avant nouvelle autorisation. Si aucune opération ne chevauche, créer un dossier indépendant. Plusieurs dossiers pour une même commande sont possibles ; les plafonds de ligne/paiement sont toujours globaux et une nouvelle référence ne réinitialise aucune capacité.

dossiers_remboursement : bénéficiaire = propriétaire de commandes, demandeur_id réservé au client réellement demandeur ; initiateur_compte_id ou origine_systeme indique l’acteur de l’ouverture. Le réexamen utilise dossier_precedent_id et décision nouvelle, pas réécriture. Une instruction A6 autoriser_refund demande examen/exécution A3 ; decider_remboursement et le plafond de décision A3 restent requis.

Retour A3 : dossier et résultat d’ouverture ; A6 crée liaisons_compensation après confirmation même commande. Les faits ultérieurs utilisent resultats_compensation unique (producteur,reference_fait) et rattachements_resultats_compensation vers toutes demandes concernées. Une nouvelle liaison récupère aussi les faits antérieurs. Un résultat reçu avant sa liaison reste dans la réception durable à rapprocher. La monnaie, l’avoir et le droit constituent des faits séparés. Un fait A4 sans rattachement financier certain reste à rapprocher, jamais affecté par simple compte/offre.

A6-21/23 → A3-05g : instruction financière, cause, cibles, opérations_bloquees, effet_a, fin/levée éventuelles. La cause autorisée est dans gels_operations_financieres ; une levée porte gel_leve_id et effet_a explicite. operations_bloquees est une sélection non vide des codes refund_vente et refund_excedent ; ces codes couvrent les chemins humains et automatiques, et excluent le rapprochement. A3 contrôle sous barrière finance, au dernier engagement du refund : tout gel applicable bloque l’opération ciblée. Comparer debut/fin/leve_effet_a, pas la seule date de décision leve_a. Toutes les causes se cumulent ; aucune clôture de litige n’en lève une implicitement. Le rapprochement continue.

L’engagement du refund est la création transactionnelle de sa capacité réservée pending autorisée. Un gel antérieur interdit cet engagement. Un gel postérieur ne prétend pas rappeler une opération déjà engagée : conserver pending/unknown et rapprocher son résultat, bloquer les nouveaux engagements selon portée. Réservations financières succeeded/pending/unknown et chargebacks rapprochés bornent les sommes, même si un gel est levé. Un échec certain permet une nouvelle tentative sous nouveau contrôle. Les mouvements PSP ne sont pas doublés par la réception de plusieurs notifications.

## CT-06 — Droits de vente et diffusion applicables en V1

Décision de lancement retenue : déclarations territoriales conservées, mais mode_territoires=selection ne peut activer une offre/version en V1. Motif TERRITOIRE_NON_SUPPORTE et exécution hold ; absence de déclaration ne vaut jamais monde. Un périmètre monde doit être expressément déclaré, prouvé et accepté. Aucune localisation déduite d’une devise, d’une IP ou d’une adresse ne sert d’autorisation fictive.

droits_offres_publication et droits_versions_publication lient les sorties à leurs périmètres acceptés. Les relations appartiennent à A5 et sont consultées par A3/A4. Un lien actif exige dossier accepted, exécution published (ou candidat d’activation dans sa transaction), cibles/édition/format/acte couverts, mode monde et dates applicables. Les droits s’interprètent acte par acte ; une sélection territoriale ne devient pas monde parce qu’un autre texte existe sans preuve équivalente.

VENTE(offre,t) : au moins un périmètre de vente actif couvrant exactement cette offre/support/édition, applicable à t et mode monde, et absence d’autre condition restrictive non satisfaite dans le dossier validé. A3 recontrôle avant nouvelle commande, initiation et gratuit ; A5 avant activation. DIFFUSION(version,t) : périmètre de diffusion actuel validé lié à cette version, mode monde et bornes satisfaites ; A4 le vérifie pour nouvelle attribution/extension et départ de nouvelle unité. Offre à prix nul suit les mêmes autorisations.

Vente et diffusion peuvent avoir des périodes différentes : expiration du droit de vendre arrête les nouvelles ventes sans effacer les origines ; expiration/interdiction de diffusion bloque les nouvelles unités pour la version concernée sans transformer les origines en revoked. Une attribution provenant d’un achat déjà engagé conserve sa preuve même si les contenus sont momentanément indisponibles ; A3/A6 coordonnent la compensation si nécessaire.

Régularisation d’anciens contenus : créer un dossier de provenance/autorisation accepté avec ses liens avant activation V1 ; ne pas fabriquer de preuve à partir du seul statut published. Le futur support territorial exige une évolution du contrat et des tests avant activation des dossiers actuellement en attente.

## CT-07 — Conservation et barrières propriétaires

Chaque adaptateur valide identité/type/version/catégorie, existence réelle et preuve. Les champs de référence ne sont pas un droit d’exécuter une requête ou une suppression arbitraire. Liste de familles autorisées :

| Propriétaire | Objets / clés sources | Catégories et preuve |
|---|---|---|
| A1 | comptes, événements, dossiers/documents KYC, demandes/pièces support, messages/tentatives ; id et version/empreinte | identité, kyc, support, transmission ; secret temporaire séparé |
| A2 | Œuvre/Édition/Offre/Auteur/AVIS/MEDIA_CATALOGUE et version/empreinte de la projection conservée | catalogue, avis, media_public ; dérivé conservé reste privé si retiré |
| A3 | commandes/lignes, paiements/mouvements, factures/avoirs, dossiers/décisions/remboursements et documents externes | preuve_commerciale, preuve_financiere ; cohérence des références comptables et états |
| A4 | origines, versions/manifeste/segments, journaux/alertes, métadonnées privées et fichiers filigranés | droit, lecture, media_prive, trace_securite |
| A5 | dossiers/décisions/sources/pièces, productions/exécutions et fichiers versionnés | publication, preuve_droits, source_privee |
| A6 | signalements/phases/décisions, pièces, notifications/retours, faits et actions | conformite, litige, preuve_execution |

À création ou rattachement d’un objet pertinent : inscription objets_conserves avec adaptateur_id, liens dossiers et application de politique valide si disponible ; les nouveaux objets entrent dans les gels de leurs dossiers/catégories avant possibilité de purge. Sans politique validée, conservation en attente d’adoption, aucune destruction. Un objet partagé a tous ses gels indépendants ; leur liste matérialisée ne suffit pas si une liaison récente attend extension.

À gel/levée : contrôle de permission ou règle validée, motif et cause, exclusion conservation ; écrire registre, liens/projection et message. En base V1 les propriétaires consultent directement cette autorité avant purge. Suivi par objet/type propriétaire dans suivis_instructions_externes ; confirmation de protection seulement si la barrière est réellement disponible. Aucun succès global si certains objets ne sont pas couverts. Closure de dossier ne lève rien.

À échéance : A6-41 crée une action d’attente, pas une autorisation irréversible. Le propriétaire prend la coordination commune de l’objet, relit politique/application/version, départ, date, tous gels/dossiers et dépendances. Une primitive propriétaire sérialise ouverture de gel et départ irréversible de suppression/anonymisation. Une simple sélection puis envoi asynchrone non protégé ne satisfait pas le contrat. Tant que cette primitive n’est pas validée dans adaptateurs_conservation, destruction refusée ; le MPD/l’architecture de stockage doivent l’implémenter et la tester.

Le point de départ irréversible est attesté dans la preuve d’action avec ordre/instant propriétaire. Gel arrivé avant : acte interdit. Gel arrivé après : déclarer l’acte déjà engagé et sa portée, ne pas affirmer que les octets étaient protégés ou déjà détruits avant confirmation. Aucun verrou SQL n’est maintenu pendant une attente réseau ; une coordination propriétaire avec exclusion/fencing doit préserver l’ordre au départ de l’acte. Résultat inconnu reste non confirmé. Nouvelle tentative exige recontrôle et même identité d’action ; destruction déjà constatée donne résultat idempotent.

A6-41c constate résultat réel, version et périmètre ; trace minimale indépendante sous sa propre politique, aucun recopiage secret des données effacées. Sauvegardes/répliques incluses dans la politique et les capacités déclarées ; une suppression partielle ne vaut pas effacement complet. L’effacement d’une preuve n’autorise pas une cascade de décisions/transactions encore nécessaires.

Secrets temporaires A1/A6 : catégorie secret_transitoire exclue des gels de preuves ; préserver événements/empreintes métadonnées autorisées, pas la valeur réutilisable du secret. Purge à consommation/révocation/expiration/envoi certain selon sa source. Toute politique approuvée ne peut utiliser un gel général pour prolonger la validité ou la rétention du secret. Le matériel de preuve durable est un objet distinct.

## CT-08 — Bibliothèque privée et livraison des accès dédiés

Pour chaque origine achat/gratuit/attribution, metadonnees_bibliotheque conserve titre historique minimal autorisé, format, source et preuve. Après dépublication, AUTH et propriété restent obligatoires ; titre et état courant du droit peuvent être affichés dans la bibliothèque privée. Pas de couverture, extrait ou fiche publique retirés recopiés dans cette projection. À cause masquer_titre_prive applicable sur version/offre/édition/œuvre, ou faute de titre autorisé : libellé neutre « Ouvrage indisponible ». Cela ne révoque pas l’origine ; lecture dépend séparément de A4. Plusieurs origines d’une licence n’éludent pas un masque sur leur cible commune.

Pour une licence regroupée, sélectionner une métadonnée autorisée d’origine de cette licence de manière stable (capture_a puis origine_id), sans faire dépendre l’historique du prix ou titre actuel. États de droit/blocage/disponibilité sont recalculés. La conservation du titre suit CT-07 ; un titre supprimé par action autorisée n’est pas reconstitué depuis une autre copie pour contourner cette action.

Les documents commerciaux restent sous politique A3, avec leurs snapshots propres ; le titre bibliothèque n’est pas une facture. Les accès support A1 ou réponse A6 ne donnent pas accès au livre ni à toute sa bibliothèque.

Une notification contenant un secret d’accès A6 utilise secrets_transmission (source acces_reponse_id) et le contenu temporaire chiffré A1 ; clé hors base, échéance ≤ grant, vérification du grant à envoi et usage, purge. notifications_fournisseur.contenu_snap garde seulement le message non secret ou sa représentation expurgée. L’empreinte seule ne sert pas à reconstruire un secret perdu. Nouveau grant après fin de phase/expiration nécessite vérification et nouvelle trace.
