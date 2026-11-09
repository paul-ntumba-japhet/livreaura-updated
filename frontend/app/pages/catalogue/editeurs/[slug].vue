<script setup lang="ts">
import { showCataloguePublisher } from '../../../services/catalogue/consultation'
import { catalogueFailure } from '../../../utils/catalogue/presentation'

definePageMeta({
  layout: 'public',
  background: 'white',
})

const route = useRoute()
const slug = computed(() => String(route.params.slug ?? ''))
const publisher = await useAsyncData(
  () => `catalogue-publisher:${route.fullPath}`,
  () => showCataloguePublisher(slug.value, { ...route.query }),
  { watch: [() => route.fullPath] },
)
const retrying = ref(false)
const failure = computed(() => publisher.error.value ? catalogueFailure(publisher.error.value) : null)

async function retry() {
  retrying.value = true
  try {
    await publisher.refresh()
  } finally {
    retrying.value = false
  }
}

useHead({
  title: () => publisher.data.value ? `${publisher.data.value.editeur.nom} — Livreaura` : 'Éditeur — Livreaura',
})
</script>

<template>
  <main class="px-page py-section">
    <div v-if="publisher.status.value === 'pending'" class="space-y-4" aria-busy="true">
      <Skeleton class="h-10 w-64" />
      <Skeleton class="h-24 w-full" />
      <span class="sr-only">Chargement de la fiche éditeur</span>
    </div>
    <ErrorState
      v-else-if="failure && failure.status !== 404"
      title="Consultation interrompue"
      :message="failure.message"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="retry"
    />
    <div v-else-if="failure?.status === 404 || !publisher.data.value">
      <PageHeader title="Éditeur" context="Catalogue" />
      <EmptyState
        class="mt-8"
        title="Cette ressource est absente"
        message="Cet éditeur n’est pas consultable."
      >
        <template #action>
          <UiButton variant="secondary" to="/catalogue">
            Retour au catalogue
          </UiButton>
        </template>
      </EmptyState>
    </div>
    <template v-else>
      <PageHeader
        :title="publisher.data.value.editeur.nom"
        :description="publisher.data.value.editeur.pays ?? undefined"
        context="Catalogue"
      >
        <template #actions>
          <UiButton variant="secondary" to="/catalogue">
            Retour au catalogue
          </UiButton>
        </template>
      </PageHeader>
      <div class="mt-8 max-w-3xl space-y-8">
        <p v-if="publisher.data.value.editeur.presentation" class="font-sans text-base text-text">
          {{ publisher.data.value.editeur.presentation }}
        </p>
        <section aria-labelledby="publisher-editions-title">
          <h2 id="publisher-editions-title" class="font-sans text-xl font-semibold text-text">
            Éditions
          </h2>
          <EmptyState
            v-if="publisher.data.value.editions.total === 0"
            class="mt-4"
            title="Aucune édition consultable"
            message="Cet éditeur n’a pas d’édition visible."
          />
          <ul v-else class="mt-4 space-y-3">
            <li v-for="edition in publisher.data.value.editions.elements" :key="`${edition.isbn}-${edition.titre}`" class="font-sans text-sm text-text">
              <p>{{ edition.titre || 'Édition' }}</p>
              <p v-if="edition.isbn" class="text-text-muted">
                ISBN {{ edition.isbn }}
              </p>
              <p v-if="edition.oeuvre">
                <UiLink :to="`/catalogue/oeuvres/${edition.oeuvre.slug}`" plain>
                  {{ edition.oeuvre.titre }}
                </UiLink>
              </p>
            </li>
          </ul>
        </section>
        <section aria-labelledby="publisher-collections-title">
          <h2 id="publisher-collections-title" class="font-sans text-xl font-semibold text-text">
            Collections
          </h2>
          <EmptyState
            v-if="publisher.data.value.collections.length === 0"
            class="mt-4"
            title="Aucune collection active"
            message="Cet éditeur ne porte pas de collection consultable."
          />
          <div v-else class="mt-4 space-y-6">
            <section v-for="collection in publisher.data.value.collections" :key="collection.nom">
              <h3 class="font-sans text-base font-semibold text-text">
                {{ collection.nom }}
              </h3>
              <p v-if="collection.kind === 'bundle'" class="mt-1 font-sans text-sm text-text-muted">
                Cette collection ne forme ni un prix ni un droit groupé.
              </p>
              <ul v-if="collection.oeuvres.length > 0" class="mt-2 space-y-1">
                <li v-for="item in collection.oeuvres" :key="item.slug">
                  <UiLink :to="`/catalogue/oeuvres/${item.slug}`">
                    {{ item.titre }}
                  </UiLink>
                </li>
              </ul>
              <p v-else class="mt-2 font-sans text-sm text-text-muted">
                Aucune œuvre visible dans cette collection.
              </p>
            </section>
          </div>
        </section>
      </div>
    </template>
  </main>
</template>
