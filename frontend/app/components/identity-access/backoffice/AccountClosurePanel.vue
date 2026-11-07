<script setup lang="ts">
const demandeId = defineModel<string>('demandeId', { default: '' })
const motif = defineModel<string>('motif', { default: '' })

const props = withDefaults(
  defineProps<{
    canClose?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    canClose: false,
    submitting: false,
    isSelf: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canClose) {
    return props.disabledReason || 'La clôture exige une attribution interne distincte, pas seulement le rôle administration.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas clôturer ce compte.'
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
      Clôture du compte
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      La clôture est définitive. Elle exige un dossier vérifié et le transfert préalable d’une représentation principale. Aucune suppression physique ni remboursement n’est déclenché ici.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Clôture indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Dossier vérifié"
        required
        hint="Référence de suivi de la demande de clôture, après vérification d’identité."
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
        label="Motif"
        required
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
      <FormActions align="start">
        <UiButton
          variant="destructive"
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (demandeId.trim().length < 8 ? 'Indiquez la référence du dossier vérifié.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :loading="submitting"
          @click="submit"
        >
          Clôturer ce compte
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
