# LIVREAURA — Recommandations UX/UI et fonctionnelles pour les catégories hiérarchiques du catalogue

**Écran concerné :** `/catalogue`  
**Périmètre :** navigation par catégories parentes/enfants, sélection, filtrage, recherche associée et responsive.  
**Destinataire :** Cursor (projet Nuxt / Laravel).  
**Base de l'analyse :** captures d'écran `Capture d’écran (76).png` (arborescence repliée) et `Capture d’écran (77).png` (branche « Littérature » développée), fournies le 9 novembre 2026.  
**Priorité :** améliorer le parcours de découverte **sans altérer la taxonomie, les données du catalogue ou les droits d'accès**.

---

## 1. Diagnostic exact des captures

### Ce qui fonctionne déjà

- Les **sept familles principales** visibles (« Littérature », « Éducation », « Sciences humaines », « Jeunesse », « Professionnel », « Arts et culture », « Vie pratique ») sont désormais regroupées sous des parents : c'est une amélioration majeure par rapport à la liste plate précédente.
- La branche « Littérature » dévoile des sous-catégories indentées ; le principe d'arborescence est donc déjà engagé.
- La grille de **quatre livres par ligne** est lisible et bien équilibrée : **ne pas la refondre dans ce chantier**.
- Le fond blanc, les traits discrets et l'accent orange sont cohérents avec l'orientation graphique choisie pour Livreaura.

### Problèmes à corriger

| Observation visible | Conséquence | Correction attendue |
|---|---|---|
| Le bloc « Catégories » arrive **après « Devise » et « Prix »**. | L'outil de découverte principal est relégué en bas des filtres. | Remonter « Catégories » au début de la colonne, près de la recherche. |
| La branche « Littérature » développée conserve un **« + » visible** sur la capture. | L'état ouvert/fermé n'est pas clairement représenté. | Afficher un chevron orienté selon l'état, ou « + »/« − » cohérents. |
| La boîte « Catégories » possède sa **propre barre de défilement verticale**, alors que la page défile également. | Défilement imbriqué, navigation moins naturelle, sous-catégories coupées. | Éviter par défaut le scroll interne ; laisser le document principal défiler. |
| Les catégories enfants sont présentées comme des lignes textuelles presque identiques aux parents. | La hiérarchie est encore peu explicite. | Différencier parent/enfant par retrait, typographie, surface active et indicateur de branche. |
| Aucun **état de catégorie sélectionnée** n'est identifiable sur ces captures. | L'utilisateur ne sait pas toujours ce qu'il a choisi. | Ajouter un état actif net et un rappel au-dessus des résultats. |
| La branche « Littérature » contient aussi « Littérature et fiction ». | Possible chevauchement sémantique avec le parent. | Auditer la taxonomie en base **sans renommer ni fusionner automatiquement**. |
| Le titre « Annuaire des auteurs » apparaît au-dessus d'une **grille de livres**. | Confusion entre annuaire d'auteurs et catalogue d'ouvrages. | Afficher un intitulé correspondant aux résultats réels ; réserver l'annuaire aux auteurs. |

**Important :** les captures montrent seulement l'aspect de l'interface. Elles ne prouvent pas comment le filtrage, les permissions ou les API sont actuellement implémentés. Auditer ces éléments avant toute correction fonctionnelle.

---

## 2. Objectifs de la refonte

La navigation doit permettre de répondre instantanément aux questions suivantes :

1. Quelles sont les grandes familles de livres ?
2. Quelles sous-catégories contient une famille ?
3. Dans quelle catégorie suis-je actuellement ?
4. Quels livres correspondent à ma sélection et à mes autres filtres ?

**Principe de minimalisme :** ne pas afficher toute l'arborescence, tous les niveaux ni tous les compteurs en permanence. Révéler le détail uniquement quand il devient utile.

---

## 3. Nouvelle position des catégories dans le catalogue

Placer « Catégories » **en premier dans la sidebar des filtres**. Ordre recommandé :

1. Catégories (arborescence).
2. Format.
3. Prix / Gratuité.
4. Langue.
5. Niveau, seulement lorsque pertinent.
6. Autres filtres avancés : auteur, éditeur, disponibilité, origine, etc.

- Placer le **tri** (« Plus récents », prix, pertinence…) au-dessus de la grille des résultats, pas à l'intérieur de l'arborescence.
- Placer la **barre de recherche générale** au-dessus de la zone résultats/filtres, visible avant le défilement sur desktop.
- Ne pas dupliquer le même champ de recherche dans plusieurs zones du catalogue.
- Si des raccourcis vers quelques grandes familles sont ajoutés au-dessus de la grille, ils doivent commander la **même sélection de catégorie** que la sidebar, sans créer une seconde logique parallèle.

---

## 4. Comportement attendu de l'arborescence

### 4.1. Parent et enfant : deux actions distinctes

Chaque parent dispose de **deux cibles d'interaction** :

- **Son libellé** : sélectionner la catégorie parente et filtrer les livres associés à cette branche, selon la règle métier de descendance retenue.
- **Son chevron** : développer ou réduire les catégories enfants **sans changer la catégorie sélectionnée**.

Ne pas rendre l'ouverture d'une branche équivalente à une sélection implicite ; cette confusion provoque des requêtes et des changements de résultats inattendus.

### 4.2. Indicateurs d'ouverture

- Branche fermée : `chevron-right`.
- Branche ouverte : `chevron-down`.
- Catégorie sans enfants : **aucun chevron**.
- Un chevron doit refléter l'état réel (`aria-expanded`) ; ne pas laisser « + » visible quand une branche est déjà ouverte.

### 4.3. Profondeur et compacité

- Afficher initialement seulement les familles parentes.
- Développer **automatiquement le chemin de la catégorie active** lors du chargement d'une URL filtrée.
- Préférer **une seule famille parente ouverte à la fois**, sauf nécessité métier démontrée, afin de contenir la hauteur de la sidebar.
- Gérer correctement une éventuelle profondeur supérieure à deux niveaux sans supposer que tous les nœuds sont à profondeur fixe.
- Autoriser la sélection d'une catégorie parente comme d'une catégorie feuille.

### 4.4. État sélectionné

La catégorie active doit avoir un signe **sobre mais visible**, par exemple :

- Fond gris neutre très clair (`surface-secondary` du design system).
- Texte de poids `medium` / `semibold`.
- Accent orange limité à une bordure latérale ou une coche discrète si cohérent avec les composants existants.

Ne pas surcharger chaque ligne avec un badge, une icône et un fond orange simultanément.

---

## 5. Hiérarchie visuelle recommandée

**Exemple de structure, à générer depuis les données réelles :**

```text
Catégories                         Effacer

Toutes les catégories

Littérature                         ▾
  Romans
  Poésie
  Théâtre
  Essais & récits
  Nouvelles
  Contes et récits

Éducation                           ›
Sciences humaines                   ›
Jeunesse                            ›
Professionnel                       ›
Arts et culture                     ›
Vie pratique                        ›
```

**L'exemple n'est pas une instruction de modifier les relations actuelles en base.** Il illustre l'organisation et la densité attendues. Les catégories réelles doivent être lues du catalogue et leur ordre doit suivre un ordre éditorial défini ou un champ d'ordre existant.

Éviter de réafficher une catégorie qui signifie presque la même chose que son parent sans justification taxonomique explicite.

### Dimensions cibles (à aligner sur les tokens existants)

| Élément | Recommandation |
|---|---|
| Largeur de la sidebar sur desktop | 240–260 px, responsive |
| Ligne de catégorie | 36–40 px minimum |
| Libellé parent | 13–14 px, medium si actif |
| Libellé enfant | 13–14 px, ton légèrement secondaire |
| Retrait par niveau | 16–20 px |
| Chevron | 16 px |
| Séparation des groupes | 4–8 px ou trait très discret |
| Conteneur de filtres | Fond blanc, pas de carte volumineuse requise |
| Focus clavier | Anneau visible, contraste suffisant |

**Ne pas agrandir les couvertures ou réduire la largeur utile de la grille pour résoudre un défaut de navigation.**

---

## 6. Supprimer le défilement imbriqué constaté

La capture de la branche « Littérature » montre une scrollbar **à l'intérieur** du bloc de catégories, en plus du défilement global du navigateur.

Corrections :

- Retirer le `max-height` + `overflow-y: auto` du petit panneau si aucune contrainte produit ne le justifie.
- Laisser les éléments développés participer à la hauteur naturelle de la sidebar et du document.
- Limiter l'allongement de la page par la **divulgation progressive** et la politique d'une seule branche ouverte, non par une seconde scrollbar étroite.
- Si le catalogue devient très profond, proposer à terme une recherche de catégories ou une vue « Toutes les catégories » distincte plutôt que d'emboîter de multiples scrollbars.
- Ne pas rendre la sidebar `sticky` au point de masquer ses propres éléments ; tester les petits écrans desktop et le zoom à 200 %.

---

## 7. Auditer la taxonomie visible avant toute fusion

Sur la capture développée, « Littérature » contient notamment : « Romans », « Poésie », « Théâtre », « Essais & récits », « Nouvelles », « Contes et récits » et **« Littérature et fiction »**.

Il faut vérifier dans la base de données :

- Les identifiants des catégories parentes et enfants.
- Leur `parent_id` ou la structure équivalente existante.
- L'absence de doublons sémantiques accidentels, de cycles, de catégories orphelines et de relations incohérentes.
- Le sens métier de « Littérature et fiction » : vraie sous-catégorie, catégorie historique, catégorie générique ou entrée à reclasser.
- Les ouvrages déjà rattachés à ces catégories, avant tout changement de taxonomie.

**Interdiction :** ne pas supprimer, fusionner, réaffecter ou renommer des catégories en base dans cette tâche sans validation explicite. Produire seulement un signalement documenté si des incohérences sont détectées.

---

## 8. Synchroniser catégories, recherche, filtres et résultats

Toute sélection de catégorie doit utiliser la même mécanique de requête que les autres filtres de la page.

### Règles fonctionnelles

- En sélectionnant une **catégorie feuille**, afficher les œuvres pertinentes selon les associations réellement enregistrées.
- En sélectionnant une **catégorie parente**, appliquer une règle métier explicite : inclure ses œuvres directes et, si prévu, celles de ses descendants. Éviter les doublons d'œuvres associées à plusieurs descendants.
- Combiner la catégorie avec `q`, format, prix, langue, disponibilité et les autres critères sans effacer les sélections précédentes.
- Remettre la pagination à la première page lors d'un changement de critères, si nécessaire.
- Mettre à jour le compteur de résultats depuis la réponse réelle de l'API.
- Gérer proprement le cas « aucun livre dans cette catégorie » avec un lien de réinitialisation des filtres.
- Ne pas afficher des œuvres non publiées ou non accessibles au public pour satisfaire artificiellement un nombre de résultats.

### URL, historique et navigation

- Encoder les filtres dans les paramètres d'URL selon la convention existante (`category`, `categorie`, `slug`, etc. — **ne pas en inventer une autre sans audit**).
- Synchroniser l'arborescence sélectionnée avec l'URL lors de l'accès direct, du rafraîchissement et du retour arrière.
- Conserver le chemin parent ouvert pour une sous-catégorie active.
- Proposer au-dessus de la grille un rappel discret, par exemple : `Littérature / Romans · 12 ouvrages`, si le compte est fiable.
- Ne pas afficher « Annuaire des auteurs » comme titre d'une liste de livres ; si une section dédiée aux auteurs existe, utiliser un composant et un intitulé réellement distincts.

---

## 9. Afficher les compteurs seulement s'ils sont fiables

Un nombre discret peut aider à choisir une catégorie : `Romans  (12)`.

Ne l'afficher que lorsque l'API fournit un comptage pertinent :

- Nombre d'**œuvres distinctes**, pas somme des éditions/offres.
- Comptage tenant compte de la visibilité publique et des règles d'accès.
- Pour un parent, comptage consolidé suivant la même règle de descendance que le filtre.
- Cohérence avec les filtres actifs, si le design prétend montrer des compteurs contextuels.

Sinon, **ne pas ajouter de chiffres**. L'absence de compteur est préférable à une information trompeuse.

---

## 10. Recherche des livres : articulation avec les catégories

Dans cette phase, ne pas reconstruire inutilement le moteur de recherche si un composant est déjà disponible.

Assurer cependant les comportements suivants :

- Le champ principal recherche titres, auteurs, ISBN et éventuellement catégories, selon les capacités actuelles du backend.
- Si des suggestions de catégories existent, cliquer sur l'une d'elles doit activer **le même filtre** que l'arborescence.
- Recherche, catégorie, tri et pagination doivent rester cohérents entre eux.
- Les résultats et l'état vide doivent indiquer les critères actifs sans répéter de longs messages.
- Les recherches textuelles doivent prendre en charge, dans la mesure du moteur existant, la normalisation des accents et de la casse.
- Les erreurs de chargement doivent être distinctes d'une recherche n'ayant retourné aucun ouvrage.

Ne pas introduire une nouvelle dépendance de moteur de recherche sans besoin démontré.

---

## 11. Responsive et accessibilité

### Desktop

- Arborescence dans la sidebar, au-dessus des autres filtres.
- Colonne de filtres compacte ; la grille reste le centre visuel.
- Éviter les scrollbars imbriquées et les panneaux de catégories géants.

### Tablette et mobile

- Remplacer la sidebar permanente par un bouton « Filtres » ouvrant un panneau ou tiroir accessible.
- Afficher « Catégories » en première section du tiroir.
- Conserver la branche développée et la sélection lors de l'ouverture/fermeture du panneau.
- Présenter un bouton permettant de voir les résultats, sans créer une seconde logique de filtrage.
- Bloquer correctement le défilement arrière-plan lorsqu'un tiroir modal est ouvert et restituer le focus à la fermeture.

### Accessibilité

- Utiliser des **boutons natifs** pour les chevrons, avec `aria-expanded`, `aria-controls` et un nom accessible pertinent.
- Veiller à l'accessibilité clavier des catégories, y compris sélection, ouverture et fermeture.
- Ne pas utiliser seulement la couleur pour signaler la catégorie active ; utiliser aussi un état typographique / `aria-current` ou `aria-selected`, selon le composant.
- Contrastes conformes aux règles WCAG AA.
- Cibles tactiles confortables et focus visible.
- Animation très courte et respect de `prefers-reduced-motion`.

---

## 12. Contraintes de développement Nuxt / Laravel

1. Identifier la page `/catalogue`, les composants de sidebar et la source API de la taxonomie avant de modifier le code.
2. Lire et respecter `LIVREAURA_DESIGN_SYSTEM_CURSOR.md` **s'il est présent** et les tokens officiels du projet ; ne pas créer une seconde palette CSS.
3. Réutiliser les composants Nuxt et les types existants autant que possible.
4. Construire la hiérarchie depuis la **source de vérité backend** ; ne pas coder en dur les sept parents ou les sous-catégories visibles sur les captures.
5. Prévoir des identifiants stables (ID/slug existants) pour les clés de rendu et les URL.
6. Vérifier que les données ne génèrent ni cycles ni récursions infinies ; gérer les nœuds malformés de façon sûre.
7. Préserver les relations œuvre–catégorie, œuvre–édition et offre commerciale ; un filtre de catégorie ne doit pas être confondu avec un filtre d'offres.
8. Ne pas modifier schéma, migrations, contrats API ou règles d'autorisation sans nécessité identifiée et signalée.
9. Prévoir un chargement et des erreurs lisibles si la récupération de la taxonomie échoue.
10. Éviter les requêtes répétées lors de chaque expansion locale de branche lorsque la taxonomie est déjà chargée.

**Composants à envisager uniquement si non existants :** `CatalogueCategoryTree.vue`, `CatalogueCategoryNode.vue`, `CatalogueActiveFilters.vue`. Leurs noms et leur emplacement doivent être adaptés à la structure réelle du projet.

---

## 13. Critères de validation à tester

### Visuel

- [ ] « Catégories » apparaît avant « Devise » et « Prix » dans la sidebar.
- [ ] Une famille repliée et une famille développée sont clairement distinguées.
- [ ] Le chevron change correctement d'orientation.
- [ ] Les enfants sont correctement indentés et ne se confondent pas avec leur parent.
- [ ] L'état sélectionné est visible et sobre.
- [ ] Plus de petite scrollbar interne imposée dans le panneau catégories.
- [ ] La grille de quatre couvertures reste intacte sur le viewport desktop montré.
- [ ] Le titre au-dessus de la grille décrit les ouvrages, pas un annuaire d'auteurs.

### Fonctionnel

- [ ] Cliquer sur le libellé d'une catégorie filtre les résultats.
- [ ] Cliquer sur le chevron ouvre/ferme sans filtrer.
- [ ] Une catégorie parente suit la règle métier de descendance validée.
- [ ] La catégorie active est restaurée après rafraîchissement ou navigation arrière.
- [ ] Le compteur de résultats ne double-compte pas une œuvre.
- [ ] Recherche, catégorie, autres filtres et tri se combinent correctement.
- [ ] La sélection d'une catégorie vide produit un état vide pertinent.
- [ ] Aucun ouvrage non accessible publiquement n'est exposé.

### Qualité

- [ ] Fonctionnement avec zéro, un et plusieurs niveaux d'enfants.
- [ ] Fonctionnement clavier, mobile, tablette et zoom 200 %.
- [ ] Aucun chevauchement, troncature accidentelle ou scroll horizontal.
- [ ] Aucun changement des relations en base sans décision métier.
- [ ] Tests effectués et limites restantes signalés explicitement.

---

## 14. Instruction exécutable à transmettre à Cursor

> Analyse les captures `Capture d’écran (76).png` et `Capture d’écran (77).png`, puis applique ce document à la navigation hiérarchique par catégories de `/catalogue`.
>
> **Priorité 1 :** remonter les catégories avant les filtres secondaires, rendre la distinction parent/enfant explicite et corriger l'indicateur d'ouverture.  
> **Priorité 2 :** supprimer le défilement imbriqué du panneau, renforcer l'état sélectionné et synchroniser la sélection avec l'URL, le compteur, la recherche et les résultats.  
> **Priorité 3 :** auditer les incohérences taxonomiques visibles (notamment « Littérature et fiction » sous « Littérature ») sans modifier automatiquement les données, et corriger le titre « Annuaire des auteurs » s'il désigne la grille de livres.  
> Préserve la grille des livres, le design system Livreaura, les API, les autorisations et les processus métier A2.  
> Implémente réellement les corrections, teste les cas principaux et termine par un résumé des fichiers modifiés, des comportements vérifiés et des anomalies de données relevées.

### Résultat attendu

Une navigation par catégories **courte à l'état initial, détaillée à la demande, parfaitement cohérente avec les résultats et visuellement intégrée au catalogue premium Livreaura**.

**Règle de synthèse :** une branche ouverte n'est pas une catégorie sélectionnée ; une catégorie sélectionnée doit toujours produire des résultats et un état de navigation cohérents.
