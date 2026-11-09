export type CatalogueOffer = {
  offre_id: string
  edition_id?: string
  format: string
  format_libelle: string
  modalite: string
  prix: string
  devise: string
  etat: string
  correspondante: boolean
}

export type CatalogueAuthorLink = {
  slug: string
  nom: string
  role?: string | null
}

export type CatalogueWorkCard = {
  slug: string
  titre: string
  sous_titre: string | null
  disponibilite: string
  badge: string | null
  africain: boolean
  mis_en_avant: boolean
  publie_a: string | null
  prix_reference: string | null
  auteurs: CatalogueAuthorLink[]
  couverture: { media_catalogue_id: string, texte_alternatif: string | null, src?: string } | null
  offres: CatalogueOffer[]
}

export type CatalogueResultPage = {
  oeuvres: CatalogueWorkCard[]
  total: number
  page: number
  taille: number
  devise: string
  tri: string
}

export type CatalogueNamedOption = {
  nom: string
}

export type CatalogueLanguageOption = CatalogueNamedOption & {
  code: string
}

export type CatalogueSlugOption = CatalogueNamedOption & {
  slug: string
}

export type CatalogueCategoryNode = {
  categorie_id: string
  nom: string
  slug: string
  chemin: string
  enfants: CatalogueCategoryNode[]
}

export type CatalogueWork = {
  slug: string
  titre: string
  sous_titre: string | null
  resume: string | null
  pages: number | null
  disponibilite: string
  badge: string | null
  africain: boolean
  mis_en_avant: boolean
  publie_a: string | null
  note_moyenne: string | null
  avis_count: number
  auteurs: CatalogueAuthorLink[]
  categories: { nom: string, chemin: string }[]
  langues: { code: string, nom: string }[]
  niveau: { nom: string } | null
  collection: { nom: string, kind: string, prix_groupe: null, droit_groupe: false } | null
  editions: {
    edition_id?: string
    isbn: string | null
    titre: string | null
    date_edition: string | null
    editeur: { slug: string, nom: string } | null
  }[]
  offres: CatalogueOffer[]
  medias: { media_catalogue_id: string, type: string, texte_alternatif: string | null, ordre?: number | null, src?: string }[]
  avis: {
    total: number
    page: number
    taille: number
    elements: { note: number, corps: string | null, soumis_a: string | null, auteur: string }[]
  }
  lecture: { etat: string }
}

export type CatalogueAuthor = {
  slug: string
  nom: string
  biographie: string | null
  pays: string | null
  role_affiche: string | null
  verified: boolean
  mis_en_avant: boolean
  tags: { slug: string, nom: string }[]
}

export type CatalogueAuthorPage = {
  auteurs: CatalogueAuthor[]
  total: number
  page: number
  taille: number
}

export type CataloguePublisher = {
  editeur: {
    slug: string
    nom: string
    presentation: string | null
    pays: string | null
    mis_en_avant: boolean
  }
  editions: {
    total: number
    page: number
    taille: number
    elements: {
      isbn: string | null
      titre: string | null
      oeuvre: { slug: string, titre: string } | null
    }[]
  }
  collections: {
    nom: string
    kind: string
    total: number
    oeuvres: { slug: string, titre: string }[]
  }[]
}
