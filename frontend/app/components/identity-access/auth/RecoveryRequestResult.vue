<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<template>
  <InlineAlert tone="success" :title="title ?? 'Demande enregistrée'">
    <slot>
      Si un compte est associé à cette adresse, un message a été préparé.
      Cela ne confirme ni l’existence du compte ni la livraison du message.
    </slot>
  </InlineAlert>
</template>
