<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  aTraiter?: number
  nonAffectes?: number
  enRetard?: number
  aCorriger?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Dossiers à traiter"
      icon="file-text"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="aTraiter"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Soumis et en examen'"
    />
    <MetricCard
      title="Non affectés"
      icon="users"
      :loading="loading"
      :unavailable="unavailable"
      :value="nonAffectes"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Affectation nécessaire'"
    />
    <MetricCard
      title="En retard"
      icon="clock"
      icon-tone="danger"
      :loading="loading"
      :unavailable="unavailable"
      :value="enRetard"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Échéance dépassée'"
    />
    <MetricCard
      title="À corriger"
      icon="warning"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="aCorriger"
      :hint="unavailable ? 'La lecture de la file a échoué.' : 'Retour attendu du demandeur'"
    />
  </div>
</template>
