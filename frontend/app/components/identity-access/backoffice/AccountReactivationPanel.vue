<script setup lang="ts">
const demandeId = defineModel<string>('demandeId', { default: '' })
const motif = defineModel<string>('motif', { default: '' })
const avisSecurite = defineModel<string>('avisSecurite', { default: '' })

const props = withDefaults(
  defineProps<{
    canReactivate?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    canReactivate: false,
    submitting: false,
    isSelf: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canReactivate) {
    return props.disabledReason || 'La réactivation exige une attribution interne distincte, pas seulement le rôle administration.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas réactiver ce compte.'
  }
  return ''
})

const canSubmit = computed(() => !blockedReason.value && demandeId.value.trim().length >= 8 && motif.value.trim().length >= 8)

function firstError(field: string): string | undefined {
  return props.fieldErrors[field]?.[0]
}

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
      Réexamen et réactivation
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      La levée attend un dossier de recours déjà vérifié. Elle n’est jamais automatique à l’échéance. Les habilitations de vérification révoquées ne sont pas rétablies.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Réactivation indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Dossier vérifié"
        required
        hint="Référence de suivi du recours, après vérification d’identité."
        :error="firstError('demande_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="demandeId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Motif du réexamen"
        required
        hint="Le motif est conservé avec l’événement. Il documente la décision humaine."
        :error="firstError('motif')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextArea
            :id="id"
            v-model="motif"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Avis de sécurité"
        hint="Requis si la suspension était une mesure de sécurité. L’avis est journalisé dans la demande, pas envoyé au titulaire."
        :error="firstError('avis_securite')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextArea
            :id="id"
            v-model="avisSecurite"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormActions align="start">
        <UiButton
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (demandeId.trim().length < 8 ? 'Indiquez la référence du dossier vérifié.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :loading="submitting"
          @click="submit"
        >
          Réactiver ce compte
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
