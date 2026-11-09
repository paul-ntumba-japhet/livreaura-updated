<script setup lang="ts">
import type { CatalogueOffer } from '../../types/catalogue/consultation'
import {
  catalogueAvailabilityLabel,
  catalogueConditionLabel,
  catalogueInitialOfferId,
  catalogueOfferIsFree,
  catalogueOfferPriceLabel,
  cataloguePublicLabel,
} from '../../utils/catalogue/presentation'

const props = defineProps<{
  offers: CatalogueOffer[]
  editions: { edition_id?: string, titre: string | null }[]
  availability: string
}>()

const groupName = useId()
const selectedId = ref<string | null>(null)
const notice = ref<'lire' | 'acheter' | null>(null)

watch(() => props.offers.map(offer => offer.offre_id).join('|'), () => {
  selectedId.value = catalogueInitialOfferId(props.offers)
  notice.value = null
}, { immediate: true })

watch(selectedId, () => {
  notice.value = null
})

const selected = computed(() => props.offers.find(offer => offer.offre_id === selectedId.value) ?? null)
const selectedIsFree = computed(() => selected.value ? catalogueOfferIsFree(selected.value.prix) : false)
const severalEditions = computed(() => new Set(props.offers.map(offer => offer.edition_id).filter(Boolean)).size > 1)
const priceLabel = computed(() => selected.value ? catalogueOfferPriceLabel(selected.value) : '')
const restriction = computed(() => props.availability === 'available' ? null : catalogueAvailabilityLabel(props.availability))

function choose(action: 'lire' | 'acheter') {
  notice.value = action
}

function editionHint(offer: CatalogueOffer): string | null {
  if (!severalEditions.value || !offer.edition_id) {
    return null
  }
  const edition = props.editions.find(item => item.edition_id === offer.edition_id)
  const label = cataloguePublicLabel(edition?.titre || '')
  return label === '' ? null : label
}
</script>

<template>
  <aside class="rounded-md border border-border bg-page p-4" aria-labelledby="offer-price">
    <p v-if="restriction" class="mb-2 font-sans text-sm text-text-muted">
      {{ restriction }}
    </p>
    <p
      v-if="selected"
      id="offer-price"
      class="font-sans text-2xl font-semibold text-text tabular-nums"
    >
      {{ priceLabel }}
    </p>
    <p v-else id="offer-price" class="font-sans text-sm text-text-muted">
      {{ restriction === 'À paraître' ? 'Aucune offre n’est encore proposée.' : 'Aucune offre n’est proposée pour le moment.' }}
    </p>

    <p v-if="offers.length === 1 && selected" class="mt-2 font-sans text-sm text-text">
      {{ selected.format_libelle }}
      <span v-if="catalogueConditionLabel(selected.etat)"> · {{ catalogueConditionLabel(selected.etat) }}</span>
      <span v-if="editionHint(selected)"> · {{ editionHint(selected) }}</span>
    </p>

    <fieldset v-else-if="offers.length > 1" class="mt-4 border-0 p-0">
      <legend class="sr-only">
        Choisir une offre
      </legend>
      <div class="space-y-1">
        <label
          v-for="offer in offers"
          :key="offer.offre_id"
          class="flex min-h-11 cursor-pointer items-center gap-3 rounded-md px-2 font-sans text-sm text-text focus-within:outline-none"
          :class="offer.offre_id === selectedId ? 'bg-subtle' : ''"
        >
          <input
            v-model="selectedId"
            class="size-4 accent-brand focus-ring"
            type="radio"
            :name="groupName"
            :value="offer.offre_id"
          >
          <span>
            <span class="font-medium">{{ offer.format_libelle }}</span>
            <span v-if="editionHint(offer)" class="text-text-muted"> · {{ editionHint(offer) }}</span>
            <span v-if="catalogueConditionLabel(offer.etat)" class="text-text-muted"> · {{ catalogueConditionLabel(offer.etat) }}</span>
          </span>
          <span class="ml-auto tabular-nums">{{ catalogueOfferPriceLabel(offer) }}</span>
        </label>
      </div>
    </fieldset>

    <div v-if="selected" class="mt-4 flex flex-col gap-2">
      <UiButton
        type="button"
        block
        :variant="selectedIsFree ? 'primary' : 'secondary'"
        @click="choose('lire')"
      >
        Lire
      </UiButton>
      <UiButton
        type="button"
        block
        :variant="selectedIsFree ? 'secondary' : 'primary'"
        @click="choose('acheter')"
      >
        Acheter
      </UiButton>
      <p v-if="notice" class="font-sans text-sm text-text-muted" role="status">
        {{ notice === 'lire' ? 'La lecture n’est pas ouverte pour le moment.' : 'L’achat n’est pas ouvert pour le moment.' }}
      </p>
    </div>
  </aside>
</template>
