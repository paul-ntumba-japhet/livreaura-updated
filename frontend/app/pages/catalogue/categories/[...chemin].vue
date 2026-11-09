<script setup lang="ts">
import { showCatalogueCategory } from '../../../services/catalogue/consultation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

const route = useRoute()
const chemin = computed(() => {
  const value = route.params.chemin
  return Array.isArray(value) ? value.map(String).join('/') : String(value ?? '')
})

const category = await useAsyncData(
  () => `catalogue-category:${route.fullPath}`,
  () => showCatalogueCategory(chemin.value, { ...route.query }),
  { watch: [() => route.fullPath] },
)
const retrying = ref(false)

async function retry() {
  retrying.value = true
  try {
    await category.refresh()
  } finally {
    retrying.value = false
  }
}

const title = computed(() => category.data.value?.categorie.nom ?? 'Catégorie')

useHead({
  title: () => `${title.value} — Livreaura`,
})
</script>

<template>
  <main class="px-page py-section">
    <PageHeader
      :title="title"
      description="Œuvres publiées de cette catégorie."
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
        v-if="category.data.value"
        :searching="category.status.value === 'pending'"
      />
      <CatalogueResults
        :status="category.status.value"
        :error="category.error.value"
        :page="category.data.value?.resultats ?? null"
        empty-title="Catégorie vide"
        empty-message="Aucune œuvre visible n’est rangée dans cette catégorie."
        absent-message="Cette catégorie n’est pas consultable."
        :retrying="retrying"
        @retry="retry"
      />
    </div>
  </main>
</template>
