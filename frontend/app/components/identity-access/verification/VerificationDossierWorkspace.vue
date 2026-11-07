<script setup lang="ts">
import type {
  FiscalProfileDraft,
  PayoutAccountDraft,
  VerificationDossier,
} from '../../../types/identity-access/verification'
import type { WorkspaceSectionId } from '../../../utils/identity-access/verification'
import {
  documentRequirementsOf,
  fiscalDraftFingerprint,
  fiscalDraftIsReady,
  isDraftDossier,
  labelForDossierStatus,
  labelForDossierSubject,
  labelForRequirementCode,
  payoutDraftFingerprint,
  payoutDraftIsReady,
  toneForDossierStatus,
  workspaceSectionsOf,
  workspaceStepperStatuses,
} from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    dossier: VerificationDossier
    fiscalDraft: FiscalProfileDraft
    payoutDraft: PayoutAccountDraft
    fieldErrors?: Record<string, string[]>
    freezeConfirmed?: boolean
    canDeposit?: boolean
    canSwitchDossier?: boolean
    opening?: boolean
    saving?: boolean
    attaching?: boolean
    attachingType?: string | null
    submitting?: boolean
    mutationBusy?: boolean
    downloadingId?: string | null
    downloadError?: string
    openSucceeded?: boolean
    saveSucceeded?: boolean
    saveUncertain?: boolean
    saveFailed?: boolean
    attachUncertain?: boolean
    attachFailed?: boolean
    submitSucceeded?: boolean
    submitUncertain?: boolean
    submitFailed?: boolean
    actionError?: string
  }>(),
  {
    fieldErrors: () => ({}),
    freezeConfirmed: false,
    canDeposit: false,
    canSwitchDossier: false,
    opening: false,
    saving: false,
    attaching: false,
    submitting: false,
    mutationBusy: false,
    openSucceeded: false,
    saveSucceeded: false,
    saveUncertain: false,
    saveFailed: false,
    attachUncertain: false,
    attachFailed: false,
    submitSucceeded: false,
    submitUncertain: false,
    submitFailed: false,
  },
)

const emit = defineEmits<{
  save: [scope: 'fiscal' | 'reversement']
  submit: []
  attach: [typeDoc: string, file: File]
  download: [pieceId: string]
  renew: []
  'change-dossier': []
  'update:fiscalDraft': [draft: FiscalProfileDraft]
  'update:payoutDraft': [draft: PayoutAccountDraft]
  'update:freezeConfirmed': [value: boolean]
}>()

const fiscalForm = ref<{ validate: () => boolean } | null>(null)
const payoutForm = ref<{ validate: () => boolean } | null>(null)
const currentStepId = ref<WorkspaceSectionId>('pieces')

const draftOpen = computed(() => isDraftDossier(props.dossier.statut))
const freezeNotice = computed(() => !draftOpen.value)
const sections = computed(() => workspaceSectionsOf(props.dossier))
const stepperSteps = computed(() => workspaceStepperStatuses(sections.value, currentStepId.value))
const documentRequirements = computed(() => documentRequirementsOf(props.dossier.exigences))
const currentIndex = computed(() => sections.value.findIndex(section => section.id === currentStepId.value))
const currentSection = computed(() => sections.value[currentIndex.value] ?? sections.value[0] ?? null)
const isFirstStep = computed(() => currentIndex.value <= 0)
const isLastStep = computed(() => currentIndex.value === sections.value.length - 1)
const canSaveHere = computed(() =>
  draftOpen.value && (currentStepId.value === 'fiscal' || currentStepId.value === 'reversement'),
)
const afterSaveAction = ref<'stay' | 'next' | WorkspaceSectionId | null>(null)
const lastSavedFiscal = ref('')
const lastSavedPayout = ref('')
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null

function pieceFor(code: string) {
  return props.dossier.pieces.find(piece => piece.type_doc === code)
}

function firstIncompleteFormStep(): WorkspaceSectionId | null {
  return sections.value.find(section =>
    (section.id === 'fiscal' || section.id === 'reversement') && !section.complete,
  )?.id ?? null
}

function firstIncompleteStep(): WorkspaceSectionId | null {
  return firstIncompleteFormStep()
    ?? sections.value.find(section => section.id !== 'soumission' && !section.complete)?.id
    ?? null
}

function saveScope(): 'fiscal' | 'reversement' | null {
  if (currentStepId.value === 'fiscal' || currentStepId.value === 'reversement') {
    return currentStepId.value
  }
  return null
}

function currentFingerprint(): string {
  if (currentStepId.value === 'fiscal') {
    return fiscalDraftFingerprint(props.fiscalDraft)
  }
  if (currentStepId.value === 'reversement') {
    return payoutDraftFingerprint(props.payoutDraft)
  }
  return ''
}

function lastSavedFingerprint(): string {
  if (currentStepId.value === 'fiscal') {
    return lastSavedFiscal.value
  }
  if (currentStepId.value === 'reversement') {
    return lastSavedPayout.value
  }
  return currentFingerprint()
}

const isDirty = computed(() => currentFingerprint() !== lastSavedFingerprint())
const currentStepReady = computed(() => {
  if (currentStepId.value === 'fiscal') {
    return fiscalDraftIsReady(props.fiscalDraft)
  }
  if (currentStepId.value === 'reversement') {
    return payoutDraftIsReady(props.payoutDraft)
  }
  return false
})
const showSavedHint = computed(() =>
  draftOpen.value
    && canSaveHere.value
    && props.saveSucceeded
    && !props.saving
    && !isDirty.value
    && afterSaveAction.value === null,
)
const showSavingHint = computed(() =>
  canSaveHere.value && props.saving && afterSaveAction.value !== 'next',
)

function markCurrentSaved() {
  if (currentStepId.value === 'fiscal') {
    lastSavedFiscal.value = fiscalDraftFingerprint(props.fiscalDraft)
    return
  }
  if (currentStepId.value === 'reversement') {
    lastSavedPayout.value = payoutDraftFingerprint(props.payoutDraft)
  }
}

function snapshotSavedFromDossier() {
  lastSavedFiscal.value = props.dossier.profil_fiscal
    ? fiscalDraftFingerprint(props.fiscalDraft)
    : ''
  lastSavedPayout.value = props.dossier.compte_reversement
    ? payoutDraftFingerprint(props.payoutDraft)
    : ''
}

function clearAutoSave() {
  if (autoSaveTimer) {
    clearTimeout(autoSaveTimer)
    autoSaveTimer = null
  }
}

function emitSave() {
  const scope = saveScope()
  if (!scope || props.saving) {
    return
  }
  emit('save', scope)
  void nextTick(() => {
    if (afterSaveAction.value && !props.saving) {
      afterSaveAction.value = null
    }
  })
}

function requestSave(action: 'stay' | 'next' | WorkspaceSectionId) {
  if (!saveScope()) {
    return
  }
  afterSaveAction.value = action
  emitSave()
}

function scheduleAutoSave() {
  clearAutoSave()
  if (!canSaveHere.value || !currentStepReady.value || !isDirty.value || props.saving) {
    return
  }
  if (afterSaveAction.value && afterSaveAction.value !== 'stay') {
    return
  }
  autoSaveTimer = setTimeout(() => {
    autoSaveTimer = null
    if (!canSaveHere.value || !currentStepReady.value || !isDirty.value || props.saving) {
      return
    }
    requestSave('stay')
  }, 1000)
}

function applyAfterSave() {
  const action = afterSaveAction.value
  afterSaveAction.value = null
  if (action === 'next') {
    const next = sections.value[currentIndex.value + 1]
    if (next) {
      currentStepId.value = next.id
    }
    return
  }
  if (action && action !== 'stay') {
    goTo(action)
  }
}

function initialStepId(): WorkspaceSectionId {
  const available = sections.value
  if (available.length === 0) {
    return 'soumission'
  }
  if (!draftOpen.value) {
    return available[available.length - 1]?.id ?? 'soumission'
  }
  return firstIncompleteStep() ?? available[0].id
}

function goTo(id: string) {
  if (!sections.value.some(section => section.id === id)) {
    return
  }
  if (id === 'soumission' && draftOpen.value) {
    const incomplete = firstIncompleteFormStep()
    if (incomplete) {
      currentStepId.value = incomplete
      return
    }
  }
  currentStepId.value = id as WorkspaceSectionId
}

function validateCurrentStep(): boolean {
  if (currentStepId.value === 'fiscal' && fiscalForm.value && !fiscalForm.value.validate()) {
    return false
  }
  if (currentStepId.value === 'reversement' && payoutForm.value && !payoutForm.value.validate()) {
    return false
  }
  return true
}

function validateDraft(): boolean {
  return validateCurrentStep()
}

function goNext() {
  const next = sections.value[currentIndex.value + 1]
  if (!next) {
    return
  }
  clearAutoSave()
  const scope = saveScope()
  if (canSaveHere.value && scope) {
    if (!validateCurrentStep()) {
      return
    }
    if (!isDirty.value) {
      goTo(next.id)
      return
    }
    requestSave('next')
    return
  }
  goTo(next.id)
}

function goPrevious() {
  const previous = sections.value[currentIndex.value - 1]
  if (!previous) {
    return
  }
  clearAutoSave()
  if (canSaveHere.value && currentStepReady.value && isDirty.value) {
    requestSave(previous.id)
    return
  }
  currentStepId.value = previous.id
}

function onSelectStep(id: string) {
  if (id === currentStepId.value) {
    return
  }
  clearAutoSave()
  if (canSaveHere.value && currentStepReady.value && isDirty.value) {
    requestSave(id as WorkspaceSectionId)
    return
  }
  goTo(id)
}

watch(
  () => props.dossier.id,
  async () => {
    clearAutoSave()
    afterSaveAction.value = null
    currentStepId.value = initialStepId()
    await nextTick()
    snapshotSavedFromDossier()
  },
  { immediate: true },
)

watch(sections, (next) => {
  if (!next.some(section => section.id === currentStepId.value)) {
    currentStepId.value = initialStepId()
  }
})

watch(() => props.saving, (saving, wasSaving) => {
  if (saving || !wasSaving) {
    return
  }
  if (props.saveFailed || props.saveUncertain) {
    afterSaveAction.value = null
    return
  }
  markCurrentSaved()
  const action = afterSaveAction.value
  if (action === 'next' || (action && action !== 'stay')) {
    if (isDirty.value && currentStepReady.value) {
      emitSave()
      return
    }
    applyAfterSave()
    return
  }
  afterSaveAction.value = null
  scheduleAutoSave()
})

watch(
  () => [
    currentStepId.value,
    fiscalDraftFingerprint(props.fiscalDraft),
    payoutDraftFingerprint(props.payoutDraft),
  ],
  () => {
    scheduleAutoSave()
  },
)

onBeforeUnmount(() => {
  clearAutoSave()
})

defineExpose({ validateDraft })
</script>

<template>
  <section
    id="dossier-courant"
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter"
  >
    <div class="flex flex-col gap-stack sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <h2 class="font-serif text-xl font-semibold text-text">
          Dossier {{ dossier.numero }}
        </h2>
        <div class="mt-stack flex flex-wrap items-center gap-2">
          <StatusBadge :label="labelForDossierStatus(dossier.statut)" :tone="toneForDossierStatus(dossier.statut)" />
          <StatusBadge :label="labelForDossierSubject(dossier.sujet)" tone="neutral" />
          <StatusBadge :label="`Version ${dossier.version}`" tone="neutral" />
        </div>
      </div>
      <UiButton
        v-if="canSwitchDossier"
        variant="quiet"
        @click="emit('change-dossier')"
      >
        Changer de dossier
      </UiButton>
    </div>

    <Stepper
      class="mt-gutter"
      interactive
      layout="stacked"
      label="Étapes du dossier"
      :steps="stepperSteps"
      @select="onSelectStep"
    />

    <RenewalPanel
      v-if="!draftOpen"
      class="mt-gutter"
      :capability="dossier.habilitation"
      :can-open-version="canDeposit"
      :opening="opening"
      @renew="emit('renew')"
    />

    <InlineAlert
      v-if="openSucceeded"
      class="mt-gutter"
      tone="success"
      title="Dossier ouvert"
    >
      Complétez chaque étape, puis soumettez le dossier.
    </InlineAlert>
    <InlineAlert
      v-if="freezeNotice && currentStepId === 'soumission'"
      class="mt-gutter"
      tone="info"
      title="Dossier figé"
    >
      Les preuves de cette version ne sont plus modifiables. L’examen n’est pas une approbation.
    </InlineAlert>
    <InlineAlert
      v-if="submitSucceeded"
      class="mt-gutter"
      tone="success"
      title="Dossier soumis"
    >
      La soumission a été confirmée par le serveur. Prochaine action : attente de l’examen.
    </InlineAlert>
    <InlineAlert
      v-if="saveUncertain || attachUncertain || submitUncertain"
      class="mt-gutter"
      tone="warning"
      title="Résultat incertain"
    >
      <template v-if="submitUncertain">
        Nous ne pouvons pas confirmer l’état de la soumission pour le moment. Vérifiez le statut du dossier avant de réessayer.
      </template>
      <template v-else>
        {{ actionError }}
      </template>
    </InlineAlert>
    <InlineAlert
      v-else-if="saveFailed || attachFailed || submitFailed || downloadError"
      class="mt-gutter"
      tone="error"
      :title="submitFailed
        ? 'Le dossier n’a pas pu être soumis. Vérifiez les informations demandées et réessayez.'
        : 'Action refusée'"
    >
      {{ actionError || downloadError }}
    </InlineAlert>

    <div class="mt-gutter">
      <h3 class="font-serif text-lg font-semibold text-text">
        {{ currentSection?.label }}
      </h3>

      <div v-show="currentStepId === 'pieces'" class="mt-stack">
        <p class="font-sans text-sm text-text-muted">
          Chaque téléchargement est contrôlé à nouveau. Aucun lien public n’est créé.
        </p>
        <div class="mt-stack grid gap-stack">
          <template v-for="requirement in documentRequirements" :key="requirement.id">
            <FileDropzone
              v-if="draftOpen"
              :label="labelForRequirementCode(requirement.code)"
              :hint="requirement.nature_preuve ? `${requirement.nature_preuve} PDF, JPEG ou PNG, 8 Mo au plus.` : 'PDF, JPEG ou PNG, 8 Mo au plus.'"
              :disabled="mutationBusy"
              :loading="attaching && attachingType === requirement.code"
              :selected-name="pieceFor(requirement.code) ? 'Pièce déjà déposée. Un nouveau fichier la complète.' : undefined"
              @select="emit('attach', requirement.code, $event)"
            />
            <EvidenceFileRow
              v-if="pieceFor(requirement.code)"
              :piece="pieceFor(requirement.code)!"
              :downloading="downloadingId === pieceFor(requirement.code)?.id"
              :disabled="Boolean(downloadingId)"
              @download="emit('download', $event)"
            />
          </template>
        </div>
      </div>

      <div v-show="currentStepId === 'fiscal'" class="mt-stack">
        <p class="font-sans text-sm text-text-muted">
          Ces informations sont un formulaire, pas une pièce jointe. La progression est enregistrée automatiquement. Elles sont figées à la soumission.
        </p>
        <FiscalProfileForm
          ref="fiscalForm"
          class="mt-stack"
          :draft="fiscalDraft"
          :field-errors="fieldErrors"
          :disabled="submitting"
          :readonly="!draftOpen"
          @update:draft="emit('update:fiscalDraft', $event)"
        />
      </div>

      <div v-show="currentStepId === 'reversement'" class="mt-stack">
        <p class="font-sans text-sm text-text-muted">
          Le canal de reversement se saisit ici. Ce n’est pas un fichier à téléverser. La progression est enregistrée automatiquement.
        </p>
        <PayoutAccountForm
          ref="payoutForm"
          class="mt-stack"
          :draft="payoutDraft"
          :field-errors="fieldErrors"
          :disabled="submitting"
          :readonly="!draftOpen"
          @update:draft="emit('update:payoutDraft', $event)"
        />
      </div>

      <div v-show="currentStepId === 'soumission'" class="mt-stack">
        <DossierSubmissionDialog
          v-if="draftOpen"
          embedded
          hide-heading
          :confirmed="freezeConfirmed"
          :complete="dossier.complet"
          :gaps="dossier.manques"
          :submitting="submitting"
          :disabled="mutationBusy && !submitting"
          @update:confirmed="emit('update:freezeConfirmed', $event)"
          @submit="emit('submit')"
        />
        <p v-else class="font-sans text-sm text-text-muted">
          Cette version n’est plus modifiable.
        </p>
      </div>
    </div>

    <FormActions class="mt-gutter" align="start">
      <UiButton
        v-if="!isFirstStep"
        variant="secondary"
        :disabled="mutationBusy"
        @click="goPrevious"
      >
        Étape précédente
      </UiButton>
      <UiButton
        v-if="!isLastStep"
        :loading="saving && afterSaveAction === 'next'"
        :disabled="(mutationBusy && !saving) || (saving && afterSaveAction === 'next')"
        @click="goNext"
      >
        Étape suivante
      </UiButton>
      <p
        v-if="showSavingHint"
        class="self-center font-sans text-sm text-text-muted"
        aria-live="polite"
      >
        Enregistrement…
      </p>
      <p
        v-else-if="showSavedHint"
        class="self-center font-sans text-sm text-text-muted"
        aria-live="polite"
      >
        Enregistré
      </p>
    </FormActions>
  </section>
</template>
