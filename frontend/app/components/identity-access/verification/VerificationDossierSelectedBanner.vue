<script setup lang="ts">
defineProps<{
  subjectLabel: string
  hint: string
}>()

const emit = defineEmits<{
  change: []
}>()
</script>

<template>
  <div class="flex flex-col gap-stack rounded-md border border-border-info bg-surface-info px-gutter py-stack sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
      <p class="font-sans text-sm font-semibold text-text">
        Dossier sélectionné : {{ subjectLabel }}
      </p>
      <p class="mt-1 font-sans text-sm text-text-muted">
        {{ hint }}
      </p>
    </div>
    <UiButton variant="secondary" @click="emit('change')">
      Changer de dossier
    </UiButton>
  </div>
</template>
