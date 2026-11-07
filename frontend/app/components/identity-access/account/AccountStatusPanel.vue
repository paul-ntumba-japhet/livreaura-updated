<script setup lang="ts">
import type { AccountStatus } from '../../../types/identity-access/account'
import { labelForAccountStatus } from '../../../utils/identity-access/account'

const props = defineProps<{
  statut: AccountStatus
}>()

const tone = computed(() => {
  switch (props.statut) {
    case 'active':
      return 'success' as const
    case 'pending':
      return 'warning' as const
    case 'suspended':
      return 'danger' as const
    default:
      return 'neutral' as const
  }
})
</script>

<template>
  <div class="rounded-md border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-xl font-semibold text-text">
      État du compte
    </h2>
    <p class="mt-stack">
      <StatusBadge :label="labelForAccountStatus(statut)" :tone="tone" />
    </p>
    <p
      v-if="statut === 'unknown'"
      class="mt-stack font-sans text-sm text-text-muted"
    >
      Le statut renvoyé n’est pas reconnu. Les actions qui en dépendent restent bloquées.
    </p>
  </div>
</template>
