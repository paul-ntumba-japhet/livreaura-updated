<script setup lang="ts">
import type { BackofficeTask } from '../../../types/identity-access/backoffice'

defineProps<{
  task: BackofficeTask
}>()
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Échéances de service
    </h2>
    <div class="mt-stack flex flex-col gap-2">
      <DeadlineIndicator label="Prise en charge" :iso="task.echeance_prise_a" />
      <DeadlineIndicator label="Première décision ou complément" :iso="task.echeance_reponse_a" />
    </div>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Un dépassement n’approuve pas le dossier.
    </p>
  </section>
</template>
