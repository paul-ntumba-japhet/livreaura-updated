<script setup lang="ts">
const compteId = defineModel<string>('compteId', { default: '' })
const motif = defineModel<string>('motif', { default: '' })
const methodeVerification = defineModel<string>('methodeVerification', { default: 'pieces' })

const props = withDefaults(
  defineProps<{
    canAuthorize?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
  }>(),
  {
    canAuthorize: false,
    submitting: false,
    isSelf: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canAuthorize) {
    return props.disabledReason || 'L’autorisation d’une session de recours exige les attributions traiter_support et autoriser_recours.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas autoriser ce recours.'
  }
  return ''
})

const canSubmit = computed(() => {
  return !blockedReason.value
    && compteId.value.trim().length === 26
    && motif.value.trim().length >= 8
    && methodeVerification.value.trim().length >= 4
})

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
      Autoriser une session de recours
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Vérifiez l’identité du titulaire suspendu avant d’autoriser. Aucun jeton n’est envoyé par e-mail. Le titulaire ouvre ensuite une session limitée par une nouvelle connexion.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Autorisation indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Compte titulaire"
        required
        hint="Identifiant du compte suspendu dont l’identité a été vérifiée. Une adresse e-mail déclarée n’est pas une preuve."
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="compteId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Méthode de vérification"
        required
        hint="Consignée avec la demande. Exemples : pieces, echange, entretien."
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
        hint="Le motif documente la vérification d’identité. Il n’est pas un mot de passe."
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
          :disabled-reason="blockedReason || (compteId.trim().length !== 26 ? 'Indiquez l’identifiant du compte.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :loading="submitting"
          @click="submit"
        >
          Autoriser la session de recours
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
