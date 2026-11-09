# LIVREAURA — Refonte premium du menu de navigation et du moteur de recherche du catalogue

**Document de référence UX/UI et contrat d'implémentation pour Cursor**  
**Périmètre :** catalogue public `/catalogue`, navigation supérieure, catégories hiérarchiques, recherche et résultats  
**Référence visuelle :** capture du catalogue du 9 novembre 2026 (titre « Catalogue », catégories à gauche, champ « Recherche » et grille de quatre ouvrages)  
**Priorité :** simplifier la navigation et améliorer la découverte sans remettre en cause la présentation actuelle des couvertures.

---

## 1. Objectif et principes non négociables

Transformer le catalogue en une interface de découverte professionnelle, compacte et facile à utiliser, inspirée de la rigueur de Cursor/GitHub et des fonctionnalités d'une grande librairie numérique, tout en conservant l'identité Livreaura.

1. **Clarté avant décoration.** Toute commande visible a une utilité directe.
2. **Une seule source de vérité.** Catégorie, requête, filtres, tri, pagination et résultats restent synchronisés.
3. **Progressivité.** Les sous-catégories sont accessibles à la demande ; les options avancées ne saturent pas l'écran.
4. **Fidélité métier.** Ne pas confondre œuvre, édition, format et offre commerciale.
5. **Aucune fonctionnalité factice.** Les éléments interactifs doivent être reliés aux vraies routes/API, sinon ils ne doivent pas paraître actifs.
6. **Réutilisation.** Employer les composants et tokens existants avant d'en créer de nouveaux.
7. **Ne pas dégrader l'existant.** Préserver la grille de quatre livres par rangée sur desktop, les couvertures réelles, la navigation vers les fiches et la logique actuelle des offres.

Avant toute modification, consulter le fichier de référence du design system Livreaura présent dans le dépôt (notamment `LIVREAURA_DESIGN_SYSTEM_CURSOR.md` s'il existe), puis inspecter l'implémentation effective.

## 2. Diagnostic fondé sur la capture

| Élément actuel | Constat | Modification attendue |
|---|---|---|
| Header | « Catalogue », « Connexion », « Créer un compte » sont traités comme des liens soulignés similaires | Hiérarchiser lien actif, action de connexion et création de compte |
| Colonne catégories | « Toutes les catégories » utilise un large fond beige et les autres lignes sont très espacées | Sélection plus discrète et lignes compactes |
| Hiérarchie | Des chevrons sont visibles pour les catégories parentes | Conserver l'arborescence, mieux distinguer sélectionner et déplier |
| Recherche | Champ vide et bouton orange très présent, au-dessus de la grille | Ajouter un placeholder utile, rapprocher visuellement les contrôles et aligner la recherche sur les résultats |
| Barre recherche/résultats | Grand vide entre le formulaire et le compteur « 4 œuvres » | Réduire l'espacement vertical |
| Résultats | Grille de quatre couvertures, composition déjà satisfaisante | **Conserver la grille et les cartes pour ce chantier** |
| Découverte | Aucun comportement de suggestions n'est visible | Prévoir une recherche assistée réelle et accessible |

**Important :** l'absence de suggestions sur une capture ne prouve pas que la fonctionnalité n'existe pas ; inspecter d'abord le code et les réponses API.

## 3. Architecture cible de la page

Disposition desktop :

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ Logo Livreaura                    Catalogue     Connexion    [Créer un compte] │
├─────────────────────────────────────────────────────────────────────────────┤
│ Catalogue                                                                   │
│ Découvrez les livres disponibles.                                          │
│                                                                             │
│ Catégories             [ Rechercher livre, auteur, ISBN…            ] [⌕]   │
│ ──────────                                                                  │
│ Toutes les catégories  4 ouvrages                         Trier : Récents ▾│
│ Littérature        ▸                                                        │
│ Éducation          ▸  [Couverture] [Couverture] [Couverture] [Couverture]    │
│ Sciences humaines  ▸                                                        │
│ Jeunesse           ▸  Titre       Titre       Titre       Titre             │
│ Professionnel      ▸  Auteur      Auteur      Auteur      Auteur            │
│ Arts et culture    ▸  Prix        Prix        Prix        Prix              │
│ Vie pratique       ▸                                                        │
└─────────────────────────────────────────────────────────────────────────────┘
```

- Colonne de gauche : catégories hiérarchiques, largeur cible **240–260 px**.
- Colonne principale : recherche, nombre de résultats, tri, filtres actifs et grille.
- Alignement : **bord gauche de la recherche = bord gauche de la grille**.
- Recherche et résultats doivent appartenir au même flux visuel ; éviter un grand vide entre les deux.
- Ne pas dupliquer les champs de recherche dans le contenu et le header.
- Sur mobile, menu catégories et filtres accessibles via un panneau dédié, tout en conservant un champ de recherche bien visible.

## 4. Refonte de la navigation supérieure

Conserver le logo officiel, les proportions et les couleurs validées.

### Pour le visiteur non connecté

- **Catalogue** : lien de navigation avec état actif discret (indicateur ou soulignement court uniquement sur l'élément actif).
- **Connexion** : lien neutre, sans soulignement orange permanent.
- **Créer un compte** : action compacte, visuellement identifiable comme CTA secondaire/marketing, à intégrer selon le composant bouton du design system.
- **Explorer / Catégories** : optionnel, uniquement si l'accès apporte une valeur réelle ; peut ouvrir le menu actuel ou une page dédiée si elle existe.
- **Recherche globale** : éventuellement une icône ouvrant le moteur commun sur d'autres pages ; ne pas afficher un deuxième champ identique sur `/catalogue`.

### Pour le membre connecté

- Remplacer les actions invitées par l'espace compte selon les mécanismes d'authentification existants.
- Éviter le débordement et conserver les états clavier et lecteur d'écran.

Ne pas ajouter un panier, une bibliothèque ou un menu « Explorer » artificiel tant que la route et la fonctionnalité ne sont pas opérationnelles.

## 5. Navigation hiérarchique des catégories

### 5.1. Hiérarchie visuelle

- Titre de section « Catégories » : **13–14 px**, poids 600.
- « Toutes les catégories » : élément racine sélectionnable, sans gros fond beige ; utiliser une surface neutre très claire et, éventuellement, une fine barre d'accent orange.
- Catégories parentes : texte 13–14 px, ligne 36–40 px, chevron aligné à droite.
- Sous-catégories : retrait de 16–20 px, guide vertical discret possible, poids normal.
- Réduire les espacements excessifs entre lignes.
- Conserver le blanc pur dans la zone principale ; l'orange reste un accent, jamais un remplissage massif du menu.

### 5.2. Deux interactions distinctes

- Clic sur **nom d'une catégorie** : sélectionner cette catégorie et actualiser les résultats.
- Clic sur **chevron** : développer ou replier ses enfants, sans modifier involontairement le filtre actif.
- Parent sélectionné : conserver son apparence active même lorsque la branche est repliée.
- Sous-catégorie active : ouvrir automatiquement ses ancêtres lors d'une restauration depuis l'URL.
- Déployer/replier avec un bouton sémantique, `aria-expanded`, `aria-controls` et un libellé accessible.
- Ne pas recourir à un simple texte `+` qui peut rester visible dans un état incohérent ; utiliser des icônes `chevron-right` / `chevron-down` ou leurs équivalents existants.

### 5.3. Comportement des catégories

- Reconstituer l'arbre depuis les vraies données (`parent_id` ou structure relationnelle existante), sans tableau de catégories codé en dur.
- Vérifier la profondeur possible de la taxonomie ; gérer des niveaux imbriqués au-delà de deux si nécessaire.
- Une catégorie parente sélectionnée doit utiliser la logique de descendants **réellement prévue** par les règles métier ; ne pas inventer une règle de filtrage.
- Si l'arbre contient un grand nombre de branches, garder les autres branches repliées par défaut.
- Éviter le petit scroll imbriqué à l'intérieur de la liste de catégories lorsque le défilement général suffit.
- Prévoir un état vide lorsqu'aucun ouvrage n'est rattaché à une catégorie.
- Un compteur d'ouvrages par catégorie est facultatif ; s'il est affiché, il doit être calculé à partir du périmètre réel des œuvres visibles.

### 5.4. Cas à tester

- Toutes les catégories.
- Parent avec enfants, avec et sans œuvres directes.
- Sous-catégorie à plusieurs niveaux.
- Catégorie sélectionnée puis changement de tri.
- Catégorie sélectionnée depuis un lien partagé.
- Parent supprimé/inactif, catégorie vide ou devenue non consultable.

## 6. Moteur de recherche : emplacement et saisie

- Positionner la recherche **au-dessus du compteur et de la grille**, alignée sur le bord gauche des couvertures.
- Placeholder recommandé : **« Rechercher un livre, un auteur ou un ISBN… »**.
- Hauteur cible : **46–48 px** ; largeur jusqu'à **720 px** sur desktop, fluide selon le conteneur.
- Ajouter une icône de loupe dans le champ.
- Bouton « Rechercher » : conserver si une soumission explicite est souhaitée, mais réduire son emphase et harmoniser sa hauteur avec celle du champ ; une icône seule n'est acceptable que si son action est explicitement accessible.
- Entrée doit lancer la recherche, même lorsqu'aucune suggestion n'est choisie.
- Ajouter une action « Effacer » lorsque la saisie est non vide.
- Ne pas placer une étiquette « Recherche » et un texte d'aide redondants si le champ et le contexte restent suffisamment clairs ; conserver un nom accessible.
- En cas de recherche persistante dans la page, garder la requête visible dans le champ.

## 7. Recherche assistée (autocomplétion)

### Périmètre métier

Le moteur doit pouvoir proposer, selon les données accessibles :

- **Ouvrages** : titre et, si utile, auteur principal.
- **Auteurs** : nom et accès à leur fiche si elle existe.
- **Catégories** : nom de catégorie et accès au catalogue filtré correspondant.
- **ISBN** : reconnaître les recherches exactes sans exposer d'identifiants internes.

Ne jamais simuler des suggestions avec une liste fictive en production.

### Comportements attendus

1. Déclenchement après **2 ou 3 caractères** pertinents, à ajuster selon l'API.
2. `debounce` indicatif **250–350 ms**.
3. Annuler ou ignorer les réponses obsolètes pour prévenir les résultats dans le mauvais ordre.
4. Afficher un panneau limité (par exemple 5–8 suggestions utiles), classé par pertinence et éventuellement par groupe.
5. Afficher « Voir tous les résultats pour “…” » au bas du panneau.
6. Mettre en évidence la portion du terme correspondant sans injecter du HTML non maîtrisé.
7. Normaliser au minimum la casse et les accents ; rechercher « poesie » doit pouvoir trouver « Poésie ».
8. Gérer les états : chargement, aucun résultat, erreur, requête vide.
9. Échap ferme le panneau ; flèches parcourent les suggestions ; Entrée sélectionne la suggestion active ou soumet la recherche.
10. Cliquer sur une suggestion d'ouvrage ouvre sa fiche réelle ; cliquer sur une catégorie applique la catégorie dans l'URL.

### Accessibilité

Adopter un composant de type **combobox** avec les rôles, labels et attributs ARIA appropriés (`aria-expanded`, `aria-controls`, `aria-activedescendant` si une suggestion active est gérée), sans créer de piège de focus.

Ne pas ouvrir le panneau automatiquement au chargement de la page si la recherche est vide.

## 8. Synchroniser recherche, catégorie, filtres et tri

La recherche ne doit pas ignorer la catégorie choisie, et le filtre de catégorie ne doit pas effacer arbitrairement le mot-clé.

Le modèle de paramètres doit regrouper, selon les capacités déjà présentes :

- `q` : recherche textuelle.
- `categorie` : catégorie choisie (slug/id selon le contrat existant).
- `format`, `langue`, `gratuite`, etc. : filtres disponibles.
- `tri` : ordre de présentation.
- `page` : pagination.

**Exemple illustratif, à adapter aux conventions existantes :**

```text
/catalogue?q=fractions&categorie=mathematiques&tri=recent&page=1
```

- L'URL est restaurable au rafraîchissement et partageable.
- Un changement de recherche ou de filtre réinitialise la page de pagination lorsque nécessaire.
- Le bouton de réinitialisation n'efface que le périmètre annoncé (par exemple « Toutes les catégories » retire le filtre catégorie, pas obligatoirement la requête).
- Les puces de filtres actifs doivent être visibles uniquement lorsque pertinentes ; elles peuvent être supprimées individuellement.
- Le compteur des œuvres est calculé à partir du même jeu de résultats que la grille.
- Le tri demeure placé **au-dessus des résultats, à droite**, plutôt que dans la navigation catégories.
- Les requêtes de recherche et de filtrage utilisent les API/périmètres d'accès existants.

## 9. En-tête des résultats

Afficher une barre compacte sous la recherche :

- À gauche : **nombre d'ouvrages** (« 4 ouvrages », et non un nombre figé).
- Le cas échéant : catégorie courante ou titre contextuel, sans duplication inutile du H1.
- À droite : tri, éventuellement bascule grille/liste seulement si les deux vues existent réellement.

Ne pas afficher « Annuaire des auteurs » au-dessus d'une grille d'ouvrages. Réserver cette appellation à une page qui liste effectivement des auteurs.

Conserver la carte livre actuelle et ses informations essentielles : couverture, titre, auteur, prix représentatif et formats, sans réintroduire les métadonnées longues supprimées précédemment.

## 10. Responsive

### Desktop (grand écran)

- Sidebar catégories : 240–260 px.
- Zone résultats : largeur restante.
- Recherche et grille alignées.
- Grille de **4 cartes par rangée** dans le contexte de la capture, avec adaptation automatique selon l'espace réel.

### Tablette

- Réduire les marges et adapter le nombre de colonnes.
- Prévoir, si nécessaire, un panneau de catégories escamotable.

### Mobile

- Recherche immédiatement visible sur toute la largeur disponible.
- Bouton « Catégories / Filtres » ouvrant un tiroir ou une feuille accessible.
- Catégorie sélectionnée identifiable et retirable.
- Aucun scroll horizontal ; aucun texte coupé.
- Le panneau de suggestions doit tenir à l'écran et rester utilisable avec le clavier mobile.

Tester également l'interface à **200 % de zoom** et en navigation clavier.

## 11. Tokens visuels indicatifs

> Utiliser en priorité les tokens centralisés du design system, même si leurs valeurs diffèrent légèrement des suggestions ci-dessous.

| Élément | Cible indicative |
|---|---|
| Fond principal | `#FFFFFF` |
| Texte principal | `#171717` |
| Texte secondaire | `#737373` |
| Bordure | `#E5E5E5` |
| Couleur d'accent | Orange officiel du logo |
| Header | 64–72 px |
| Sidebar catégories | 240–260 px |
| Ligne catégorie | 36–40 px |
| Retrait d'un niveau | 16–20 px |
| Typographie menu | 13–14 px |
| Champ de recherche | 46–48 px |
| Rayon du champ | 8–10 px |
| Espacement recherche/résultats | 24–32 px |
| Icônes | 16–18 px |
| Transitions discrètes | 150–200 ms |

Conserver une interface très sobre, avec des états de focus visibles et des contrastes conformes aux exigences WCAG AA.

## 12. Architecture technique recommandée

Avant de créer de nouveaux composants, identifier ceux déjà existants. Une découpe logique pourrait ressembler à :

```text
components/catalogue/
  CatalogueSearch.vue
  SearchSuggestions.vue
  CatalogueCategoryTree.vue
  CatalogueCategoryNode.vue
  CatalogueResultsToolbar.vue
  CatalogueGrid.vue            # déjà existant : le réutiliser
  BookCard.vue                 # déjà existant : le préserver
```

Cette arborescence est indicative, pas une injonction à renommer les composants actuels.

### Nuxt

- État de recherche et de filtres cohérent avec `useRoute` / `useRouter` (ou infrastructure équivalente existante).
- Requêtes serveur correctement annulées/ignorées si obsolètes.
- Éviter de déclencher une requête doublée au montage et aux changements de route.
- Préserver l'hydratation et la stabilité du rendu (SSR si applicable).
- Ne pas utiliser de données de démonstration en production.

### Laravel

- Réutiliser les endpoints existants lorsqu'ils répondent au besoin.
- Vérifier l'indexation et la pertinence des requêtes de recherche (titre, auteurs, ISBN selon le schéma réel).
- Appliquer pagination, contrôle de visibilité, limitation des données exposées et gestion des erreurs.
- Ne pas modifier la structure de la base de données sans justification technique et accord explicite.
- Ne pas introduire de moteur de recherche externe par défaut : mesurer d'abord les limites de l'existant.

### Métier A2

- Ne présenter que les œuvres réellement publiées et consultables dans le contexte public.
- Respecter la hiérarchie des catégories enregistrées.
- Éviter de fusionner abusivement œuvre, édition, format et offre.
- Préserver la logique du prix affiché et le contexte de devise existants.

## 13. Ordre d'exécution pour Cursor

1. **Auditer** les composants, routes, tokens et API de `/catalogue`.
2. **Harmoniser le header** et supprimer les soulignements permanents inutiles.
3. **Compacter le menu catégories** ; dissocier sélection et déploiement.
4. **Repositionner/améliorer la recherche** au-dessus de la grille.
5. **Intégrer les suggestions** en utilisant les vraies données, avec états et clavier.
6. **Synchroniser l'URL** avec la recherche, catégorie, tri, filtres et pagination.
7. **Valider le responsive**, l'accessibilité et les régressions.
8. **Conserver la grille de cartes existante**, sauf correctif mineur indispensable.

## 14. Checklist de validation

### Visuel et ergonomie

- [ ] Header plus lisible, lien actif distinct, action « Créer un compte » hiérarchisée.
- [ ] Le logo officiel et le blanc pur sont conservés.
- [ ] Le menu catégories est visuellement compact, sans grands espaces ni fond beige trop présent.
- [ ] Les niveaux parents/enfants sont immédiatement distinguables.
- [ ] Le clic sur un chevron ne change pas le filtre actif.
- [ ] Le clic sur le libellé applique la catégorie.
- [ ] La recherche est alignée sur la grille et possède un placeholder utile.
- [ ] Le nombre de résultats et le tri sont placés juste au-dessus des livres.
- [ ] Les quatre cartes par ligne sur grand desktop sont conservées lorsque la largeur le permet.

### Fonctionnel

- [ ] Les suggestions utilisent l'API réelle et non un tableau fictif.
- [ ] La recherche fonctionne au clavier et au clic.
- [ ] Les requêtes anciennes ne remplacent pas des résultats plus récents.
- [ ] Les accents, la casse et les recherches sans résultat sont gérés.
- [ ] Les liens de suggestion ouvrent les routes correctes.
- [ ] Recherche + catégorie + filtres + tri sont combinables.
- [ ] L'URL restaure correctement l'état après rafraîchissement.
- [ ] Les filtres réinitialisent la pagination lorsque nécessaire.
- [ ] Le compteur correspond aux résultats serveur.
- [ ] Le menu catégories reflète la taxonomie MySQL effective.
- [ ] Aucune donnée métier ni fonctionnalité existante n'a été supprimée.

### Qualité

- [ ] Aucun débordement horizontal desktop/tablette/mobile.
- [ ] Aucune barre de défilement interne inutile dans l'arborescence.
- [ ] Navigation clavier, libellés ARIA, focus et contrastes vérifiés.
- [ ] États chargement/vide/erreur testés.
- [ ] Aucune nouvelle dépendance sans nécessité.
- [ ] Aucun changement de contrat API ou de schéma BDD non justifié.

## 15. Livrable attendu de Cursor

Procéder à la **modification effective du code**, et non à un simple rapport de recommandations.

À la fin, fournir :

1. Fichiers modifiés et composants réutilisés/créés.
2. Modifications visuelles apportées au header, aux catégories et à la recherche.
3. Endpoints et paramètres réellement employés.
4. Scénarios fonctionnels et responsive testés, avec résultats honnêtes.
5. Limites restantes, notamment si les suggestions ou la recherche auteurs/ISBN exigent une capacité backend absente.

**Règle finale :** sur `/catalogue`, la recherche doit être immédiatement visible, les catégories doivent être faciles à explorer et la grille doit rester le centre de l'attention. Simplifier la surface visuelle sans appauvrir les capacités de découverte ni compromettre les règles métier A2.
