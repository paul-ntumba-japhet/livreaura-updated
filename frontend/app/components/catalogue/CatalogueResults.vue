<script setup lang="ts">
import type { CatalogueResultPage } from '../../types/catalogue/consultation'
import { catalogueFailure } from '../../utils/catalogue/presentation'

const props = withDefaults(
  defineProps<{
    status: string
    error: unknown
    page: CatalogueResultPage | null
    emptyTitle: string
    emptyMessage: string
    absentMessage?: string
    retrying?: boolean
    lockSort?: boolean
    context?: string
  }>(),
  {
    lockSort: false,
    context: '',
  },
)

const emit = defineEmits<{
  retry: []
}>()

const failure = computed(() => props.error ? catalogueFailure(props.error) : null)
const loading = computed(() => props.status === 'pending' || props.status === 'idle')
const layout = ref<'dense' | 'wide'>('dense')
const columns = computed(() => layout.value === 'wide'
  ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3'
  : 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-4')

function toggleLayout() {
  layout.value = layout.value === 'dense' ? 'wide' : 'dense'
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="loading" class="grid gap-x-6 gap-y-8" :class="columns" aria-busy="true">
      <div v-for="index in 8" :key="index" class="space-y-3">
        <Skeleton class="aspect-[3/4] w-full" label="Chargement d’une couverture" />
        <Skeleton class="h-4 w-4/5" />
        <Skeleton class="h-3 w-1/2" />
      </div>
      <span class="sr-only">Chargement des œuvres</span>
    </div>
    <ErrorState
      v-else-if="failure && failure.status !== 422 && failure.status !== 404"
      title="Consultation interrompue"
      :message="failure.message"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="emit('retry')"
    />
    <InlineAlert
      v-else-if="failure?.status === 422"
      tone="error"
      title="Critères non recevables"
    >
      {{ failure.message }}
    </InlineAlert>
    <EmptyState
      v-else-if="failure?.status === 404"
      title="Cette ressource est absente"
      :message="absentMessage ?? 'Le catalogue ne peut pas afficher cette ressource.'"
    >
      <template #action>
        <UiButton variant="secondary" to="/catalogue">
          Retour au catalogue
        </UiButton>
      </template>
    </EmptyState>
    <template v-else-if="!page || page.total === 0">
      <p v-if="context && page" class="font-sans text-sm text-text-muted">
        {{ context }} · 0 ouvrage
      </p>
      <EmptyState
        :title="emptyTitle"
        :message="emptyMessage"
      >
      <template v-if="$slots.empty" #action>
        <slot name="empty" />
      </template>
      </EmptyState>
    </template>
    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="font-sans text-sm text-text-muted tabular-nums">
          <template v-if="context">{{ context }} · </template>{{ page.total }} {{ page.total > 1 ? 'ouvrages' : 'ouvrage' }}
        </p>
        <div class="flex items-center">
          <slot name="actions" />
          <CatalogueSortChoices v-if="!lockSort" />
          <button
            type="button"
            class="focus-ring inline-flex size-11 items-center justify-center bg-transparent text-text"
            :aria-label="layout === 'dense' ? 'Afficher de plus grandes couvertures' : 'Afficher plus d’œuvres par ligne'"
            @click="toggleLayout"
          >
            <UiIcon :name="layout === 'dense' ? 'layout-grid' : 'columns-2'" />
          </button>
        </div>
      </div>
      <CatalogueWorkList :works="page.oeuvres" :currency="page.devise" :layout="layout" />
      <CataloguePager :page="page.page" :size="page.taille" :total="page.total" />
    </template>
  </div>
</template>
