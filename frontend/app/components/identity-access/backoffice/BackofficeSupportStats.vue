<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  ouvertes?: number
  nonAffectes?: number
  prioriteHaute?: number
  reponsesEnRetard?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Demandes ouvertes"
      icon="message-circle"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="ouvertes"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Demandes encore en cours'"
    />
    <MetricCard
      title="Non affectées"
      icon="users"
      :loading="loading"
      :unavailable="unavailable"
      :value="nonAffectes"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Affectation nécessaire'"
    />
    <MetricCard
      title="Priorité haute"
      icon="warning"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="prioriteHaute"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Priorité urgente'"
    />
    <MetricCard
      title="Réponses en retard"
      icon="clock"
      icon-tone="danger"
      :loading="loading"
      :unavailable="unavailable"
      :value="reponsesEnRetard"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Échéance de réponse dépassée'"
    />
  </div>
</template>
