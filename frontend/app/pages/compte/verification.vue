<script setup lang="ts">
import type {
  FiscalProfileDraft,
  PayoutAccountDraft,
  VerificationSubjectOption,
  VerificationSubjectSummary,
} from '../../types/identity-access/verification'
import { accessScopeOf, accountStatusOf, isRepresentantAccountView } from '../../utils/identity-access/account'
import {
  isDraftDossier,
  isFrozenDossier,
  labelForRequirementCode,
  requirementNeedsDocument,
} from '../../utils/identity-access/verification'
import { messageForVerificationError } from '../../utils/identity-access/errors'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { downloadVerificationEvidence } from '../../services/identity-access/verification'
import { useAttachVerificationEvidence } from '../../composables/identity-access/useAttachVerificationEvidence'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useOpenVerificationDossier } from '../../composables/identity-access/useOpenVerificationDossier'
import { useSaveVerificationDraft } from '../../composables/identity-access/useSaveVerificationDraft'
import { useSubmitVerificationDossier } from '../../composables/identity-access/useSubmitVerificationDossier'
import { useVerificationDossier } from '../../composables/identity-access/useVerificationDossier'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const route = useRoute()
const { account, loadState, errorMessage, refreshing, refresh } = useCurrentAccount()
const {
  dossier,
  loadState: dossierLoadState,
  errorMessage: dossierError,
  errorKind: dossierErrorKind,
  sessionEnded: dossierSessionEnded,
  load: loadDossier,
  applyDossier,
} = useVerificationDossier()
const {
  mutationState: openState,
  errorMessage: openError,
  result: openResult,
  sessionEnded: openSessionEnded,
  submit: submitOpen,
} = useOpenVerificationDossier()
const {
  mutationState: saveState,
  fieldErrors: saveFieldErrors,
  errorMessage: saveError,
  result: saveResult,
  sessionEnded: saveSessionEnded,
  submit: submitSave,
} = useSaveVerificationDraft()
const {
  mutationState: attachState,
  errorMessage: attachError,
  result: attachResult,
  sessionEnded: attachSessionEnded,
  attachingType,
  uploadProgress,
  submit: submitAttach,
} = useAttachVerificationEvidence()
const {
  mutationState: submitState,
  errorMessage: submitError,
  result: submitResult,
  sessionEnded: submitSessionEnded,
  submit: submitDossier,
} = useSubmitVerificationDossier()

const retrying = ref(false)
const selectedSubjectId = ref('particulier')
const selectedDossierId = ref<string | null>(null)
const createPanelRequested = ref(false)
const dossierListRequested = ref(false)
const freezeConfirmed = ref(false)
const detailsOpen = ref(false)
const downloadingId = ref<string | null>(null)
const downloadError = ref('')
const workspace = ref<{ validateDraft: () => boolean } | null>(null)
const fiscalDraft = ref<FiscalProfileDraft>({
  identifiant_fiscal: '',
  pays_fiscal: '',
  nom_legal: '',
})
const payoutDraft = ref<PayoutAccountDraft>({
  canal: '',
  prestataire: '',
  titulaire: '',
  pays: '',
  devise: '',
  reference_prestataire: '',
  affichage_masque: '',
})

const status = computed(() => (account.value ? accountStatusOf(account.value) : 'unknown'))
const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const dossiers = computed(() => account.value?.dossiers_verification ?? [])
const organisations = computed(() => account.value?.organisations ?? [])
const canDeposit = computed(() => status.value === 'active' && scope.value === 'normal')
const unknownStatus = computed(() => status.value === 'unknown')
const supportOnly = computed(() => scope.value === 'support_only')
const needsEmailVerification = computed(() => status.value === 'pending' || scope.value === 'limited')
const opening = computed(() => openState.value === 'submitting')
const saving = computed(() => saveState.value === 'submitting')
const attaching = computed(() => attachState.value === 'submitting')
const submitting = computed(() => submitState.value === 'submitting')
const mutationBusy = computed(() => opening.value || saving.value || attaching.value || submitting.value)
const draftOpen = computed(() => Boolean(dossier.value && isDraftDossier(dossier.value.statut)))
const showFollowUp = computed(() => Boolean(dossier.value && isFrozenDossier(dossier.value.statut)))

useHead({
  title: computed(() => showFollowUp.value ? 'Dossier de vérification — Livreaura' : 'Vérification — Livreaura'),
})
const subjectOptions = computed<VerificationSubjectOption[]>(() => {
  const options: VerificationSubjectOption[] = [
    {
      id: 'particulier',
      kind: 'particulier',
      label: 'Compte personnel',
      description: 'Dossier partenaire commercial pour ce compte.',
      niveau: 'partenaire_commercial',
    },
  ]

  for (const organization of organisations.value) {
    const allowed = organization.adhesion.statut === 'active'
      && organization.adhesion.permissions.includes('deposer_kyc')
    const requiredFiles = (organization.exigences ?? []).filter(item => requirementNeedsDocument(item.volet)).length
    options.push({
      id: organization.id,
      kind: 'organisation',
      label: organization.raison_sociale,
      description: allowed
        ? (requiredFiles > 0
          ? `Organisation vérifiée · ${requiredFiles} fichier${requiredFiles > 1 ? 's' : ''} exigé${requiredFiles > 1 ? 's' : ''}. Une correspondance de nom n’accorde aucun accès.`
          : 'Dossier organisation vérifiée. Une correspondance de nom n’accorde aucun accès.')
        : 'Le dépôt d’identité n’est pas accordé sur cette organisation.',
      niveau: 'organisation_verifiee',
      organisation_id: organization.id,
      disabled: !allowed,
      disabledReason: allowed ? undefined : 'Le dépôt d’identité n’est pas accordé pour cette organisation.',
    })
  }

  return options
})
const selectedSubject = computed(() => subjectOptions.value.find(option => option.id === selectedSubjectId.value) ?? null)
const createConstitutionSteps = [
  { id: 'sujet', label: 'Choisir le sujet', status: 'current' as const },
  { id: 'pieces', label: 'Préparer les pièces', status: 'upcoming' as const },
  { id: 'soumettre', label: 'Soumettre', status: 'upcoming' as const },
]
const requestedSubjectWithoutDossier = computed(() => {
  const requested = requestedSubjectId()
  if (!requested) {
    return false
  }
  return !dossiers.value.some(item => dossierBelongsToSubject(item.sujet, requested))
})
const viewingDossier = computed(() => {
  if (requestedSubjectWithoutDossier.value && !dossier.value && !selectedDossierId.value) {
    return false
  }
  if (dossierLoadState.value === 'error' && !dossier.value) {
    return false
  }
  if (dossier.value || selectedDossierId.value) {
    return true
  }
  if (typeof route.query.dossier === 'string' && route.query.dossier !== '') {
    return true
  }
  return dossiers.value.length > 0
})
const creatableSubjects = computed(() =>
  subjectOptions.value.filter(option =>
    !option.disabled
    && !dossiers.value.some(item => optionMatchesDossierSubject(option, item.sujet)),
  ),
)
const createPanelOptions = computed(() => creatableSubjects.value)
const canOpenAnotherSubject = computed(() => canDeposit.value && creatableSubjects.value.length > 0)
const showCreatePanel = computed(() => {
  if (dossierListRequested.value && !createPanelRequested.value) {
    return false
  }
  return createPanelRequested.value || !viewingDossier.value
})
const canSwitchDossier = computed(() => dossiers.value.length > 1 || canOpenAnotherSubject.value)
const showDossierList = computed(() => {
  if (showCreatePanel.value || dossiers.value.length === 0) {
    return false
  }
  if (dossierListRequested.value) {
    return true
  }
  if (requestedSubjectWithoutDossier.value && !dossier.value) {
    return false
  }
  if (!dossier.value) {
    return true
  }
  return false
})
const showOpenDossier = computed(() => !showCreatePanel.value && !showDossierList.value)
const showVerificationNav = computed(() =>
  !unknownStatus.value
  && !supportOnly.value
  && dossiers.value.length > 0,
)
const canOfferNewDossier = computed(() => canOpenAnotherSubject.value)
const depositRequirements = computed(() => {
  const organization = organisations.value.find(item => item.id === selectedSubjectId.value)
  return (organization?.exigences ?? []).filter(item => requirementNeedsDocument(item.volet))
})
const selectedCreateSubject = computed(() => createPanelOptions.value.find(option => option.id === selectedSubjectId.value) ?? null)
const pageTitle = computed(() => 'Vérification')
const pageDescription = computed(() => {
  if (dossier.value) {
    return 'Complétez chaque étape, puis soumettez le dossier. La soumission fige les preuves.'
  }
  return 'Constituez un dossier, déposez des pièces privées, puis soumettez-le. La soumission fige les preuves. Aucune pièce n’est publique.'
})
const followUpSubjectLine = computed(() => {
  const sujet = dossier.value?.sujet
  if (!sujet) {
    return ''
  }
  if (sujet.type === 'particulier') {
    return 'Compte personnel'
  }
  if (sujet.type !== 'organisation') {
    return 'Sujet indisponible'
  }
  const name = sujet.raison_sociale?.trim() || 'Organisation'
  const organization = organisations.value.find(item => item.id === sujet.organisation_id)
  const type = organization?.type?.libelle?.trim()
  if (type && type.localeCompare(name, 'fr', { sensitivity: 'base' }) !== 0) {
    return `${name} · ${type}`
  }
  return name
})
const dossierCountLabel = computed(() => {
  const count = dossiers.value.length
  return count <= 1 ? `${count} dossier` : `${count} dossiers`
})

function optionMatchesDossierSubject(option: VerificationSubjectOption, sujet: VerificationSubjectSummary): boolean {
  if (option.kind === 'organisation') {
    return sujet.type === 'organisation' && sujet.organisation_id === option.organisation_id
  }
  return sujet.type !== 'organisation'
}

function subjectIdForDossier(sujet: VerificationSubjectSummary): string {
  if (sujet.type === 'organisation' && sujet.organisation_id) {
    return sujet.organisation_id
  }
  return 'particulier'
}

function restoreSubjectFromDossier(): void {
  const sujet = dossier.value?.sujet
  if (!sujet) {
    return
  }
  const id = subjectIdForDossier(sujet)
  if (subjectOptions.value.some(option => option.id === id)) {
    selectedSubjectId.value = id
  }
}

function revealCreatePanel(): void {
  const current = creatableSubjects.value.find(option => option.id === selectedSubjectId.value)
  const next = current ?? creatableSubjects.value[0]
  if (next) {
    selectedSubjectId.value = next.id
  }
  dossierListRequested.value = false
  createPanelRequested.value = true
  void nextTick(() => {
    document.getElementById('creation-dossier')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

function hideCreatePanel(): void {
  createPanelRequested.value = false
  restoreSubjectFromDossier()
}

function changeDossier(): void {
  hideCreatePanel()
  if (dossiers.value.length > 1) {
    dossierListRequested.value = true
    void nextTick(() => {
      document.getElementById('dossiers-du-compte')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
    return
  }
  if (canOpenAnotherSubject.value) {
    revealCreatePanel()
  }
}

function viewDossiers(): void {
  hideCreatePanel()
  dossierListRequested.value = true
  void nextTick(() => {
    document.getElementById('dossiers-du-compte')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

function requestedSubjectId(): string | null {
  return typeof route.query.sujet === 'string' && route.query.sujet !== ''
    ? route.query.sujet
    : null
}

function dossierBelongsToSubject(sujet: VerificationSubjectSummary, requested: string): boolean {
  if (requested === 'particulier') {
    return sujet.type !== 'organisation'
  }
  return sujet.organisation_id === requested
}

watch(
  () => [account.value, organisations.value, route.query.sujet],
  () => {
    if (!account.value || createPanelRequested.value) {
      return
    }
    const requested = requestedSubjectId()
    if (requested && subjectOptions.value.some(option => option.id === requested)) {
      selectedSubjectId.value = requested
      return
    }
    if (selectedSubjectId.value !== 'particulier') {
      return
    }
    if (!isRepresentantAccountView(account.value) || organisations.value.length === 0) {
      return
    }
    selectedSubjectId.value = organisations.value[0].id
  },
  { immediate: true },
)

watch(
  () => dossier.value?.id,
  (id) => {
    const next = dossier.value
    if (!id || !next) {
      return
    }
    freezeConfirmed.value = false
    detailsOpen.value = false
    if (!createPanelRequested.value) {
      restoreSubjectFromDossier()
    }
    fiscalDraft.value = {
      identifiant_fiscal: next.profil_fiscal?.identifiant_fiscal ?? '',
      pays_fiscal: next.profil_fiscal?.pays_fiscal || account.value?.profil?.pays || '',
      nom_legal: next.profil_fiscal?.nom_legal || legalName(),
    }
    payoutDraft.value = {
      canal: next.compte_reversement?.canal ?? '',
      prestataire: next.compte_reversement?.prestataire ?? '',
      titulaire: next.compte_reversement?.titulaire || legalName(),
      pays: next.compte_reversement?.pays || account.value?.profil?.pays || '',
      devise: next.compte_reversement?.devise ?? '',
      reference_prestataire: '',
      affichage_masque: next.compte_reversement?.affichage_masque ?? '',
    }
  },
)

function legalName(): string {
  const prenom = account.value?.profil?.prenom?.trim() ?? ''
  const nom = account.value?.profil?.nom?.trim() ?? ''
  return [prenom, nom].filter(Boolean).join(' ')
}

async function afterSession(ended: boolean): Promise<boolean> {
  if (ended) {
    await navigateTo('/connexion')
    return true
  }
  return false
}

async function revealDossier(id: string): Promise<void> {
  selectedDossierId.value = id
  createPanelRequested.value = false
  dossierListRequested.value = false
  await nextTick()
  document.getElementById('dossier-courant')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function selectDossier(id: string) {
  await revealDossier(id)
  const query: Record<string, string> = { dossier: id }
  const sujet = requestedSubjectId()
  if (sujet) {
    query.sujet = sujet
  }
  await navigateTo({ path: '/compte/verification', query, replace: true })
  await loadDossier(id)
  if (await afterSession(dossierSessionEnded.value)) {
    return
  }
}

async function loadAccount() {
  await refresh({ allowAnonymous: false })
  if (loadState.value === 'anonymous') {
    return navigateTo('/connexion')
  }
  const requested = typeof route.query.dossier === 'string' ? route.query.dossier : null
  const sujet = requestedSubjectId()
  const matching = sujet
    ? dossiers.value.find(item => item.sujet.organisation_id === sujet)
    : dossiers.value[0]
  const initial = requested || matching?.id || null
  if (initial) {
    await selectDossier(initial)
  }
  else {
    selectedDossierId.value = null
    createPanelRequested.value = false
    await loadDossier(null)
  }
}

async function retry() {
  retrying.value = true
  try {
    await loadAccount()
  }
  finally {
    retrying.value = false
  }
}

async function onOpenDossier() {
  const option = (showCreatePanel.value && viewingDossier.value
    ? createPanelOptions.value.find(item => item.id === selectedSubjectId.value)
    : selectedSubject.value) ?? null
  if (!option || option.disabled || !canDeposit.value) {
    return
  }
  await submitOpen({
    niveau: option.niveau,
    organisation_id: option.organisation_id,
  })
  if (await afterSession(openSessionEnded.value)) {
    return
  }
  const opened = openResult.value?.dossier
  if (openState.value !== 'succeeded' || !opened) {
    return
  }
  applyDossier(opened)
  await revealDossier(opened.id)
  const query: Record<string, string> = { dossier: opened.id }
  if (option.organisation_id) {
    query.sujet = option.organisation_id
  }
  await navigateTo({ path: '/compte/verification', query, replace: true })
}

async function onSaveDraft(scope: 'fiscal' | 'reversement' = 'fiscal') {
  if (!dossier.value || !draftOpen.value) {
    return
  }
  await submitSave(dossier.value.id, {
    profil_fiscal: scope === 'fiscal' ? fiscalDraft.value : null,
    compte_reversement: scope === 'reversement'
      ? {
          ...payoutDraft.value,
          prestataire: payoutDraft.value.prestataire.trim() || '',
        }
      : null,
  })
  if (saveResult.value?.dossier) {
    applyDossier(saveResult.value.dossier)
  }
  if (await afterSession(saveSessionEnded.value)) {
    return
  }
}

async function onAttach(typeDoc: string, file: File) {
  if (!dossier.value || !draftOpen.value) {
    return
  }
  await submitAttach(dossier.value.id, typeDoc, file)
  if (await afterSession(attachSessionEnded.value)) {
    return
  }
  if (attachResult.value?.dossier) {
    applyDossier(attachResult.value.dossier)
  }
}

async function onSubmitDossier() {
  if (!dossier.value || !draftOpen.value || !dossier.value.complet) {
    return
  }
  const dossierId = dossier.value.id
  await submitDossier(dossierId)
  if (await afterSession(submitSessionEnded.value)) {
    return
  }
  if (submitResult.value?.dossier) {
    applyDossier(submitResult.value.dossier)
    freezeConfirmed.value = false
    await nextTick()
    document.getElementById('dossier-suivi')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }
  if (submitState.value === 'uncertain' || submitState.value === 'failed') {
    await loadDossier(dossierId)
    if (await afterSession(dossierSessionEnded.value)) {
      return
    }
    await nextTick()
    if (dossier.value && isFrozenDossier(dossier.value.statut)) {
      document.getElementById('dossier-suivi')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
}

async function onDownload(pieceId: string) {
  if (!dossier.value || downloadingId.value) {
    return
  }
  const piece = dossier.value.pieces.find(item => item.id === pieceId)
  downloadingId.value = pieceId
  downloadError.value = ''
  try {
    await downloadVerificationEvidence(dossier.value.id, pieceId, `${piece?.type_doc || 'piece'}.bin`)
  }
  catch (error) {
    downloadError.value = messageForVerificationError(error as IdentityRequestError)
  }
  finally {
    downloadingId.value = null
  }
}

onMounted(() => {
  void loadAccount()
})
</script>

<template>
  <main class="flex w-full flex-col gap-gutter py-gutter lg:py-section">
    <StepBackAction
      label="Retour au compte"
      to="/compte"
    />
    <PageHeader
      v-if="!showFollowUp && !showCreatePanel"
      :title="pageTitle"
      :description="pageDescription"
      :loading="!account && (loadState === 'idle' || loadState === 'loading')"
    />

    <InlineAlert
      v-if="refreshing && account"
      tone="info"
      title="Actualisation"
    >
      Les informations affichées sont en cours de vérification auprès du serveur.
    </InlineAlert>

    <ErrorState
      v-if="loadState === 'error' && !account"
      title="Impossible de charger le compte"
      :message="errorMessage"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="retry"
    />

    <div
      v-else-if="!account && (loadState === 'idle' || loadState === 'loading')"
      aria-busy="true"
    >
      <Skeleton variant="detail" />
    </div>

    <template v-else-if="account && (loadState === 'ready' || loadState === 'error')">
      <RestrictionBanner
        v-if="unknownStatus"
        title="Statut indisponible"
      >
        Le statut du compte n’est pas reconnu. Les décisions qui en dépendent restent bloquées.
      </RestrictionBanner>

      <RestrictionBanner
        v-else-if="supportOnly"
        title="Accès restreint"
      >
        Seuls les recours et documents explicitement autorisés restent accessibles. Aucun dossier de vérification ne peut être déposé ici.
      </RestrictionBanner>

      <RestrictionBanner
        v-else-if="needsEmailVerification"
        title="Activation encore nécessaire"
      >
        Le compte doit d’abord être activé avant de déposer un dossier.
        <template #action>
          <UiLink to="/inscription/verification">Aller à la vérification e-mail</UiLink>
        </template>
      </RestrictionBanner>

      <nav
        v-if="showVerificationNav"
        class="flex flex-wrap items-center gap-gutter"
        aria-label="Dossiers de vérification"
      >
        <button
          type="button"
          class="inline-flex min-h-11 items-center rounded-sm font-sans text-sm focus-ring disabled:cursor-not-allowed disabled:opacity-50"
          :class="showDossierList
            ? 'font-semibold text-brand underline decoration-brand underline-offset-4'
            : 'font-medium text-text-muted hover:text-text'"
          :aria-current="showDossierList ? 'page' : undefined"
          :disabled="mutationBusy"
          @click="viewDossiers"
        >
          Afficher les dossiers
        </button>
        <button
          v-if="canOfferNewDossier"
          type="button"
          class="inline-flex min-h-11 items-center rounded-sm font-sans text-sm focus-ring disabled:cursor-not-allowed disabled:opacity-50"
          :class="showCreatePanel
            ? 'font-semibold text-brand underline decoration-brand underline-offset-4'
            : 'font-medium text-text-muted hover:text-text'"
          :aria-current="showCreatePanel ? 'page' : undefined"
          :disabled="mutationBusy"
          @click="revealCreatePanel"
        >
          Créer un dossier
        </button>
      </nav>

      <VerificationCreatePanel
        v-if="showCreatePanel"
        id="creation-dossier"
        v-model="selectedSubjectId"
        :steps="createConstitutionSteps"
        :options="createPanelOptions"
        :can-deposit="canDeposit"
        :opening="opening"
        :mutation-busy="mutationBusy"
        :can-submit-open="Boolean(selectedCreateSubject) && !selectedCreateSubject?.disabled"
        :selected-disabled-reason="selectedCreateSubject?.disabledReason"
        :open-label="depositRequirements.length > 0 ? 'Déposer les justificatifs' : 'Ouvrir un dossier'"
        @open="onOpenDossier"
      >
        <template #notices>
          <div v-if="depositRequirements.length > 0" class="mt-stack">
            <p class="font-sans text-sm text-text">
              Aucun fichier n’a encore été envoyé. Ces justificatifs seront demandés dans le dossier.
            </p>
            <ul class="mt-2 list-disc pl-5 font-sans text-sm text-text-muted">
              <li v-for="requirement in depositRequirements" :key="requirement.code">
                {{ labelForRequirementCode(requirement.code) }}
              </li>
            </ul>
          </div>
          <InlineAlert
            v-if="openState === 'uncertain'"
            class="mt-stack"
            tone="warning"
            title="Résultat incertain"
          >
            {{ openError }}
          </InlineAlert>
          <InlineAlert
            v-else-if="openState === 'failed'"
            class="mt-stack"
            tone="error"
            title="Ouverture refusée"
          >
            {{ openError }}
          </InlineAlert>
          <p v-if="viewingDossier" class="mt-stack">
            <UiButton variant="quiet" :disabled="opening" @click="hideCreatePanel">
              Masquer
            </UiButton>
          </p>
        </template>
      </VerificationCreatePanel>

      <ForbiddenState
        v-if="dossierLoadState === 'error' && !dossier && dossierErrorKind === 'forbidden'"
        title="Accès refusé"
        message="Vous n’êtes pas autorisé à consulter ce dossier."
      >
        <template #action>
          <UiButton variant="secondary" to="/compte">
            Retour à mon compte
          </UiButton>
        </template>
      </ForbiddenState>
      <ErrorState
        v-else-if="dossierLoadState === 'error' && !dossier"
        title="Impossible de charger le dossier"
        :message="dossierError || 'Impossible de charger les détails de ce dossier. Réessayez.'"
        retry-label="Réessayer"
        :retrying="dossierLoadState === 'loading'"
        @retry="selectedDossierId ? selectDossier(selectedDossierId) : retry"
      />
      <div
        v-else-if="dossierLoadState === 'loading' && !dossier"
        aria-busy="true"
      >
        <Skeleton variant="detail" />
      </div>

      <VerificationDossierFollowUp
        v-else-if="showOpenDossier && showFollowUp && dossier"
        :dossier="dossier"
        :subject-line="followUpSubjectLine"
        :can-deposit="canDeposit"
        :opening="opening"
        :downloading-id="downloadingId"
        :download-error="downloadError"
        :details-open="detailsOpen"
        @view-dossiers="viewDossiers"
        @toggle-details="detailsOpen = !detailsOpen"
        @renew="onOpenDossier"
        @download="onDownload"
      />

      <VerificationDossierWorkspace
        v-else-if="showOpenDossier && dossier"
        ref="workspace"
        :dossier="dossier"
        :fiscal-draft="fiscalDraft"
        :payout-draft="payoutDraft"
        :field-errors="saveFieldErrors"
        :freeze-confirmed="freezeConfirmed"
        :can-deposit="canDeposit"
        :opening="opening"
        :saving="saving"
        :attaching="attaching"
        :attaching-type="attachingType"
        :upload-progress="uploadProgress"
        :submitting="submitting"
        :mutation-busy="mutationBusy"
        :downloading-id="downloadingId"
        :download-error="downloadError"
        :open-succeeded="openState === 'succeeded'"
        :save-succeeded="saveState === 'succeeded'"
        :save-uncertain="saveState === 'uncertain'"
        :save-failed="saveState === 'failed'"
        :attach-uncertain="attachState === 'uncertain'"
        :attach-failed="attachState === 'failed'"
        :submit-succeeded="submitState === 'succeeded' && submitResult?.statut === 'submitted'"
        :submit-uncertain="submitState === 'uncertain'"
        :submit-failed="submitState === 'failed'"
        :action-error="saveError || attachError || submitError"
        @save="onSaveDraft"
        @submit="onSubmitDossier"
        @attach="onAttach"
        @download="onDownload"
        @renew="onOpenDossier"
        @change-dossier="changeDossier"
        :can-switch-dossier="canSwitchDossier"
        @update:fiscal-draft="fiscalDraft = $event"
        @update:payout-draft="payoutDraft = $event"
        @update:freeze-confirmed="freezeConfirmed = $event"
      />

      <section
        v-if="showDossierList"
        id="dossiers-du-compte"
        class="scroll-mt-gutter rounded-md border border-border bg-surface-raised px-gutter py-gutter"
      >
        <h2 class="font-serif text-xl font-semibold text-text">
          Dossiers du compte
          <span class="ml-2 font-sans text-sm font-normal text-text-muted">
            {{ dossierCountLabel }}
          </span>
        </h2>
        <div
          v-if="dossiers.length === 0"
          class="flex min-h-56 items-center justify-center"
        >
          <EmptyState
            plain
            icon="folder"
            title="Aucun dossier"
            message="Aucun dossier de vérification n’est encore associé à ce compte. Ouvrez un dossier ci-dessus pour commencer."
          />
        </div>
        <ul v-else class="mt-gutter grid gap-stack">
          <li v-for="item in dossiers" :key="item.id">
            <VerificationDossierCard
              :dossier="item"
              selectable
              :selected="item.id === selectedDossierId"
              :disabled="mutationBusy"
              @select="selectDossier"
            />
          </li>
        </ul>
      </section>
    </template>
  </main>
</template>
