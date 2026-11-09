<script setup lang="ts">
import { listCatalogueAuthors } from '../../../services/catalogue/consultation'
import { demonstrationAuthors } from '../../../utils/catalogue/demonstration'
import { catalogueFailure, cataloguePublicLabel } from '../../../utils/catalogue/presentation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

useHead({ title: 'Auteurs — Livreaura' })

const route = useRoute()
const authors = await useAsyncData(
  () => `catalogue-authors:${route.fullPath}`,
  () => listCatalogueAuthors({ ...route.query }),
  { watch: [() => route.fullPath] },
)
const examples = demonstrationAuthors()
const retrying = ref(false)
const failure = computed(() => authors.error.value ? catalogueFailure(authors.error.value) : null)

async function retry() {
  retrying.value = true
  try {
    await authors.refresh()
  } finally {
    retrying.value = false
  }
}
</script>

<template>
  <main class="px-page py-section">
    <PageHeader
      title="Auteurs"
      description="Annuaire des auteurs publiés."
      context="Catalogue"
    >
      <template #actions>
        <UiButton variant="secondary" to="/catalogue">
          Retour au catalogue
        </UiButton>
      </template>
    </PageHeader>
    <div class="mt-8 space-y-10">
      <section class="space-y-4">
        <div class="space-y-1">
          <h2 class="font-sans text-2xl font-semibold leading-8 text-text">
            Exemples locaux
          </h2>
          <p class="max-w-3xl font-sans text-sm text-text-muted">
            Ces quatre personnes sont écrites dans la page. Chacune a un livre d’exemple. Cette liste ne vient pas de la base.
          </p>
        </div>
        <ul class="space-y-3">
          <li v-for="author in examples" :key="author.slug">
            <UiLink :to="`/catalogue/auteurs/${author.slug}`">
              {{ cataloguePublicLabel(author.nom) }}
            </UiLink>
            <p v-if="author.pays" class="font-sans text-sm text-text-muted">
              {{ author.pays }}
            </p>
          </li>
        </ul>
      </section>
      <div v-if="authors.status.value === 'pending'" class="space-y-3" aria-busy="true">
        <Skeleton class="h-16 w-full" />
        <Skeleton class="h-16 w-full" />
        <span class="sr-only">Chargement des auteurs</span>
      </div>
      <ErrorState
        v-else-if="failure && failure.status !== 422"
        title="Consultation interrompue"
        :message="failure.message"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="retry"
      />
      <InlineAlert v-else-if="failure?.status === 422" tone="error" title="Critères non recevables">
        {{ failure.message }}
      </InlineAlert>
      <EmptyState
        v-else-if="!authors.data.value || authors.data.value.total === 0"
        title="Aucun auteur publié"
        message="Aucun auteur n’est consultable pour le moment."
      />
      <div v-else class="space-y-6">
        <p class="font-sans text-sm text-text-muted tabular-nums">
          {{ authors.data.value.total }} {{ authors.data.value.total > 1 ? 'auteurs' : 'auteur' }}
        </p>
        <ul class="space-y-3">
          <li v-for="author in authors.data.value.auteurs" :key="author.slug">
            <UiLink :to="`/catalogue/auteurs/${author.slug}`">
              {{ cataloguePublicLabel(author.nom) }}
            </UiLink>
            <p v-if="author.pays" class="font-sans text-sm text-text-muted">
              {{ author.pays }}
            </p>
          </li>
        </ul>
        <CataloguePager
          :page="authors.data.value.page"
          :size="authors.data.value.taille"
          :total="authors.data.value.total"
        />
      </div>
    </div>
  </main>
</template>
