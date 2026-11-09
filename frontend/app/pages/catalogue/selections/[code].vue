<script setup lang="ts">
import { showCatalogueSelection } from '../../../services/catalogue/consultation'
import { demonstrationWorks } from '../../../utils/catalogue/demonstration'

definePageMeta({
  layout: 'public',
  background: 'white',
})

const route = useRoute()
const code = computed(() => String(route.params.code ?? ''))
const hasCriteria = computed(() => Object.keys(route.query).some(key => key !== 'page' && key !== 'selection' && route.query[key]))
const localShelf = computed(() => code.value === 'a_paraitre' || code.value === 'tendances' ? code.value : null)
const localWorks = computed(() => localShelf.value && !hasCriteria.value
  ? demonstrationWorks(localShelf.value)
  : [])
const selection = await useAsyncData(
  () => `catalogue-selection:${route.fullPath}`,
  () => localWorks.value.length > 0
    ? Promise.resolve(null)
    : showCatalogueSelection(code.value, { ...route.query, selection: undefined }),
  { watch: [() => route.fullPath] },
)
const retrying = ref(false)

async function retry() {
  retrying.value = true
  try {
    await selection.refresh()
  } finally {
    retrying.value = false
  }
}

const title = computed(() => {
  if (localWorks.value.length > 0 && code.value === 'a_paraitre') {
    return 'À paraître'
  }
  if (localWorks.value.length > 0 && code.value === 'tendances') {
    return 'Tendances'
  }
  return selection.data.value?.selection.libelle ?? 'Sélection'
})

useHead({
  title: () => `${title.value} — Livreaura`,
})
</script>

<template>
  <main class="px-page py-section">
    <PageHeader
      :title="title"
      description="Sélection du catalogue."
      context="Catalogue"
    >
      <template #actions>
        <UiButton variant="secondary" to="/catalogue">
          Tout le catalogue
        </UiButton>
      </template>
    </PageHeader>
    <div class="mt-8 space-y-8">
      <CatalogueSearchForm
        v-if="selection.data.value || localWorks.length > 0"
        :lock-sort="code === 'tendances' || selection.data.value?.selection.mode === 'manuelle'"
        :lock-availability="code === 'a_paraitre' || selection.data.value?.selection.code === 'a_paraitre'"
        :searching="selection.status.value === 'pending'"
      />
      <div v-if="localWorks.length > 0" class="space-y-4">
        <p class="font-sans text-sm text-text-muted">
          Ces livres sont des exemples écrits dans la page. Ils ne viennent pas de la base.
        </p>
        <CatalogueWorkList :works="localWorks" currency="USD" />
      </div>
      <CatalogueResults
        v-else
        :status="selection.status.value"
        :error="selection.error.value"
        :lock-sort="selection.data.value?.selection.mode === 'manuelle'"
        :page="selection.data.value?.resultats ?? null"
        empty-title="Sélection vide"
        empty-message="Aucune œuvre visible ne figure dans cette sélection."
        absent-message="Cette sélection n’est pas consultable."
        :retrying="retrying"
        @retry="retry"
      />
    </div>
  </main>
</template>
