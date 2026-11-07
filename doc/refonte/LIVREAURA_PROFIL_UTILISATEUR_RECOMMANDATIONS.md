# LIVREAURA — Refonte de la page « Mon profil »

**Route :** `/compte/profil`  
**Nature :** cahier de recommandations UX/UI, architecture de l'information et consignes d'implémentation pour Cursor  
**Périmètre :** page de consultation du profil personnel, sans modification arbitraire des processus A1  
**Référence :** `LIVREAURA_DESIGN_SYSTEM_CURSOR.md`

## 1. Objectif

Transformer l'écran « Mon profil » en une page de consultation concise, cohérente et premium, dans l'esprit des interfaces de gestion de Cursor, GitHub et ChatGPT. **L'objectif principal est de retirer les informations superflues, les répétitions et les contrôles inopérants**, non d'ajouter des cartes ou des effets visuels.

L'utilisateur doit identifier immédiatement : **qui est connecté, quelles coordonnées sont associées au compte, quel est l'état de vérification de son e-mail et quelles actions réellement disponibles il peut effectuer**. Les détails concernant les sessions, la sécurité, les consentements et les organisations disposent déjà de rubriques dédiées.

## 2. Constat sur les trois captures

- Le nom « Claude Ntumba » est présenté dans une grande carte récapitulative, puis les prénom et nom sont répétés dans une seconde carte.
- L'avatar avec les initiales « CN » est répété dans la carte « Photo et identité ».
- Deux badges « Compte actif » et « E-mail vérifié » occupent l'en-tête ; l'e-mail possède ensuite un troisième indicateur « Vérifié ».
- Un grand bouton « Modifier le profil » paraît désactivé, accompagné d'un message expliquant que la modification n'est pas disponible ; une autre action « Modifier » apparaît dans les informations personnelles et les coordonnées.
- « Voir le compte » renvoie vraisemblablement à une section déjà disponible dans la sidebar.
- La section « Parcours sur Livreaura » présente trois grandes tuiles (Lecteur, Auteur, Représentant), dont l'une est « Non activé » et une autre « Représentant déclarant » : les notions de parcours, rôle et habilitation demandent une clarification.
- Les « Actions rapides » répètent des destinations déjà accessibles par la navigation (sessions, consentements) ; l'action « Mettre à jour mes informations » paraît indisponible.
- Le contenu est dispersé entre deux colonnes asymétriques et plusieurs grandes cartes, ce qui augmente inutilement la hauteur et la charge cognitive.

## 3. Tri du contenu : conserver, déplacer ou supprimer

| Élément actuel | Décision | Justification / destination |
|---|---|---|
| Titre « Mon profil. » | Conserver en « Mon profil » | Un seul H1, sans ponctuation superflue. |
| Description « Consultez les informations personnelles associées à ce compte. » | Supprimer | Le titre et le contenu suffisent. |
| « Retour au compte » | Supprimer sur desktop si redondant | Sidebar « Vue d'ensemble » déjà présente ; conserver une navigation de retour adaptée sur mobile si nécessaire. |
| Nom complet et initiales | Conserver une seule fois | Bloc d'identité compact. |
| Localisation dans l'en-tête | Supprimer si elle est déjà affichée dans les coordonnées | Éviter de répéter « Lille, France ». |
| Badge « Compte actif » | Déplacer vers le statut du compte si son affichage est utile | Ne pas confondre état de compte et vérification d'e-mail. Ne pas multiplier les badges. |
| Badge « E-mail vérifié » | Conserver uniquement à proximité de l'e-mail | Éviter une deuxième ou troisième répétition. |
| Grand bouton « Modifier le profil » désactivé | Supprimer | Ne pas afficher un CTA inopérant comme action principale. |
| Texte « La modification du profil n'est pas proposée dans cette version » | Supprimer | Information sur les limitations du produit, non utile à chaque visite. |
| Liens « Modifier » inactifs dans les sections | Supprimer ou remplacer par la fonction réellement disponible | Ne pas simuler des actions de modification. |
| Bouton « Voir le compte » | Supprimer si lien redondant | La vue d'ensemble est déjà accessible. |
| Carte « Photo et identité » | Supprimer | Répète les initiales et explique une fonctionnalité absente. |
| « Prénom » et « Nom » séparés | Conserver si nécessaire dans les détails, sinon un seul « Nom complet » | Le nom est déjà présent en tête ; éviter la répétition dans une vue concise. |
| « Langue » | Conserver uniquement si c'est une préférence active et utile ; sinon déplacer vers Paramètres | Ne pas afficher une métadonnée sans usage sur cette page. |
| E-mail masqué | Conserver selon la politique existante | Associé à son statut réel de vérification. |
| Ville et pays | Conserver de façon condensée si utile | Une ligne « Localisation : Lille, France ». |
| Trois grandes tuiles de parcours | Remplacer par un résumé textuel compact si utile | N'afficher que les parcours/rôles réels et pertinents. |
| « Auteur — Non activé » | Supprimer du résumé par défaut | Un parcours non actif n'a pas à occuper une carte permanente ; accès aux possibilités d'activation ailleurs, si existant. |
| « Représentant déclarant » | Conserver seulement si pertinent | Préciser que ce n'est pas une habilitation vérifiée. |
| Carte « Actions rapides » | Supprimer | Duplique Sessions / Consentements / autres pages. |

**Principe :** supprimer les blocs d'interface, pas les données ou fonctionnalités sous-jacentes. Conserver les liens nécessaires dans la navigation partagée.

## 4. Nouvelle hiérarchie proposée

### Section 1 — Identité

Afficher dans un en-tête compact :

- Avatar par initiales (une seule occurrence) ; pas de composant de photo si l'upload n'existe pas.
- Nom complet.
- Éventuellement un rôle personnel principal **réel** et utile, par exemple « Lecteur ».

Ne pas inclure une longue description ni une rangée de badges.

### Section 2 — Informations du compte

Présenter les informations en **lignes clé/valeur**, sans cartes imbriquées :

- **E-mail** : valeur provenant de l'API, avec un seul indicateur « Vérifié » ou « Non vérifié » selon son état réel ; respecter le masquage prévu.
- **Localisation** : « Lille, France » si les deux données sont présentes et pertinentes.
- **Langue** : uniquement si la préférence peut être utilement consultée ici ; sinon la déplacer vers les préférences du compte.

Le nom complet étant déjà visible dans l'en-tête, éviter de répéter les lignes « Prénom » et « Nom » à moins qu'une exigence métier ou une action de modification effective ne le justifie.

### Section 3 — Accès et parcours (facultative et compacte)

Cette section ne doit pas présenter systématiquement les trois parcours possibles.

- Afficher uniquement les parcours effectivement activés ou les relations réelles utiles à l'utilisateur.
- Distinguer **parcours déclaré**, **rôle**, **adhésion à une organisation**, **statut de vérification** et **habilitation**.
- Ne pas représenter « Représentant déclarant » comme un rôle privilégié ou un droit d'administration d'organisation.
- Supprimer les cartes géantes et les icônes décoratives ; utiliser une ligne descriptive ou de petits libellés neutres.
- Si ces informations sont mieux traitées dans « Organisations », supprimer cette section du profil et conserver un accès depuis la sidebar.

### Aucun quatrième bloc par défaut

Ne pas ajouter de panneau « Actions rapides », « Photo et identité », « Sécurité » ou « À savoir » pour remplir l'espace.

## 5. Proposition de composition

```text
Mon profil

[ CN ]  Claude Ntumba
        Lecteur                         (si réellement pertinent)

Informations du compte
────────────────────────────────────────────────────
E-mail          z***@vertexacademia.tech     Vérifié
Localisation    Lille, France
Langue          Français                      (si utile ici)
────────────────────────────────────────────────────

Accès et parcours                            (facultatif)
Lecteur · Représentant déclarant             (si données exactes)
```

Il est acceptable que la page soit courte : **ne pas remplir le vide avec du contenu redondant**.

## 6. Règles visuelles

Réutiliser les tokens du design system global, sans inventer une palette parallèle.

| Propriété | Cible indicative |
|---|---|
| Fond | `#FFFFFF` |
| Largeur maximale du bloc de profil | 720–800 px |
| Titre principal | 28–30 px, graisse 600 |
| Nom de l'utilisateur | 18–20 px, graisse 600 |
| Corps et valeurs | 14 px |
| Libellés et métadonnées | 12–13 px |
| Avatar | 44–48 px |
| Espacement entre sections | 24–32 px |
| Hauteur des lignes d'information | 44–52 px, adaptée au contenu |
| Bordures | `1px solid #E5E5E5` ou séparateurs subtils |
| Rayon éventuel des surfaces | 8–12 px |
| Icônes utiles | 16–18 px |

- Utiliser Inter ou Geist selon le référentiel ; **pas de grande police serif** dans l'espace de gestion.
- Ne pas multiplier les badges verts ou orange. Le vert est réservé à un état vérifié ou réussi réellement confirmé.
- Préférer une seule colonne claire plutôt que deux colonnes de cartes volumineuses.
- Ne pas transformer chaque ligne en carte bordée.
- Conserver le header et la sidebar partagés déjà harmonisés.

## 7. Actions et comportements réels

- **Ne pas créer de bouton Modifier** si la modification du profil n'est pas implémentée ou autorisée. Si une modification est réellement disponible, utiliser un lien/bouton secondaire contextualisé et vérifier les permissions côté backend.
- Si les changements de coordonnées sensibles sont disponibles, préserver les mécanismes de revalidation (ex. nouvelle adresse e-mail), les protections de session et les contrôles backend existants.
- Si la photo de profil n'est pas disponible, afficher uniquement les initiales et supprimer toute explication sur l'absence de photo.
- Utiliser les données de l'API : ne jamais coder en dur le nom, la ville, l'e-mail, la langue ou les rôles.
- Gérer les états : chargement, données absentes, erreur de récupération, champs facultatifs non renseignés.
- N'afficher un statut « E-mail vérifié » que si le backend le confirme.
- Ne pas déduire l'existence de droits privilégiés d'un simple parcours de représentant.
- Préserver l'accès aux rubriques « Sessions », « Sécurité » et « Consentements » par la sidebar ; aucun besoin de les dupliquer ici.

## 8. Responsive et accessibilité

- Desktop : colonne principale maîtrisée ; possibilité de laisser volontairement de l'espace vide.
- Mobile : un seul flux vertical, lignes clé/valeur capables de revenir à la ligne, e-mail long sans débordement.
- Avatar et identité au sommet ; statut d'e-mail lisible et non transmis uniquement par la couleur.
- Focus visibles, contraste WCAG AA, titres sémantiques, texte sélectionnable, contrôles désactivés non trompeurs.
- Aucun scroll horizontal à 320 px de largeur ni au zoom 200 %.

## 9. Consignes d'exécution pour Cursor

1. Examiner le composant Nuxt de `/compte/profil`, les composants partagés et le design system.
2. Inventorier **chaque donnée visible et chaque action** ; vérifier la réponse API et les fonctionnalités effectivement disponibles.
3. Supprimer les blocs redondants : grand résumé à badges multiples, « Photo et identité », « Actions rapides », liens de modification inopérants et « Voir le compte » redondant.
4. Créer une composition compacte en une colonne : identité, informations essentielles, parcours seulement si utile.
5. Réduire les répétitions, et distinguer les statuts de compte, d'e-mail et d'habilitation.
6. Préserver les routes, permissions, composants fonctionnels et données métiers.
7. Tester zéro information optionnelle, e-mail non vérifié, utilisateur avec plusieurs relations d'organisation, chargement et erreur API.
8. Vérifier desktop/mobile, clavier, zoom et absence de débordement.
9. Fournir un compte rendu des fichiers modifiés, éléments retirés, fonctions préservées et tests réellement réalisés.

## 10. Critères d'acceptation

- [ ] Le nom complet et l'avatar n'apparaissent qu'une fois dans la zone de profil.
- [ ] Le statut de vérification e-mail n'est pas répété.
- [ ] Les boutons « Modifier » indisponibles ont disparu.
- [ ] Aucune phrase n'explique inutilement qu'une fonction n'est pas proposée dans cette version.
- [ ] Le bloc « Photo et identité » redondant est supprimé.
- [ ] Le bloc « Actions rapides » est supprimé, sans perdre l'accès aux pages concernées.
- [ ] La page ne présente pas des parcours non activés comme s'ils constituaient des rôles courants.
- [ ] Les relations organisationnelles n'accordent visuellement aucun droit non vérifié.
- [ ] Les données sont dynamiques et les états de vérification sont exacts.
- [ ] La vue principale tient dans une colonne compacte et évite les cartes inutiles.
- [ ] La navigation, l'accessibilité, la sécurité et le responsive sont préservés.

## 11. Directive finale

**Réduire la page à ce qui sert immédiatement à consulter son identité et ses coordonnées.** Ne pas ajouter de fonctions fictives, ne pas exposer de données internes, ne pas supprimer de données de la base. La simplicité de la page et le faible nombre de blocs sont des qualités recherchées, pas des défauts à corriger.

**Implémente directement les modifications dans Nuxt, en respectant la chaîne métier A1 et les règles Livreaura.**
