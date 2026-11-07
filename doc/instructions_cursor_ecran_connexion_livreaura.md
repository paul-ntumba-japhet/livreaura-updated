# Livreaura — Instructions Cursor pour l’écran de connexion

## Objectif
Modifier l’écran `/connexion` afin qu’il corresponde à la maquette Livreaura premium validée, tout en conservant la logique métier, les routes, validations, appels API et composants fonctionnels existants.

## Direction visuelle
- Conserver l’identité **Coffee Black + Orange** définie dans la charte.
- Utiliser le fond éditorial Livreaura fourni comme image plein écran.
- Conserver une grande zone claire et respirante autour de la carte.
- Le rendu doit être élégant, sobre, éditorial et premium, sans effets excessifs.

## Structure de la page
1. Afficher le fond sur toute la hauteur de l’écran avec `background-size: cover`.
2. Centrer horizontalement la carte de connexion.
3. Prévoir une largeur de carte d’environ **520 à 580 px** sur desktop.
4. Carte :
   - fond blanc/ivoire très léger ;
   - bordure discrète ;
   - `border-radius` cohérent avec le design system ;
   - ombre très douce ;
   - padding généreux.
5. Sur mobile :
   - masquer ou réduire les éléments décoratifs du fond ;
   - carte presque pleine largeur avec marges latérales de 16–24 px.

## Contenu de la carte
Ordre recommandé :
1. Logo Livreaura centré, taille modérée.
2. Titre : **« Connectez-vous »**
3. Sous-titre : **« Accédez à votre espace personnel pour lire, publier et faire vivre vos idées. »**
4. Champ **Adresse e-mail**
5. Champ **Mot de passe** avec bouton afficher/masquer
6. Bouton principal **Se connecter**
7. Lien centré **Mot de passe oublié ?**
8. Séparateur discret
9. Texte **« Pas encore de compte ? »**
10. Bouton secondaire outline **Créer un compte**
11. Zone tertiaire discrète :
   - Déposer un recours
   - Demander une clôture
   - Aide
12. Ligne de réassurance :
   - Connexion sécurisée
   - Vos données sont protégées

## Champs et interactions
- Hauteur des inputs : environ **50–52 px**.
- Icônes à gauche, gris chaud.
- Bordure neutre par défaut.
- Focus avec bordure/ring **Orange**.
- Labels en **Coffee Black**.
- Placeholder plus clair et discret.
- Ne jamais supprimer les validations actuelles.
- Les messages d’erreur doivent apparaître sous le champ concerné.
- Conserver l’accessibilité clavier, les labels associés et les attributs ARIA existants.

## Boutons
### Principal
- Fond Orange.
- Texte Coffee Black ou contraste conforme à la charte.
- Hauteur ~52 px.
- Largeur 100 %.
- Hover légèrement plus sombre.
- Aucun gradient agressif.

### Secondaire
- Fond transparent.
- Bordure Orange.
- Texte Orange/Coffee selon le design system.
- Hover très léger.

## Typographie
- **Noto Serif** pour le titre principal.
- **Inter** pour les labels, inputs, boutons, liens et textes d’interface.
- Ne pas recréer le logo avec une police : utiliser l’asset officiel.

## Responsive
### Desktop
- Carte centrée.
- Le fond décoratif reste visible sur les côtés.
- Éviter que la carte dépasse la hauteur utile de l’écran.

### Mobile
- Une seule colonne.
- Réduire les paddings.
- Préserver les éléments essentiels uniquement.
- La connexion doit rester immédiatement accessible sans scroll excessif.

## Contraintes
- Ne pas modifier les endpoints, stores, composables, middleware ou logique d’authentification sans nécessité.
- Ne pas changer les noms de champs attendus par le backend.
- Ne pas dupliquer les tokens CSS existants.
- Réutiliser les composants UI et tokens Livreaura déjà présents dans le projet.
- Ne pas coder directement les couleurs en hexadécimal si des variables/tokens existent déjà.

## Critères d’acceptation
- L’écran correspond visuellement à la maquette premium Livreaura.
- Le fond éditorial est visible sans nuire à la lisibilité.
- La carte est propre, centrée, compacte et élégante.
- Les liens secondaires ne concurrencent pas l’action de connexion.
- Tous les états existants fonctionnent encore : normal, focus, erreur, loading, disabled.
- Aucun comportement d’authentification n’est cassé.
- Le rendu desktop et mobile est cohérent avec la charte graphique.
