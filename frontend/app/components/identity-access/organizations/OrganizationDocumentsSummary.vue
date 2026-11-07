<script setup lang="ts">
import type { OrganizationDocumentHighlight } from '../../../utils/identity-access/organization-display'
import { pieceCountLabel } from '../../../utils/identity-access/organization-display'

defineProps<{
  highlights: OrganizationDocumentHighlight[]
}>()

const emit = defineEmits<{
  select: [id: string]
  'see-requirements': []
}>()
</script>

<template>
  <div class="flex flex-col gap-gutter">
    <div
      class="grid gap-stack"
      :class="highlights.length >= 3 ? 'grid-cols-3' : (highlights.length === 2 ? 'grid-cols-2' : 'grid-cols-1')"
    >
      <button
        v-for="item in highlights"
        :key="item.id"
        type="button"
        class="flex flex-col items-center rounded-md px-1 py-stack text-center focus-ring"
        @click="emit('select', item.id)"
      >
        <span
          class="inline-flex size-12 items-center justify-center rounded-full bg-surface-muted text-text"
          aria-hidden="true"
        >
          <UiIcon :name="item.icon" size="md" />
        </span>
        <span class="mt-2 font-sans text-sm font-medium text-text">
          {{ item.label }}
        </span>
        <span class="mt-0.5 font-sans text-xs text-text-muted">
          {{ pieceCountLabel(item.count) }}
        </span>
      </button>
    </div>
    <p>
      <button
        type="button"
        class="inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
        @click="emit('see-requirements')"
      >
        Voir les exigences
        <UiIcon name="arrow-right" />
      </button>
    </p>
  </div>
</template>
