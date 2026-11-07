<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    tone?: 'warning' | 'info'
  }>(),
  {
    tone: 'warning',
  },
)
</script>

<template>
  <InlineAlert :tone="tone" :title="title">
    <span class="block">
      <slot />
    </span>
    <span v-if="$slots.action" class="mt-stack block">
      <slot name="action" />
    </span>
  </InlineAlert>
</template>
