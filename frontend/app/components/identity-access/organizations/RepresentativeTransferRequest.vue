<script setup lang="ts">
const nouveauCompteId = defineModel<string>('nouveauCompteId', { default: '' })
const motif = defineModel<string>('motif', { default: '' })
const depart = defineModel<boolean>('depart', { default: false })

const props = withDefaults(
  defineProps<{
    canTransfer?: boolean
    submitting?: boolean
    disabledReason?: string
  }>(),
  {
    canTransfer: false,
    submitting: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canTransfer) {
    return props.disabledReason || 'Le transfert exige une attribution interne distincte, pas seulement le rôle administration.'
  }
  return ''
})

const canSubmit = computed(() => {
  return !blockedReason.value
    && nouveauCompteId.value.trim().length === 26
    && motif.value.trim().length >= 8
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
      Demande de transfert
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Vérifiez l’identité et le mandat du nouveau titulaire actif avant d’enregistrer. Le transfert n’est pas une invitation.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Transfert indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Nouveau représentant"
        required
        hint="Identifiant du compte actif qui devient principal. Une correspondance de nom ne suffit pas."
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="nouveauCompteId"
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
        hint="Le motif est conservé avec l’événement. Il documente la vérification du mandat."
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
      <label class="flex items-start gap-gutter font-sans text-sm text-text">
        <Checkbox
          v-model="depart"
          :disabled="Boolean(blockedReason) || submitting"
        />
        <span>
          Révoquer l’adhésion de l’ancien principal
          <span class="mt-1 block text-text-muted">Si cette case n’est pas cochée, l’ancien titulaire reste membre, sans être principal.</span>
        </span>
      </label>
      <FormActions align="start">
        <UiButton
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (nouveauCompteId.trim().length !== 26 ? 'Indiquez l’identifiant du nouveau compte.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :loading="submitting"
          @click="submit"
        >
          Enregistrer le transfert
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
