# LIVREAURA — Contrat de design UI/UX pour Cursor

> **Statut : NORMATIF — version 1.0**  
> **Portée : toute l'application Livreaura (frontend public, authentification, espaces personnels, auteur, éditeur, partenaires et back-office).**  
> **Objectif : une interface premium, sobre et cohérente, inspirée par la rigueur visuelle de Cursor, la lisibilité de ChatGPT et la finition de Laravel, sans copier leurs identités.**

## 0. Instructions impératives pour Cursor

1. Lire ce fichier **avant toute création ou modification d'un écran**.
2. Considérer les règles marquées **OBLIGATOIRE** comme des contraintes globales, et non comme de simples suggestions.
3. Utiliser les tokens et composants partagés du projet : **ne pas réinventer tailles, couleurs, rayons, ombres et espacements dans chaque page**.
4. Ne pas modifier le logo, son graphisme, ses proportions, ni remplacer l'orange officiel par une couleur arbitraire.
5. Préserver les processus métiers, routes, validations, permissions, états et fonctionnalités existants ; une refonte graphique ne doit pas modifier le comportement sans demande explicite.
6. Pour toute exception à ce référentiel, expliquer la raison, limiter l'exception et la documenter.
7. Vérifier les critères d'acceptation de la section 15 avant de déclarer un écran terminé.
8. En cas de conflit : **accessibilité et sécurité > cohérence fonctionnelle > règles du design system > préférence locale de page**.

**Règle de déploiement :** ce document spécifie des valeurs cibles. Cursor doit d'abord auditer les composants et tokens existants, puis effectuer une migration progressive, sans remplacer aveuglément les classes ou styles utilisés dans des écrans non audités.

---

## 1. ADN visuel

- **Sobriété fonctionnelle** : l'interface sert le contenu et les tâches, jamais l'inverse.
- **Hiérarchie évidente** : une action principale identifiable par écran ou section.
- **Densité maîtrisée** : panneaux et formulaires compacts, sans congestion.
- **Cohérence systémique** : mêmes composants, mêmes comportements et mêmes tokens partout.
- **Identité Livreaura** : logo officiel, orange de marque, tonalité noir café, traitement éditorial réservé aux contenus appropriés.
- **Fond d'application blanc pur** par défaut, sans ivoire global, sans dégradé de fond systématique.
- **Zéro décoration gratuite** : illustrations, photographies, halos et ombres doivent avoir une fonction éditoriale ou informative explicite.

### Influences (et limites)

| Référence | Ce que l'on retient | Ce qu'on évite |
|---|---|---|
| Cursor | Grille nette, compacité, typographie, composants discrets | Copie des écrans et de l'identité |
| ChatGPT | Simplicité, espaces utiles, parcours lisibles | Neutralisation de la marque Livreaura |
| Laravel | Finitions typographiques, détails d'interface soignés | Effets décoratifs systématiques |

---

## 2. Couleurs et tokens

**OBLIGATOIRE :** le logo fourni par le projet reste la source d'autorité pour la couleur orange. Tant que sa valeur HEX exacte n'est pas identifiée/validée, utiliser `--brand-orange` comme token raccordé à la valeur déjà en production ; ne pas inventer un nouvel orange pour harmoniser la page.

```css
:root {
  /* Identité : raccorder au token / à la valeur exacte du logo déjà validée */
  --brand-orange: var(--livreaura-orange-officiel);
  --brand-coffee: #29180f; /* proposition : validation visuelle requise */

  /* Neutres */
  --surface-page: #ffffff;
  --surface-card: #ffffff;
  --surface-subtle: #fafafa;
  --surface-muted: #f5f5f5;
  --text-primary: #171717;
  --text-secondary: #525252;
  --text-muted: #737373;
  --border-default: #e5e5e5;
  --border-strong: #d4d4d4;

  /* Sémantique : à vérifier selon contraste et usage */
  --status-error: #b42318;
  --status-success: #067647;
  --status-warning: #b54708;

  /* Ne jamais figer une couleur de focus sans validation de contraste */
  --focus-ring: var(--brand-orange);
}
```

**Interdictions :** champs bleu clair par défaut, fonds ivoire généralisés, dégradés décoratifs non justifiés, couleurs hexadécimales locales répétées au lieu des tokens, usage de l'orange sur toutes les actions.

**Contraste :** respecter au minimum WCAG AA (texte courant ≥ 4,5:1 ; texte large ≥ 3:1 ; composants/indicateurs pertinents ≥ 3:1). L'orange officiel peut servir de fond si le contraste du libellé est vérifié ; sinon préférer un bouton noir café avec texte clair et accents orange.

---

## 3. Typographie

**OBLIGATOIRE :** utiliser une seule famille sans-serif principale sur l'interface, **Geist ou Inter**, choisie et chargée globalement. Ne pas changer de police selon les pages. Une police serif n'est autorisée que dans les contextes **éditoriaux** (citations, mises en avant de livres, campagnes) et le logo officiel reste inchangé.

| Rôle | Taille / hauteur de ligne | Graisse |
|---|---|---|
| Titre principal de page | 30 px / 38 px | 600 |
| Titre de section | 22–24 px / 30–32 px | 600 |
| Sous-titre d'interface | 18 px / 26 px | 600 |
| Titre de carte | 16 px / 24 px | 600 |
| Corps principal | 14–16 px / 22–24 px | 400 |
| Texte secondaire | 14 px / 21 px | 400 |
| Label de formulaire | 13 px / 20 px | 500 |
| Bouton | 14 px / 20 px | 600 |
| Aide et métadonnées | 12–13 px / 18–20 px | 400–500 |
| Chiffres de KPI | 24–32 px / 30–40 px | 600 |

- **OBLIGATOIRE :** maximum deux graisses dominantes par composition (400 et 600, avec 500 pour labels).
- Éviter les titres de formulaire surdimensionnés, les grandes lignes de texte trop larges, les capitales décoratives et les interlignages serrés.
- Utiliser `font-variant-numeric: tabular-nums` sur les tableaux numériques, statistiques et montants comparables.
- Privilégier une largeur de lecture de 60–75 caractères pour les paragraphes longs.

---

## 4. Espacement et grille

**Échelle officielle :** `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 px`.

| Usage | Valeur de base |
|---|---:|
| Icône ↔ texte | 8 px |
| Label ↔ champ | 6–8 px |
| Champ ↔ champ | 16–20 px |
| Blocs apparentés | 24 px |
| Sections de page | 32–48 px |
| Padding carte compacte | 16–24 px |
| Padding principal desktop | 24–32 px |
| Padding principal mobile | 16–20 px |

- **OBLIGATOIRE :** utiliser une grille responsive partagée, des marges alignées et des espacements cohérents.
- Éviter les cartes trop larges avec peu de contenu et les grands vides sans intention.
- Ne pas imposer une hauteur fixe aux pages de contenu ; autoriser la croissance naturelle de la page.
- Adapter les tableaux : pagination, colonnes prioritaires, défilement horizontal maîtrisé ou vue mobile dédiée.

---

## 5. Géométrie, bordures et ombres

```css
:root {
  --radius-xs: 4px;
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --control-height-default: 48px;
  --border-width-default: 1px;
  --shadow-subtle: 0 2px 8px rgb(0 0 0 / 0.04);
}
```

- Champs et boutons : rayon **8 px** par défaut.
- Cartes : rayon **12–16 px** si utile.
- Séparations : bordures 1 px neutres, préférées aux ombres.
- **OBLIGATOIRE :** éviter les ombres épaisses, les grands effets de verre, les boutons très arrondis sans justification et les cartes « flottantes » inutiles.

---

## 6. Boutons et actions

- Un **CTA primaire** par formulaire / section d'action.
- Hauteur standard **48 px** (variante compacte 40 px lorsque pertinente), rayon 8 px.
- Texte : 14 px / 600 ; padding horizontal 16–20 px.
- Primaire : fond noir café ou orange officiel **après contrôle du contraste**.
- Secondaire : fond blanc, bordure neutre, texte foncé.
- Tertiaire / lien : sans fond, hiérarchie visuelle discrète.
- États obligatoires : `default`, `hover`, `focus-visible`, `active`, `disabled`, `loading`.
- Le bouton `loading` doit empêcher les doubles soumissions et afficher un état accessible.
- Ne jamais simuler une action par un élément visuellement cliquable qui ne fonctionne pas.

---

## 7. Formulaires

- Champs standard : hauteur **46–48 px**, fond blanc, bordure `--border-default`, rayon 8 px.
- Texte : 14 px. Labels explicites : 13 px / 500, **toujours visibles**.
- Icônes optionnelles : 16–18 px, couleur discrète, jamais au détriment de l'espace de saisie.
- Ne pas utiliser un fond bleu clair sur les champs au repos ; contrôler aussi le rendu de l'autoremplissage du navigateur.
- États obligatoires : `default`, `hover`, `focus-visible`, `filled`, `invalid`, `disabled`, `read-only`, `autofill`, et `success` lorsqu'utile.
- Messages d'erreur proches du champ concerné, compréhensibles et liés par `aria-describedby`.
- Focus visible, labels associés, navigation clavier et gestion accessible du bouton afficher/masquer le mot de passe.
- Ne pas réduire les zones tactiles sous **44 × 44 px** pour les actions interactives mobiles, sauf cas justifié par la norme applicable.

---

## 8. Mise en page et navigation

### Espaces publics
- En-tête sobre, recherche mise en avant lorsque pertinente, densité contrôlée.
- Le livre et sa couverture sont le sujet visuel principal : éviter de surcharger les cartes de badges ou d'actions.
- Les filtres, tris et résultats doivent être compréhensibles et stables.

### Espaces applicatifs (lecteur, auteur, éditeur, organisations, admin)
- Navigation latérale et en-tête partagés lorsque le parcours le nécessite.
- Titres et actions alignés sur une grille commune.
- Tables, cartes, formulaires, onglets, dialogues et notifications réutilisent les composants globaux.
- Une densité plus élevée est permise pour les tableaux de bord, **sans réduire la lisibilité ni les cibles interactives**.

### Authentification
- Sur fond blanc pur : largeur idéale du formulaire **360–400 px**.
- Sur desktop, choisir soit un formulaire centré sans carte décorative, soit un layout à deux colonnes avec panneau éditorial secondaire.
- **Ne pas appliquer d'image de fond sur toute la page derrière le formulaire.**
- Sur mobile : panneau illustré masqué ou repositionné ; formulaire prioritaire.
- Toutes les actions essentielles doivent, si raisonnablement possible, apparaître sans défilement à une hauteur desktop courante, sans imposer de `height` qui tronque le contenu.
- « Mot de passe oublié ? » près du champ de mot de passe ; « Créer un compte » sous l'action principale.
- « Déposer un recours » et « Demander une clôture » relégués au pied de page discret mais accessible ; préserver leur fonctionnement.
- Les mentions de sécurité génériques et décoratives sont facultatives ; ne pas promettre une propriété technique non vérifiée.

---

## 9. Images, couvertures et illustrations

- Les illustrations servent le contenu ; elles ne doivent pas concurrencer un formulaire ou un CTA.
- Couvertures de livres : ratios constants **selon le type de contenu**, `object-fit` cohérent, images nettes et versions adaptées aux résolutions d'écran.
- Prévoir les états image absente, chargement et erreur.
- Chargement différé (`lazy loading`) pour les images hors écran ; éviter le saut de mise en page (dimensions réservées).
- Conserver les visuels et actifs de marque validés ; aucune génération ou substitution d'image sans demande explicite.

---

## 10. Motion et micro-interactions

- Durées usuelles **150–200 ms**, courbe d'animation homogène.
- Animer essentiellement les changements d'état : hover, focus, ouverture, validation, chargement.
- Éviter animations d'entrée systématiques, effets excessifs, grands zooms, parallax et mouvements perturbant la lecture.
- **OBLIGATOIRE :** respecter `prefers-reduced-motion`.
- Aucun effet ne doit retarder l'accès à une action critique.

---

## 11. Responsive et accessibilité

- Concevoir d'abord la logique des composants, puis leurs variantes adaptées au viewport ; ne pas rétrécir simplement l'interface desktop.
- Vérifier au minimum **360, 390, 768, 1024, 1440 et 1649 px** de largeur ; vérifier aussi les hauteurs faibles et les zooms navigateur.
- Pas de débordement horizontal global à 320–360 px sauf contenu explicitement scrollable (ex. tableaux).
- Sémantique HTML correcte, focus visible, navigation clavier, ordre logique, messages d'état annoncés, contrastes WCAG AA.
- Ne pas dépendre uniquement de la couleur pour signaler erreur, succès, sélection ou statut.
- Préserver la lisibilité à 200 % de zoom et l'espacement des textes ; ne pas bloquer l'agrandissement.
- Tester les modes chargement, vide, erreur, succès, absence d'autorisation et réseau lent.

---

## 12. Architecture d'implémentation Nuxt / Tailwind

**OBLIGATOIRE :** adapter à l'architecture réelle du dépôt ; ne pas créer des copies parallèles de composants existants.

Structure cible indicative :

```text
assets/css/
  tokens.css
  typography.css
  globals.css
components/ui/
  UiButton.vue
  UiInput.vue
  UiSelect.vue
  UiTextarea.vue
  UiCard.vue
  UiDialog.vue
  UiAlert.vue
  UiBadge.vue
  UiTabs.vue
  UiTable.vue
  UiSkeleton.vue
layouts/
  default.vue
  auth.vue
  dashboard.vue
```

- Les valeurs de référence vivent dans **une seule source de vérité** (variables CSS ou tokens reliés au thème Tailwind).
- Utiliser `@apply`, utilitaires Tailwind ou classes CSS suivant les conventions établies du projet, sans multiplier les styles concurrents.
- Interdire les styles inline arbitraires lorsque des tokens existent.
- Centraliser les variantes de boutons, champs, cartes et états.
- Employer des composants partagés avec props explicites (`size`, `variant`, `state`, etc.) plutôt que dupliquer le markup.
- Ne pas modifier silencieusement les contrats de composants déjà utilisés dans des parcours métiers.
- Toute migration globale doit comporter un inventaire des pages concernées et une validation de régression.

---

## 13. Gouvernance du design system

### Ce qui est figé
- Palette neutre et système de couleurs sémantiques.
- Famille typographique globale et échelle des tailles.
- Échelle d'espacement, rayons, hauteurs standards et bordures.
- Comportements d'interaction, états, accessibilité et responsive.
- Usage prioritaire des composants UI partagés.

### Ce qui reste adaptable
- La densité des pages complexes et des tableaux.
- Les compositions éditoriales (hero, mise en valeur de livres, campagnes).
- Les présentations spécialisées : lecteur numérique, bibliothèque, publication, impression, KYC.

### Ce qui doit être confirmé dans le dépôt
1. Valeur HEX exacte de l'orange du logo : `--livreaura-orange-officiel`.
2. Validation du noir café `#29180f` par rapport à la charte existante.
3. Choix définitif entre Geist et Inter.
4. Liste et API des composants UI déjà disponibles.
5. Éventuel thème sombre : à concevoir séparément et ne pas le déduire automatiquement des tokens clairs.

**Attention :** le token `--livreaura-orange-officiel` montré dans l'exemple CSS doit être remplacé/raccordé à une variable réellement définie dans le dépôt, faute de quoi la déclaration `--brand-orange` restera inexploitable.

---

## 14. Procédure obligatoire de refonte d'une page pour Cursor

1. **Auditer** le composant actuel, ses sous-composants, ses dépendances et ses états.
2. **Identifier** les tokens et composants partagés existants.
3. **Établir un plan** minimal de modifications, sans changer les parcours métiers.
4. **Appliquer** typographie, surfaces, grille, tailles et états selon ce contrat.
5. **Conserver** toutes les informations, opérations, validations, liens et autorisations.
6. **Tester** desktop, tablette, mobile, clavier, erreurs et chargement.
7. **Documenter** les exceptions et les nouveaux composants réutilisables.
8. **Rendre compte** des fichiers changés, des règles appliquées et des contrôles effectués.

### Instruction prête à copier dans Cursor

> Lis `LIVREAURA_DESIGN_SYSTEM_CURSOR.md` dans son intégralité. Effectue d'abord un audit de la page et des composants réutilisables. Réalise une refonte visuelle conforme au contrat global sans modifier les fonctionnalités, la logique métier, les routes, les validations ni les autorisations. Réutilise les tokens et composants partagés plutôt que des valeurs locales. Conserve le logo et la couleur orange officiels. Vérifie les états interactifs, la navigation clavier, les contrastes, les versions responsive et l'absence de régressions. À la fin, récapitule précisément les fichiers modifiés, les décisions prises et les tests effectués.

---

## 15. Critères d'acceptation d'un écran

- [ ] Fond général blanc et surfaces cohérentes ; aucune décoration intrusive.
- [ ] Logo officiel respecté ; orange exact réutilisé via un token partagé.
- [ ] Typographie conforme aux rôles et tailles définis.
- [ ] Alignements et espacements issus de l'échelle commune.
- [ ] Champs et boutons respectent tailles, états, rayons et contrastes.
- [ ] Action principale immédiatement identifiable.
- [ ] Liens secondaires visibles, hiérarchisés et fonctionnels.
- [ ] Composants existants mutualisés, pas de duplication inutile.
- [ ] Affichage correct sur mobile, tablette et desktop, y compris zoom.
- [ ] Navigation clavier et focus visible opérationnels.
- [ ] États loading / error / empty / success gérés quand ils s'appliquent.
- [ ] Réduction du mouvement prise en charge.
- [ ] Aucun parcours métier, appel API, contrôle d'accès ou validation cassé.
- [ ] Aucun texte ou contrôle coupé sur une hauteur de viewport réduite.
- [ ] Exceptions documentées ; aucune valeur « magique » non justifiée.

---

## 16. Ordre de migration recommandé

1. **Fondations :** couleur orange validée, police choisie, tokens CSS, règles globales.
2. **Bibliothèque UI :** boutons, champs, liens, messages, cartes, tableaux, dialogues.
3. **Layouts partagés :** authentification, public, application, administration.
4. **Écrans pilotes :** connexion, inscription, catalogue livres, fiche livre, tableau de bord.
5. **Déploiement progressif :** toutes les autres pages par domaine fonctionnel.
6. **Audit final :** capture d'écran comparée, contrôle responsive, accessibilité, régressions.

**Définition de terminé :** une page n'est « premium » que lorsqu'elle est cohérente avec tout le système et reste fonctionnelle, accessible et agréable sur l'ensemble des supports.
