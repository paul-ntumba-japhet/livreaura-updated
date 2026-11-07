<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'line' | 'card' | 'form' | 'detail'
    label?: string
  }>(),
  {
    variant: 'line',
    label: 'Chargement',
  },
)
</script>

<template>
  <div
    class="animate-pulse rounded-md bg-surface-muted"
    aria-busy="true"
    :aria-label="label"
  >
    <span class="sr-only">{{ label }}</span>
    <div v-if="variant === 'card'" class="h-24" />
    <div v-else-if="variant === 'line'" class="h-4" />
    <div v-else-if="variant === 'form'" class="space-y-3 p-gutter">
      <div class="h-4 w-1/3 rounded-sm bg-border" />
      <div class="h-11 rounded-md bg-border" />
      <div class="h-4 w-1/2 rounded-sm bg-border" />
      <div class="h-11 rounded-md bg-border" />
    </div>
    <div v-else class="space-y-3 p-gutter">
      <div class="h-6 w-2/3 rounded-sm bg-border" />
      <div class="h-4 w-full rounded-sm bg-border" />
      <div class="h-4 w-5/6 rounded-sm bg-border" />
    </div>
  </div>
</template>
