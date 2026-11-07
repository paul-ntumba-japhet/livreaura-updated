<script setup lang="ts">
const demandeId = defineModel<string>('demandeId', { default: '' })
const compteId = defineModel<string>('compteId', { default: '' })
const motif = defineModel<string>('motif', { default: '' })
const methodeVerification = defineModel<string>('methodeVerification', { default: 'pieces' })

const props = withDefaults(
  defineProps<{
    canVerify?: boolean
    submitting?: boolean
    isSelf?: boolean
    compteLocked?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    canVerify: false,
    submitting: false,
    isSelf: false,
    compteLocked: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canVerify) {
    return props.disabledReason || 'La vérification d’identité d’une clôture exige l’attribution interne traiter_support.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas vérifier cette demande.'
  }
  return ''
})

const canSubmit = computed(() => {
  return !blockedReason.value
    && demandeId.value.trim().length >= 8
    && compteId.value.trim().length === 26
    && motif.value.trim().length >= 8
    && methodeVerification.value.trim().length >= 4
})

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
      Vérifier l’identité de la clôture
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Une adresse déclarée n’est pas une preuve. Cette vérification ne ferme pas le compte et n’ouvre pas de session de recours.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Vérification indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Référence de la demande"
        required
        hint="Référence de suivi remise au déposant, pas une recherche par nom."
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
        label="Compte concerné"
        required
        hint="Identifiant du compte dont l’identité a été vérifiée sur pièces."
        :error="firstError('compte_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="compteId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting || compteLocked"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Méthode de vérification"
        required
        hint="Consignée avec la demande. Exemples : pieces, echange, entretien."
        :error="firstError('methode_verification')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="methodeVerification"
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
        hint="Le motif documente la vérification. Il n’est pas un mot de passe."
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
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (demandeId.trim().length < 8 ? 'Indiquez la référence de la demande.' : (compteId.trim().length !== 26 ? 'Indiquez l’identifiant du compte.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined)))"
          :loading="submitting"
          @click="submit"
        >
          Consigner la vérification d’identité
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
