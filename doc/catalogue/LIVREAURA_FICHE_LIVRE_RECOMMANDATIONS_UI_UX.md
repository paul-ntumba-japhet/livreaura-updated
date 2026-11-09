# LIVREAURA — Refonte premium de la fiche d’un livre

**Document de recommandations UX/UI, contenu et implémentation pour Cursor**  
**Périmètre :** page publique `/catalogue/oeuvres/[slug]` (adapter à la route réellement définie)  
**Référence observée :** `/catalogue/oeuvres/test-recits-deux-rives`  
**Version :** 1.0 — 9 novembre 2026  
**Contexte :** chaîne A2 — Catalogue ; continuité avec le design system Livreaura et la grille de livres déjà refondue.

---

## 1. Mission

Transformer la fiche actuelle d’une œuvre en **page de découverte, de consultation et de sélection d’offres premium**, à la fois sobre, précise et rassurante, cohérente avec les interfaces publiques déjà validées de Livreaura.

**Il ne s’agit pas d’une simple correction CSS.** Revoir aussi l’ordre, le regroupement, la priorité et la pertinence des informations. Supprimer de la vue principale les détails superflus, les champs absents, les textes techniques et les répétitions, **sans supprimer les données du système**.

**Principes directeurs :**

1. Le livre lui-même prime : **couverture → titre / auteur → description → formats / prix / actions**.
2. La première zone visible doit permettre d’identifier l’œuvre et de comprendre comment l’obtenir ou la consulter.
3. Aucun texte technique, métadonnée interne ou état vide ne doit distraire le lecteur.
4. L’orange officiel est un accent fonctionnel, pas une couleur de remplissage décorative.
5. Réutiliser les tokens et composants existants ; ne pas créer une deuxième charte graphique.
6. **Préserver rigoureusement les distinctions métier** : œuvre, édition, format, offre et droit d’accès.

---

## 2. Diagnostic précis des captures fournies

| Observation | Problème UX | Action recommandée |
|---|---|---|
| La couverture n’apparaît que très bas dans une grande colonne beige | Mauvais alignement vertical ou panneau surdimensionné ; le visuel principal disparaît du premier écran | Afficher la couverture dès le haut de la fiche, sans vide disproportionné ; auditer le CSS du conteneur |
| « Disponible » est isolé au-dessus de l’auteur | Message générique à faible valeur | Masquer si toutes les offres accessibles sont normalement disponibles ; préciser seulement les restrictions réelles |
| Auteur et métadonnées éloignés du titre | Lecture fragmentée | Regrouper titre, sous-titre, auteur et éléments éditoriaux essentiels |
| Date au format « 4 novembre 2026 à 09:00 (heure de Kinshasa) » | Précision inutile pour une fiche publique | Préférer année ou date d’édition pertinente ; conserver l’horodatage interne ailleurs |
| « Badge : Non renseigné », « Niveau : Non renseigné » | Bruit informationnel | Ne rien afficher lorsqu’une donnée facultative est absente |
| « Origine africaine : Oui », « Mise en avant : Oui » | Drapeaux internes exposés comme attributs éditoriaux | Supprimer de la fiche ; si une distinction éditoriale est utile, prévoir un vrai libellé éditorial validé |
| Catégories suivies de chemins `education/...` et `litterature/...` | Exposition inutile des slugs techniques | Ne montrer que les noms lisibles, sous forme de liens |
| EPUB gratuit, PDF en USD, PDF en CDF présentés dans de grandes cartes identiques | Les offres occupent trop d’espace et l’action de choix n’est pas évidente | Un sélecteur d’offres compact ; actions contextuelles connectées à la logique existante |
| Message bleu « La vérification du droit de lecture n’est pas disponible... » | Diagnostic technique qui n’a pas sa place dans le contenu commercial | Ne pas exposer en permanence ; gérer l’incertitude au moment de l’action avec un message exact et non trompeur |
| Éditions et collection dispersées en fin de page | Hiérarchie secondaire non maîtrisée | Placer dans « Informations sur l’ouvrage » / « Éditions », selon leur pertinence |
| « Aucune autre image n’est publiée », « Aucun avis publié » | Sections vides sans valeur | Masquer la section vide, sauf action utilisateur réellement disponible |
| « Retour au catalogue » sous forme de grand bouton et lien « Catalogue » | Navigation redondante | Utiliser un fil d’Ariane discret ou un seul lien de retour, pas les deux en gros CTA |

**Constat de priorité 0 :** la couverture n’est pas correctement mise en évidence au chargement et cela nuit davantage à la fiche que toute question de couleur ou de typographie.

---

## 3. Contenu indispensable de la première zone visible

### Toujours afficher si disponible

- Couverture principale **réelle** et correctement chargée.
- Titre de l’œuvre.
- Sous-titre, **uniquement** s’il existe.
- Auteur(s) et rôle(s) si la distinction est utile ; liens vers les pages d’auteur disponibles.
- Une description éditoriale courte et lisible ; pas de texte inventé pour meubler.
- Une synthèse commerciale vérifiée : « Gratuit » ou prix de départ dans une devise **cohérente**.
- Sélection des formats / offres publics effectivement disponibles.
- **Une action principale réelle**, adaptée à l’offre et aux droits.

### Afficher seulement lorsqu’utile

- Langue(s), notamment pour un ouvrage bilingue.
- Nombre de pages si associé au bon niveau métier.
- Disponibilité limitée, précommande, indisponibilité, restrictions territoriales ou d’usage, si réelles.
- Éditeur, édition, année, ISBN.
- Catégories et collection, sous forme de liens lisibles.

### Éviter dans la première zone

- Date et heure exactes de création / publication dans l’application.
- Flags de configuration ou de modération.
- Identifiants et slugs internes.
- Répétitions de prix et d’offres sous forme de paragraphes.
- Statuts techniques du lecteur, DRM ou vérifications internes de droits, sauf si cela change directement l’action possible.

---

## 4. Nouvelle architecture de page

### 4.1. Navigation légère

- Réutiliser le **header public Livreaura** déjà adopté pour le catalogue : logo, Accueil, Catalogue, Connexion et S’inscrire selon l’état de session.
- Ajouter en haut du contenu un **fil d’Ariane discret** : `Catalogue / [Catégorie principale si pertinente] / [Titre du livre]`.
- Éviter de conserver un large bouton « Retour au catalogue » si le fil d’Ariane fait déjà ce travail.
- Les chemins de navigation doivent utiliser les vraies routes et libellés issus du backend.

### 4.2. Zone principale : trois colonnes sur grand desktop

Ordre recommandé :

1. **Colonne visuelle** — couverture, éventuellement galerie.
2. **Colonne éditoriale** — titre, sous-titre, auteur, métadonnées essentielles, description.
3. **Colonne commerciale** — prix synthétique, choix d’offre, actions.

Proportions indicatives pour la zone centrale : **28 % / 42 % / 30 %**, à ajuster avec des largeurs minimales et maximales ; ne pas imposer des pourcentages si cela provoque un écrasement.

```text
Catalogue / Littérature / Nouvelles

┌────────────────────┬─────────────────────────────┬────────────────────────┐
│                    │ Récits entre deux rives     │ À partir de : Gratuit │
│ VRAIE COUVERTURE   │ Édition bilingue            │                        │
│                    │ Amélie Nsona                │ EPUB        Gratuit   │
│  object-fit:       │                             │ PDF         6 USD     │
│  contain           │ Français · Anglais          │ [autre offre réelle]   │
│                    │                             │                        │
│                    │ Description éditoriale      │ [Action selon offre]   │
└────────────────────┴─────────────────────────────┴────────────────────────┘

Description détaillée
Informations sur l’ouvrage
Éditions disponibles (si pertinent)
Avis / autres images / recommandations (seulement si utiles)
```

### 4.3. Ne pas créer de grandes cartes partout

La colonne commerciale peut avoir une bordure neutre discrète. Les deux autres colonnes n’ont pas besoin d’être enfermées dans de grands rectangles.

---

## 5. Couverture : correction prioritaire

Sur les captures, la colonne beige est visible pendant une grande partie du défilement et la couverture n’apparaît que plus bas. **Corriger cette anomalie en priorité.**

- Ancrer le visuel **en haut** de sa colonne avec `align-items: start` / `align-self: start`, selon le layout.
- Vérifier la présence éventuelle de `justify-content: center/end`, `height: 100%`, `min-height` excessif, `position: sticky` mal dimensionné, ou d’une image rendue au bas d’un conteneur étiré.
- Ne pas faire dépendre la hauteur du conteneur visuel de toute la colonne de droite.
- Utiliser la véritable URL de média de l’œuvre, avec chargement et fallback explicites.
- Dimensions cibles desktop : largeur visuelle d’environ **260–320 px**, ajustable selon la grille.
- Conserver les proportions de la couverture ; privilégier `object-fit: contain` pour ne pas couper le titre intégré au visuel.
- Background neutre (`#FFFFFF`, éventuellement `#FAFAFA`), sans aplat beige géant.
- Ne pas rendre toute la colonne sticky. Si la galerie devient sticky sur grand écran, sa hauteur doit rester inférieure à l’espace vertical réellement disponible ; désactiver ce comportement sur petit écran.
- Si plusieurs médias publics existent, afficher de petites miniatures. **Sinon, aucune galerie vide.**
- Option : zoom / visualisation agrandie accessible au clavier et au tactile.

---

## 6. Fiche éditoriale : ordre et sobriété

Ordre recommandé dans la colonne centrale :

1. Titre de l’œuvre — `h1` unique.
2. Sous-titre non vide.
3. Auteur(s), liens et rôle(s) pertinents.
4. Une ligne de métadonnées courte, par ex. `Français · Anglais` et `120 pages` si exact.
5. Description ou résumé éditorial **fourni par les données**, avec « Lire la suite » si le texte est long.
6. Liens de catégories en texte neutre, peu nombreux, sans montrer le slug.

Pour l’ouvrage illustré, l’information de test observée « Recueil fictif bilingue français et anglais... permet de vérifier la distinction œuvre–édition » ressemble à une note destinée aux développeurs. Elle ne doit pas être exposée telle quelle dans un catalogue grand public : **remplacer uniquement si un vrai résumé éditorial est disponible, sinon ne pas inventer de résumé**.

- Supprimer la mention « (Auteur) » lorsqu’elle est inutile (un auteur seul est déjà identifié par son emplacement). La conserver si plusieurs contributeurs doivent être distingués.
- Ne pas afficher « Disponible » comme texte systématique ; préférer un état qui change réellement le choix du lecteur.
- Éviter les liens orange appliqués à toutes les métadonnées ; liens neutres avec hover / focus clair.

---

## 7. Offres : sélecteur commercial compact et exact

### 7.1. Structure

Créer ou réutiliser un composant `BookOfferSelector` qui :

- présente **seulement les offres publiquement accessibles** ;
- affiche format, prix et devise, avec libellé commercial précis ;
- permet de sélectionner clairement l’offre en cours ;
- met à jour le prix principal et l’action pertinente ;
- gère les états indisponibles sans permettre une action trompeuse ;
- ne mélange pas les éditions / licences / territoires qui ont des conditions différentes.

### 7.2. Cas réel visible dans la capture

- EPUB — `0,00 USD` (affichage possible : **Gratuit**, uniquement si l’offre le permet réellement).
- PDF — `6,00 USD`.
- PDF — `15 000,00 CDF`.

**Attention :** deux offres PDF dans des devises différentes ne sont pas automatiquement interchangeables. Les regrouper dans un choix de devise uniquement si elles portent sur la même édition, les mêmes droits, les mêmes conditions et si le contrat métier le permet. Sinon, les présenter comme offres distinctes.

### 7.3. Prix de départ et devises

- Utiliser la devise de consultation / de paiement réellement prise en charge par l’interface.
- Ne jamais comparer numériquement `6 USD` à `15 000 CDF` sans convention de conversion fiable.
- Ne jamais annoncer « À partir de 0 » si cette offre n’est pas accessible au visiteur ou dans son contexte.
- Si une offre gratuite existe et est accessible, « Gratuit » est préférable à « 0,00 USD ».
- Si aucun prix fiable ne peut être synthétisé, afficher « Voir les offres » plutôt qu’un montant inventé.

### 7.4. Actions par offre

- Achat : bouton « Acheter » seulement si la chaîne commerce / checkout est opérationnelle.
- Lecture gratuite : « Lire gratuitement » uniquement si les droits et le lecteur l’autorisent.
- Obtention gratuite : « Obtenir gratuitement » si l’acquisition d’une licence gratuite est effectivement implémentée.
- Extrait : « Lire un extrait » seulement si un aperçu public autorisé est disponible.
- Papier : préciser la disponibilité / les modalités physiques seulement si une vraie offre papier existe.

**Pas d’action fictive.** En cas de fonctionnalité à venir, ne pas créer de bouton qui simule une opération réussie.

---

## 8. Informations à supprimer, déplacer ou conditionner

| Champ / bloc actuel | Décision | Destination éventuelle |
|---|---|---|
| `Badge : Non renseigné` | **Supprimer** | Aucune |
| `Niveau : Non renseigné` | **Supprimer** | Réapparaît seulement si renseigné et utile |
| `Mise en avant : Oui` | **Supprimer** | Flag interne de merchandising |
| `Origine africaine : Oui` | **Supprimer sous cette forme** | Label éditorial lisible seulement si défini et pertinent |
| Date de publication précise à 09:00, heure de Kinshasa | **Simplifier** | Année / date éditoriale si pertinente |
| Slugs `education/langues-et-expression`, `litterature/nouvelles` | **Supprimer de l’affichage** | Les conserver dans les URL et données |
| Message « La vérification du droit de lecture n’est pas disponible » | **Retirer de la fiche permanente** | Gestion contextuelle de l’action / erreur, sans fausse assurance |
| Cartes géantes EPUB/PDF | **Remplacer** | Sélecteur d’offres compact |
| Édition archivée non commercialisée | **Ne pas promouvoir** | Détails, si une consultation publique est légitime |
| `Aucune autre image n’est publiée` | **Masquer la section** | Galerie si images publiées |
| `Aucun avis publié` | **Masquer**, sauf interaction réelle | Avis et action d’évaluation si module opérationnel |
| Explication technique de la collection (« ne forme ni un prix ni un droit groupé ») | **Supprimer** | Règles internes métier |
| Texte d’essai explicitant des scénarios de test | **Ne pas afficher au public** | Données de test / documentation technique |

**Règle globale :** cacher une information dans l’UI ne doit jamais entraîner sa suppression dans la base ni l’altération de la traçabilité.

---

## 9. Sections sous la zone principale

Limiter les sections permanentes au strict nécessaire et adopter cet ordre :

### 9.1. Description

Le texte descriptif complet, si disponible. S’il a déjà été montré intégralement dans la zone principale, ne pas le répéter.

### 9.2. Informations sur l’ouvrage

Tableau léger ou liste de définitions de métadonnées réellement renseignées : nombre de pages, langues, éditeur, édition, année, ISBN, collection. Adapter le niveau métier des attributs : certaines informations relèvent de l’édition, pas de l’œuvre.

### 9.3. Éditions disponibles

À afficher seulement en présence de plusieurs éditions **pertinentes et publiquement consultables**. Ne pas confondre une édition avec le format d’une offre.

### 9.4. Avis des lecteurs

À afficher s’il existe des avis publics ou une fonctionnalité d’avis réellement accessible. Pas de grand état vide simplement « Aucun avis ».

### 9.5. Autres médias et recommandations

- Galerie uniquement si plusieurs images autorisées sont publiées.
- « Du même auteur » / « Livres similaires » seulement si les résultats existent, sont pertinents et respectent la visibilité catalogue.

Les sections secondaires peuvent être repliables lorsque cela améliore la lecture, sans cacher artificiellement les informations importantes.

---

## 10. Identité visuelle cohérente avec Livreaura

Réutiliser les valeurs du design system déjà présent. Les valeurs ci-dessous sont des **cibles indicatives**, à convertir en tokens communs et non en styles isolés.

| Élément | Cible |
|---|---|
| Fond général | `#FFFFFF` |
| Fond secondaire ponctuel | `#FAFAFA` |
| Texte principal | `#171717` |
| Texte secondaire | `#737373` |
| Bordures | `#E5E5E5` |
| Accent primaire | Orange officiel Livreaura (token existant) |
| Police UI | Geist ou Inter selon le projet |
| Titre de l’œuvre | 30–34 px desktop, adaptatif |
| Sous-titre | 15–16 px |
| Auteur / description | 14–15 px |
| Métadonnées | 12–13 px |
| Prix principal | 24–28 px |
| Bouton primaire | Hauteur 42–46 px |
| Rayon surfaces | 8–12 px |
| Espaces entre sections | 24–32 px |
| Largeur max du contenu | 1280–1360 px |

- Éviter les grandes ombres, les dégradés décoratifs et les fonds beige rosé occupant une grande hauteur.
- Préserver les états `hover`, `focus-visible`, `disabled`, `loading` et `error` des composants UI.
- Ne pas ajouter un nouveau jeu d’icônes si celui du projet suffit.

---

## 11. Responsive et accessibilité

### Grand écran

Trois colonnes, couverture visible sans scroller, lecteur focalisé sur l’œuvre et les offres.

### Écran moyen / tablette

Passer progressivement à deux colonnes : couverture + contenu ; panneau d’offres sous ou à côté selon la place disponible.

### Mobile

Ordre : fil d’Ariane discret → couverture → titre / auteur → prix / format / action → description → informations secondaires. Utiliser une seule colonne, éviter les débordements latéraux et les panneaux fixes qui masquent le contenu.

### Accessibilité

- Un `h1` unique ; autres titres dans un ordre sémantique.
- Texte alternatif pertinent de la couverture ; ne pas recopier inutilement toute la couverture dans le `alt`.
- Sélection des offres utilisable au clavier, avec état sélectionné annoncé.
- Contrastes conformes aux critères WCAG AA.
- Zones de clic / tactiles suffisantes.
- Erreurs, restrictions de lecture et échecs d’achat annoncés clairement.
- Ne pas rendre le prix ou la disponibilité compréhensibles **uniquement par la couleur**.

---

## 12. Contraintes métier A2 / commerce / droits

1. **Œuvre** : titre, description, auteurs, classifications.
2. **Édition** : éditeur, date, ISBN, langues, caractéristiques éditoriales.
3. **Format** : EPUB, PDF, papier, etc. selon le modèle réellement stocké.
4. **Offre** : prix, devise, canal, statut commercial, disponibilité, conditions.
5. **Droit de lecture / licence** : calcul et vérification indépendants de la simple existence d’une offre.

**Interdictions :**

- Ne pas modifier arbitrairement les migrations, tables, statuts, contrats API ou règles de disponibilité.
- Ne pas exposer les offres non publiées / archivées comme si elles étaient immédiatement achetables.
- Ne pas exposer les médias privés, extraits sous droits, informations administratives ou métadonnées internes.
- Ne pas assimiler « gratuit » à une licence déjà acquise.
- Ne pas déclarer une vérification de droits réussie en cas de service inaccessible.
- Ne pas créer de faux prix convertis entre CDF et USD.
- Ne pas inventer de synopsis ni d’avis.

Avant de modifier les actions, inspecter le backend et les contrats API déjà utilisés par le catalogue.

---

## 13. Architecture de composants proposée (à adapter)

```text
components/
  catalogue/
    BookDetailHero.vue
    BookCoverGallery.vue
    BookEditorialSummary.vue
    BookOfferSelector.vue
    BookOfferOption.vue
    BookMetadata.vue
    BookEditionList.vue
    BookRelatedTitles.vue
    BookDetailSkeleton.vue
```

**Ce n’est pas une consigne pour multiplier les fichiers :** réutiliser en priorité les composants du projet et n’extraire que les éléments ayant une responsabilité indépendante ou une réelle réutilisabilité.

Les calculs de prix, droits, disponibilité et regroupement d’offres doivent rester dans les services / composables adaptés et non dans les templates de présentation.

---

## 14. Ordre d’implémentation pour Cursor

### Phase 1 — Correction de la couverture et du layout

- Identifier la cause du grand panneau beige et de la couverture décalée.
- Placer la couverture visible dès le premier écran.
- Réorganiser la zone principale en trois parties adaptatives.
- Conserver le header partagé du catalogue.

### Phase 2 — Simplification éditoriale

- Regrouper titre, auteur, langues et description.
- Retirer les champs non renseignés, slugs, flags internes et répétitions.
- Réorganiser les sections secondaires.

### Phase 3 — Offres et actions

- Remplacer les grandes cartes par un sélecteur compact.
- Vérifier les relations édition / format / offre / devise.
- Connecter les CTA aux fonctionnalités réellement existantes.

### Phase 4 — Finitions et tests

- Adapter desktop / tablette / mobile.
- Vérifier l’accessibilité, l’absence de débordements, les états de chargement et les erreurs.
- Contrôler les permissions, les médias publics, les offres gratuites et payantes.
- Tester les cas à une seule édition, plusieurs éditions, une seule offre, plusieurs devises, sans couverture, sans description, sans avis, sans prix et sans offre publique.

---

## 15. Checklist de validation

### Présentation

- [ ] La vraie couverture apparaît en haut de la page, sans grande colonne vide.
- [ ] Titre, sous-titre et auteur sont regroupés.
- [ ] La composition est équilibrée et respecte le design system global.
- [ ] Les offres occupent un espace raisonnable et sont faciles à comparer.
- [ ] Les marges, tailles, couleurs et états interactifs sont cohérents avec la grille catalogue.
- [ ] Responsive correct et aucun débordement horizontal.

### Pertinence du contenu

- [ ] Aucun champ « Non renseigné » visible.
- [ ] Aucun slug technique affiché sous les catégories.
- [ ] Aucun drapeau interne « Mise en avant : Oui » affiché.
- [ ] Aucune section vide « Autres images » ou « Avis ».
- [ ] Aucune date technique inutile dans la zone principale.
- [ ] Aucune explication développeur/test dans le descriptif public.
- [ ] Aucune répétition inutile entre le résumé et les détails.

### Fonctionnel et métier

- [ ] Les offres visibles correspondent réellement à celles publiées.
- [ ] La devise et les prix sont fiables ; aucune conversion fictive.
- [ ] Sélection d’offre et CTA donnent le comportement autorisé.
- [ ] Les contrôles de droits ne sont pas contournés.
- [ ] Le cas d’une offre gratuite est traité sans attribution automatique de licence.
- [ ] Les éditions archivées ne sont pas promues à tort.
- [ ] Le modèle A2 reste intact ; aucune suppression de données métier.
- [ ] Les états d’erreur et les limitations fonctionnelles sont gérés clairement.

---

## 16. Instruction finale à Cursor

> Examine la route publique de fiche œuvre et les composants réellement utilisés par Livreaura. **Implémente directement** les recommandations de ce document, en commençant par le problème de couverture décalée et la hiérarchie de la première zone visible. Préserve le design system existant, les routes, API, statuts, permissions, offres, devises et distinctions œuvre–édition–format–offre–licence. Supprime de l’interface les informations redondantes, techniques, vides ou non indispensables, mais ne supprime aucune donnée métier. Ne crée pas de fonctionnalité factice. Termine par un rapport concis des fichiers modifiés, composants réutilisés, informations supprimées de l’UI, comportements vérifiés, tests exécutés et éventuelles limites.

**Critère de réussite ultime :** en quelques secondes, le visiteur doit voir **quel livre il consulte, qui l’a écrit, de quoi il parle et comment le lire ou l’obtenir**, sans devoir parcourir une succession de cartes et de métadonnées techniques.
