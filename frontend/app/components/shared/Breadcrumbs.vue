<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

defineProps<{
  items: Array<{
    label: string
    to?: RouteLocationRaw
  }>
  label?: string
}>()
</script>

<template>
  <nav :aria-label="label || 'Fil d’Ariane'">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 font-sans text-sm text-text-muted">
      <li
        v-for="(item, index) in items"
        :key="`${item.label}-${index}`"
        class="inline-flex min-w-0 items-center gap-2"
      >
        <UiLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          muted
        >
          {{ item.label }}
        </UiLink>
        <span
          v-else
          class="min-w-0 truncate"
          :class="index === items.length - 1 ? 'font-medium text-text' : ''"
          :aria-current="index === items.length - 1 ? 'page' : undefined"
        >
          {{ item.label }}
        </span>
        <span
          v-if="index < items.length - 1"
          aria-hidden="true"
        >
          /
        </span>
      </li>
    </ol>
  </nav>
</template>
