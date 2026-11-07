<script setup lang="ts">
import { uiIcons, type UiIconName } from './icons'

const props = withDefaults(
  defineProps<{
    name: UiIconName
    label?: string
    size?: 'sm' | 'md' | 'lg'
    strokeWidth?: number
  }>(),
  {
    size: 'sm',
    strokeWidth: 1.75,
  },
)

const isDecorative = computed(() => !props.label)
const icon = computed(() => uiIcons[props.name])
</script>

<template>
  <component
    :is="icon"
    class="shrink-0"
    :class="{
      'size-4': size === 'sm',
      'size-6': size === 'md',
      'size-8': size === 'lg',
    }"
    :stroke-width="strokeWidth"
    :aria-hidden="isDecorative ? true : undefined"
    :aria-label="label"
    :role="label ? 'img' : undefined"
  />
</template>
