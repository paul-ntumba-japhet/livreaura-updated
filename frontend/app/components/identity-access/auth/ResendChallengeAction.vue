<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    loading?: boolean
    disabled?: boolean
    waitSeconds?: number
  }>(),
  {
    loading: false,
    disabled: false,
    waitSeconds: 0,
  },
)

const emit = defineEmits<{
  resend: []
}>()

const waiting = computed(() => (props.waitSeconds ?? 0) > 0)

const disabledReason = computed(() => {
  if (waiting.value) {
    const seconds = props.waitSeconds ?? 0
    return `Nouvel envoi possible dans ${seconds} s.`
  }
  return undefined
})
</script>

<template>
  <UiButton
    type="button"
    variant="secondary"
    block
    class="verification-resend"
    :loading="loading"
    :disabled="disabled || waiting || loading"
    :disabled-reason="disabledReason"
    @click="emit('resend')"
  >
    <UiIcon name="refresh" />
    Renvoyer l’e-mail de vérification
  </UiButton>
</template>
