<script setup lang="ts">
withDefaults(
  defineProps<{
    canRetry?: boolean
    submitting?: boolean
    disabledReason?: string
  }>(),
  {
    canRetry: false,
    submitting: false,
  },
)

const emit = defineEmits<{
  retry: []
}>()
</script>

<template>
  <UiButton
    variant="secondary"
    :loading="submitting"
    :disabled="!canRetry || submitting"
    :disabled-reason="!canRetry ? (disabledReason || 'La reprise exige reprendre_transmissions. Elle ne rejoue pas une décision.') : undefined"
    @click="emit('retry')"
  >
    Reprendre l’envoi
  </UiButton>
</template>
