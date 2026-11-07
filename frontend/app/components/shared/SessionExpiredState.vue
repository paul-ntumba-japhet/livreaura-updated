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
          {{ title || 'Session expirée' }}
        </h2>
        <p class="mt-2 max-w-xl font-sans text-sm text-text-muted">
          {{ message || 'La session n’est plus valable. Connectez-vous pour continuer.' }}
        </p>
        <p class="mt-stack">
          <slot name="action">
            <UiLink to="/connexion">Se connecter</UiLink>
          </slot>
        </p>
      </div>
    </div>
  </div>
</template>
