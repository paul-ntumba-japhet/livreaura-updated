<script setup lang="ts">
withDefaults(
  defineProps<{
    align?: 'start' | 'end' | 'stretch'
  }>(),
  {
    align: 'end',
  },
)
</script>

<template>
  <div
    class="flex flex-wrap items-start gap-gutter"
    :class="{
      'justify-end': align === 'end',
      'justify-start': align === 'start',
      'w-full flex-col items-stretch': align === 'stretch',
    }"
  >
    <slot />
  </div>
</template>
