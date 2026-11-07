<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

withDefaults(
  defineProps<{
    title: string
    value?: string | number
    hint?: string
    icon: UiIconName
    iconTone?: 'brand' | 'warning' | 'danger' | 'info' | 'success'
    loading?: boolean
    unavailable?: boolean
  }>(),
  {
    iconTone: 'brand',
    loading: false,
    unavailable: false,
  },
)
</script>

<template>
  <article class="flex h-full items-start gap-stack rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm">
    <span
      class="inline-flex size-12 shrink-0 items-center justify-center rounded-full"
      :class="{
        'bg-surface-muted text-brand': iconTone === 'brand',
        'bg-surface-warning text-warning': iconTone === 'warning',
        'bg-surface-danger text-danger': iconTone === 'danger',
        'bg-surface-info text-info': iconTone === 'info',
        'bg-surface-success text-success': iconTone === 'success',
      }"
      aria-hidden="true"
    >
      <UiIcon :name="icon" size="md" />
    </span>
    <div class="min-w-0">
      <template v-if="loading">
        <Skeleton class="h-8 w-12" />
        <Skeleton class="mt-2 h-4 w-36 max-w-full" />
        <Skeleton class="mt-2 h-3 w-24 max-w-full" />
        <span class="sr-only">Chargement de {{ title }}</span>
      </template>
      <template v-else>
        <p class="font-sans text-3xl font-semibold tabular-nums text-text">
          {{ unavailable ? '—' : value }}
        </p>
        <h2 class="mt-1 font-sans text-sm font-medium text-text">
          {{ title }}
        </h2>
        <p
          v-if="hint"
          class="mt-1 font-sans text-sm text-text-muted"
        >
          {{ hint }}
        </p>
      </template>
    </div>
  </article>
</template>
