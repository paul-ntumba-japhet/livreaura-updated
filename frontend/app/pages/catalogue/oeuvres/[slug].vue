<script setup lang="ts">
import BookOfferSelector from '../../../components/catalogue/BookOfferSelector.vue'
import { catalogueMediaUrl, listCatalogueCategories, showCatalogueWork } from '../../../services/catalogue/consultation'
import { findDemonstrationWork } from '../../../utils/catalogue/demonstration'
import {
  catalogueCategoryTarget,
  catalogueCategoryTrail,
  catalogueFailure,
  catalogueMediaTypeLabel,
  cataloguePublicLabel,
  catalogueRoleLabel,
  formatCatalogueDate,
  formatCatalogueDay,
} from '../../../utils/catalogue/presentation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

const route = useRoute()
const slug = computed(() => String(route.params.slug ?? ''))
const work = await useAsyncData(
  () => `catalogue-work:${slug.value}`,
  () => findDemonstrationWork(slug.value) ?? showCatalogueWork(slug.value),
  { watch: [slug] },
)
const categoryTree = await useAsyncData('catalogue-categories', () => listCatalogueCategories())
const retrying = ref(false)
const failedMedia = ref<Record<string, boolean>>({})
const selectedMediaKey = ref<string | null>(null)
const summaryOpen = ref(false)
const failure = computed(() => work.error.value ? catalogueFailure(work.error.value) : null)
const title = computed(() => cataloguePublicLabel(work.data.value?.titre ?? ''))
const summary = computed(() => {
  const value = work.data.value?.resume
  if (!value) {
    return null
  }
  const label = cataloguePublicLabel(value)
  return label === '' ? null : label
})
const summaryLong = computed(() => (summary.value?.length ?? 0) > 320)
const cover = computed(() => work.data.value?.medias.find(media => media.type === 'couverture') ?? null)
const gallery = computed(() => {
  const medias = work.data.value?.medias ?? []
  let coverSeen = false
  return medias.filter((media) => {
    if (media.type !== 'couverture') {
      return true
    }
    if (coverSeen) {
      return true
    }
    coverSeen = true
    return false
  })
})
const activeMedia = computed(() => {
  const medias = work.data.value?.medias ?? []
  if (selectedMediaKey.value) {
    const selected = medias.find(media => mediaKey(media) === selectedMediaKey.value)
    if (selected) {
      return selected
    }
  }
  return cover.value
})
const frames = computed(() => {
  const items = []
  if (cover.value) {
    items.push(cover.value)
  }
  items.push(...gallery.value.filter(media => !cover.value || mediaKey(media) !== mediaKey(cover.value)))
  return items
})
const activeSrc = computed(() => {
  if (!activeMedia.value || failedMedia.value[mediaKey(activeMedia.value)]) {
    return ''
  }
  return mediaAddress(activeMedia.value)
})
const showContributorRole = computed(() => {
  const authors = work.data.value?.auteurs ?? []
  if (authors.length > 1) {
    return true
  }
  const role = catalogueRoleLabel(authors[0]?.role)
  return role !== null && role !== 'Auteur'
})
const metadataLine = computed(() => {
  const current = work.data.value
  if (!current) {
    return ''
  }
  const parts = current.langues.map(language => language.nom)
  if (current.pages) {
    parts.push(`${current.pages} pages`)
  }
  return parts.join(' · ')
})
const primaryCategory = computed(() => work.data.value?.categories[0] ?? null)
const categoryCrumbs = computed(() => {
  const primary = primaryCategory.value
  if (!primary) {
    return []
  }
  const trail = catalogueCategoryTrail(categoryTree.data.value?.categories ?? [], primary.chemin)
  const items = trail.length > 0 ? trail : [{ nom: primary.nom, chemin: primary.chemin }]
  return items.map(item => ({
    label: cataloguePublicLabel(item.nom),
    to: catalogueCategoryTarget({}, item.chemin),
  }))
})
const breadcrumbs = computed(() => [
  { label: 'Catalogue', to: '/catalogue' },
  ...categoryCrumbs.value,
  { label: title.value || 'Œuvre' },
])
const singleEdition = computed(() => {
  const editions = work.data.value?.editions ?? []
  return editions.length === 1 ? editions[0] : null
})
const workFacts = computed(() => {
  const current = work.data.value
  if (!current) {
    return []
  }
  const facts: { term: string, value: string, to?: { path: string, query: Record<string, string | string[]> } }[] = []
  if (current.niveau?.nom) {
    facts.push({ term: 'Niveau', value: cataloguePublicLabel(current.niveau.nom) })
  }
  if (current.collection?.nom) {
    const name = cataloguePublicLabel(current.collection.nom)
    if (name) {
      facts.push({ term: 'Collection', value: name })
    }
  }
  const edition = singleEdition.value
  if (edition) {
    const editionTitle = cataloguePublicLabel(edition.titre || '')
    if (editionTitle) {
      facts.push({ term: 'Édition', value: editionTitle })
    }
    const date = formatCatalogueDate(edition.date_edition)
    if (date) {
      facts.push({ term: 'Date d’édition', value: date })
    }
    if (edition.isbn) {
      facts.push({ term: 'ISBN', value: edition.isbn })
    }
    if (edition.editeur) {
      const publisher = cataloguePublicLabel(edition.editeur.nom)
      if (publisher) {
        facts.push({
          term: 'Éditeur',
          value: publisher,
          to: { path: `/catalogue/editeurs/${edition.editeur.slug}`, query: {} },
        })
      }
    }
  }
  return facts
})

watch(slug, () => {
  failedMedia.value = {}
  selectedMediaKey.value = null
  summaryOpen.value = false
})

function mediaKey(media: { media_catalogue_id: string, type: string, ordre?: number | null }): string {
  return `${media.type}-${media.ordre ?? ''}-${media.media_catalogue_id}`
}

function mediaAddress(media: { media_catalogue_id: string, src?: string }): string {
  if (media.src) {
    return media.src
  }
  return media.media_catalogue_id ? catalogueMediaUrl(media.media_catalogue_id) : ''
}

function mediaAlt(media: { texte_alternatif: string | null, type: string } | null): string {
  const provided = cataloguePublicLabel(media?.texte_alternatif || '')
  if (provided) {
    return provided
  }
  if (media?.type && media.type !== 'couverture') {
    return catalogueMediaTypeLabel(media.type)
  }
  return title.value
}

async function retry() {
  retrying.value = true
  try {
    await work.refresh()
  } finally {
    retrying.value = false
  }
}

useHead({
  title: () => title.value ? `${title.value} — Livreaura` : 'Œuvre — Livreaura',
})
</script>

<template>
  <main class="px-page py-section">
    <div v-if="work.status.value === 'pending'" class="book-hero" aria-busy="true">
      <Skeleton class="book-hero-cover aspect-[3/4] w-full" />
      <div class="book-hero-copy space-y-3">
        <Skeleton class="h-10 w-72 max-w-full" />
        <Skeleton class="h-5 w-40" />
      </div>
      <Skeleton class="book-hero-offer h-28 w-full" />
      <span class="sr-only">Chargement de la fiche</span>
    </div>
    <ErrorState
      v-else-if="failure && failure.status !== 404"
      title="Consultation interrompue"
      :message="failure.message"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="retry"
    />
    <div v-else-if="failure?.status === 404 || !work.data.value">
      <Breadcrumbs :items="[{ label: 'Catalogue', to: '/catalogue' }, { label: 'Œuvre' }]" />
      <EmptyState
        class="mt-8"
        title="Cette ressource est absente"
        message="Cette œuvre n’est pas consultable."
      >
        <template #action>
          <UiButton variant="secondary" to="/catalogue">
            Retour au catalogue
          </UiButton>
        </template>
      </EmptyState>
    </div>
    <template v-else>
      <Breadcrumbs :items="breadcrumbs" />

      <div class="book-hero mt-6" :class="{ 'is-compact': !summary }">
        <div class="book-hero-cover w-full max-w-80 justify-self-center md:max-w-none md:justify-self-stretch">
          <div class="overflow-hidden rounded-md border border-border bg-subtle">
            <img
              v-if="activeSrc"
              :src="activeSrc"
              :alt="mediaAlt(activeMedia)"
              class="aspect-[3/4] w-full object-contain"
              @error="activeMedia && (failedMedia[mediaKey(activeMedia)] = true)"
            >
            <p v-else class="flex aspect-[3/4] items-center justify-center p-4 text-center font-sans text-sm text-text-muted">
              {{ mediaAlt(activeMedia) || 'Aucune couverture n’est affichée.' }}
            </p>
          </div>
          <ul v-if="gallery.length > 0" class="mt-3 flex flex-wrap gap-2">
            <li v-for="media in frames" :key="mediaKey(media)">
              <button
                type="button"
                class="focus-ring size-11 overflow-hidden rounded-md border border-border bg-subtle"
                :aria-pressed="activeMedia ? mediaKey(media) === mediaKey(activeMedia) : false"
                :aria-label="mediaAlt(media)"
                @click="selectedMediaKey = mediaKey(media)"
              >
                <img
                  v-if="mediaAddress(media) && !failedMedia[mediaKey(media)]"
                  :src="mediaAddress(media)"
                  alt=""
                  class="size-full object-contain"
                  @error="failedMedia[mediaKey(media)] = true"
                >
              </button>
            </li>
          </ul>
        </div>

        <div class="book-hero-copy min-w-0">
          <h1 class="font-geist text-3xl font-semibold text-text">
            {{ title }}
          </h1>
          <p v-if="work.data.value.sous_titre" class="mt-2 font-sans text-base text-text-muted">
            {{ cataloguePublicLabel(work.data.value.sous_titre) }}
          </p>
          <p v-if="work.data.value.auteurs.length > 0" class="mt-3 font-sans text-sm text-text">
            <template v-for="(author, index) in work.data.value.auteurs" :key="author.slug">
              <span v-if="index > 0">, </span>
              <UiLink :to="`/catalogue/auteurs/${author.slug}`">
                {{ cataloguePublicLabel(author.nom) }}
              </UiLink>
              <span v-if="showContributorRole && catalogueRoleLabel(author.role)"> ({{ catalogueRoleLabel(author.role) }})</span>
            </template>
          </p>
          <p v-if="metadataLine" class="mt-3 font-sans text-sm text-text-muted">
            {{ metadataLine }}
          </p>
          <ul v-if="work.data.value.categories.length > 0" class="mt-4 flex flex-wrap gap-x-4 gap-y-2">
            <li v-for="category in work.data.value.categories" :key="category.chemin">
              <UiLink :to="catalogueCategoryTarget({}, category.chemin)">
                {{ cataloguePublicLabel(category.nom) }}
              </UiLink>
            </li>
          </ul>
        </div>

        <BookOfferSelector
          class="book-hero-offer"
          :offers="work.data.value.offres"
          :editions="work.data.value.editions"
          :availability="work.data.value.disponibilite"
        />

        <div v-if="summary" class="book-hero-summary min-w-0">
          <h2 class="font-sans text-xl font-semibold text-text">
            Résumé
          </h2>
          <p
            class="mt-3 max-w-3xl font-sans text-base text-text"
            :class="summaryLong && !summaryOpen ? 'line-clamp-4' : ''"
          >
            {{ summary }}
          </p>
          <UiButton
            v-if="summaryLong"
            class="mt-2"
            type="button"
            variant="quiet"
            @click="summaryOpen = !summaryOpen"
          >
            {{ summaryOpen ? 'Réduire' : 'Lire la suite' }}
          </UiButton>
        </div>
      </div>

      <div class="mt-8 space-y-8">
        <section v-if="workFacts.length > 0" aria-labelledby="work-facts-title">
          <h2 id="work-facts-title" class="font-sans text-xl font-semibold text-text">
            Informations sur l’ouvrage
          </h2>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div v-for="fact in workFacts" :key="fact.term">
              <dt class="font-sans text-sm text-text-muted">
                {{ fact.term }}
              </dt>
              <dd class="mt-1 font-sans text-sm text-text">
                <UiLink v-if="fact.to" :to="fact.to">
                  {{ fact.value }}
                </UiLink>
                <span v-else>{{ fact.value }}</span>
              </dd>
            </div>
          </dl>
        </section>

        <section v-if="work.data.value.editions.length > 1" aria-labelledby="editions-title">
          <h2 id="editions-title" class="font-sans text-xl font-semibold text-text">
            Éditions disponibles
          </h2>
          <ul class="mt-4 space-y-4">
            <li
              v-for="edition in work.data.value.editions"
              :key="edition.edition_id ?? edition.isbn ?? edition.titre ?? edition.editeur?.slug"
              class="font-sans text-sm text-text"
            >
              <p>{{ cataloguePublicLabel(edition.titre || 'Édition') }}</p>
              <p v-if="edition.isbn" class="text-text-muted">
                ISBN {{ edition.isbn }}
              </p>
              <p v-if="formatCatalogueDate(edition.date_edition)" class="text-text-muted">
                {{ formatCatalogueDate(edition.date_edition) }}
              </p>
              <p v-if="edition.editeur">
                <UiLink :to="`/catalogue/editeurs/${edition.editeur.slug}`">
                  {{ cataloguePublicLabel(edition.editeur.nom) }}
                </UiLink>
              </p>
            </li>
          </ul>
        </section>

        <section v-if="work.data.value.avis.elements.length > 0" aria-labelledby="reviews-title">
          <h2 id="reviews-title" class="font-sans text-xl font-semibold text-text">
            Avis des lecteurs
          </h2>
          <p v-if="work.data.value.avis_count > 0" class="mt-3 font-sans text-sm text-text tabular-nums">
            Note {{ work.data.value.note_moyenne }} sur 5, sur {{ work.data.value.avis_count }} avis.
          </p>
          <ul class="mt-4 space-y-4">
            <li v-for="(review, index) in work.data.value.avis.elements" :key="index" class="font-sans text-sm text-text">
              <p class="font-semibold">
                {{ review.auteur }} · {{ review.note }} sur 5
              </p>
              <p v-if="formatCatalogueDay(review.soumis_a)" class="text-text-muted">
                {{ formatCatalogueDay(review.soumis_a) }}
              </p>
              <p v-if="review.corps" class="mt-1 max-w-3xl">
                {{ review.corps }}
              </p>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </main>
</template>

<style scoped>
.book-hero {
  display: grid;
  align-items: start;
  gap: 2rem;
  grid-template-areas:
    "cover"
    "copy"
    "offer"
    "summary";
}

.book-hero-cover { grid-area: cover; align-self: start; }
.book-hero-copy { grid-area: copy; }
.book-hero-offer { grid-area: offer; align-self: start; }
.book-hero-summary { grid-area: summary; }

.book-hero.is-compact {
  grid-template-areas:
    "cover"
    "copy"
    "offer";
}

@media (min-width: 768px) {
  .book-hero {
    grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
    grid-template-areas:
      "cover copy"
      "cover summary"
      "offer offer";
  }

  .book-hero.is-compact {
    grid-template-areas:
      "cover copy"
      "offer offer";
  }
}

@media (min-width: 1024px) {
  .book-hero {
    grid-template-columns: minmax(16rem, 20rem) minmax(0, 1fr) minmax(16rem, 20rem);
    grid-template-areas:
      "cover copy offer"
      "cover summary offer";
  }

  .book-hero.is-compact {
    grid-template-areas:
      "cover copy offer";
  }
}
</style>
