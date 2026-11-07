<script setup lang="ts">
import { deadlineKind, labelForDeadline } from '../../utils/identity-access/backoffice'

const props = defineProps<{
  iso?: string | null
  label?: string
}>()

const kind = computed(() => deadlineKind(props.iso))
</script>

<template>
  <p class="font-sans text-sm" :class="{
    'text-danger': kind === 'overdue',
    'text-warning': kind === 'soon',
    'text-text-muted': kind === 'upcoming' || kind === 'none',
  }">
    <span v-if="label" class="font-medium text-text">{{ label }} · </span>
    {{ labelForDeadline(iso) }}
  </p>
</template>
