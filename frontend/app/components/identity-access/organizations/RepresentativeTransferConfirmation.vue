<script setup lang="ts">
withDefaults(
  defineProps<{
    open: boolean
    submitting?: boolean
    depart?: boolean
  }>(),
  {
    submitting: false,
    depart: false,
  },
)

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <ConfirmDialog
    :open="open"
    title="Confirmer le transfert"
    confirm-label="Enregistrer le transfert"
    :submitting="submitting"
    :destructive="depart"
    @confirm="emit('confirm')"
    @cancel="emit('cancel')"
  >
    <p>
      Le principal actuel sera retiré puis le nouveau titulaire installé dans la même opération.
      Il n’y aura jamais deux principaux actifs. Cette action attend la confirmation du serveur.
    </p>
    <p v-if="depart" class="mt-stack">
      L’adhésion de l’ancien principal sera révoquée.
    </p>
    <p v-else class="mt-stack">
      L’ancien principal restera membre, sans les permissions de représentant.
    </p>
  </ConfirmDialog>
</template>
