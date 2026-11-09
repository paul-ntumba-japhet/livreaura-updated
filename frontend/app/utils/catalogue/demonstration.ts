import type {
  CatalogueAuthor,
  CatalogueOffer,
  CatalogueResultPage,
  CatalogueWork,
  CatalogueWorkCard,
} from '../../types/catalogue/consultation'

type Shelf = 'a_paraitre' | 'tendances' | 'auteurs'

type Example = {
  shelf: Shelf
  slug: string
  titre: string
  sous_titre: string | null
  resume: string
  categorie: string
  chemin: string
  langue: string
  fichier: string
  publie_a: string | null
  disponibilite: 'soon' | 'available'
  auteurs: { slug: string, nom: string, pays: string, biographie: string }[]
  editeur: string
  offres: { format: string, libelle: string, prix: string }[]
}

const examples: Example[] = [
  {
    shelf: 'a_paraitre',
    slug: 'la-pluie-sur-les-toits-de-kintambo',
    titre: 'La pluie sur les toits de Kintambo',
    sous_titre: 'Carnets d’une saison courte',
    resume: 'Une enfant note la pluie, les voix de la cour et les départs de la saison.',
    categorie: 'Contes et récits',
    chemin: 'contes-et-recits',
    langue: 'Français',
    fichier: 'a-paraitre-01-kintambo.png',
    publie_a: null,
    disponibilite: 'soon',
    auteurs: [{
      slug: 'nadine-ilunga',
      nom: 'Nadine Ilunga',
      pays: 'République démocratique du Congo',
      biographie: 'Nadine Ilunga écrit des carnets sur les cours et les saisons de Kinshasa.',
    }],
    editeur: 'Éditions Fleuve clair',
    offres: [],
  },
  {
    shelf: 'a_paraitre',
    slug: 'manuel-des-fractions-au-marche',
    titre: 'Manuel des fractions au marché',
    sous_titre: null,
    resume: 'Un cahier relie les fractions aux prix, aux parts et aux mesures du marché.',
    categorie: 'Mathématiques',
    chemin: 'mathematiques',
    langue: 'Français',
    fichier: 'a-paraitre-02-fractions-marche.png',
    publie_a: null,
    disponibilite: 'soon',
    auteurs: [{
      slug: 'joseph-mbuyi',
      nom: 'Joseph Mbuyi',
      pays: 'République démocratique du Congo',
      biographie: 'Joseph Mbuyi prépare des cahiers de mathématiques à partir des situations du marché.',
    }],
    editeur: 'Atelier scolaire Lumière',
    offres: [],
  },
  {
    shelf: 'a_paraitre',
    slug: 'bleu-petrole-bleu-fleuve',
    titre: 'Bleu pétrole, bleu fleuve',
    sous_titre: 'Recueil',
    resume: 'Vingt-quatre poèmes sur l’eau, la ville et le départ.',
    categorie: 'Poésie',
    chemin: 'poesie',
    langue: 'Français',
    fichier: 'a-paraitre-03-bleu-fleuve.png',
    publie_a: null,
    disponibilite: 'soon',
    auteurs: [{
      slug: 'amina-bakary',
      nom: 'Amina Bakary',
      pays: 'République du Congo',
      biographie: 'Amina Bakary publie des poèmes sur le fleuve et les villes qui le bordent.',
    }],
    editeur: 'Maison des rives',
    offres: [],
  },
  {
    shelf: 'a_paraitre',
    slug: 'le-dernier-bus-de-limete',
    titre: 'Le dernier bus de Limete',
    sous_titre: null,
    resume: 'Deux cousins cherchent un sac oublié dans le dernier bus de la soirée.',
    categorie: 'Romans',
    chemin: 'romans',
    langue: 'Français',
    fichier: 'a-paraitre-04-bus-limete.png',
    publie_a: null,
    disponibilite: 'soon',
    auteurs: [
      {
        slug: 'paul-okito',
        nom: 'Paul Okito',
        pays: 'République démocratique du Congo',
        biographie: 'Paul Okito écrit des romans dont l’action tient dans une soirée.',
      },
      {
        slug: 'grace-nsemi',
        nom: 'Grâce Nsemi',
        pays: 'République démocratique du Congo',
        biographie: 'Grâce Nsemi écrit avec Paul Okito des récits de quartier.',
      },
    ],
    editeur: 'Romanciers du soir',
    offres: [],
  },
  {
    shelf: 'tendances',
    slug: 'cuisine-de-la-veranda',
    titre: 'Cuisine de la véranda',
    sous_titre: null,
    resume: 'Quarante recettes de saison, préparées sans four.',
    categorie: 'Cuisine',
    chemin: 'cuisine',
    langue: 'Français',
    fichier: 'tendances-01-veranda.png',
    publie_a: '2026-10-02T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'hortense-kabila',
      nom: 'Hortense Kabila',
      pays: 'République démocratique du Congo',
      biographie: 'Hortense Kabila recueille des recettes de véranda et de saison.',
    }],
    editeur: 'Table et mémoire',
    offres: [{ format: 'pdf', libelle: 'PDF', prix: '8.00' }],
  },
  {
    shelf: 'tendances',
    slug: 'comptines-pour-compter-jusqua-vingt',
    titre: 'Comptines pour compter jusqu’à vingt',
    sous_titre: null,
    resume: 'Des comptines pour compter avec les doigts et les cailloux.',
    categorie: 'Outils pédagogiques',
    chemin: 'outils-pedagogiques',
    langue: 'Français',
    fichier: 'tendances-02-comptines.png',
    publie_a: '2026-09-14T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'sarah-lumbu',
      nom: 'Sarah Lumbu',
      pays: 'République démocratique du Congo',
      biographie: 'Sarah Lumbu compose des comptines pour les premières années d’école.',
    }],
    editeur: 'Atelier scolaire Lumière',
    offres: [{ format: 'epub', libelle: 'EPUB', prix: '0.00' }],
  },
  {
    shelf: 'tendances',
    slug: 'cartes-muettes-du-fleuve',
    titre: 'Cartes muettes du fleuve',
    sous_titre: null,
    resume: 'Des cartes à compléter, du bassin du fleuve aux quartiers.',
    categorie: 'Géographie',
    chemin: 'geographie',
    langue: 'Français',
    fichier: 'tendances-03-cartes-fleuve.png',
    publie_a: '2026-08-21T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'david-ngeba',
      nom: 'David Ngeba',
      pays: 'République démocratique du Congo',
      biographie: 'David Ngeba dessine des cartes destinées à être complétées en classe.',
    }],
    editeur: 'Repères d’Afrique',
    offres: [
      { format: 'pdf', libelle: 'PDF', prix: '12.00' },
      { format: 'paper', libelle: 'PAPER', prix: '18.00' },
    ],
  },
  {
    shelf: 'tendances',
    slug: 'le-petit-atelier-de-kora',
    titre: 'Le petit atelier de kora',
    sous_titre: null,
    resume: 'Une initiation sonore à la kora, en douze pistes.',
    categorie: 'Musique',
    chemin: 'musique',
    langue: 'Français',
    fichier: 'tendances-04-kora.png',
    publie_a: '2026-07-03T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'yves-diabate',
      nom: 'Yves Diabaté',
      pays: 'Mali',
      biographie: 'Yves Diabaté enregistre des initiations courtes aux instruments à cordes.',
    }],
    editeur: 'Atelier des cordes',
    offres: [{ format: 'audio', libelle: 'AUDIO', prix: '6.50' }],
  },
  {
    shelf: 'auteurs',
    slug: 'droit-du-petit-commerce',
    titre: 'Droit du petit commerce',
    sous_titre: null,
    resume: 'Les règles utiles à un vendeur de marché, écrites en langage simple.',
    categorie: 'Droit',
    chemin: 'droit',
    langue: 'Français',
    fichier: 'annuaire-01-petit-commerce.png',
    publie_a: '2026-06-11T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'clarisse-moke',
      nom: 'Clarisse Moke',
      pays: 'République démocratique du Congo',
      biographie: 'Clarisse Moke explique le droit du petit commerce sans vocabulaire de tribunal.',
    }],
    editeur: 'Cabinet des cases',
    offres: [{ format: 'pdf', libelle: 'PDF', prix: '15.00' }],
  },
  {
    shelf: 'auteurs',
    slug: 'herbes-du-jardin-de-derriere',
    titre: 'Herbes du jardin de derrière',
    sous_titre: null,
    resume: 'Un guide des plantes courantes du jardin, sans promesse de soin.',
    categorie: 'Santé et bien-être',
    chemin: 'sante-et-bien-etre',
    langue: 'Français',
    fichier: 'annuaire-02-herbes.png',
    publie_a: '2026-05-19T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'berthe-swana',
      nom: 'Berthe Swana',
      pays: 'République démocratique du Congo',
      biographie: 'Berthe Swana décrit les plantes du jardin et leur usage quotidien.',
    }],
    editeur: 'Feuilles utiles',
    offres: [
      { format: 'epub', libelle: 'EPUB', prix: '4.00' },
      { format: 'paper', libelle: 'PAPER', prix: '7.00' },
    ],
  },
  {
    shelf: 'auteurs',
    slug: 'premiers-pas-en-scratch',
    titre: 'Premiers pas en Scratch',
    sous_titre: null,
    resume: 'Un parcours pour animer un premier jeu, écran après écran.',
    categorie: 'Informatique',
    chemin: 'informatique',
    langue: 'Français',
    fichier: 'annuaire-03-scratch.png',
    publie_a: '2026-04-08T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'lionel-kanza',
      nom: 'Lionel Kanza',
      pays: 'République démocratique du Congo',
      biographie: 'Lionel Kanza rédige des parcours d’informatique pour un premier projet.',
    }],
    editeur: 'Écran ouvert',
    offres: [{ format: 'pdf', libelle: 'PDF', prix: '9.00' }],
  },
  {
    shelf: 'auteurs',
    slug: 'nouvelles-de-la-gare-centrale',
    titre: 'Nouvelles de la gare centrale',
    sous_titre: null,
    resume: 'Six nouvelles autour d’un quai, d’un retard et d’un colis.',
    categorie: 'Nouvelles',
    chemin: 'nouvelles',
    langue: 'Français',
    fichier: 'annuaire-04-gare-centrale.png',
    publie_a: '2026-03-27T08:00:00+01:00',
    disponibilite: 'available',
    auteurs: [{
      slug: 'esther-bolia',
      nom: 'Esther Bolia',
      pays: 'République démocratique du Congo',
      biographie: 'Esther Bolia écrit des nouvelles qui tiennent dans le temps d’une attente.',
    }],
    editeur: 'Romanciers du soir',
    offres: [{ format: 'epub', libelle: 'EPUB', prix: '3.00' }],
  },
]

function lowestPrice(offers: Example['offres']): string | null {
  if (offers.length === 0) {
    return null
  }
  return offers.reduce((min, offer) => Number(offer.prix) < Number(min) ? offer.prix : min, offers[0].prix)
}

function toOffers(example: Example): CatalogueOffer[] {
  return example.offres.map((offer, index) => ({
    offre_id: `${example.slug}-${index + 1}`,
    format: offer.format,
    format_libelle: offer.libelle,
    modalite: 'achat',
    prix: offer.prix,
    devise: 'USD',
    etat: 'new',
    correspondante: true,
  }))
}

function toWork(example: Example): CatalogueWork {
  const offers = toOffers(example)
  return {
    slug: example.slug,
    titre: example.titre,
    sous_titre: example.sous_titre,
    resume: example.resume,
    pages: null,
    disponibilite: example.disponibilite,
    badge: null,
    africain: true,
    mis_en_avant: false,
    publie_a: example.publie_a,
    note_moyenne: null,
    avis_count: 0,
    auteurs: example.auteurs.map(author => ({ slug: author.slug, nom: author.nom, role: 'Auteur' })),
    categories: [{ nom: example.categorie, chemin: example.chemin }],
    langues: [{ code: 'fr', nom: example.langue }],
    niveau: null,
    collection: null,
    editions: [{
      isbn: null,
      titre: example.editeur,
      date_edition: example.publie_a,
      editeur: null,
    }],
    offres: offers,
    medias: [{
      media_catalogue_id: '',
      type: 'couverture',
      texte_alternatif: `Couverture de ${example.titre}`,
      src: `/catalogue-exemples/${example.fichier}`,
    }],
    avis: { total: 0, page: 1, taille: 20, elements: [] },
    lecture: { etat: 'indisponible' },
  }
}

function toCard(example: Example): CatalogueWorkCard {
  const offers = toOffers(example)
  return {
    slug: example.slug,
    titre: example.titre,
    sous_titre: example.sous_titre,
    disponibilite: example.disponibilite,
    badge: null,
    africain: true,
    mis_en_avant: false,
    publie_a: example.publie_a,
    prix_reference: lowestPrice(example.offres),
    auteurs: example.auteurs.map(author => ({ slug: author.slug, nom: author.nom })),
    couverture: {
      media_catalogue_id: '',
      texte_alternatif: `Couverture de ${example.titre}`,
      src: `/catalogue-exemples/${example.fichier}`,
    },
    offres: offers,
  }
}

const works = new Map(examples.map(example => [example.slug, toWork(example)]))
const cards = new Map(examples.map(example => [example.slug, toCard(example)]))

function pageOf(shelf: Shelf): CatalogueResultPage {
  const oeuvres = examples.filter(example => example.shelf === shelf).map(example => cards.get(example.slug)!)
  return {
    oeuvres,
    total: oeuvres.length,
    page: 1,
    taille: oeuvres.length,
    devise: 'USD',
    tri: shelf === 'tendances' ? 'selection' : 'recent',
  }
}

export function demonstrationWorks(shelf: Shelf): CatalogueWorkCard[] {
  return pageOf(shelf).oeuvres
}

export function findDemonstrationWork(slug: string): CatalogueWork | null {
  return works.get(slug) ?? null
}

const authors = new Map<string, CatalogueAuthor>()
for (const example of examples) {
  for (const author of example.auteurs) {
    if (!authors.has(author.slug)) {
      authors.set(author.slug, {
        slug: author.slug,
        nom: author.nom,
        biographie: author.biographie,
        pays: author.pays,
        role_affiche: 'Auteur',
        verified: false,
        mis_en_avant: false,
        tags: [{ slug: example.chemin, nom: example.categorie }],
      })
    }
  }
}

export function demonstrationAuthors(): CatalogueAuthor[] {
  return examples
    .filter(example => example.shelf === 'auteurs')
    .flatMap(example => example.auteurs)
    .map(author => authors.get(author.slug)!)
}

export function findDemonstrationAuthor(slug: string): { auteur: CatalogueAuthor, bibliographie: CatalogueResultPage } | null {
  const auteur = authors.get(slug)
  if (!auteur) {
    return null
  }
  const oeuvres = examples
    .filter(example => example.auteurs.some(author => author.slug === slug))
    .map(example => cards.get(example.slug)!)
  return {
    auteur,
    bibliographie: {
      oeuvres,
      total: oeuvres.length,
      page: 1,
      taille: Math.max(oeuvres.length, 1),
      devise: 'USD',
      tri: 'recent',
    },
  }
}
