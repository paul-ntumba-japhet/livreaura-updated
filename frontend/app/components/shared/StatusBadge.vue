<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

withDefaults(
  defineProps<{
    label: string
    tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
    plain?: boolean
    dot?: boolean
    icon?: UiIconName
  }>(),
  {
    tone: 'neutral',
    plain: false,
    dot: false,
  },
)
</script>

<template>
  <span
    class="inline-flex items-center font-sans"
    :class="(dot || icon)
      ? ['gap-2 rounded-full px-2.5 py-0.5 text-sm font-medium', {
        'bg-surface-muted text-text-muted': tone === 'neutral',
        'bg-surface-success text-success': tone === 'success',
        'bg-surface-warning text-warning': tone === 'warning',
        'bg-surface-danger text-danger': tone === 'danger',
        'bg-surface-info text-info': tone === 'info',
      }]
      : ['px-2.5 py-0.5 text-xs', plain ? 'rounded-sm' : 'rounded-full border', {
        'bg-surface-muted text-text-muted': tone === 'neutral',
        'border-border': !plain && tone === 'neutral',
        'bg-surface-success text-success': tone === 'success',
        'border-border-success': !plain && tone === 'success',
        'bg-surface-warning text-warning': tone === 'warning',
        'border-border-warning': !plain && tone === 'warning',
        'bg-surface-danger text-danger': tone === 'danger',
        'border-border-danger': !plain && tone === 'danger',
        'bg-surface-info text-info': tone === 'info',
        'border-border-info': !plain && tone === 'info',
      }]"
  >
    <UiIcon
      v-if="icon"
      :name="icon"
      size="sm"
    />
    <span
      v-else-if="dot"
      class="size-2 shrink-0 rounded-full"
      :class="{
        'bg-text-muted': tone === 'neutral',
        'bg-success': tone === 'success',
        'bg-warning': tone === 'warning',
        'bg-danger': tone === 'danger',
        'bg-info': tone === 'info',
      }"
      aria-hidden="true"
    />
    {{ label }}
  </span>
</template>
