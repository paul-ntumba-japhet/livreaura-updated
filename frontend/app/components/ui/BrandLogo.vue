<script setup lang="ts">
withDefaults(
  defineProps<{
    alt?: string
    size?: 'md' | 'lg' | 'xl'
  }>(),
  {
    alt: 'Livreaura',
    size: 'md',
  },
)
</script>

<template>
  <img
    src="/brand/logo-principal.png"
    :alt="alt"
    class="brand-logo w-auto"
    :class="{
      'h-10': size === 'md',
      'h-16': size === 'lg',
      'h-20': size === 'xl',
    }"
    width="160"
    height="60"
    decoding="async"
  >
</template>
