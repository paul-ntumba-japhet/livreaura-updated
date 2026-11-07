# LIVREAURA — Refonte de la déclaration d'organisation : étape 1

> **Référentiel de recommandations UX/UI, contenu et règles fonctionnelles pour Cursor**  
> **Écran concerné :** `/compte/organisation/declarer` — Étape 1 « Informations »  
> **Portée :** première étape seulement ; conserver les étapes « Vérification » et « Confirmation ».  
> **Référence transversale :** `LIVREAURA_DESIGN_SYSTEM_CURSOR.md` (ses tokens officiels priment sur les valeurs indicatives de ce document).

## 1. Objectif

Refondre la première étape de la déclaration d'une organisation pour obtenir une interface **premium, compacte, accessible et centrée sur la tâche**, dans l'esprit de Cursor, GitHub et ChatGPT, sans perdre les exigences de sécurité et les règles métier de Livreaura.

L'amélioration doit concerner **autant le contenu affiché que le design** : ne montrer que ce dont l'utilisateur a besoin au moment où il en a besoin. Ne pas supprimer de données ou de contrôles métiers sous prétexte de simplification visuelle.

## 2. Diagnostic de l'écran actuel

| Élément | Problème | Action recommandée |
|---|---|---|
| Illustration de livres | Occupe une zone importante sans aider à saisir le formulaire | Supprimer de cette page fonctionnelle |
| Titre « Déclarer une organisation » | Répété dans le bandeau et dans le formulaire | Conserver un seul titre principal |
| Introduction | Longue, mélange l'identification et l'avertissement sur les droits | Une courte phrase ; déplacer les précisions de sécurité au bon moment |
| Stepper | Enchâssé dans une grande carte | Le placer directement sous l'en-tête, en version compacte |
| Grand encadré du formulaire | Ajoute des bordures et du vide sans nécessité | Formulaire sur fond blanc, sans carte englobante lourde |
| Panneau « Exigences par type » | Affiche toutes les catégories avant tout choix | Retirer le panneau permanent ; contextualiser selon le type sélectionné |
| Champs | Trop hauts et espacés | Standardiser à 46–48 px avec espacements réguliers |
| Aide sous « Raison sociale » | Affichée en permanence et plutôt technique | Aide contextuelle ou information lors d'une correspondance détectée |
| CTA « Continuer vers la vérification » | Libellé long | « Continuer » avec destination indiquée par le stepper |
| Hauteur de page | Défilement très important pour peu de champs | Réduire significativement la longueur de l'étape |

## 3. Architecture cible

Utiliser **une seule colonne principale** de **640 à 720 px maximum**, dans la zone de contenu de l'espace personnel. Préserver le header et la sidebar partagés.

Ordre recommandé :

1. Navigation de retour discrète vers « Mes organisations » si utile.
2. Un seul titre : **Déclarer une organisation**.
3. Description courte : « Renseignez les informations permettant d'identifier votre organisation. »
4. Stepper compact : **1. Informations → 2. Vérification → 3. Confirmation**.
5. Champs d'identification.
6. Information contextuelle sur les justificatifs correspondant au type choisi, avec accès facultatif à la liste complète.
7. CTA principal **Continuer**.

Ne pas afficher simultanément un panneau latéral volumineux, une seconde carte de formulaire et des éléments décoratifs. Il est acceptable de laisser de l'espace libre à droite : ne pas le remplir artificiellement.

### Wireframe indicatif

```text
← Mes organisations

Déclarer une organisation
Renseignez les informations permettant d'identifier votre organisation.

● Informations  ───  ○ Vérification  ───  ○ Confirmation

Type d'organisation *
[ Choisir un type                                  ▾ ]

Nom officiel *
[ Nom de l'organisation                             ]

Pays *                              Ville
[ Sélectionner               ▾ ]    [ Ville                  ]

E-mail professionnel                Site web
[ contact@organisation.org     ]    [ https://...           ]

[Informations sur les pièces du type choisi]  [Voir les documents requis]

                                            [ Continuer → ]
```

Les libellés et champs ci-dessus sont illustratifs : aligner l'implémentation sur le modèle métier et les validations existantes.

## 4. Informations à conserver et informations à simplifier

| Champ | Traitement proposé | Remarque métier |
|---|---|---|
| Type d'organisation | **Conserver, obligatoire** | Pilote les exigences documentaires et les parcours autorisés |
| Raison sociale | **Conserver**, envisager le libellé « Nom officiel » | Plus inclusif pour école, bibliothèque, association, etc. ; ne pas modifier l'attribut API sans nécessité |
| Pays | **Conserver, obligatoire** | Peut influer sur l'identification et les règles applicables |
| Ville | **Conserver si utile** | Respecter le caractère obligatoire ou facultatif existant |
| E-mail professionnel | **Conserver facultatif** si conforme au modèle | Information de contact, pas une preuve d'habilitation |
| Site web | **Conserver facultatif** si conforme au modèle | Information complémentaire, pas une preuve d'habilitation |

Ne pas ajouter de champs nouveaux sans besoin fonctionnel identifié. Ne pas supprimer silencieusement un champ exigé par Laravel : si un champ paraît inutile, vérifier sa dépendance métier avant de le retirer.

### Microcopy

- **Titre unique :** « Déclarer une organisation ».
- **Description :** « Renseignez les informations permettant d'identifier votre organisation. »
- **Nom officiel :** remplacer la répétition technique par une aide contextuelle, seulement lorsque cela améliore la compréhension.
- **Bouton :** « Continuer ».
- **Exigences :** « Des justificatifs pourront être demandés à l'étape suivante. » puis « Voir les documents requis ».

## 5. Panneau « Exigences par type » : refonte prioritaire

**Supprimer le catalogue permanent** des exigences pour les auteurs, écoles, universités, bibliothèques, maisons d'édition, librairies et associations, affiché à côté du formulaire.

À la place :

1. Tant qu'aucun type n'est sélectionné, ne montrer aucune liste documentaire.
2. Après choix d'un type, afficher une courte indication contextuelle.
3. Proposer un lien discret **« Voir les documents requis »** qui ouvre un accordéon, un panneau ou un dialogue contenant **seulement les pièces de ce type**.
4. Conserver le détail documentaire dans l'étape « Vérification » où il devient directement pertinent.
5. Alimenter les exigences avec le **référentiel métier réellement utilisé par Laravel**, et non une liste frontend divergente.
6. Si les exigences dépendent aussi du pays ou du statut juridique, les actualiser selon ces critères sans faire de promesse erronée.

Exemple de message pour une école, à ajuster au backend : « Des justificatifs d'existence de l'établissement et de représentation pourront être requis lors de la vérification. »

Ne jamais affirmer qu'une pièce précise est obligatoire sans correspondance avec les règles du système.

## 6. Typographie et tokens

Conserver les tokens globaux de Livreaura. Valeurs indicatives si le design system ne fixe pas déjà ces propriétés :

| Élément | Valeur cible |
|---|---|
| Titre principal | 28 px, poids 600 |
| Description | 14 px |
| Labels | 13 px, medium |
| Texte de saisie | 14 px |
| Texte d'aide | 12–13 px |
| Stepper | 12–13 px |
| Bouton | 14 px, semi-gras |
| Fond principal | `#FFFFFF` |
| Texte principal | `#171717` |
| Texte secondaire | `#737373` |
| Bordures | `#E5E5E5` |
| Accents et focus | Orange **officiel** Livreaura |

Police de l'interface : **Geist ou Inter**, selon le design system retenu. Éviter la serif pour les titres de formulaires applicatifs.

## 7. Dimensions et espacements

| Propriété | Recommandation |
|---|---|
| Largeur du formulaire | 640–720 px maximum |
| Hauteur des champs | 46–48 px |
| Espacement entre champs/groupes | 16–20 px |
| Espacement entre sections | 24–32 px |
| Padding horizontal desktop | 24–32 px |
| Rayon des champs | 8 px |
| Bordure | 1 px, ton neutre |
| Bouton principal | 44–48 px de hauteur, largeur adaptée au contenu |
| Transitions | 150–200 ms, discrètes, avec respect des préférences de mouvement |

Privilégier le fond blanc et les alignements plutôt que des cartes imbriquées. Les champs « Pays/Ville » et « E-mail/Site web » peuvent être côte à côte sur desktop, mais doivent s'empiler sans débordement sur mobile.

## 8. Comportement contextuel

- **Type sélectionné :** ne présenter que les exigences correspondantes ; ne pas afficher des catégories étrangères à ce choix.
- **Changement de type :** actualiser les aides et exigences, préserver les valeurs communes appropriées et gérer proprement les données spécifiques devenues inapplicables.
- **Navigation entre étapes :** préserver les valeurs saisies lors du retour, selon le mécanisme d'état ou de brouillon existant.
- **Validation :** afficher l'erreur près du champ, avec lien clair entre message et saisie ; empêcher une progression non valide sans effacer les autres données.
- **États :** prévoir focus, erreur, désactivé, chargement, réponse serveur ; éviter le double envoi.
- **Accessibilité :** labels associés, contrôles utilisables au clavier, focus visible, contrastes AA, messages d'erreur annoncés correctement.

## 9. Séparation identité / vérification / habilitation

Le formulaire sert à **déclarer et identifier** une organisation. Il ne doit ni prouver à lui seul son existence légale, ni accorder des droits de représentation.

Contraintes impératives :

- Une correspondance de nom ne donne **aucun droit d'accès** à une organisation déjà connue.
- Le rapprochement d'une entité existante doit suivre la procédure métier prévue, sans rattachement privilégié automatique.
- L'identité du déclarant, l'existence de l'organisation et les pouvoirs de représentation sont des contrôles distincts.
- Les justificatifs sont protégés ; les habilitations sont vérifiées par le backend.
- Ne pas modifier les états, cardinalités, permissions ou transitions définis dans la chaîne A1.

### Catégorie « Auteur ou éditeur à titre personnel »

Vérifier, au regard du MPT A1, si elle appartient réellement au processus de déclaration d'**organisation** ou plutôt au parcours de profil/publication d'un particulier. **Ne pas la supprimer ni la déplacer sans validation des règles métier existantes.**

## 10. Détection de doublons et saisie

- Si le produit possède déjà un mécanisme de détection d'organisations similaires, le rendre lisible et non bloquant selon les règles métier.
- Une similarité nom + pays **ne constitue pas** une preuve d'identité ni une permission.
- Éviter d'afficher en permanence un texte technique sur les doublons ; apporter l'information seulement lorsque le scénario se présente.
- Conserver le comportement existant de normalisation, validation et enregistrement.
- Ne pas créer de requête de recherche de doublons, de logique de fusion ou de nouveau droit sans vérification préalable des API et exigences métier.

## 11. Responsive

**Desktop :** une colonne principale compacte, stepper proche du titre, champs à deux colonnes lorsqu'ils sont compatibles, CTA visible sans défilement excessif dans les résolutions habituelles.

**Tablette :** réduire progressivement les marges et adapter les groupes de champs.

**Mobile :** champs sur une seule colonne, CTA clair, stepper compact ou simplifié, aucun défilement horizontal ni libellé tronqué.

Vérifier aussi le zoom navigateur à 200 % et les contenus longs.

## 12. Contraintes d'implémentation pour Cursor

1. Examiner le composant de page et les composants partagés.
2. Lire `LIVREAURA_DESIGN_SYSTEM_CURSOR.md`.
3. Analyser les types d'organisation et les exigences réelles renvoyés par l'API.
4. Vérifier les champs obligatoires/facultatifs et leur validation Laravel.
5. Refondre uniquement la première étape en réutilisant les composants et tokens existants.
6. Préserver les routes, étapes, permissions, sécurité et données métier.
7. Tester les types et les états significatifs, notamment les erreurs et les retours d'étape.
8. Signaler les points métier incertains plutôt que de les résoudre arbitrairement.

**Interdictions :** données fictives en production ; exigences documentaires codées séparément sans alignement ; suppression de contrôles backend ; changement automatique de rôle ; modification de schéma de base de données non justifiée ; ajout d'illustrations décoratives ; création de composants redondants.

## 13. Critères d'acceptation

- [ ] Un seul titre « Déclarer une organisation ».
- [ ] Illustration décorative supprimée.
- [ ] Grand encadré de formulaire supprimé ou fortement allégé.
- [ ] Stepper compact placé sous l'en-tête.
- [ ] Formulaire principal limité à 640–720 px sur desktop.
- [ ] Champs normalisés et alignés ; erreurs accessibles.
- [ ] Plus de panneau permanent listant tous les types d'organisation.
- [ ] Les exigences documentaires ne concernent que le type sélectionné.
- [ ] Un accès facultatif aux pièces requises est disponible avant l'étape 2.
- [ ] Bouton « Continuer » compact et fonctionnel.
- [ ] Données de saisie préservées lors du retour depuis l'étape 2.
- [ ] Aucune attribution automatique de droits par correspondance de nom.
- [ ] Règles métier, validations et sécurité Laravel préservées.
- [ ] Responsive et accessibilité contrôlés.
- [ ] Amélioration nette de la compacité sans perte de fonctionnalités indispensables.

## 14. Résultat attendu

Une première étape où l'utilisateur peut rapidement :

1. Choisir le type d'organisation.
2. Saisir les informations essentielles d'identification.
3. Consulter facultativement les pièces exigées pour **son** cas.
4. Passer à la vérification en comprenant que la déclaration n'accorde aucun droit privilégié.

**Directive finale :** supprimer l'information superflue, pas la logique métier. Préférer une interface simple et contextualisée à une page exhaustive et surchargée.

En fin d'implémentation, Cursor doit indiquer les fichiers modifiés, les éléments retirés, les fonctionnalités conservées, les tests réellement effectués et les éventuelles questions métier restantes.
