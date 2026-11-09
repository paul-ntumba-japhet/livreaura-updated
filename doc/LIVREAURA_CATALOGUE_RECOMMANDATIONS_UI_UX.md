# LIVREAURA — Cahier de recommandations UI/UX du catalogue public

**Écran cible :** `/catalogue`  
**Objet :** refonte premium de l'affichage, de la recherche, du filtrage et des cartes de livres  
**Destinataire :** Cursor — frontend Nuxt, backend Laravel  
**Statut :** recommandations d'implémentation ; ne remplacent pas les contrats métier existants

## 1. Mission et principes non négociables

Transformer le catalogue actuel, très vertical et fortement chargé en filtres, en une interface de librairie numérique premium : claire, élégante, performante et conçue pour évoluer vers un grand volume d'œuvres.

S'inspirer de **Cursor, GitHub et ChatGPT** pour la rigueur typographique, les composants et la densité visuelle, et adopter une **présentation éditoriale** de qualité pour les ouvrages. Préserver le logo officiel Livreaura, la couleur orange de marque et le fond blanc pur.

**Avant de modifier le code**, lire `LIVREAURA_DESIGN_SYSTEM_CURSOR.md`, relever les composants existants et identifier les contrats API, les paramètres de filtrage, les objets métier et les règles de prix. Les valeurs indiquées ici sont des cibles de conception : utiliser les tokens globaux lorsqu'ils existent.

**Règle centrale :** sur la liste, n'afficher que ce qui aide à reconnaître un livre, à en évaluer l'intérêt et à accéder à sa fiche. Réserver les informations complètes à la page de détail.

## 2. Diagnostic des captures fournies

| Problème observé | Conséquence | Réponse attendue |
|---|---|---|
| Livres présentés en grandes fiches horizontales | Très peu de livres visibles à la fois | Grille de cartes verticales |
| Couvertures trop petites | L'identité éditoriale disparaît | Couvertures dominantes et intactes |
| Chaque offre et devise affichées séparément | Lecture complexe | Prix de départ commercialement exact et formats résumés |
| Dates de publication avec heure et fuseau | Bruit informationnel | Retirer de la grille |
| Nombreux filtres en colonne sur plusieurs écrans | Navigation longue, espace vide | Filtres prioritaires puis avancés |
| Arborescence complète de catégories en liste | Scroll démesuré | Catégories hiérarchiques repliables |
| Nombreux liens soulignés orange | Concurrence visuelle | Liens et états conformes aux tokens |
| Espaces vides sous les résultats pendant que la sidebar continue | Déséquilibre de la page | Aligner l'expérience de filtrage sur la liste de résultats |

## 3. Structure de page cible

1. **Header public** léger : logo officiel, navigation utile, recherche cohérente, compte et fonctions réellement opérationnelles.
2. **En-tête catalogue** : « Explorer les livres » ou « Catalogue », courte introduction facultative.
3. **Recherche principale** : recherche par titre, auteur et ISBN selon les capacités réelles de l'API.
4. **Barre d'outils des résultats** : nombre réel d'œuvres, tri, contrôle des filtres, éventuellement choix grille/liste si ce dernier apporte une vraie valeur.
5. **Zone de résultats** : grille de cartes verticales avec pagination ou chargement progressif maîtrisé.
6. **Filtrage** : colonne desktop limitée à l'essentiel, panneau de filtres mobile, section avancée déployée à la demande.

Ne pas doubler la recherche de manière contradictoire entre header et corps de page. Choisir une recherche principale identifiable.

### Largeurs et grille recommandées

| Contexte | Cible indicative |
|---|---|
| Conteneur principal | `max-width: 1280–1440px` |
| Marges desktop | `24–48px` |
| Sidebar filtres si présente | `240–260px` |
| Large desktop | `4–5` cartes selon largeur réelle |
| Desktop moyen | `3–4` cartes |
| Tablette | `2–3` cartes |
| Mobile | `2` cartes si elles restent lisibles, sinon `1` |
| Gap horizontal | `20–24px` |
| Gap vertical | `28–36px` |

Déterminer les colonnes à partir de l'espace **après déduction** de la sidebar : ne pas forcer quatre cartes si elles deviennent trop petites.

## 4. Carte de livre : contenu obligatoire et contenu à retirer

### Informations directement visibles

- **Couverture véritable** du livre (issue des médias du catalogue).
- **Titre**, limité visuellement à deux lignes avec accès au titre complet.
- **Auteur ou auteurs**, présentés de manière concise.
- **Prix représentatif exact** ou mention « Gratuit » lorsque justifiée.
- **Format(s)** facultatifs en indication courte, si pertinents pour décider.
- **Badge exceptionnel**, uniquement lorsque significatif (nouveauté véritable, précommande, indisponibilité, démonstration en environnement test, etc.).

### Retirer des cartes

- « Disponible · Ouvrage africain » répété systématiquement.
- Dates détaillées : « Publié le ... à 09:00 (heure de Kinshasa) ».
- Liste exhaustive `EPUB / PDF / PAPIER` avec tous les montants et monnaies.
- Descriptions, sous-titres ou métadonnées longs lorsqu'ils n'aident pas directement au choix.
- Badges ordinaires répétés et informations techniques internes.
- Multiplication des accents orange sur chaque ligne de texte.

**Exception :** une disponibilité limitée, une origine ou un caractère scolaire significatif pour une décision ou un filtre peut être montré sous une forme discrète et contextualisée. Ne pas retirer les marqueurs `[TEST]` des données ; si nécessaire, représenter « Démonstration » dans l'interface de test uniquement.

### Aspect de la carte

- Carte verticale compacte, sans grande bordure décorative obligatoire.
- Zone de couverture stable, avec ratio visuel proche de `3:4` comme conteneur de mise en page, **sans déformer ni couper la vraie couverture**.
- Privilégier `object-fit: contain` si les proportions des médias varient.
- Titre `14–16px`, medium/semi-bold ; auteur `12–13px` en texte secondaire ; prix `14–15px` semi-bold.
- `hover`, `focus-visible`, `loading`, image manquante et état désactivé si applicable.
- La carte ou son titre doit mener de façon accessible à la fiche de l'ouvrage.
- Ne pas placer une action d'achat sur la carte tant que le produit, l'édition et l'offre ne sont pas sélectionnés conformément au processus métier.

## 5. Prix, formats, devises : rigueur métier

Livreaura sépare **œuvre, édition, format et offre commerciale**. Le composant de carte ne doit pas les confondre.

- « **Gratuit** » : seulement si une offre réellement accessible est gratuite ; ne pas signifier que toutes les éditions ou formats le sont.
- « **Dès X [devise]** » : prix le plus bas parmi les offres admissibles comparables, dans une même devise d'affichage et selon les droits, disponibilités et règles tarifaires.
- Si plusieurs devises existent, ne pas comparer directement CDF et USD sans conversion validée.
- Si aucune offre n'est accessible dans la devise sélectionnée, ne pas inventer de conversion ni de prix.
- Lorsque les formats ont des conditions différentes (numérique/papier, prêt/location/achat), renvoyer à la fiche pour le détail des prix et des licences.
- Extraire la règle d'agrégation dans un service ou composable testable, et non dans le template de `BookCard`.
- Afficher un prix indisponible ou « Voir les offres » lorsqu'un prix représentatif exact ne peut être calculé.

Vérifier également le traitement des œuvres avec plusieurs auteurs, plusieurs éditions et offres géographiquement limitées.

## 6. Filtres : simplification sans perte des fonctionnalités

### Premier niveau visible

- **Catégorie**.
- **Format** : numérique/papier, selon le modèle réel.
- **Prix / gratuité**.
- **Langue**.

### Filtres avancés, sur demande

- Niveau scolaire.
- Auteur et éditeur.
- État et disponibilité de l'offre.
- Prix minimum et maximum.
- Origine et badges.
- Autres critères déjà pris en charge par le backend.

Le **tri** doit être placé dans la barre d'outils des résultats : « Plus récents », « Prix croissant », etc., uniquement si la requête les prend réellement en charge.

La **devise** doit être cohérente avec la présentation des offres et les règles commerciales ; ne pas la traiter aveuglément comme un filtre ordinaire.

Règles UX :

- Montrer le nombre de filtres actifs et fournir « Effacer les filtres ».
- Afficher les filtres actifs sous forme de petites étiquettes supprimables si utile.
- Éviter d'afficher des options n'ayant aucun résultat, ou les désactiver avec indication appropriée.
- Sur mobile, regrouper les filtres dans un panneau accessible, validable et refermable.
- Conserver les critères dans l'URL quand c'est pertinent : recherche, filtres, tri et pagination.
- Éviter les requêtes excessives : debounce de recherche et gestion des réponses obsolètes.
- Ne pas sacrifier la possibilité d'une recherche ISBN précise au profit de la simplification visuelle.

## 7. Catégories : navigation hiérarchique

Ne plus afficher la totalité des catégories et sous-catégories sous forme de liens successifs.

- Montrer en priorité les catégories de premier niveau.
- Révéler les enfants dans un accordéon ou un arbre navigable.
- Faire apparaître l'élément sélectionné et sa hiérarchie.
- Respecter les relations parent/enfant de la base de données.
- Éviter les soulignements orange omniprésents ; utiliser états actifs, hover et focus du design system.
- Proposer « Toutes les catégories » ou un accès dédié à l'arborescence si nécessaire.
- Conserver la navigation et les routes de catégories déjà existantes.

## 8. Barre supérieure et navigation publique

Conserver le logo officiel à gauche et le compte à droite. Examiner l'intérêt de liens publics tels que « Catalogue » et « Catégories », et des fonctions Bibliothèque / Panier **uniquement si elles sont réellement opérationnelles**.

Ne pas ajouter des pages vides ou des liens trompeurs. Harmoniser les tailles du header avec les composants publics existants. La recherche doit être clairement identifiée et compatible avec les options de filtrage du catalogue.

## 9. États indispensables

| État | Comportement attendu |
|---|---|
| Chargement | Skeletons à proportions de couvertures fixes |
| Aucun ouvrage | État vide sobre avec action pertinente |
| Aucun résultat | Texte clair, possibilité de retirer des filtres |
| Erreur API | Erreur non technique et action réessayer |
| Couverture manquante | Placeholder éditorial sans déformation |
| Prix non déterminable | Mention prudente, jamais prix fictif |
| Filtre actif | État visible et réversible |
| Pagination | Résultats stables, scroll maîtrisé, URL cohérente |

La pagination doit être **côté serveur** pour les grands catalogues. Prévoir la limitation des images chargées et le lazy-loading, sans détériorer la lisibilité des premières cartes.

## 10. Design system cible

| Élément | Cible indicative |
|---|---|
| Fond global | `#FFFFFF` |
| Police de l'interface | Geist / Inter selon les tokens officiels |
| Titre page | `28–32px`, semi-bold |
| Titre livre | `14–16px` |
| Auteur et métadonnées | `12–13px` |
| Prix | `14–15px` |
| Labels filtres | `13–14px` |
| Champs / boutons | `40–44px` de haut |
| Bordure neutre | `#E5E5E5` si token absent |
| Couleur d'accent | Orange **officiel** du logo Livreaura |
| Transition | `150–200ms`, réduite si `prefers-reduced-motion` |
| Ombres | Très légères ou absentes |

Respecter contrastes WCAG AA, navigation clavier, zones cliquables et libellés accessibles. L'orange ne doit pas remplacer tous les liens, ni être la seule indication d'un état.

## 11. Architecture Nuxt / Laravel

Identifier les composants existants avant toute extraction. Une organisation possible, **sans obligation de reproduire exactement ces noms** :

```text
components/catalogue/
  CatalogueHeader.vue
  CatalogueSearch.vue
  CatalogueToolbar.vue
  CatalogueFilters.vue
  CatalogueCategoryTree.vue
  CatalogueGrid.vue
  BookCard.vue
  BookPrice.vue
  CatalogueEmptyState.vue
  BookCardSkeleton.vue
```

Responsabilités :

- **Laravel** : ouvrages visibles, règles de disponibilité, droits, éditions, offres, filtres, tri et pagination.
- **Services/composables Nuxt** : paramètres de requête, normalisation des données, synchronisation URL, présentation des prix selon le contrat existant.
- **Composants UI** : affichage et états, sans recoder la sécurité ni les règles commerciales.

Préserver les liens vers les pages livres et les identifiants métier existants. Ne pas modifier le schéma SQL, les processus A2 ou les API sans justification et vérification préalable.

## 12. Plan d'implémentation

### Phase 1 — Carte livre et grille (priorité critique)

- Inventorier les données réellement renvoyées.
- Mettre en place `BookCard` réutilisable.
- Implémenter le prix représentatif de façon correcte.
- Remplacer les listes horizontales par une grille responsive.
- Tester images, auteurs multiples, offres multiples, titres longs.

### Phase 2 — Recherche, filtres et catégories

- Mettre le tri au-dessus des résultats.
- Réduire le nombre de filtres visibles.
- Créer un panneau « Plus de filtres » et une navigation hiérarchique par catégories.
- Conserver la logique API et synchroniser les critères avec l'URL.

### Phase 3 — Harmonisation graphique et responsive

- Header, titres, paddings, bordures, hover/focus, skeletons.
- Vérifier desktop, tablette, mobile et zoom 200 %.
- Contrôler que les résultats restent au centre de l'expérience de consultation.

### Phase 4 — Tests métier et performance

- Valider les offres en USD/CDF, gratuites/payantes, numériques/papier.
- Tester états vides, erreurs, catégories imbriquées, pagination, filtres combinés.
- Vérifier accessibilité, performances et absence de régressions.

## 13. Checklist d'acceptation

- [ ] Les ouvrages s'affichent en grille responsive, et non dans de grandes fiches horizontales.
- [ ] Les couvertures sont dominantes, de bonne qualité et non déformées.
- [ ] Chaque carte contient seulement titre, auteur(s), prix représentatif et éventuellement format/badge pertinent.
- [ ] Dates détaillées et listes exhaustives des offres ont disparu de la grille.
- [ ] Les prix ne comparent pas arbitrairement les monnaies et reflètent les offres admissibles.
- [ ] Les filtres les plus utiles sont visibles ; les autres restent accessibles.
- [ ] La navigation des catégories est hiérarchique et compacte.
- [ ] La zone des résultats ne laisse pas une grande surface vide à cause de la liste des filtres.
- [ ] Tri, recherche, filtres et pagination restent opérationnels.
- [ ] Les critères sont partageables via URL lorsque l'architecture le permet.
- [ ] Loading, empty, error et images manquantes sont traités.
- [ ] Les versions desktop, tablette et mobile sont lisibles et accessibles.
- [ ] Les règles métier A2 et les contrats API sont préservés.
- [ ] Les tests effectivement réalisés sont mentionnés dans le rapport final.

## 14. Instruction finale à Cursor

> Analyse l'existant et applique effectivement ce cahier à `/catalogue`. Commence par la carte et la grille, puis rationalise la recherche, les filtres et les catégories. Retire toutes les informations non indispensables de la vue de liste, sans supprimer les données ou les fonctionnalités métier. Utilise les tokens officiels de Livreaura et préserve les contrats Laravel. À la fin, fournis les fichiers modifiés, les changements de contenu, les décisions de prix, les tests effectués et toute limite constatée. N'ajoute pas de fonctionnalités factices pour meubler l'interface.

**Critère ultime :** permettre au visiteur de parcourir rapidement les livres et de comprendre, sans surcharge, lesquels l'intéressent et quelles options de lecture ou d'achat sont disponibles.
