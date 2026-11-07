<script setup lang="ts">
const email = defineModel<string>('email', { default: '' })
const message = defineModel<string>('message', { default: '' })

const props = withDefaults(
  defineProps<{
    submitting?: boolean
    fieldErrors?: Record<string, string[]>
    readonly?: boolean
    disabledReason?: string
    block?: boolean
  }>(),
  {
    submitting: false,
    fieldErrors: () => ({}),
    readonly: false,
    block: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (props.readonly) {
    return props.disabledReason || 'Cette demande de clôture n’accepte plus de nouveau dépôt.'
  }
  return props.disabledReason || ''
})

function submit() {
  emit('submit')
}
</script>

<template>
  <section class="grid gap-stack">
    <InlineAlert
      v-if="blockedReason"
      tone="warning"
      title="Dépôt indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <PublicSupportForm
      v-model:email="email"
      v-model:message="message"
      :submitting="submitting || Boolean(blockedReason)"
      :field-errors="fieldErrors"
      submit-label="Déposer la demande de clôture"
      email-hint="Utilisez l’adresse du compte à fermer si vous la connaissez. L’accusé ne confirme pas l’existence d’un compte."
      message-hint="Indiquez le motif de la demande sans mot de passe ni code de vérification. Ce dépôt ne ferme pas le compte."
      :block="block"
      @submit="submit"
    />
  </section>
</template>
