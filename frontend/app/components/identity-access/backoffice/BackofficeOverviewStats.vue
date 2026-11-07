<script setup lang="ts">
defineProps<{
  kycLoading: boolean
  kycRestricted: boolean
  kycError: boolean
  kycCount: number
  unassignedCount: number
  overdueCount: number
  urgentCount: number
  transmissionsLoading: boolean
  transmissionsRestricted: boolean
  transmissionsError: boolean
  transmissionsCount: number
}>()

function countHint(count: number, singular: string, plural: string): string {
  return `${count} ${count === 1 ? singular : plural}`
}
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Dossiers KYC à traiter"
      icon="file-check"
      :loading="kycLoading"
      :unavailable="kycRestricted || kycError"
      :value="kycCount"
      :hint="kycRestricted
        ? 'Attribution interne d’affectation ou de contrôle requise.'
        : (kycError
          ? 'La file n’a pas pu être lue.'
          : countHint(unassignedCount, 'non affecté', 'non affectés'))"
    />
    <MetricCard
      title="Tâches en retard"
      icon="clock"
      :loading="kycLoading"
      :unavailable="kycRestricted || kycError"
      :value="overdueCount"
      :hint="kycRestricted
        ? 'Attribution interne d’affectation ou de contrôle requise.'
        : (kycError
          ? 'La file n’a pas pu être lue.'
          : (overdueCount === 0 ? 'Aucune échéance dépassée' : 'À prendre en charge'))"
    />
    <MetricCard
      title="Tâches urgentes"
      icon="warning"
      :loading="kycLoading"
      :unavailable="kycRestricted || kycError"
      :value="urgentCount"
      :hint="kycRestricted
        ? 'Attribution interne d’affectation ou de contrôle requise.'
        : (kycError
          ? 'La file n’a pas pu être lue.'
          : countHint(urgentCount, 'priorité urgente', 'priorités urgentes'))"
    />
    <MetricCard
      title="Transmissions à reprendre"
      icon="mail"
      :loading="transmissionsLoading"
      :unavailable="transmissionsRestricted || transmissionsError"
      :value="transmissionsCount"
      :hint="transmissionsRestricted
        ? 'Attribution de reprise des envois requise.'
        : (transmissionsError
          ? 'Les transmissions n’ont pas pu être lues.'
          : (transmissionsCount === 0 ? 'Aucune reprise ouverte' : 'Échec ou attente de nouvelle tentative'))"
    />
  </div>
</template>
