<script setup lang="ts">
import type { CatalogueWorkCard } from '../../types/catalogue/consultation'
import { catalogueMediaUrl } from '../../services/catalogue/consultation'
import { catalogueRepresentativeOffer, cataloguePublicLabel } from '../../utils/catalogue/presentation'

const props = withDefaults(
  defineProps<{
    works: CatalogueWorkCard[]
    currency: string
    layout?: 'dense' | 'wide'
    showPrice?: boolean
  }>(),
  {
    layout: 'dense',
    showPrice: true,
  },
)

const columns = computed(() => props.layout === 'wide'
  ? 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3'
  : 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-4')

function offerOf(work: CatalogueWorkCard) {
  return catalogueRepresentativeOffer(work, props.currency)
}

const missingCovers = reactive<Record<string, boolean>>({})

function authorsOf(work: CatalogueWorkCard): string {
  return work.auteurs.map(author => cataloguePublicLabel(author.nom)).join(', ')
}

function titleOf(work: CatalogueWorkCard): string {
  return cataloguePublicLabel(work.titre)
}

function coverOf(work: CatalogueWorkCard): string {
  if (missingCovers[work.slug] || !work.couverture) {
    return ''
  }
  if (work.couverture.src) {
    return work.couverture.src
  }
  if (work.couverture.media_catalogue_id) {
    return catalogueMediaUrl(work.couverture.media_catalogue_id)
  }
  return ''
}
</script>

<template>
  <ol class="grid gap-x-6 gap-y-8" :class="columns">
    <li v-for="work in works" :key="work.slug" class="min-w-0">
      <article class="h-full">
        <NuxtLink
          :to="`/catalogue/oeuvres/${work.slug}`"
          class="focus-ring flex h-full flex-col gap-3 rounded-md transition-colors duration-200 motion-reduce:transition-none"
        >
          <span class="flex aspect-[3/4] items-center justify-center overflow-hidden rounded-md border border-border bg-surface-muted">
            <img
              v-if="coverOf(work)"
              :src="coverOf(work)"
              :alt="work.couverture?.texte_alternatif || ''"
              class="h-full w-full object-contain"
              loading="lazy"
              decoding="async"
              @error="missingCovers[work.slug] = true"
            >
            <span
              v-else
              class="px-3 text-center font-sans text-xs text-text-muted"
            >
              Aucune couverture
            </span>
          </span>
          <span class="flex min-w-0 flex-1 flex-col gap-1">
            <h2 class="line-clamp-2 font-sans text-sm font-semibold leading-5 text-text" :title="titleOf(work)">
              {{ titleOf(work) }}
            </h2>
            <p v-if="authorsOf(work)" class="line-clamp-2 font-sans text-xs text-text-muted">
              {{ authorsOf(work) }}
            </p>
            <p v-if="work.badge" class="font-sans text-xs text-text-muted">
              {{ work.badge }}
            </p>
            <p v-if="showPrice" class="mt-auto pt-2 font-sans text-sm font-semibold text-text tabular-nums">
              {{ offerOf(work).label }}
            </p>
            <p v-if="showPrice && offerOf(work).formats" class="font-sans text-xs text-text-muted">
              {{ offerOf(work).formats }}
            </p>
          </span>
        </NuxtLink>
      </article>
    </li>
  </ol>
</template>
