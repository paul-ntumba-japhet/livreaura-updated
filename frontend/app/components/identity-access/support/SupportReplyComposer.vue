<script setup lang="ts">
const contenu = defineModel<string>('contenu', { default: '' })
const visibilite = defineModel<string>('visibilite', { default: 'demandeur' })

const props = withDefaults(
  defineProps<{
    canReply?: boolean
    canInternal?: boolean
    submitting?: boolean
    readonly?: boolean
    disabledReason?: string
  }>(),
  {
    canReply: false,
    canInternal: false,
    submitting: false,
    readonly: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (props.readonly) {
    return props.disabledReason || 'Cette demande n’accepte plus de nouveau message.'
  }
  if (!props.canReply) {
    return props.disabledReason || 'L’envoi d’un message n’est pas disponible pour cette session.'
  }
  return ''
})

const visibilityOptions = computed(() => {
  const options = [{ value: 'demandeur', label: 'Visible du demandeur' }]
  if (props.canInternal) {
    options.push({ value: 'interne', label: 'Note interne' })
  }
  return options
})

const canSubmit = computed(() => !blockedReason.value && contenu.value.trim().length >= 8)

function submit() {
  if (!canSubmit.value || props.submitting) {
    return
  }
  emit('submit')
}
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Répondre
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      N’indiquez jamais un mot de passe ou un code de vérification. Une note interne n’est pas visible du demandeur.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Réponse indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        v-if="canInternal && !blockedReason"
        label="Visibilité"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="visibilite"
            :options="visibilityOptions"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Message"
        required
      >
        <template #default="{ id, describedBy, invalid }">
          <TextArea
            :id="id"
            v-model="contenu"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormActions align="start">
        <UiButton
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (contenu.trim().length < 8 ? 'Indiquez un message d’au moins 8 caractères.' : undefined)"
          :loading="submitting"
          @click="submit"
        >
          Envoyer ce message
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
