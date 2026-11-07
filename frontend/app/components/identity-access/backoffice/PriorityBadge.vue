<script setup lang="ts">
import { normalizeTaskPriority } from '../../../types/identity-access/backoffice'
import { labelForTaskPriority } from '../../../utils/identity-access/backoffice'

const props = defineProps<{
  priorite: string
  motif?: string | null
}>()

const known = computed(() => normalizeTaskPriority(props.priorite))
</script>

<template>
  <span class="inline-flex flex-col items-start gap-1">
    <StatusBadge
      :label="labelForTaskPriority(priorite)"
      :tone="known === 'urgente' ? 'warning' : 'neutral'"
    />
    <span v-if="known === 'urgente' && motif" class="font-sans text-sm text-text-muted">
      {{ motif }}
    </span>
  </span>
</template>
