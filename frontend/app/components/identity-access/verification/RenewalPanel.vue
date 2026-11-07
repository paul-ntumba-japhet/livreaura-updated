<script setup lang="ts">
import type { VerificationCapabilitySummary } from '../../../types/identity-access/verification'
import { normalizeCapabilityState } from '../../../types/identity-access/backoffice'
import { labelForCapabilityState, toneForCapabilityState, deadlineKind } from '../../../utils/identity-access/backoffice'
import { labelForVerificationLevel } from '../../../utils/identity-access/verification'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'

const props = defineProps<{
  capability?: VerificationCapabilitySummary | null
  canOpenVersion?: boolean
  opening?: boolean
}>()

const emit = defineEmits<{
  renew: []
}>()

const state = computed(() => normalizeCapabilityState(props.capability?.etat))
const overdue = computed(() => deadlineKind(props.capability?.valide_jusqua) === 'overdue')
const soon = computed(() => deadlineKind(props.capability?.valide_jusqua) === 'soon')

const title = computed(() => {
  if (!props.capability) {
    return 'Aucune habilitation'
  }
  if (state.value === 'expired' || overdue.value) {
    return 'Renouvellement nécessaire'
  }
  if (state.value === 'restreint' || state.value === 'revoked') {
    return 'Habilitation bloquée'
  }
  if (state.value === 'a_renouveler' || soon.value) {
    return 'Renouvellement à anticiper'
  }
  return 'Habilitation en cours'
})

const message = computed(() => {
  if (!props.capability) {
    return 'Aucune habilitation n’est créée tant que le dossier n’est pas approuvé.'
  }
  if (state.value === 'revoked') {
    return 'Cette habilitation est révoquée. Une nouvelle version approuvée est exigée. L’ancienne autorisation n’est pas prolongée.'
  }
  if (state.value === 'expired' || overdue.value) {
    return 'L’échéance est dépassée. Les accès courants sont refusés, même si un traitement planifié n’a pas encore abouti. Ouvrez une nouvelle version.'
  }
  if (state.value === 'restreint') {
    return props.capability.motif_restriction || 'Cette habilitation est restreinte. Une nouvelle version est exigée pour rétablir l’accès.'
  }
  if (state.value === 'a_renouveler' || soon.value) {
    return 'Un rappel n’enlève pas la validité actuelle. Préparez une nouvelle version avant l’échéance.'
  }
  return 'L’habilitation reste valable jusqu’à l’échéance indiquée, s’il y en a une.'
})

const showRenew = computed(() => {
  if (!props.canOpenVersion || !props.capability) {
    return false
  }
  if (state.value !== 'valid') {
    return true
  }
  return soon.value || overdue.value
})
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      {{ title }}
    </h2>
    <div v-if="capability" class="mt-stack flex flex-wrap items-center gap-gutter">
      <p class="font-sans text-sm text-text">
        {{ labelForVerificationLevel(capability.niveau) }}
      </p>
      <StatusBadge
        :label="labelForCapabilityState(capability.etat)"
        :tone="toneForCapabilityState(capability.etat)"
      />
    </div>
    <p class="mt-stack font-sans text-sm text-text-muted">
      {{ message }}
    </p>
    <p v-if="capability?.valide_jusqua" class="mt-stack font-sans text-sm text-text-muted">
      Échéance : {{ formatActivationExpiry(capability.valide_jusqua) }}
    </p>
    <FormActions v-if="showRenew" class="mt-stack" align="start">
      <UiButton
        :loading="opening"
        :disabled="opening"
        @click="emit('renew')"
      >
        Ouvrir une nouvelle version
      </UiButton>
    </FormActions>
  </section>
</template>
