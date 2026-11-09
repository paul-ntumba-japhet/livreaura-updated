export function formatCatalogueInstant(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  const formatted = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone: 'Africa/Kinshasa',
  }).format(date)
  return `${formatted} (heure de Kinshasa)`
}

function compareCatalogueMoney(left: string, right: string): number {
  const normalize = (value: string) => {
    const [whole, fraction = ''] = value.split('.')
    return `${whole.padStart(16, '0')}.${fraction.padEnd(4, '0').slice(0, 4)}`
  }
  const a = normalize(left)
  const b = normalize(right)
  if (a < b) {
    return -1
  }
  return a > b ? 1 : 0
}

export function catalogueRepresentativeOffer(work: {
  disponibilite: string
  prix_reference: string | null
  offres: { format_libelle: string, prix: string, devise: string, correspondante: boolean }[]
}, currency: string): { label: string, formats: string } {
  const comparable = work.offres.filter(offer => offer.correspondante && offer.devise === currency)
  const formats = [...new Set(comparable.map(offer => offer.format_libelle))].join(' · ')
  if (comparable.length === 0) {
    return {
      label: work.disponibilite === 'soon' ? 'À paraître' : 'Voir les offres',
      formats: '',
    }
  }
  const lowest = comparable.reduce((min, offer) => compareCatalogueMoney(offer.prix, min) < 0 ? offer.prix : min, comparable[0].prix)
  const reference = work.prix_reference && compareCatalogueMoney(work.prix_reference, lowest) === 0
    ? work.prix_reference
    : lowest
  const allFree = comparable.every(offer => compareCatalogueMoney(offer.prix, '0') === 0)
  if (allFree) {
    return { label: 'Gratuit', formats }
  }
  return {
    label: `Dès ${formatCataloguePrice(reference, currency)}`,
    formats,
  }
}

export function formatCataloguePrice(amount: string, currency: string): string {
  const number = Number(amount)
  const formatted = Number.isFinite(number)
    ? new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number)
    : amount
  return `${formatted} ${currency}`
}

export function formatCatalogueDay(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeZone: 'Africa/Kinshasa' }).format(date)
}

export function formatCatalogueDate(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
  if (!match) {
    return null
  }
  const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])))
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeZone: 'UTC' }).format(date)
}

export function catalogueRoleLabel(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }
  if (value === 'author') {
    return 'Auteur'
  }
  const label = cataloguePublicLabel(value)
  return label === '' ? null : label
}

export function catalogueModalityLabel(value: string): string {
  if (value === 'achat') {
    return 'Achat'
  }
  return 'Modalité indisponible'
}

export function catalogueMediaTypeLabel(value: string): string {
  if (value === 'couverture') {
    return 'Couverture'
  }
  if (value === 'galerie') {
    return 'Galerie'
  }
  if (value === 'apercu') {
    return 'Aperçu'
  }
  return 'Type indisponible'
}

export function catalogueAvailabilityLabel(value: string): string {
  if (value === 'available') {
    return 'Disponible'
  }
  if (value === 'soon') {
    return 'À paraître'
  }
  return 'Statut indisponible'
}

export function cataloguePublicLabel(value: string): string {
  return value.replace(/\[\s*test\s*\]/gi, ' ').replace(/\s{2,}/g, ' ').trim()
}

export function catalogueOfferIsFree(amount: string): boolean {
  return compareCatalogueMoney(amount, '0') === 0
}

export function catalogueOfferPriceLabel(offer: { prix: string, devise: string }): string {
  if (catalogueOfferIsFree(offer.prix)) {
    return 'Gratuit'
  }
  return formatCataloguePrice(offer.prix, offer.devise)
}

export function catalogueInitialOfferId(offers: { offre_id: string, prix: string, correspondante?: boolean }[]): string | null {
  const visible = offers.filter(offer => offer.correspondante !== false)
  const free = visible.find(offer => catalogueOfferIsFree(offer.prix))
  return (free ?? visible[0])?.offre_id ?? null
}

type CategoryBranch = {
  nom: string
  slug: string
  chemin: string
  enfants?: CategoryBranch[]
}

export function catalogueCategoryTrail(
  nodes: CategoryBranch[],
  chemin: string,
): { nom: string, chemin: string }[] {
  const trail: { nom: string, chemin: string }[] = []
  let level = nodes
  for (const slug of chemin.split('/').filter(Boolean)) {
    const node = level.find(item => item.slug === slug)
    if (!node) {
      return trail
    }
    trail.push({ nom: node.nom, chemin: node.chemin })
    level = node.enfants ?? []
  }
  return trail
}

export function catalogueConditionLabel(value: string): string | null {
  if (value === 'new') {
    return 'Neuf'
  }
  if (value === 'used') {
    return 'Occasion'
  }
  if (value === 'none') {
    return null
  }
  return 'État indisponible'
}

export function catalogueCategoryTarget(
  current: Record<string, unknown>,
  chemin: string,
): { path: string, query: Record<string, string | string[]> } {
  const query: Record<string, string | string[]> = {}
  for (const [key, value] of Object.entries(current)) {
    if (key === 'page' || key === 'categorie' || key === 'descendance') {
      continue
    }
    if (typeof value === 'string' && value !== '') {
      query[key] = value
    } else if (Array.isArray(value)) {
      const items = value.filter((item): item is string => typeof item === 'string' && item !== '')
      if (items.length > 0) {
        query[key] = items
      }
    }
  }
  if (chemin !== '') {
    query.categorie = chemin
    query.descendance = '1'
  }
  return { path: '/catalogue', query }
}

export function catalogueFailure(error: unknown): { status: number | null, message: string } {
  const candidate = error as {
    statusCode?: number
    status?: number
    data?: { error?: { message?: string } }
  }
  const status = candidate.statusCode ?? candidate.status ?? null
  if (status === 404) {
    return { status, message: 'Cette ressource est absente.' }
  }
  if (status === 422) {
    return {
      status,
      message: candidate.data?.error?.message ?? 'Certains critères ne peuvent pas être appliqués.',
    }
  }
  if (status === 429) {
    return { status, message: 'Le catalogue a reçu trop de demandes. Réessayez dans un moment.' }
  }
  if (status === null) {
    return { status: null, message: 'Le catalogue est inaccessible pour le moment.' }
  }
  return { status, message: 'La consultation n’a pas pu aboutir.' }
}
