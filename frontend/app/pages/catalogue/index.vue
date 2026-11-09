<script setup lang="ts">
import type { CatalogueCategoryNode } from '../../types/catalogue/consultation'
import { listCatalogueCategories, searchCatalogue } from '../../services/catalogue/consultation'
import { demonstrationWorks } from '../../utils/catalogue/demonstration'
import { catalogueCategoryTarget, catalogueFailure } from '../../utils/catalogue/presentation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

useHead({
  title: 'Catalogue — Livreaura',
})

const route = useRoute()

const filterKeys = ['texte', 'categorie', 'format', 'gratuite', 'langue', 'etat', 'disponibilite', 'niveau', 'auteur', 'editeur', 'africain', 'devise', 'prix_min', 'prix_max']
const exampleShelves = [
  { title: 'À paraître', works: demonstrationWorks('a_paraitre'), showPrice: false },
  { title: 'Tendances', works: demonstrationWorks('tendances'), showPrice: true },
  { title: 'Autres lectures', works: demonstrationWorks('auteurs'), showPrice: true },
]
const hasFilters = computed(() => filterKeys.some(key => typeof route.query[key] === 'string' && route.query[key] !== ''))
const showsExamples = computed(() => {
  if (hasFilters.value) {
    return false
  }
  const page = route.query.page
  return typeof page !== 'string' || page === '' || page === '1'
})
const search = await useAsyncData(
  () => `catalogue-search:${route.fullPath}`,
  () => searchCatalogue({ ...route.query }),
  { watch: [() => route.fullPath] },
)

const categories = await useAsyncData('catalogue-categories', () => listCatalogueCategories())
const activeCategory = computed(() => typeof route.query.categorie === 'string' ? route.query.categorie : '')
const categoryTarget = computed(() => catalogueCategoryTarget(route.query, ''))
const categoryContext = computed(() => {
  const trail = categoryTrail(categories.data.value?.categories ?? [], activeCategory.value)
  return trail ? trail.join(' / ') : ''
})
const categoryFailure = computed(() => categories.error.value ? catalogueFailure(categories.error.value) : null)
const retrying = ref(false)

function categoryTrail(nodes: CatalogueCategoryNode[], active: string, names: string[] = [], seen = new Set<string>()): string[] | null {
  if (active === '') {
    return null
  }
  for (const node of nodes) {
    if (seen.has(node.categorie_id)) {
      continue
    }
    seen.add(node.categorie_id)
    const next = [...names, node.nom]
    if (node.chemin === active || node.categorie_id === active) {
      return next
    }
    const child = categoryTrail(node.enfants, active, next, seen)
    if (child) {
      return child
    }
  }
  return null
}

async function retry() {
  retrying.value = true
  try {
    await search.refresh()
  } finally {
    retrying.value = false
  }
}

const filtersOpen = ref(false)
const activeFilterCount = computed(() => filterKeys.filter(key => typeof route.query[key] === 'string' && route.query[key] !== '').length)
</script>

<template>
  <main class="px-page py-section">
    <PageHeader
      title="Catalogue"
      description="Parcourez les livres publiés et ouvrez leur fiche."
    />
    <div class="mt-8 grid items-start gap-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
      <aside
        class="space-y-6 lg:sticky lg:top-[calc(var(--layout-header-height)+1rem)] lg:z-10"
        :class="filtersOpen ? 'block' : 'max-lg:hidden'"
      >
        <span class="lg:hidden">
          <UiButton type="button" variant="secondary" @click="filtersOpen = false">
            Fermer les filtres
          </UiButton>
        </span>
        <section aria-labelledby="catalogue-categories-titre" class="space-y-1 border-b border-border pb-4">
          <div class="flex items-center justify-between gap-2">
            <h2 id="catalogue-categories-titre" class="font-sans text-sm font-semibold leading-5 text-text">
              Catégories
            </h2>
            <NuxtLink
              v-if="activeCategory"
              :to="categoryTarget"
              aria-current="false"
              class="focus-ring inline-flex min-h-11 items-center px-2 font-sans text-sm text-text"
            >
              Effacer
            </NuxtLink>
          </div>
          <p v-if="categories.status.value === 'pending'" class="font-sans text-sm text-text-muted">
            Chargement des catégories.
          </p>
          <div v-else-if="categoryFailure" class="space-y-3">
            <InlineAlert tone="error" title="Catégories indisponibles">
              {{ categoryFailure.message }}
            </InlineAlert>
            <UiButton type="button" variant="secondary" @click="categories.refresh()">
              Réessayer
            </UiButton>
          </div>
          <template v-else-if="categories.data.value?.categories.length">
            <NuxtLink
              :to="categoryTarget"
              class="focus-ring flex min-h-10 items-center rounded-md border-l-2 px-2 font-sans text-sm leading-5 hover:bg-[var(--surface-subtle)]"
              :class="activeCategory ? 'border-transparent text-text' : 'border-brand bg-[var(--surface-subtle)] font-semibold text-text'"
              :aria-current="activeCategory ? undefined : 'true'"
            >
              Toutes les catégories
            </NuxtLink>
            <CatalogueCategoryLinks
              :categories="categories.data.value.categories"
              :active-path="activeCategory"
            />
          </template>
          <p v-else class="font-sans text-sm text-text-muted">
            Aucune catégorie n’est disponible.
          </p>
        </section>
        <CatalogueSearchForm
          variant="aside"
          :searching="search.status.value === 'pending'"
        />
      </aside>
      <div class="space-y-12">
        <div class="space-y-6">
        <CatalogueTextSearch :categories="categories.data.value?.categories ?? []" />
        <CatalogueResults
          :status="search.status.value"
          :error="search.error.value"
          :page="search.data.value"
          :context="categoryContext"
          :empty-title="hasFilters ? 'Aucun résultat' : 'Aucune œuvre publiée'"
          :empty-message="hasFilters
            ? 'Aucune œuvre ne correspond à ces critères. Effacez un filtre pour élargir la liste.'
            : 'Aucune œuvre publiée n’est disponible pour le moment.'"
          :retrying="retrying"
          @retry="retry"
        >
          <template v-if="hasFilters" #empty>
            <UiButton variant="secondary" to="/catalogue" aria-current="false">
              Effacer les filtres
            </UiButton>
          </template>
          <template #actions>
            <span class="lg:hidden">
              <UiButton type="button" variant="secondary" @click="filtersOpen = !filtersOpen">
                {{ filtersOpen ? 'Masquer les filtres' : activeFilterCount > 0 ? `Filtres (${activeFilterCount})` : 'Filtres' }}
              </UiButton>
            </span>
          </template>
        </CatalogueResults>
        </div>
        <div v-if="showsExamples && search.data.value" class="space-y-12">
          <p class="font-sans text-sm text-text-muted">
            Ces blocs sont des exemples écrits dans la page.
          </p>
          <section v-for="shelf in exampleShelves" :key="shelf.title" class="space-y-4">
            <h2 class="font-sans text-2xl font-semibold leading-8 text-text">
              {{ shelf.title }}
            </h2>
            <CatalogueWorkList
              :works="shelf.works"
              currency="USD"
              :show-price="shelf.showPrice"
            />
          </section>
        </div>
      </div>
    </div>
  </main>
</template>
