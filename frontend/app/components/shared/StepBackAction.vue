<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

withDefaults(
  defineProps<{
    label: string
    to?: RouteLocationRaw
    disabled?: boolean
  }>(),
  {
    disabled: false,
  },
)

const emit = defineEmits<{
  click: []
}>()
</script>

<template>
  <NuxtLink
    v-if="to && !disabled"
    :to="to"
    class="inline-flex items-center gap-1 font-sans text-sm text-text-muted rounded-sm focus-ring"
  >
    <UiIcon name="arrow-left" />
    {{ label }}
  </NuxtLink>
  <button
    v-else
    type="button"
    class="inline-flex items-center gap-1 font-sans text-sm text-text-muted rounded-sm focus-ring"
    :class="{ 'cursor-not-allowed opacity-50': disabled }"
    :disabled="disabled"
    @click="emit('click')"
  >
    <UiIcon name="arrow-left" />
    {{ label }}
  </button>
</template>
