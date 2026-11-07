<script setup lang="ts">
import { labelForAcknowledgement } from '../../../utils/identity-access/backoffice'

const props = defineProps<{
  niveau?: string | null
}>()

const tone = computed(() => {
  switch (props.niveau) {
    case 'effet_confirme':
      return 'success' as const
    case 'engage_local':
      return 'info' as const
    case 'recu_durable':
      return 'warning' as const
    default:
      return 'neutral' as const
  }
})
</script>

<template>
  <StatusBadge
    :label="labelForAcknowledgement(niveau)"
    :tone="tone"
  />
</template>
