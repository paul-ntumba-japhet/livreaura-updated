<script setup lang="ts">
import type { AccessScope } from '../../../types/identity-access/account'
import { labelForAccessScope } from '../../../utils/identity-access/account'

const props = defineProps<{
  portee: AccessScope
}>()

const tone = computed(() => {
  switch (props.portee) {
    case 'normal':
      return 'info' as const
    case 'limited':
    case 'support_only':
      return 'warning' as const
    default:
      return 'neutral' as const
  }
})
</script>

<template>
  <div class="rounded-md border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-xl font-semibold text-text">
      Portée de la session
    </h2>
    <p class="mt-stack">
      <StatusBadge :label="labelForAccessScope(portee)" :tone="tone" />
    </p>
    <p
      v-if="portee === 'unknown'"
      class="mt-stack font-sans text-sm text-text-muted"
    >
      La portée renvoyée n’est pas reconnue. Aucun accès supplémentaire n’est présumé.
    </p>
  </div>
</template>
