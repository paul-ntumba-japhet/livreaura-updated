<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    canAssign: boolean
    assignedToSelf: boolean
    assigning?: boolean
    disabledReason?: string
  }>(),
  {
    assigning: false,
  },
)

const emit = defineEmits<{
  assign: []
}>()

const explanation = computed(() => {
  if (props.assignedToSelf) {
    return 'Vous êtes le responsable courant de cette tâche.'
  }
  if (!props.canAssign) {
    return props.disabledReason || 'L’affectation n’est pas disponible pour ce compte.'
  }
  return 'Aucun autre responsable éligible n’est proposé ici. Vous pouvez vous affecter cette tâche.'
})
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Affectation
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      {{ explanation }}
    </p>
    <FormActions v-if="canAssign && !assignedToSelf" class="mt-stack" align="start">
      <UiButton
        variant="secondary"
        :loading="assigning"
        :disabled="assigning"
        @click="emit('assign')"
      >
        M’affecter cette tâche
      </UiButton>
    </FormActions>
  </section>
</template>
