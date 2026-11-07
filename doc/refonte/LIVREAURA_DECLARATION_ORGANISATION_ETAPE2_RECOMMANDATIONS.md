# LIVREAURA — Recommandations UX/UI et métier
## Déclaration d’une organisation — Étape 2 : vérification préalable

**Écran concerné :** `/compte/organisation/declarer`, étape 2 sur 3  
**Version :** 1.0 — 7 novembre 2026  
**Destinataire :** Cursor (projet Nuxt / Laravel)  
**Référence :** `LIVREAURA_DESIGN_SYSTEM_CURSOR.md` et modèles métier de la chaîne A1

---

## 1. Diagnostic de l’écran actuel

Cette étape affiche actuellement :

- le titre général « Déclarer une organisation », un sous-titre et le stepper de trois étapes ;
- un grand encadré bleu « Pièces à déposer ensuite » ;
- « Pièces exigées pour Université » avec trois grandes cartes pour les justificatifs ;
- « Correspondances possibles », une explication juridique / métier longue et une grande carte « Aucune correspondance » ;
- les boutons « Retour aux informations » et « Continuer vers la confirmation ».

Le contenu est globalement compréhensible, mais il est **excessivement long pour une étape sans dépôt de fichiers**. Les avertissements et les cartes répètent les mêmes idées. Le titre « Vérification » peut suggérer qu'une identité ou un dossier KYC est déjà vérifié, alors que l'écran présente principalement **les justificatifs à prévoir et le résultat d’une recherche de correspondances**.

**Objectif :** permettre à l’utilisateur de comprendre rapidement (1) quels justificatifs il devra préparer, (2) si une organisation existante semble correspondre à sa déclaration, (3) ce qui se passera en continuant. Cette étape ne doit ni demander des pièces prématurément, ni attribuer un droit d’accès.

## 2. Arbitrages sur le contenu actuel

| Élément | Décision | Raison / remplacement |
|---|---|---|
| Titre « Déclarer une organisation » | **Conserver une seule occurrence** | Même titre que l’étape 1 pour maintenir le contexte. |
| Sous-titre « Vérifiez si la structure existe déjà, puis prenez connaissance… » | **Raccourcir** | « Vérifiez les correspondances et préparez les justificatifs. » ; supprimer si le contenu suffit. |
| Stepper 1 Informations / 2 Vérification / 3 Confirmation | **Conserver, compacter** | Utiliser le composant partagé, sans recréer un nouveau stepper. |
| Grand encadré bleu « Pièces à déposer ensuite » | **Supprimer** | Sa phrase est répétée juste après dans « Pièces exigées ». |
| Paragraphe « 3 fichiers obligatoires. Le dépôt se fait… Aucune pièce n’est publique. » | **Remplacer par une seule phrase** | Par exemple : « 3 pièces à préparer · Dépôt à l’étape de vérification du dossier, après la déclaration. » Ne pas promettre un délai ou un emplacement non confirmé. |
| Trois grandes cartes de pièces | **Remplacer par une liste compacte** | Un item par type de document, sans bordure individuelle et sans répétition de « fichier · obligatoire ». |
| Détails des pièces | **Affichage progressif** | Une courte description à la demande si le nom du document est ambigu. |
| Titre « Pièces exigées pour Université » | **Conserver en plus court** | « Documents à prévoir » ; afficher le type sélectionné dans un petit sous-libellé si nécessaire. |
| Bloc « Correspondances possibles » | **Conserver comme fonction, simplifier sa présentation** | Son existence est utile pour éviter doublons et usurpation. |
| Explication « Une correspondance de nom ne prouve ni… » | **Retirer de l’affichage permanent** | Garder un avis succinct seulement si une correspondance est détectée ; les garde-fous restent appliqués côté serveur. |
| Grande carte « Aucune correspondance » + texte | **Remplacer par une ligne de statut discrète** | « Aucune organisation similaire trouvée. » si la recherche est effectivement terminée. |
| Gros pictogramme d’état vide | **Supprimer** | N’apporte pas d’information supplémentaire. |
| Bouton « Continuer vers la confirmation » | **Raccourcir** | « Continuer » si le stepper indique déjà la destination ; conserver libellé explicite si nécessaire à l'accessibilité. |
| Bouton « Retour aux informations » | **Conserver, simplifier** | « Retour » ou « Modifier les informations » selon le contexte. |

**Règle :** supprimer des *présentations*, jamais les données, justificatifs exigés, contrôles anti-doublon, traces d’audit ou autorisations métier.

## 3. Hiérarchie de la page cible

Une seule colonne, centrée dans la zone de travail, avec un `max-width` indicatif de **680 à 760 px**.

1. **En-tête** — titre unique « Déclarer une organisation », stepper compact (étape 2/3).
2. **Documents à prévoir** — liste compacte des pièces exigées pour le type choisi, sans upload à cette étape.
3. **Organisations similaires** — résultat réel de la recherche de correspondances, avec état clair et traitement adapté.
4. **Actions** — « Retour » / « Continuer » en fin de formulaire.

Éviter les deux colonnes, les illustrations décoratives, les grandes cartes successives et les encadrés d’information répétés.

### Exemple de contenu — Université

```text
Déclarer une organisation
[✓ Informations] — [2 Vérification] — [3 Confirmation]

Documents à prévoir                                      Université
3 justificatifs seront demandés dans le dossier de vérification.

  • Existence de l’université                [Détails]
  • Identité du représentant                 [Détails]
  • Mandat de représentation                 [Détails]

Organisations similaires
✓ Aucune organisation similaire trouvée.

[Retour]                                          [Continuer →]
```

Ce schéma est **illustratif**, non un état métier garanti. La liste et la correspondance doivent provenir des règles et données réelles.

## 4. Liste documentaire : règles précises

- Afficher **uniquement** les justificatifs du type d’organisation choisi à l’étape 1.
- Reprendre les libellés officiels issus du référentiel documentaire existant ; ne pas coder en dur les trois pièces « Université ».
- Exemple observé sur la capture : **Existence de l’université**, **Identité du représentant**, **Mandat de représentation**.
- Une liste de lignes de **40–56 px** par pièce suffit généralement.
- Afficher le caractère obligatoire une seule fois dans l’introduction si **toutes** les pièces de la liste sont obligatoires ; sinon afficher « Obligatoire » ou « Facultatif » sur les lignes concernées.
- Ne pas répéter « fichier » sur chaque ligne lorsqu'il s'agit déjà clairement de justificatifs à déposer.
- Des descriptions détaillées peuvent être montrées via un accordéon compact ou un lien « Détails » pour les cas ambigus.
- Si une pièce dépend du pays, du type, du statut juridique ou de la qualité du représentant, respecter la logique métier de conditionnalité.
- Si aucun justificatif n’est défini dans le référentiel, afficher un état explicite ; ne pas inventer des exigences.
- **Ne pas introduire de zone d’upload à cette étape** si le processus actuel prévoit le dépôt dans un dossier distinct après la déclaration.
- Ajouter une note courte sur le moment du dépôt ; ne pas multiplier les mentions de confidentialité. Les documents doivent rester protégés techniquement indépendamment du texte affiché.

## 5. Correspondances : distinguer les états et les risques

La recherche d’organisation existante doit rester distincte de la validation KYC et de l’habilitation d’un représentant.

Prévoir des états UI précis :

| État technique / métier | Affichage recommandé | Suite possible |
|---|---|---|
| Recherche en cours | « Recherche d’organisations similaires… » | Éviter d’afficher prématurément « Aucune correspondance ». |
| Aucune correspondance trouvée | Ligne neutre : « Aucune organisation similaire trouvée. » | Continuer selon les règles métier. |
| Une ou plusieurs correspondances possibles | Liste compacte des **seules informations partageables** nécessaires à l’identification | Suivre le processus d’examen, de rapprochement ou de déclaration prévu ; aucun accès automatique. |
| Recherche indisponible / en erreur | « La recherche n’a pas pu être effectuée. » | Réessayer ou appliquer explicitement la politique backend ; ne jamais assimiler l’échec à zéro résultat. |
| Résultat à examiner manuellement | « Une vérification complémentaire est nécessaire. » | Orienter vers le processus existant. |

**Important :** ne pas présenter une correspondance exacte simplement parce que le nom correspond. Le rapprochement peut inclure type, pays et autres critères internes. Ne jamais révéler des données privées d’organisations existantes. Ne jamais créer d’adhésion, de représentant habilité, ni de droit d’accès par la seule détection d’un nom.

L’avertissement long et permanent devient, **uniquement en cas de correspondance**, une phrase courte du type :

> « Une organisation similaire existe peut-être. Cette correspondance ne donne aucun accès automatique. »

Les décisions et autorisations restent côté Laravel.

## 6. Le libellé « Vérification » doit être non ambigu

Le stepper actuel indique « Vérification », alors que le contenu ne réalise pas encore le dépôt ni l’instruction du dossier.

- **Option recommandée :** renommer la deuxième étape **« Contrôle préalable »** ou **« Préparation »**, **si** cela ne perturbe pas les libellés communs des autres étapes et la documentation métier.
- **Option conservative :** conserver « Vérification » mais afficher un sous-texte bref : « Documents à prévoir et recherche d’organisation similaire ».
- Ne pas annoncer « Organisation vérifiée », « Identité validée » ou « Dossier approuvé » à ce stade.
- Vérifier que la troisième étape « Confirmation » indique exactement ce qui est créé ou soumis à son terme.

## 7. Design premium et compacité

Appliquer en priorité les tokens de `LIVREAURA_DESIGN_SYSTEM_CURSOR.md`.

| Propriété | Cible indicative |
|---|---|
| Fond de la zone principale | `#FFFFFF` |
| Largeur de contenu | `680–760px` max |
| Titre de page | `26–28px`, 600 |
| Titres de section | `16–18px`, 600, police sans serif |
| Texte standard | `13–14px` |
| Texte secondaire | `12–13px`, `#737373` |
| Espacement entre sections | `24–32px` |
| Espacement entre lignes | `8–12px` |
| Bordures | `1px solid #E5E5E5`, quand utiles |
| Rayons | `8–10px` |
| Hauteur des boutons | `40–44px` |
| Transitions | `150–200ms` discrètes |

- Supprimer les titres serif « Pièces exigées… » et « Correspondances possibles » dans cette interface fonctionnelle.
- Éviter une carte par document ; utiliser des séparateurs ou une surface légère unique, si nécessaire.
- Réserver l’orange officiel à l’étape active et à l’action principale.
- Employer des couleurs sémantiques sobres : un résultat « aucune correspondance » n’est pas une approbation administrative.
- Ne pas introduire de grandes icônes, visuels de livres, ombres prononcées ou fonds bleus très étendus.
- Ne pas chercher à remplir l’espace vide à droite.

## 8. Actions et navigation

- Conserver un bouton secondaire permettant de revenir à l’étape 1 **sans perdre les valeurs déjà saisies**.
- Bouton primaire **« Continuer »** vers l’étape 3, soumis aux conditions métier réelles.
- Prévoir les états `loading`, `disabled`, `error` et la prévention du double clic.
- En cas de correspondance, appliquer le parcours de validation/déclaration prévu par le MPT A1 ; ne pas permettre une continuation contournant un blocage serveur.
- Conserver la possibilité de consulter les pièces requises sans obliger l’utilisateur à ouvrir toutes les descriptions.
- Ne pas annoncer qu’une organisation a été créée avant la confirmation effective de l’opération backend.

## 9. Responsive, accessibilité et erreurs

- Desktop : une colonne principale, sections compactes, actions proches du contenu.
- Mobile : largeur fluide, marges de `16–20px`, libellés et références sans débordement, boutons accessibles.
- Stepper adapté aux petits écrans (libellés raccourcis au besoin), sans défilement horizontal forcé.
- Navigation clavier et focus visibles ; accordéons avec état `aria-expanded` si utilisés.
- Textes de statut annoncés de manière accessible (`aria-live` approprié) et non par la couleur seule.
- Tester : zéro pièce, de nombreuses pièces, titre long, plusieurs correspondances, erreur réseau, réponse lente, zoom à 200 %.
- Vérifier que l’interface de résultats ne produit ni sauts de mise en page excessifs ni fausses certitudes.

## 10. Protections métier impératives

Ne pas modifier arbitrairement :

- les règles de déclaration et de création d’organisation ;
- les exigences documentaires par type/pays ;
- les critères de rapprochement ou de détection des doublons ;
- les procédures d’adhésion, d’habilitation et de KYC ;
- les vérifications d’autorisation côté serveur ;
- les historiques et traces d’audit ;
- la confidentialité des données organisationnelles et personnelles.

**Spécificité à contrôler :** la catégorie « Auteur ou éditeur à titre personnel » évoquée à l’étape 1 ne doit pas être traitée automatiquement comme une organisation sans correspondance avec le MPT A1. Vérifier le parcours réellement prévu avant tout changement de catégorisation.

## 11. Plan d’implémentation pour Cursor

1. Lire le design system et les composants réutilisés par le wizard.
2. Inspecter les données réelles des étapes 1 et 2 ainsi que l’API de recherche de correspondances.
3. Vérifier les règles documentaires par type, pays et représentant.
4. Retirer le grand encadré bleu, les grandes cartes documentaires et l’état vide surdimensionné.
5. Recomposer l’écran en deux sections fonctionnelles compactes : **Documents à prévoir** et **Organisations similaires**.
6. Gérer les états réels de recherche, l’erreur réseau et le cas de plusieurs correspondances.
7. Compacter le stepper et les actions avec les composants globaux.
8. Tester le retour vers l’étape 1, la conservation des données et la progression vers la confirmation.
9. Vérifier le responsive et les règles d’accessibilité.
10. Rendre un bilan des fichiers modifiés, des informations supprimées de la vue et des fonctionnalités préservées.

## 12. Checklist de validation

- [ ] Aucune répétition du message « les pièces seront déposées ensuite ».
- [ ] Le grand bloc informatif bleu est supprimé.
- [ ] Les exigences affichées correspondent **uniquement** au type choisi.
- [ ] Les pièces sont listées de façon compacte, sans grands encadrés individuels.
- [ ] Les détails documentaires secondaires sont accessibles à la demande.
- [ ] Aucune pièce n’est téléversée à cette étape si le métier ne le prévoit pas.
- [ ] Le contrôle de correspondances conserve toutes ses fonctions et protections.
- [ ] Un échec de recherche n’est jamais affiché comme « aucune correspondance ».
- [ ] Les correspondances ne confèrent aucun accès automatique.
- [ ] Le texte et le statut reflètent l’état réellement renvoyé par Laravel.
- [ ] La page n’annonce aucune vérification KYC réalisée à tort.
- [ ] Un seul bouton primaire est affiché.
- [ ] Le retour conserve les champs saisis.
- [ ] La page est compacte, sans surcharge visuelle ni sections décoratives.
- [ ] Pas de chevauchement, pas de débordement, tests clavier/mobile/zoom réalisés.
- [ ] Aucun changement de schéma, d’autorisation ou de processus métier sans justification.

---

## Directive finale à Cursor

> **Refonds réellement la deuxième étape du formulaire `/compte/organisation/declarer`.** Supprime les informations redondantes, les explications permanentes et les grandes cartes non nécessaires. Présente une liste compacte des justificatifs adaptés au type sélectionné et un résultat de rapprochement fiable, avec les états techniques correctement distingués. Garde une seule colonne, un stepper discret et des actions simples. Préserve rigoureusement les contrôles anti-doublon, les autorisations et les règles KYC de la chaîne A1. N’ajoute pas de données fictives et n’annonce jamais une vérification, une livraison de document ou un accès non confirmés par le serveur. Termine par un récapitulatif des changements et des tests réellement exécutés.
