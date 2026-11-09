<script setup lang="ts">
import { showCatalogueAuthor } from '../../../services/catalogue/consultation'
import { findDemonstrationAuthor } from '../../../utils/catalogue/demonstration'
import { catalogueFailure, cataloguePublicLabel } from '../../../utils/catalogue/presentation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

const route = useRoute()
const slug = computed(() => String(route.params.slug ?? ''))
const author = await useAsyncData(
  () => `catalogue-author:${route.fullPath}`,
  () => findDemonstrationAuthor(slug.value) ?? showCatalogueAuthor(slug.value, { ...route.query }),
  { watch: [() => route.fullPath] },
)
const retrying = ref(false)
const failure = computed(() => author.error.value ? catalogueFailure(author.error.value) : null)

async function retry() {
  retrying.value = true
  try {
    await author.refresh()
  } finally {
    retrying.value = false
  }
}

useHead({
  title: () => author.data.value ? `${cataloguePublicLabel(author.data.value.auteur.nom)} — Livreaura` : 'Auteur — Livreaura',
})
</script>

<template>
  <main class="px-page py-section">
    <div v-if="author.status.value === 'pending'" class="space-y-4" aria-busy="true">
      <Skeleton class="h-10 w-64" />
      <Skeleton class="h-24 w-full" />
      <span class="sr-only">Chargement de la fiche auteur</span>
    </div>
    <ErrorState
      v-else-if="failure && failure.status !== 404"
      title="Consultation interrompue"
      :message="failure.message"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="retry"
    />
    <div v-else-if="failure?.status === 404 || !author.data.value">
      <PageHeader title="Auteur" context="Catalogue" />
      <EmptyState
        class="mt-8"
        title="Cette ressource est absente"
        message="Cet auteur n’est pas consultable."
      >
        <template #action>
          <UiButton variant="secondary" to="/catalogue/auteurs">
            Retour à l’annuaire
          </UiButton>
        </template>
      </EmptyState>
    </div>
    <template v-else>
      <PageHeader
        :title="cataloguePublicLabel(author.data.value.auteur.nom)"
        :description="author.data.value.auteur.role_affiche ?? undefined"
        context="Catalogue"
      >
        <template #actions>
          <UiButton variant="secondary" to="/catalogue/auteurs">
            Annuaire des auteurs
          </UiButton>
        </template>
      </PageHeader>
      <div class="mt-8 max-w-3xl space-y-8">
        <p v-if="author.data.value.auteur.pays" class="font-sans text-sm text-text-muted">
          {{ author.data.value.auteur.pays }}
        </p>
        <p v-if="author.data.value.auteur.verified" class="font-sans text-sm text-text">
          Profil vérifié. Cet indicateur ne donne aucun droit commercial.
        </p>
        <p v-if="author.data.value.auteur.biographie" class="font-sans text-base text-text">
          {{ author.data.value.auteur.biographie }}
        </p>
        <ul v-if="author.data.value.auteur.tags.length > 0" class="flex flex-wrap gap-2">
          <li v-for="tag in author.data.value.auteur.tags" :key="tag.slug" class="font-sans text-sm text-text-muted">
            {{ tag.nom }}
          </li>
        </ul>
        <section aria-labelledby="bibliography-title">
          <h2 id="bibliography-title" class="font-sans text-xl font-semibold text-text">
            Bibliographie
          </h2>
          <div class="mt-4">
            <CatalogueResults
              status="success"
              :error="null"
              :page="author.data.value.bibliographie"
              empty-title="Bibliographie vide"
              empty-message="Aucune œuvre visible n’est associée à cet auteur."
            />
          </div>
        </section>
      </div>
    </template>
  </main>
</template>
