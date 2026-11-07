<script setup lang="ts">
const email = defineModel<string>('email', { default: '' })
const message = defineModel<string>('message', { default: '' })

const props = withDefaults(
  defineProps<{
    submitting?: boolean
    fieldErrors?: Record<string, string[]>
    submitLabel?: string
    emailHint?: string
    messageHint?: string
    block?: boolean
  }>(),
  {
    submitting: false,
    fieldErrors: () => ({}),
    submitLabel: 'Déposer le recours',
    emailHint: 'Utilisez l’adresse du compte concerné si vous la connaissez. L’accusé ne confirme pas l’existence d’un compte.',
    messageHint: 'Décrivez la situation sans indiquer de mot de passe ni de code de vérification.',
    block: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const canSubmit = computed(() => email.value.trim().includes('@') && message.value.trim().length >= 8)

function submit() {
  if (!canSubmit.value || props.submitting) {
    return
  }
  emit('submit')
}
</script>

<template>
  <form
    class="grid gap-stack"
    @submit.prevent="submit"
  >
    <FormField
      label="Adresse e-mail"
      required
      :hint="emailHint"
      :error="fieldErrors.email?.[0]"
    >
      <template #default="{ id, describedBy, invalid }">
        <EmailInput
          :id="id"
          v-model="email"
          placeholder="votre.email@exemple.com"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :disabled="submitting"
          autocomplete="email"
        />
      </template>
    </FormField>
    <FormField
      label="Message"
      required
      :hint="messageHint"
      :error="fieldErrors.message?.[0]"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextArea
          :id="id"
          v-model="message"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :disabled="submitting"
        />
      </template>
    </FormField>
    <FormActions :align="block ? 'stretch' : 'start'">
      <UiButton
        type="submit"
        :block="block"
        :disabled="!canSubmit || submitting"
        :disabled-reason="canSubmit ? undefined : 'Indiquez une adresse e-mail et un message d’au moins 8 caractères.'"
        :loading="submitting"
      >
        {{ submitLabel }}
      </UiButton>
    </FormActions>
  </form>
</template>
