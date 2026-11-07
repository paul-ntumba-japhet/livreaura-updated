<script setup lang="ts">
defineProps<{
  visible: boolean
}>()
</script>

<template>
  <RestrictionBanner
    v-if="visible"
    title="Conflit d’intérêt"
  >
    Ce dossier est lié à ce compte. Les contrôles et la décision sont bloqués. Une réaffectation indépendante est nécessaire.
  </RestrictionBanner>
</template>
