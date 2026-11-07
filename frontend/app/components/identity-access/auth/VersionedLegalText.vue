<script setup lang="ts">
defineProps<{
  title: string
  titleId?: string
  version?: string
  content?: string | null
}>()
</script>

<template>
  <article class="flex flex-col gap-stack">
    <header>
      <h2 :id="titleId" class="font-serif text-xl font-semibold text-text">
        {{ title }}
      </h2>
      <p v-if="version" class="mt-1 font-sans text-sm text-text-muted">
        Version {{ version }}
      </p>
    </header>
    <div
      v-if="content"
      class="whitespace-pre-wrap font-sans text-sm text-text"
    >
      {{ content }}
    </div>
    <p v-else class="font-sans text-sm text-text-muted">
      Ce texte n’est pas disponible pour le moment.
    </p>
  </article>
</template>
