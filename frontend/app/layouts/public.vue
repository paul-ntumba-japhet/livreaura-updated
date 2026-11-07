<script setup lang="ts">
const route = useRoute()
const canvas = computed(() => (route.meta.canvas === 'plain' ? 'plain' : 'default'))
const background = computed(() => (route.meta.background === 'white' ? 'white' : 'image'))
</script>

<template>
  <PublicLayout :canvas="canvas" :background="background">
    <template v-if="$slots.banner" #banner>
      <slot name="banner" />
    </template>
    <template v-if="$slots.unavailable" #unavailable>
      <slot name="unavailable" />
    </template>
    <slot />
  </PublicLayout>
</template>
