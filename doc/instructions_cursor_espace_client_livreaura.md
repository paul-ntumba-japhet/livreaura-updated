# Livreaura — Instructions Cursor pour améliorer l’espace numérique (sans image)

## Objectif
Refondre l’espace client `/compte` pour obtenir un rendu **premium, professionnel et cohérent avec la charte Livreaura**, avec une structure proche de la maquette validée.

**Important :**
- viser un rendu proche de la maquette générée ;
- **ne pas utiliser d’images, de photos, d’illustrations décoratives ni de bannières bitmap** ;
- produire le même niveau de qualité visuelle uniquement avec la **mise en page, la typographie, les cartes, les icônes, les badges, les couleurs et les espacements**.

---

## Intention UI
L’espace client doit donner l’impression :
- d’un tableau de bord éditorial haut de gamme ;
- d’un produit sérieux, moderne et rassurant ;
- d’une interface plus structurée, plus lisible et plus valorisante que la version actuelle.

Le rendu recherché est :
- lumineux ;
- très propre ;
- aéré ;
- hiérarchisé ;
- discret ;
- cohérent avec le tandem **Coffee Black + Orange**.

---

## Contraintes majeures
1. **Aucune image**
   - pas de photo d’ambiance ;
   - pas d’illustration ;
   - pas de vignette décorative ;
   - pas de bloc hero avec image ;
   - pas de texture importée.
2. Utiliser uniquement :
   - couleurs de la charte ;
   - typographies du design system ;
   - icônes vectorielles ;
   - cartes ;
   - bordures ;
   - badges ;
   - ombres légères ;
   - séparateurs ;
   - composants UI existants ou cohérents avec le système.
3. **Ne pas casser la logique métier existante** :
   - routes ;
   - appels API ;
   - stores/composables ;
   - permissions ;
   - statuts ;
   - actions.
4. Réutiliser les **tokens** existants ; ne pas multiplier les couleurs codées en dur.
5. Conserver le contenu métier actuel, mais améliorer fortement la présentation.

---

## Structure globale attendue

### 1. Layout dashboard
Transformer la page en vrai dashboard applicatif avec :

#### A. Topbar
Une barre supérieure contenant :
- logo Livreaura à gauche ;
- champ de recherche global au centre ou centre-gauche ;
- actions utilitaires à droite :
  - notifications (si déjà prévues) ;
  - avatar/initiale utilisateur ;
  - nom utilisateur ;
  - menu de session / déconnexion.

#### B. Sidebar gauche
Créer une navigation latérale fixe ou semi-fixe avec entrées claires :
- Vue d’ensemble
- Profil
- Organisations
- Vérification
- Sécurité
- Sessions
- Consentements

Règles :
- item actif bien visible ;
- icône + libellé ;
- style sobre ;
- fonds clairs ;
- accent orange uniquement pour l’état actif ou focus.

#### C. Zone principale
Zone de contenu principale en largeur confortable, avec :
- titre de bienvenue ;
- sous-texte d’introduction ;
- CTA principal de vérification ;
- grille de cartes de synthèse ;
- blocs de détails organisés par priorité.

---

## Composition recommandée de la page

### 1. En-tête de contenu
En haut de la zone principale :
- titre : `Bonjour, {Prénom}.`
- sous-texte : résumé clair de la situation du compte ;
- à droite ou dans la même ligne : bouton primaire
  - `Ouvrir un dossier de vérification`

Le bouton doit être mis en valeur, mais ne pas casser l’équilibre général.

---

### 2. Cartes de synthèse (KPI / statut)
Créer une première ligne de **4 cartes** de synthèse.

Exemples :
1. **État du compte**
   - badge `Actif`
   - micro-texte : `Votre compte est en règle.`

2. **Portée de la session**
   - badge `Accès normal`
   - micro-texte : `Accès aux fonctionnalités standard.`

3. **Rôle principal**
   - badge `Lecteur`
   - micro-texte : `Votre rôle sur la plateforme.`

4. **Vérification**
   - badge `À démarrer` ou équivalent
   - micro-texte : `Constituez votre dossier.`

Règles visuelles :
- cartes de même hauteur ;
- icône circulaire ou pictogramme discret ;
- titre de carte ;
- badge sémantique ;
- ligne explicative courte ;
- ombre légère et bordure subtile.

---

### 3. Grille principale de contenu
Sous les cartes de synthèse, organiser la page en **grille desktop 2 colonnes**.

#### Colonne A
- Profil et identité
- Vérification
- Consentements

#### Colonne B
- Organisations
- Sécurité
- Session courante

Sur mobile :
- repasser en une seule colonne.

---

## Détail des sections

### Profil et identité
Présenter les informations d’identité dans une carte élégante :
- titre de section ;
- action secondaire `Modifier` ;
- contenu en grille de paires label/valeur.

Exemples de champs :
- Adresse e-mail
- Langue
- Prénom
- Nom
- Nom de plume
- Pays
- Ville

Règles :
- labels secondaires ;
- valeurs bien lisibles ;
- séparation claire ;
- pas de gros pavé vertical brut.

---

### Organisations
Créer une carte dédiée avec :
- titre de section ;
- état principal ;
- zone d’état vide élégante si aucune organisation.

Exemple :
- titre interne : `Aucune organisation`
- texte d’aide : `Ce compte n’est encore rattaché à aucune organisation.`
- bouton secondaire : `Gérer les organisations`

L’état vide doit être visuellement soigné, avec icône simple, cadre discret et texte court.

---

### Sécurité
Créer une carte claire contenant :
- titre ;
- texte explicatif ;
- action `Changer le mot de passe`

Le bouton doit être en style secondaire/outline et non primaire.

---

### Vérification
Créer une carte dédiée et plus visible, car importante pour le produit :
- titre ;
- texte explicatif ;
- bouton primaire `Ouvrir un dossier de vérification`

La carte peut recevoir un accent visuel léger (bordure ou en-tête subtil) mais **sans image**.

---

### Consentements
Présenter les consentements dans une carte lisible :
- titre ;
- éventuellement action `Voir les documents`
- liste de consentements avec :
  - intitulé ;
  - version ;
  - badge `Accepté` ;
  - date/heure d’acceptation.

Ne pas afficher cela comme un simple texte brut. Préférer des lignes structurées.

---

### Session courante
Créer une carte importante pour les informations de session :
- titre ;
- badge `Active` ;
- grille d’informations :
  - Portée
  - Ouverte
  - Dernière activité
  - Échéance absolue
  - Échéance d’inactivité
  - Contexte

Sous ces données :
- bouton secondaire : `Se déconnecter de cette session`
- bouton danger : `Révoquer toutes les sessions`

Le bouton danger doit être visuellement distinct, mais rester élégant.

Sous la zone principale des actions sensibles :
- lien discret danger : `Demander la clôture de ce compte`

---

## Style visuel

### Palette
Utiliser uniquement les couleurs de la charte / tokens correspondants :
- Coffee Black
- Orange
- Ivory / off-white
- Soft Sand
- Warm Gray
- White
- couleurs d’état sémantiques pour succès / info / danger

### Typographie
- **Noto Serif** pour les grands titres de page et quelques en-têtes majeurs.
- **Inter** pour le corps, les labels, badges, boutons et navigation.

### Cartes
Toutes les sections doivent être dans des cartes homogènes :
- rayon cohérent ;
- padding généreux ;
- bordure légère ;
- ombre très modérée ;
- fonds clairs.

### Espacements
Accentuer la respiration :
- plus d’espace entre sections ;
- plus d’espace entre titres et contenu ;
- alignements propres ;
- marges cohérentes.

### Icônes
Utiliser des icônes simples, cohérentes et fines :
- profil ;
- organisation ;
- sécurité ;
- vérification ;
- sessions ;
- consentements.

Pas d’icônes trop épaisses ni trop décoratives.

---

## Comportements attendus
- Le tableau de bord doit être immédiatement compréhensible.
- Les CTA primaires doivent ressortir sans être agressifs.
- Les actions sensibles doivent être différenciées :
  - secondaire pour les actions neutres ;
  - danger pour les révocations/clôtures.
- Les états vides doivent être premium, pas simplement du texte nu.
- Tous les liens et boutons doivent conserver les comportements existants.

---

## Responsive
### Desktop
- sidebar visible ;
- topbar complète ;
- grille 4 cartes en synthèse ;
- grille principale 2 colonnes.

### Tablette
- sidebar éventuellement réduite ;
- cartes synthèse en 2x2 ;
- sections en une ou deux colonnes selon largeur.

### Mobile
- sidebar remplacée par navigation compacte/drawer ;
- topbar simplifiée ;
- cartes synthèse empilées ;
- toutes les sections en une colonne ;
- boutons pleine largeur si nécessaire.

---

## Accessibilité
- conserver les contrastes ;
- conserver les focus states visibles ;
- garder les balises sémantiques correctes ;
- badges non porteurs d’information uniquement par couleur ;
- actions sensibles explicites.

---

## Ce qu’il ne faut pas faire
- ne pas ajouter d’images ;
- ne pas transformer la page en landing page marketing ;
- ne pas surcharger avec trop d’orange ;
- ne pas multiplier les styles de cartes ;
- ne pas disperser les actions ;
- ne pas casser la structure de données métier ;
- ne pas créer de faux statuts ;
- ne pas réécrire les règles backend.

---

## Critères d’acceptation
Le résultat est valide si :
1. l’espace client ressemble à un **dashboard premium** ;
2. il reste **100 % cohérent avec Livreaura** ;
3. il est **plus lisible et plus structuré** que la version actuelle ;
4. il est **proche de la maquette générée**, mais sans aucune image ;
5. il utilise uniquement la charte, les tokens et les composants cohérents ;
6. il conserve tous les comportements fonctionnels existants.

---

## Plan d’implémentation recommandé
1. Refondre le layout général :
   - topbar ;
   - sidebar ;
   - zone principale.
2. Créer ou améliorer les composants de base :
   - dashboard card ;
   - stat card ;
   - section card ;
   - badge d’état ;
   - action row.
3. Recomposer la page `/compte`.
4. Vérifier desktop, tablette, mobile.
5. Vérifier focus, hover, disabled, loading si nécessaire.
6. Vérifier qu’aucune logique métier n’a été cassée.

---

## Attendu final
Produire une page compte / espace numérique :
- plus éditoriale ;
- plus professionnelle ;
- plus premium ;
- plus structurée ;
- sans image ;
- immédiatement exploitable dans le produit.
