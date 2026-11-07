<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  organisations?: number
  verifiees?: number
  rattachements?: number
  transferts?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Organisations"
      icon="building-2"
      :loading="loading"
      :unavailable="unavailable"
      :value="organisations"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Organisations vérifiées"
      icon="success"
      :loading="loading"
      :unavailable="unavailable"
      :value="verifiees"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Demandes de rattachement"
      icon="inbox"
      :loading="loading"
      :unavailable="unavailable"
      :value="rattachements"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Transferts en attente"
      icon="refresh"
      :loading="loading"
      :unavailable="unavailable"
      :value="transferts"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
  </div>
</template>
