<script setup lang="ts">
import type { BackofficeCapability } from '../../../types/identity-access/backoffice'
import { normalizeCapabilityState } from '../../../types/identity-access/backoffice'

const props = withDefaults(
  defineProps<{
    capability?: BackofficeCapability | null
    canRestrict?: boolean
    submitting?: boolean
    disabledReason?: string
  }>(),
  {
    canRestrict: false,
    submitting: false,
  },
)

const emit = defineEmits<{
  restrict: [payload: { etat: 'a_renouveler' | 'restreint' | 'revoked', motif: string }]
}>()

const etat = ref('')
const motif = ref('')
const confirmOpen = ref(false)

const blockedReason = computed(() => {
  const state = normalizeCapabilityState(props.capability?.etat)
  if (!props.capability) {
    return 'Aucune habilitation n’est encore créée.'
  }
  if (state === 'expired' || state === 'revoked') {
    return 'Cette habilitation n’est plus modifiable. Le rétablissement exige une nouvelle version approuvée.'
  }
  if (!props.canRestrict) {
    return props.disabledReason || 'La restriction exige une attribution interne distincte.'
  }
  return ''
})

const canOpen = computed(() => !blockedReason.value && (etat.value === 'a_renouveler' || etat.value === 'restreint' || etat.value === 'revoked') && motif.value.trim().length >= 8)

function openConfirm() {
  if (!canOpen.value) {
    return
  }
  confirmOpen.value = true
}

function confirm() {
  if (etat.value !== 'a_renouveler' && etat.value !== 'restreint' && etat.value !== 'revoked') {
    return
  }
  emit('restrict', {
    etat: etat.value,
    motif: motif.value.trim(),
  })
}

watch(() => props.submitting, (submitting) => {
  if (!submitting) {
    confirmOpen.value = false
  }
})
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-xl font-semibold text-text">
      Restriction d’habilitation
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Une restriction motivée est enregistrée immédiatement. Elle ne rétablit jamais un accès. Le titulaire soumet une nouvelle version.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Restriction indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <RadioGroup
        v-model="etat"
        legend="Nouvel état"
        name="restriction-habilitation"
        :disabled="Boolean(blockedReason) || submitting"
      >
        <ChoiceCard
          value="a_renouveler"
          title="Marquer à renouveler"
          description="Le titulaire est invité à préparer une nouvelle version. Un rappel seul ne retire pas la validité tant que l’échéance n’est pas dépassée."
        />
        <ChoiceCard
          value="restreint"
          title="Restreindre"
          description="Les accès courants sont refusés. Le rétablissement exige une nouvelle version approuvée."
        />
        <ChoiceCard
          value="revoked"
          title="Révoquer"
          description="Clôture l’habilitation. L’ancienne autorisation n’est pas prolongée."
        />
      </RadioGroup>
      <FormField label="Motif" required hint="Le motif est conservé avec l’événement.">
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
          :disabled="!canOpen || submitting"
          :disabled-reason="blockedReason || (!etat ? 'Choisissez un état.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
          :variant="etat === 'revoked' ? 'destructive' : 'primary'"
          @click="openConfirm"
        >
          Enregistrer la restriction
        </UiButton>
      </FormActions>
    </div>
    <ConfirmDialog
      :open="confirmOpen"
      title="Confirmer la restriction"
      confirm-label="Enregistrer la restriction"
      :submitting="submitting"
      :destructive="etat === 'revoked'"
      @confirm="confirm"
      @cancel="confirmOpen = false"
    >
      Cette action attend la confirmation du serveur. Elle n’est pas une approbation et ne prolonge pas l’habilitation actuelle.
    </ConfirmDialog>
  </section>
</template>
