<script setup lang="ts">
defineProps<{
  title?: string
  message?: string
}>()
</script>

<template>
  <div class="rounded-md border border-border bg-surface-raised px-gutter py-section">
    <div class="flex items-start gap-gutter">
      <UiIcon name="lock" class="mt-1 text-text-muted" />
      <div class="min-w-0">
        <h2 class="font-serif text-xl font-semibold text-text">
          {{ title || 'Accès refusé' }}
        </h2>
        <p class="mt-2 max-w-xl font-sans text-sm text-text-muted">
          {{ message || 'Vous n’avez pas accès à cet espace.' }}
        </p>
        <div v-if="$slots.action" class="mt-stack">
          <slot name="action" />
        </div>
      </div>
    </div>
  </div>
</template>
