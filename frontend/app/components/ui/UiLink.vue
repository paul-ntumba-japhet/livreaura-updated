<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

withDefaults(
  defineProps<{
    to: RouteLocationRaw
    external?: boolean
    current?: boolean
    muted?: boolean
    danger?: boolean
    plain?: boolean
  }>(),
  {
    external: false,
    current: false,
    muted: false,
    danger: false,
    plain: false,
  },
)
</script>

<template>
  <NuxtLink
    :to="to"
    :external="external"
    :target="external ? '_blank' : undefined"
    :rel="external ? 'noopener noreferrer' : undefined"
    class="font-sans focus-ring rounded-sm"
    :class="plain
      ? 'inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-hover'
      : danger
        ? (muted ? 'text-xs text-danger underline decoration-danger underline-offset-4' : 'text-sm text-danger underline decoration-danger underline-offset-4')
        : muted
          ? 'text-xs text-text-muted underline decoration-brand underline-offset-4'
          : 'text-sm text-text underline decoration-brand underline-offset-4'"
    :aria-current="current ? 'page' : undefined"
  >
    <slot />
    <span v-if="external" class="sr-only"> (ouvre un nouvel onglet)</span>
  </NuxtLink>
</template>
