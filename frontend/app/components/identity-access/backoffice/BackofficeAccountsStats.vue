<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  total?: number
  active?: number
  pending?: number
  suspended?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Comptes au total"
      icon="users"
      :loading="loading"
      :unavailable="unavailable"
      :value="total"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Actifs"
      icon="user"
      :loading="loading"
      :unavailable="unavailable"
      :value="active"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="En attente"
      icon="clock"
      :loading="loading"
      :unavailable="unavailable"
      :value="pending"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
    <MetricCard
      title="Suspendus"
      icon="error"
      :loading="loading"
      :unavailable="unavailable"
      :value="suspended"
      :hint="unavailable ? 'La lecture du répertoire a échoué.' : undefined"
    />
  </div>
</template>
