<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  agentsActifs?: number
  attributionsActives?: number
  suppleancesPlanifiees?: number
  configurationsAApprouver?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Agents actifs"
      icon="users"
      :loading="loading"
      :unavailable="unavailable"
      :value="agentsActifs"
      :hint="unavailable ? 'La lecture de l’équipe a échoué.' : 'Comptes actifs avec permission courante'"
    />
    <MetricCard
      title="Attributions de permissions actives"
      icon="key"
      icon-tone="info"
      :loading="loading"
      :unavailable="unavailable"
      :value="attributionsActives"
      :hint="unavailable ? 'La lecture de l’équipe a échoué.' : 'Périodes encore en vigueur'"
    />
    <MetricCard
      title="Suppléances planifiées"
      icon="calendar"
      :loading="loading"
      :unavailable="unavailable"
      :value="suppleancesPlanifiees"
      :hint="unavailable ? 'La lecture de l’équipe a échoué.' : 'Périodes à venir'"
    />
    <MetricCard
      title="Configuration à approuver"
      icon="file-text"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="configurationsAApprouver"
      :hint="unavailable ? 'La lecture de l’équipe a échoué.' : 'Dernière version pas encore en vigueur'"
    />
  </div>
</template>
