<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

withDefaults(
  defineProps<{
    title: string
    description?: string
    accent?: boolean
    elevated?: boolean
    icon?: UiIconName
    iconSize?: 'sm' | 'md' | 'lg'
  }>(),
  {
    accent: false,
    elevated: false,
    iconSize: 'sm',
  },
)
</script>

<template>
  <section
    class="flex h-full scroll-mt-24 flex-col rounded-md bg-surface-raised px-gutter py-gutter"
    :class="elevated
      ? 'shadow-md'
      : (accent ? 'border border-border border-l-4 border-l-brand shadow-sm' : 'border border-border shadow-sm')"
  >
    <div class="flex flex-wrap items-start justify-between gap-stack">
      <div class="min-w-0">
        <h2 class="flex items-center gap-2 font-sans text-base font-semibold text-text">
          <span
            v-if="icon"
            class="inline-flex shrink-0 items-center justify-center"
            :class="iconSize === 'lg'
              ? 'size-12 rounded-md bg-surface-muted text-brand'
              : ''"
            aria-hidden="true"
          >
            <UiIcon
              :name="icon"
              :size="iconSize"
              :class="iconSize === 'lg' ? 'text-brand' : 'text-text-muted'"
            />
          </span>
          <span>{{ title }}</span>
        </h2>
        <p
          v-if="description"
          class="mt-2 font-sans text-sm text-text-muted"
        >
          {{ description }}
        </p>
      </div>
      <div
        v-if="$slots.action"
        class="shrink-0"
      >
        <slot name="action" />
      </div>
    </div>
    <div class="mt-gutter flex min-h-0 flex-1 flex-col">
      <slot />
    </div>
  </section>
</template>
