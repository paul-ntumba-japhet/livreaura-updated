# Livreaura — Cursor : améliorer l’interface après soumission d’un dossier de vérification

## Objectif

Améliorer l’expérience utilisateur de la page de vérification du compte Livreaura **après la soumission réussie d’un dossier**. Aujourd’hui, l’interface conserve le *wizard* de préparation et affiche simultanément « Dossier figé », « Dossier soumis », « Soumettre le dossier », « Aucune habilitation » et « Étape précédente ». Cet état est ambigu, trop technique et insuffisamment conclusif.

**Résultat attendu :** après confirmation par Laravel, montrer une **vue premium de confirmation et de suivi, en lecture seule**, plutôt qu’une étape de formulaire déjà terminée. Les écrans de création et de préparation restent inchangés sur le plan métier.

## Références et contraintes

1. Respecter les règles existantes de `.cursor/rules/`, en particulier le référentiel A1 et les règles du design system, ainsi que la charte Livreaura : **Coffee Black `#2B160D`, Orange `#FF8C00`, blanc, ivoire et tons sable**.
2. Inspecter d’abord les composants, routes Nuxt, composables/stores, états métier, endpoints Laravel et permissions **réellement existants**. **Ne pas inventer** de statut, route API, délai d’examen, droit ou habilitation.
3. Ne modifier **ni les règles KYC, ni les pièces figées, ni les preuves, ni les décisions, ni les autorisations**, ni les contrats d’API sans nécessité démontrée et autorisation explicite. Un dossier soumis ne peut pas redevenir éditable par un simple retour UI.
4. Réutiliser les composants et tokens existants. **Aucune photo, image de décoration ou illustration** ; utiliser si nécessaire des icônes vectorielles sobres. Conserver la topbar et la sidebar du compte.
5. Conserver l’accessibilité (WCAG, focus, labels, annonces accessibles) et le responsive. L’orange est une couleur de marque/action, **pas** un statut d’approbation.

## Travail préparatoire obligatoire : audit sans modification

- Identifier comment la soumission est confirmée par le backend : retour HTTP, identité du dossier, statut, version, date horodatée, éventuel statut d’examen et droits associés.
- Repérer la route actuelle, notamment `/compte/verification?dossier=...`, et la gestion de la navigation / du rafraîchissement.
- Lister les vrais états du dossier (brouillon, soumis, en examen, approuvé, refusé, demande de compléments, etc. **seulement s’ils existent**) et les actions autorisées pour chacun.
- Vérifier si les notifications et les habilitations commerciales sont effectivement prévues. Ne jamais les présenter comme acquises lors de la simple soumission.
- Établir un bref plan indiquant les composants réutilisés, ceux à ajuster, les fichiers à modifier et les scénarios de tests. Puis implémenter.

## Parcours utilisateur après soumission

1. L’utilisateur complète les pièces, le profil fiscal et le compte de reversement requis pour **ce type de dossier**, puis confirme la soumission.
2. Pendant la requête, afficher `Soumission en cours…` ; protéger contre le double clic et les envois répétés.
3. **Uniquement après confirmation positive et vérifiée par le serveur**, passer à une vue « Dossier soumis ». Préférer une route dédiée au suivi si le routage actuel s’y prête ; sinon utiliser une vue/état dédié sur la route existante. Le dossier doit rester retrouvable par son identifiant après rechargement ou reconnexion.
4. Ne **pas** rediriger immédiatement vers l’accueil, ni activer de redirection temporisée. Présenter une issue explicite : `Voir mes dossiers` (principal) et `Retour à mon compte` (secondaire).
5. Pour une réponse ambiguë (timeout / perte réseau), **ne pas** affirmer que l’envoi a échoué ni réémettre automatiquement une soumission potentiellement déjà enregistrée : relire l’état serveur avant de permettre une nouvelle tentative.
6. En cas d’échec confirmé : conserver le formulaire et les saisies autorisées, afficher un message exploitable ; ne jamais afficher une confirmation de succès fictive.

## Vue cible : confirmation premium (lecture seule)

Sous la topbar et la sidebar existantes, prévoir une carte principale d’environ **720–860 px** de largeur utile sur grand écran, centrée dans la zone de contenu (ou adaptée à la grille existante). Ne pas recycler le formulaire de l’étape 4.

**Ordre des éléments :**

1. Fil d’Ariane discret : `Mon compte / Vérification / Dossier [référence]`.
2. Icône de confirmation, dans un cercle discret, puis titre principal clair.
3. Court texte de confirmation ; **statut actuel distinct** et lisible.
4. Récapitulatif compact et structuré : référence du dossier (copiable si composant disponible), type/sujet, version et **date réelle de soumission** si l’API l’expose.
5. Section `Que se passe-t-il maintenant ?` : examen, documents figés et privés, consultation du suivi. Éviter les promesses de délais.
6. CTA primaire `Voir mes dossiers` ; lien secondaire `Retour à mon compte`.
7. Facultatif : lien discret `Consulter les détails du dossier`, uniquement si une vue autorisée existe. **Aucune action d’édition** sur une version figée.

**Style :** fond clair, espaces généreux, Noto Serif pour le titre éditorial si prévu par les tokens, Inter pour l’interface, icônes fines, bordures Soft Sand, ombres très discrètes. Ne pas multiplier les encarts bleu/vert/orange : **un message de réussite central** et **un badge d’état sémantique** suffisent. Le texte sur le bouton orange doit satisfaire le contraste (Coffee Black recommandé).

## Texte UI à implémenter (libellés français)

### Après soumission confirmée

- **Titre H1 :** `Votre dossier a été soumis avec succès.`
- **Texte d’introduction :** `Votre dossier de vérification a bien été reçu. Il sera examiné selon la procédure prévue.`
- **Badge :** afficher le **libellé du statut réel retourné par le serveur** : par exemple `Soumis`, et seulement si l’API le précise `En attente d’examen` ou `En cours d’examen`. **Ne pas prétendre que l’examen a commencé si ce n’est pas attesté.**
- **Récapitulatif :** `Référence`, `Type de dossier`, `Version`, `Soumis le` (date seulement si disponible, selon le fuseau/format de l’application).
- **Section :** `Que se passe-t-il maintenant ?`
  - **Examen du dossier :** `Votre demande sera examinée. La soumission ne vaut pas approbation.`
  - **Pièces protégées :** `Les pièces de cette version sont figées après soumission et restent privées.`
  - **Suivi :** `Vous pouvez consulter l’évolution du dossier depuis votre espace personnel.`
- **Action principale :** `Voir mes dossiers`
- **Action secondaire :** `Retour à mon compte`

### États transitoires et erreurs

| Situation | Message à afficher |
|---|---|
| Envoi en cours | `Soumission en cours… Ne fermez pas cette page.` |
| Confirmation indisponible / réseau interrompu | `Nous ne pouvons pas confirmer l’état de la soumission pour le moment. Vérifiez le statut du dossier avant de réessayer.` |
| Échec de soumission confirmé | `Le dossier n’a pas pu être soumis. Vérifiez les informations demandées et réessayez.` |
| Données du dossier indisponibles | `Impossible de charger les détails de ce dossier. Réessayez.` |
| Accès non autorisé | `Vous n’êtes pas autorisé à consulter ce dossier.` |
| Dossier en cours d’examen (si statut existant) | `Votre dossier est en cours d’examen. Les pièces de cette version ne peuvent plus être modifiées.` |
| Compléments requis (si prévu par le métier) | `Des informations complémentaires sont demandées. Consultez les instructions associées à la décision.` |
| Approuvé (si confirmé) | `Votre dossier a été approuvé.` |
| Refusé (si confirmé) | `Une décision a été rendue sur votre dossier. Consultez les détails et les recours disponibles, le cas échéant.` |

### Messages à supprimer ou déplacer **sur l’écran de confirmation**

- Ne plus afficher `Soumettre le dossier` ni sa case à cocher de confirmation après réussite.
- Supprimer `Étape précédente` dès lors que la version est figée.
- Remplacer l’alerte dominante `Dossier figé` par l’information discrète sur la protection des pièces.
- Ne pas afficher `Aucune habilitation` comme message principal : la vérification est une **demande**, pas l’octroi de droits. Si cette information est nécessaire, la déplacer dans un détail métier distinct.
- Ne pas cumuler plusieurs bandeaux de succès/information répétant la même chose.
- Ne pas afficher `Enregistrer le brouillon` pour un dossier déjà soumis.

## Stepper et états

- Le stepper de **préparation** ne doit pas rester bloqué visuellement sur `4 — Soumettre le dossier` après réussite. Le remplacer par un indicateur `Préparation terminée • Dossier soumis`, ou afficher les étapes de préparation comme accomplies.
- Si un suivi de traitement existe, le séparer nettement : `Soumis` ≠ `En examen` ≠ `Approuvé` / `Refusé`.
- **Ne pas simuler une progression d’examen** (pourcentage, durée, étapes artificielles).
- Conserver les versions historiques en lecture seule et respecter la logique de réouverture ou de complément, uniquement si elle est explicitement autorisée par A1.

## Détails techniques Nuxt / Laravel

- Le rendu doit dépendre **du statut et des capacités retournés par le backend**, non d’un simple booléen local `submitted = true` persistant seul.
- Ne pas considérer un `200` isolé comme une décision favorable : distinguer **succès de soumission**, **examen** et **habilitations**.
- Empêcher les actions interdites sur les dossiers soumis, y compris par navigation directe, changement de query string et actualisation (l’API reste l’autorité).
- Prévoir chargement, erreurs, états vides, accès interdits et retour depuis la liste de dossiers.
- Si un dossier n’exige pas certaines informations, ne pas présenter universellement le profil fiscal ou le reversement comme requis. Utiliser les exigences retournées par le serveur.
- Ne pas exposer les documents privés, données fiscales ou références sensibles dans les traces, toasts et URLs au-delà des identifiants déjà utilisés par la route et validés côté serveur.
- Ne pas créer artificiellement de notification ou d’e-mail de confirmation : l’indiquer uniquement si le produit envoie réellement ces messages.

## Responsive et accessibilité

- Desktop : une carte centrale bien hiérarchisée, sans longueur de page inutile.
- Tablette/mobile : largeur fluide, titres et valeurs non tronqués, CTA pleine largeur si nécessaire, boutons espacés.
- Focus visible, ordre de tabulation logique, messages d’erreur liés à leur contexte, `aria-live` pour les changements d’état sans annonce redondante, tailles cliquables suffisantes.
- Ne pas se reposer uniquement sur la couleur pour distinguer les statuts.

## Tests et validation

Vérifier avec les outils de test **déjà installés** (ne pas introduire de framework lourd) :

- soumission confirmée → rendu succès + identifiant serveur + actions de suivi ;
- page rechargée / reconnexion → statut correct depuis Laravel ;
- pas de `Soumettre`, `Étape précédente` ni d’édition sur version figée ;
- brouillon → ancien parcours de préparation conservé ;
- attente, examen, approbation, refus et compléments → textes conformes aux statuts **réellement pris en charge** ;
- échec réseau / timeout → aucune fausse confirmation ni doublon involontaire ;
- 403/404 → pas de fuite d’informations privées ;
- contrastes, clavier, mobile 360 px, tablette, desktop.

## Livrable Cursor

1. Faire un **audit court** des fichiers/états disponibles avant modifications.
2. Implémenter l’**état de confirmation/suivi** et harmoniser les textes en français.
3. Donner un tableau **fichiers modifiés / raison**.
4. Exécuter lint, typecheck et tests pertinents existants et signaler tout échec ou limite.
5. Conserver les fonctionnalités métier et la nomenclature contractuelle de la chaîne A1 ; faire valider les différences éventuelles plutôt que les deviner.
