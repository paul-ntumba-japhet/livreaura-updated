<script setup lang="ts">
const titulaireId = defineModel<string>('titulaireId', { default: '' })
const suppleantId = defineModel<string>('suppleantId', { default: '' })
const fonction = defineModel<string>('fonction', { default: 'kyc' })
const debutAt = defineModel<string>('debutAt', { default: '' })
const finAt = defineModel<string>('finAt', { default: '' })
const motif = defineModel<string>('motif', { default: '' })

const props = withDefaults(
  defineProps<{
    canAssign?: boolean
    submitting?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    canAssign: false,
    submitting: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const fonctionOptions = [
  { value: 'kyc', label: 'Vérification (KYC)' },
  { value: 'support', label: 'Support' },
]

const blockedReason = computed(() => {
  if (!props.canAssign) {
    return props.disabledReason || 'L’enregistrement exige affecter_taches. Aucune permission n’est déléguée au suppléant.'
  }
  return ''
})

const canSubmit = computed(() => !blockedReason.value
  && titulaireId.value.trim().length === 26
  && suppleantId.value.trim().length === 26
  && fonction.value.trim() !== ''
  && debutAt.value.trim() !== ''
  && finAt.value.trim() !== ''
  && motif.value.trim().length >= 8)

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
      Enregistrer une suppléance
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Le suppléant n’obtient aucune permission. S’il n’a pas les droits propres, la tâche reste non attribuée et peut être escaladée.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Enregistrement indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Titulaire"
        required
        hint="Référence du compte titulaire, pas une recherche par nom."
        :error="firstError('titulaire_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="titulaireId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Suppléant"
        required
        :error="firstError('suppleant_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="suppleantId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Fonction"
        required
        :error="firstError('fonction')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="fonction"
            :options="fonctionOptions"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <div class="grid gap-stack sm:grid-cols-2">
        <FormField
          label="Début"
          required
          :error="firstError('debut_a')"
        >
          <template #default="{ id, describedBy, invalid }">
            <DateTimeInput
              :id="id"
              v-model="debutAt"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :disabled="Boolean(blockedReason) || submitting"
            />
          </template>
        </FormField>
        <FormField
          label="Fin"
          required
          :error="firstError('fin_a')"
        >
          <template #default="{ id, describedBy, invalid }">
            <DateTimeInput
              :id="id"
              v-model="finAt"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :disabled="Boolean(blockedReason) || submitting"
            />
          </template>
        </FormField>
      </div>
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
          :disabled-reason="blockedReason || (!canSubmit ? 'Complétez le titulaire, le suppléant, la période et un motif d’au moins 8 caractères.' : undefined)"
          :loading="submitting"
          @click="submit"
        >
          Enregistrer cette suppléance
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
