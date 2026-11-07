<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  valides?: number
  expirent30j?: number
  restreintes?: number
  expirees?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Valides"
      icon="building-2"
      icon-tone="success"
      :loading="loading"
      :unavailable="unavailable"
      :value="valides"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : 'Organisation et commerce'"
    />
    <MetricCard
      title="Expirent sous 30 jours"
      icon="calendar"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="expirent30j"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : 'Parmi les habilitations valides'"
    />
    <MetricCard
      title="Restreintes"
      icon="warning"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="restreintes"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Expirées"
      icon="close"
      icon-tone="danger"
      :loading="loading"
      :unavailable="unavailable"
      :value="expirees"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
  </div>
</template>
