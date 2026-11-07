<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

withDefaults(
  defineProps<{
    title: string
    message: string
    plain?: boolean
    split?: boolean
    icon?: UiIconName
  }>(),
  {
    plain: false,
    split: false,
    icon: 'empty',
  },
)
</script>

<template>
  <div
    :class="split
      ? ''
      : (plain
        ? 'flex flex-col items-center px-gutter py-stack text-center'
        : 'rounded-md border border-border bg-surface-raised px-gutter py-section')"
  >
    <div
      class="flex gap-gutter"
      :class="split
        ? 'flex-col sm:flex-row sm:items-center sm:justify-between'
        : (plain ? 'flex-col items-center' : 'items-start')"
    >
      <div
        class="flex gap-gutter"
        :class="plain && !split ? 'flex-col items-center' : 'items-start'"
      >
        <span
          class="inline-flex shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-muted"
          :class="plain && !split ? 'size-16' : 'size-11'"
          aria-hidden="true"
        >
          <UiIcon :name="icon" size="md" />
        </span>
        <div class="min-w-0">
          <component
            :is="split ? 'p' : 'h2'"
            :class="plain || split
              ? 'font-sans text-sm font-semibold text-text'
              : 'font-serif text-xl font-semibold text-text'"
          >
            {{ title }}
          </component>
          <p
            class="font-sans text-sm text-text-muted"
            :class="plain && !split ? 'mt-1 max-w-sm' : 'mt-1 max-w-xl'"
          >
            {{ message }}
          </p>
          <div v-if="$slots.action && !split" class="mt-stack">
            <slot name="action" />
          </div>
        </div>
      </div>
      <div v-if="$slots.action && split" class="shrink-0 sm:self-center">
        <slot name="action" />
      </div>
    </div>
  </div>
</template>
