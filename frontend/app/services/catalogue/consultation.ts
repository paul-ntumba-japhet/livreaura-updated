import { apiBase } from '../identity-access/http'
import type {
  CatalogueAuthor,
  CatalogueAuthorPage,
  CatalogueCategoryNode,
  CatalogueLanguageOption,
  CataloguePublisher,
  CatalogueResultPage,
  CatalogueSlugOption,
  CatalogueWork,
} from '../../types/catalogue/consultation'

type Envelope<T> = { data: T }

function cleanQuery(query: Record<string, unknown>): Record<string, string | string[]> {
  const cleaned: Record<string, string | string[]> = {}
  for (const [key, value] of Object.entries(query)) {
    if (typeof value === 'string' && value !== '') {
      cleaned[key] = value
    } else if (Array.isArray(value)) {
      const items = value.filter((item): item is string => typeof item === 'string' && item !== '')
      if (items.length > 0) {
        cleaned[key] = items
      }
    }
  }
  return cleaned
}

async function read<T>(path: string, query?: Record<string, unknown>): Promise<T> {
  const response = await $fetch<Envelope<T>>(`${apiBase()}${path}`, {
    query: query ? cleanQuery(query) : undefined,
    headers: { Accept: 'application/json' },
  })
  return response.data
}

export function searchCatalogue(query: Record<string, unknown>): Promise<CatalogueResultPage> {
  return read('/api/v1/catalogue/recherche', query)
}

export function listCatalogueCategories(): Promise<{ categories: CatalogueCategoryNode[] }> {
  return read('/api/v1/catalogue/categories')
}

export function listCatalogueLanguages(): Promise<{ langues: CatalogueLanguageOption[] }> {
  return read('/api/v1/catalogue/langues')
}

export function listCatalogueLevels(): Promise<{ niveaux: CatalogueSlugOption[] }> {
  return read('/api/v1/catalogue/niveaux')
}

export function listCataloguePublishers(): Promise<{ editeurs: CatalogueSlugOption[] }> {
  return read('/api/v1/catalogue/editeurs')
}

export function showCatalogueCategory(chemin: string, query: Record<string, unknown>): Promise<{
  categorie: { nom: string, chemin: string }
  resultats: CatalogueResultPage
}> {
  return read(`/api/v1/catalogue/categories/${chemin.split('/').map(encodeURIComponent).join('/')}`, query)
}

export function showCatalogueSelection(code: string, query: Record<string, unknown>): Promise<{
  selection: { code: string, libelle: string, mode: string }
  resultats: CatalogueResultPage
}> {
  return read(`/api/v1/catalogue/selections/${encodeURIComponent(code)}`, query)
}

export function catalogueMediaUrl(mediaId: string): string {
  return `${apiBase()}/api/v1/catalogue/medias/${encodeURIComponent(mediaId)}`
}

export function showCatalogueWork(slug: string, query: Record<string, unknown> = {}): Promise<CatalogueWork> {
  return read(`/api/v1/catalogue/oeuvres/${encodeURIComponent(slug)}`, query)
}

export function listCatalogueAuthors(query: Record<string, unknown>): Promise<CatalogueAuthorPage> {
  return read('/api/v1/catalogue/auteurs', query)
}

export function showCatalogueAuthor(slug: string, query: Record<string, unknown>): Promise<{
  auteur: CatalogueAuthor
  bibliographie: CatalogueResultPage
}> {
  return read(`/api/v1/catalogue/auteurs/${encodeURIComponent(slug)}`, query)
}

export function showCataloguePublisher(slug: string, query: Record<string, unknown>): Promise<CataloguePublisher> {
  return read(`/api/v1/catalogue/editeurs/${encodeURIComponent(slug)}`, query)
}
