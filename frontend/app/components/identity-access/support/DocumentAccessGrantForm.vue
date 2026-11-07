<script setup lang="ts">
const documentType = defineModel<string>('documentType', { default: 'facture_vente' })
const documentId = defineModel<string>('documentId', { default: '' })
const compteId = defineModel<string>('compteId', { default: '' })
const debut = defineModel<string>('debut', { default: '' })
const fin = defineModel<string>('fin', { default: '' })
const motif = defineModel<string>('motif', { default: '' })

const props = withDefaults(
  defineProps<{
    canAuthorize?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    canAuthorize: false,
    submitting: false,
    isSelf: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const documentOptions = [
  { value: 'facture_vente', label: 'Facture de vente' },
  { value: 'facture_commission', label: 'Facture de commission' },
  { value: 'avoir', label: 'Avoir' },
  { value: 'recu_paiement', label: 'Reçu de paiement' },
]

const blockedReason = computed(() => {
  if (!props.canAuthorize) {
    return props.disabledReason || 'L’autorisation d’un document exige les attributions traiter_support et autoriser_recours.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas autoriser ce document.'
  }
  return ''
})

const canSubmit = computed(() => !blockedReason.value && compteId.value.trim().length === 26 && documentId.value.trim().length === 26 && motif.value.trim().length >= 8 && debut.value.trim() !== '' && fin.value.trim() !== '')

function firstError(field: string): string | undefined {
  return props.fieldErrors[field]?.[0] || props.fieldErrors.document?.[0]
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
      Autoriser un document
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Le grant pointe un document commercial exact. Le téléchargement n’est pas proposé ici : il reste contrôlé à chaque usage. Aucune liste globale de documents n’est exposée.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Grant indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Compte"
        required
        hint="Compte titulaire déjà lié à la demande vérifiée."
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
        label="Type de document"
        required
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="documentType"
            :options="documentOptions"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Référence du document"
        required
        hint="Identifiant exact du document A3, pas une recherche par nom."
        :error="firstError('facture_vente_id') || firstError('facture_commission_id') || firstError('avoir_id') || firstError('recu_paiement_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="documentId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Début"
        required
        :error="firstError('debut')"
      >
        <template #default="{ id, describedBy, invalid }">
          <DateTimeInput
            :id="id"
            v-model="debut"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Fin"
        required
        hint="La fin doit être postérieure au début. L’accès est refusé après expiration."
        :error="firstError('fin')"
      >
        <template #default="{ id, describedBy, invalid }">
          <DateTimeInput
            :id="id"
            v-model="fin"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
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
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (documentId.trim().length !== 26 ? 'Indiquez la référence exacte du document.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :loading="submitting"
          @click="submit"
        >
          Autoriser ce document
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
