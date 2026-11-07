<script setup lang="ts">
defineProps<{
  loading?: boolean
  unavailable?: boolean
  echecs?: number
  attenteAccuse?: number
  messagesEnAttente?: number
  evenementsAujourdhui?: number
}>()
</script>

<template>
  <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-4">
    <MetricCard
      title="Transmissions en échec"
      icon="circle-x"
      icon-tone="danger"
      :loading="loading"
      :unavailable="unavailable"
      :value="echecs"
      :hint="unavailable ? 'La lecture de la supervision a échoué.' : 'État failed, reprise possible'"
    />
    <MetricCard
      title="En attente d’accusé"
      icon="success"
      icon-tone="warning"
      :loading="loading"
      :unavailable="unavailable"
      :value="attenteAccuse"
      :hint="unavailable ? 'La lecture de la supervision a échoué.' : 'Acceptées par le canal, pas encore acquittées'"
    />
    <MetricCard
      title="Messages en attente"
      icon="send"
      :loading="loading"
      :unavailable="unavailable"
      :value="messagesEnAttente"
      :hint="unavailable ? 'La lecture de la supervision a échoué.' : 'File, envoi ou nouvelle tentative'"
    />
    <MetricCard
      title="Événements aujourd’hui"
      icon="calendar"
      icon-tone="success"
      :loading="loading"
      :unavailable="unavailable"
      :value="evenementsAujourdhui"
      :hint="unavailable ? 'La lecture de la supervision a échoué.' : 'Interventions compte et vérification'"
    />
  </div>
</template>
