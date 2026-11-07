<script setup lang="ts">
import type { BackofficeDecision } from '../../../types/identity-access/backoffice'
import { labelForDecisionResult } from '../../../utils/identity-access/backoffice'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'

const props = withDefaults(
  defineProps<{
    canDecide: boolean
    controlsComplete: boolean
    commercialOnly?: boolean
    commercialDecision?: boolean
    conflict?: boolean
    submitting?: boolean
    decision?: BackofficeDecision | null
    disabledReason?: string
  }>(),
  {
    commercialOnly: false,
    commercialDecision: false,
    conflict: false,
    submitting: false,
    decision: null,
  },
)

const emit = defineEmits<{
  decide: [payload: { resultat: 'approved' | 'needs_fix' | 'rejected', motif: string }]
}>()

const resultat = ref('')
const motif = ref('')
const confirmOpen = ref(false)

const blockedReason = computed(() => {
  if (props.decision) {
    return 'Une décision est déjà enregistrée pour cette version.'
  }
  if (props.conflict) {
    return 'Ce dossier est lié à ce compte. Une réaffectation indépendante est nécessaire.'
  }
  if (props.commercialOnly) {
    return 'La décision commerciale n’est pas proposée ici.'
  }
  if (!props.canDecide) {
    return props.disabledReason || 'La décision n’est pas disponible pour ce compte.'
  }
  if (!props.controlsComplete) {
    return 'Tous les contrôles doivent être consignés avant la décision.'
  }
  return ''
})

const canOpen = computed(() => !blockedReason.value && (resultat.value === 'approved' || resultat.value === 'needs_fix' || resultat.value === 'rejected') && motif.value.trim().length >= 8)

function openConfirm() {
  if (!canOpen.value) {
    return
  }
  confirmOpen.value = true
}

function confirm() {
  if (resultat.value !== 'approved' && resultat.value !== 'needs_fix' && resultat.value !== 'rejected') {
    return
  }
  emit('decide', {
    resultat: resultat.value,
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
      Décision
    </h2>
    <p v-if="decision" class="mt-stack font-sans text-sm text-text">
      {{ labelForDecisionResult(decision.resultat) }}
      <template v-if="decision.decide_a"> · {{ formatActivationExpiry(decision.decide_a) }}</template>
    </p>
    <p v-if="decision" class="mt-2 font-sans text-sm text-text-muted">
      {{ decision.motif }}
    </p>
    <p v-if="decision" class="mt-stack font-sans text-sm text-text-muted">
      Cette décision n’est plus modifiable. Une correction passe par une nouvelle version.
    </p>
    <template v-else>
      <InlineAlert
        v-if="blockedReason"
        class="mt-stack"
        tone="warning"
        title="Décision indisponible"
      >
        {{ blockedReason }}
      </InlineAlert>
      <div class="mt-stack grid gap-stack">
        <RadioGroup
          v-model="resultat"
          legend="Résultat"
          name="decision-kyc"
          :disabled="Boolean(blockedReason) || submitting"
        >
          <ChoiceCard
            value="approved"
            title="Approuver"
            :description="commercialDecision
              ? 'Crée l’habilitation partenaire commercial et associe un vendeur. Cela n’accorde pas les droits d’un ouvrage.'
              : 'Crée l’habilitation organisation vérifiée. Tous les contrôles obligatoires doivent être satisfaits.'"
          />
          <ChoiceCard
            value="needs_fix"
            title="Demander une correction"
            description="Le titulaire devra soumettre une nouvelle version. Aucune habilitation n’est créée."
          />
          <ChoiceCard
            value="rejected"
            title="Rejeter"
            description="Clôture cette version sans habilitation. Une nouvelle version reste possible."
          />
        </RadioGroup>
        <FormField label="Motif" required hint="Le motif est conservé avec la décision.">
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
            :disabled-reason="blockedReason || (!resultat ? 'Choisissez un résultat.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined))"
            @click="openConfirm"
          >
            Enregistrer la décision
          </UiButton>
        </FormActions>
      </div>
    </template>
    <ConfirmDialog
      :open="confirmOpen"
      title="Confirmer la décision"
      confirm-label="Enregistrer la décision"
      :submitting="submitting"
      :destructive="resultat === 'rejected'"
      @confirm="confirm"
      @cancel="confirmOpen = false"
    >
      Cette décision est unique pour cette version. Elle n’est pas une auto-approbation et ne peut plus être amendée ensuite.
    </ConfirmDialog>
  </section>
</template>
