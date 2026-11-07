<script setup lang="ts">
import type { AccountNavItem } from '../../utils/identity-access/account-nav'

const props = withDefaults(
  defineProps<{
    query: string
    results: AccountNavItem[]
    id?: string
  }>(),
  {
    id: 'account-search',
  },
)

const emit = defineEmits<{
  'update:query': [value: string]
  select: [item: AccountNavItem]
  submit: []
}>()

const listId = computed(() => `${props.id}-results`)
const open = computed(() => props.query.trim() !== '')

function onSubmit(event: Event) {
  event.preventDefault()
  emit('submit')
}
</script>

<template>
  <div class="account-search relative w-full max-w-xs md:mx-auto">
    <form role="search" @submit="onSubmit">
      <label :for="id" class="sr-only">Rechercher dans le compte</label>
      <TextInput
        :id="id"
        :model-value="query"
        icon="search"
        placeholder="Rechercher sur Livreaura…"
        class="account-search-input rounded-full bg-white"
        autocomplete="off"
        :aria-expanded="open ? 'true' : 'false'"
        :aria-controls="open ? listId : undefined"
        @update:model-value="emit('update:query', $event)"
      />
    </form>
    <ul
      v-if="open"
      :id="listId"
      class="absolute z-30 mt-1 w-full rounded-md border border-border bg-surface-raised py-1 shadow-md"
      role="listbox"
    >
      <li v-if="results.length === 0" class="px-gutter py-2 font-sans text-sm text-text-muted">
        Aucune section du compte ne correspond.
      </li>
      <li v-for="item in results" :key="item.id">
        <button
          type="button"
          class="flex w-full items-center gap-stack px-gutter py-2 text-left font-sans text-sm text-text hover:bg-surface-muted focus-ring"
          role="option"
          @click="emit('select', item)"
        >
          <UiIcon :name="item.icon" class="text-text-muted" />
          <span>{{ item.label }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
