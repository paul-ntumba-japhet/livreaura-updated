<script setup lang="ts">
import type { OrganizationFormDraft } from '../../../types/identity-access/organization'
import { accessScopeOf, accountStatusOf, countryLabel } from '../../../utils/identity-access/account'
import { fetchRegistrationPaths } from '../../../services/identity-access/registration-paths'
import { useCreateOrganization } from '../../../composables/identity-access/useCreateOrganization'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useOrganizationMatches } from '../../../composables/identity-access/useOrganizationMatches'
import type { OrganizationTypeDto } from '../../../types/identity-access/registration'
import { messageForIdentityError } from '../../../utils/identity-access/errors'
import { labelForRequirementCode } from '../../../utils/identity-access/verification'
import type { IdentityRequestError } from '../../../utils/identity-access/errors'
import type { StepItem, StepStatus } from '../../../components/shared/Stepper.vue'

const wizardOrder = ['informations', 'verification', 'confirmation'] as const
type WizardStepId = typeof wizardOrder[number]

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Déclarer une organisation — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account, loadState, errorMessage, refreshing, refresh } = useCurrentAccount()
const {
  mutationState,
  fieldErrors,
  errorMessage: mutationError,
  result,
  sessionEnded,
  submit,
  reset,
} = useCreateOrganization()
const {
  loadState: lookupState,
  matches,
  errorMessage: lookupError,
  search: searchMatches,
} = useOrganizationMatches()

const retrying = ref(false)
const typesLoadState = ref<'idle' | 'loading' | 'ready' | 'empty' | 'error'>('idle')
const typesError = ref('')
const organizationTypes = ref<OrganizationTypeDto[]>([])
const selectedMatchId = ref<string | null>(null)
const wizardStep = ref<WizardStepId>('informations')
const draft = ref<OrganizationFormDraft>({
  type_organisation_id: '',
  raison_sociale: '',
  pays: '',
  ville: '',
  email_pro: '',
  site: '',
})

const status = computed(() => (account.value ? accountStatusOf(account.value) : 'unknown'))
const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const canCreate = computed(() => status.value === 'active' && scope.value === 'normal')
const submitting = computed(() => mutationState.value === 'submitting')
const unknownStatus = computed(() => status.value === 'unknown')
const supportOnly = computed(() => scope.value === 'support_only')
const needsEmailVerification = computed(() => status.value === 'pending' || scope.value === 'limited')
const lookupMatches = computed(() => {
  if (result.value?.statut === 'correspondances_possibles' && result.value.correspondances.length > 0) {
    return result.value.correspondances
  }
  return matches.value
})
const lookupDisplayState = computed(() => {
  if (result.value?.statut === 'correspondances_possibles') {
    return result.value.correspondances.length > 0 ? 'ready' : 'empty'
  }
  return lookupState.value
})
const confirmNew = computed(() => lookupDisplayState.value === 'ready' && lookupMatches.value.length > 0)
const successTitle = computed(() => {
  if (mutationState.value !== 'succeeded' || !result.value) {
    return ''
  }
  if (result.value.statut === 'correspondances_possibles') {
    return ''
  }
  if (result.value.statut === 'examen') {
    return 'Demande enregistrée'
  }
  if (result.value.statut === 'deja_membre') {
    return 'Adhésion déjà active'
  }
  return 'Organisation créée'
})
const successMessage = computed(() => {
  if (mutationState.value !== 'succeeded' || !result.value) {
    return ''
  }
  if (result.value.statut === 'correspondances_possibles') {
    return ''
  }
  if (result.value.statut === 'examen' && result.value.demande?.reference) {
    return `La demande de représentation a été enregistrée sous la référence ${result.value.demande.reference}. Aucun droit d’accès n’est accordé tant que l’examen n’est pas terminé.`
  }
  if (result.value.statut === 'deja_membre') {
    return 'Ce compte est déjà membre actif de cette organisation. Aucune nouvelle adhésion n’a été créée.'
  }
  if (result.value.statut === 'creee') {
    return 'L’organisation a été créée. Vous en êtes le représentant déclarant, avec le dépôt d’identité uniquement. Ce n’est pas un représentant vérifié.'
  }
  return 'La demande a été confirmée par le serveur.'
})
const selectedType = computed(() => organizationTypes.value.find(type => type.id === draft.value.type_organisation_id) ?? null)
const preparationRequirements = computed(() => selectedType.value?.exigences ?? [])
const allRequirementsObligatory = computed(() => preparationRequirements.value.length > 0 && preparationRequirements.value.every(item => item.obligatoire))
const preparationIntro = computed(() => {
  const count = preparationRequirements.value.length
  if (count === 0) {
    return ''
  }
  const subject = count > 1 ? `${count} justificatifs` : '1 justificatif'
  const verb = count > 1 ? 'seront demandés' : 'sera demandé'
  const qualifier = allRequirementsObligatory.value ? ' obligatoires' : ''
  return `${subject}${qualifier} ${verb} dans le dossier de vérification.`
})
const openRequirementCodes = ref<string[]>([])
const declarationFinished = computed(() => {
  if (mutationState.value !== 'succeeded' || !result.value) {
    return false
  }
  return result.value.statut !== 'correspondances_possibles'
})
const declarationSteps = computed<StepItem[]>(() => {
  const labels: Record<WizardStepId, string> = {
    informations: 'Informations',
    verification: 'Contrôle préalable',
    confirmation: 'Confirmation',
  }
  const currentIndex = wizardOrder.indexOf(wizardStep.value)
  return wizardOrder.map((id, index) => {
    let status: StepStatus
    if (declarationFinished.value) {
      status = id === 'confirmation' ? 'current' : 'complete'
    }
    else if (id === wizardStep.value) {
      status = 'current'
    }
    else if (index < currentIndex) {
      status = 'complete'
    }
    else {
      status = 'upcoming'
    }
    return {
      id,
      label: labels[id],
      status,
    }
  })
})
const recapItems = computed(() => [
  {
    term: 'Type',
    value: selectedType.value?.libelle || 'Non renseigné',
    missing: !selectedType.value,
  },
  {
    term: 'Nom officiel',
    value: draft.value.raison_sociale.trim() || 'Non renseignée',
    missing: !draft.value.raison_sociale.trim(),
  },
  {
    term: 'Pays',
    value: countryLabel(draft.value.pays) || draft.value.pays || 'Non renseigné',
    missing: !draft.value.pays,
  },
  {
    term: 'Ville',
    value: draft.value.ville.trim() || 'Non renseignée',
    missing: !draft.value.ville.trim(),
  },
  {
    term: 'E-mail professionnel',
    value: draft.value.email_pro.trim() || 'Non renseigné',
    missing: !draft.value.email_pro.trim(),
  },
  {
    term: 'Site web',
    value: draft.value.site.trim() || 'Non renseigné',
    missing: !draft.value.site.trim(),
  },
])
const canOpenCreatedKyc = computed(() => {
  if (!declarationFinished.value || !result.value?.organisation?.id) {
    return false
  }
  return (result.value.adhesion?.permissions ?? []).includes('deposer_kyc')
})
const pageDescription = computed(() => {
  if (declarationFinished.value) {
    return 'La demande a été enregistrée par le serveur. Les pièces d’identité se déposent dans le dossier de vérification.'
  }
  if (wizardStep.value === 'verification') {
    return 'Vérifiez les correspondances et préparez les justificatifs.'
  }
  if (wizardStep.value === 'confirmation') {
    return 'Contrôlez le résumé, puis déclarez l’organisation. Aucun fichier n’est envoyé à cette étape.'
  }
  return 'Renseignez les informations permettant d’identifier votre organisation.'
})
const sawMatches = computed(() => lookupDisplayState.value === 'ready' && lookupMatches.value.length > 0)

let lookupTimer: ReturnType<typeof setTimeout> | null = null

function goToVerification() {
  wizardStep.value = 'verification'
}

function goToConfirmation() {
  wizardStep.value = 'confirmation'
}

function toggleRequirement(code: string) {
  openRequirementCodes.value = openRequirementCodes.value.includes(code)
    ? openRequirementCodes.value.filter(item => item !== code)
    : [...openRequirementCodes.value, code]
}

function retryLookup() {
  void searchMatches({
    type_organisation_id: draft.value.type_organisation_id,
    raison_sociale: draft.value.raison_sociale,
    pays: draft.value.pays,
  })
}

function onSelectWizardStep(id: string) {
  if (declarationFinished.value) {
    return
  }
  const target = wizardOrder.find(step => step === id)
  if (!target) {
    return
  }
  if (wizardOrder.indexOf(target) > wizardOrder.indexOf(wizardStep.value)) {
    return
  }
  wizardStep.value = target
}

watch(
  () => draft.value.type_organisation_id,
  () => {
    openRequirementCodes.value = []
  },
)

watch(
  () => [draft.value.type_organisation_id, draft.value.raison_sociale, draft.value.pays],
  () => {
    selectedMatchId.value = null
    if (lookupTimer) {
      clearTimeout(lookupTimer)
    }
    if (!canCreate.value) {
      return
    }
    lookupTimer = setTimeout(() => {
      void searchMatches({
        type_organisation_id: draft.value.type_organisation_id,
        raison_sociale: draft.value.raison_sociale,
        pays: draft.value.pays,
      })
    }, 400)
  },
)

async function loadTypes() {
  typesLoadState.value = 'loading'
  typesError.value = ''
  try {
    const paths = await fetchRegistrationPaths(account.value?.compte.locale || 'fr')
    organizationTypes.value = paths.types_organisation
    typesLoadState.value = paths.types_organisation.length > 0 ? 'ready' : 'empty'
  }
  catch (error) {
    typesLoadState.value = 'error'
    typesError.value = messageForIdentityError(error as IdentityRequestError)
  }
}

async function loadAccount() {
  await refresh({ allowAnonymous: false })
  if (loadState.value === 'anonymous') {
    return navigateTo('/connexion')
  }
  if (account.value?.profil?.pays && !draft.value.pays) {
    draft.value = { ...draft.value, pays: account.value.profil.pays }
  }
  await loadTypes()
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

async function afterMutation() {
  if (sessionEnded.value) {
    return navigateTo('/connexion')
  }
  if (mutationState.value !== 'succeeded' || !result.value) {
    return
  }
  if (result.value.statut === 'correspondances_possibles') {
    wizardStep.value = 'verification'
    return
  }
  const organisationId = result.value.organisation?.id
  const canDeposit = (result.value.adhesion?.permissions ?? []).includes('deposer_kyc')
  if (result.value.statut === 'creee' && organisationId && canDeposit) {
    await navigateTo({ path: '/compte/verification', query: { sujet: organisationId } })
    return
  }
  wizardStep.value = 'confirmation'
}

function payloadFromDraft(extra: { confirmer_nouvelle?: boolean, organisation_id?: string } = {}) {
  return {
    type_organisation_id: draft.value.type_organisation_id,
    raison_sociale: draft.value.raison_sociale.trim(),
    pays: draft.value.pays,
    ville: draft.value.ville.trim() || null,
    email_pro: draft.value.email_pro.trim() || null,
    site: draft.value.site.trim() || null,
    ...extra,
  }
}

async function onSubmit() {
  reset()
  selectedMatchId.value = null
  await submit(payloadFromDraft(sawMatches.value ? { confirmer_nouvelle: true } : {}))
  await afterMutation()
}

async function onConfirmNew() {
  await submit(payloadFromDraft({ confirmer_nouvelle: true }))
  await afterMutation()
}

async function onClaim(organizationId: string) {
  selectedMatchId.value = organizationId
  await submit(payloadFromDraft({ organisation_id: organizationId }))
  await afterMutation()
}

onBeforeUnmount(() => {
  if (lookupTimer) {
    clearTimeout(lookupTimer)
  }
})

onMounted(() => {
  void loadAccount()
})
</script>

<template>
  <main class="flex w-full flex-col gap-gutter py-gutter lg:py-section">
    <div class="declare-org">
    <StepBackAction label="Retour aux organisations" to="/compte/organisation" />
    <PageHeader
      title="Déclarer une organisation"
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
        Seuls les recours et documents explicitement autorisés restent accessibles. Aucune organisation ne peut être créée ici.
      </RestrictionBanner>

      <RestrictionBanner
        v-else-if="needsEmailVerification"
        title="Activation encore nécessaire"
      >
        Le compte doit d’abord être activé avant de déclarer une organisation.
        <template #action>
          <UiLink to="/inscription/verification">Aller à la vérification e-mail</UiLink>
        </template>
      </RestrictionBanner>

      <div class="flex min-w-0 flex-col gap-6">
        <Stepper
          label="Étapes de la déclaration"
          layout="inline"
          :steps="declarationSteps"
          :interactive="canCreate && !declarationFinished"
          @select="onSelectWizardStep"
        />

        <div class="flex min-w-0 flex-col gap-6">
          <p v-if="!canCreate" class="font-sans text-sm text-text-muted">
            Cette action n’est disponible qu’après activation du compte, avec un accès normal.
          </p>

          <InlineAlert
            v-if="mutationState === 'uncertain'"
            tone="warning"
            title="Résultat incertain"
          >
            {{ mutationError }}
          </InlineAlert>
          <InlineAlert
            v-else-if="mutationState === 'failed'"
            tone="error"
            title="Demande refusée"
          >
            {{ mutationError }}
          </InlineAlert>
          <InlineAlert
            v-else-if="successTitle && successMessage"
            tone="success"
            :title="successTitle"
          >
            {{ successMessage }}
          </InlineAlert>

          <ErrorState
            v-if="canCreate && typesLoadState === 'error'"
            title="Impossible de charger les types"
            :message="typesError"
            retry-label="Réessayer"
            :retrying="typesLoadState === 'loading'"
            @retry="loadTypes"
          />
          <EmptyState
            v-else-if="canCreate && typesLoadState === 'empty'"
            title="Aucun type disponible"
            message="Aucun type d’organisation n’est proposé pour le moment."
          />
          <div
            v-else-if="canCreate && (typesLoadState === 'idle' || typesLoadState === 'loading')"
            aria-busy="true"
          >
            <Skeleton variant="form" />
          </div>
          <template v-else-if="canCreate && typesLoadState === 'ready'">
            <OrganizationForm
              v-show="wizardStep === 'informations' && !declarationFinished"
              :types="organizationTypes"
              :draft="draft"
              :field-errors="fieldErrors"
              :submitting="submitting"
              :disabled="submitting"
              :show-requirements="false"
              contextual-requirements
              :similar-found="sawMatches"
              submit-label="Continuer"
              :submit-icon="false"
              @update:draft="draft = $event"
              @submit="goToVerification"
            />

            <div
              v-show="wizardStep === 'verification' && !declarationFinished"
              class="declare-check"
            >
              <section class="declare-check-docs" aria-labelledby="declare-docs-title">
                <div class="declare-check-head">
                  <h2 id="declare-docs-title">Documents à prévoir</h2>
                  <p v-if="selectedType" class="declare-check-kicker">{{ selectedType.libelle }}</p>
                </div>
                <p v-if="!selectedType" class="declare-muted">
                  Retournez aux informations pour choisir un type d’organisation.
                </p>
                <p v-else-if="preparationRequirements.length === 0" class="declare-muted">
                  Aucun justificatif n’est défini pour ce type.
                </p>
                <template v-else>
                  <p class="declare-muted">{{ preparationIntro }}</p>
                  <ul class="declare-doc-list">
                    <li v-for="requirement in preparationRequirements" :key="requirement.code">
                      <div class="declare-doc-row">
                        <span class="declare-doc-name">{{ labelForRequirementCode(requirement.code) }}</span>
                        <span v-if="!allRequirementsObligatory" class="declare-doc-flag">
                          {{ requirement.obligatoire ? 'Obligatoire' : 'Facultatif' }}
                        </span>
                        <button
                          v-if="requirement.nature_preuve.trim()"
                          type="button"
                          class="declare-doc-toggle focus-ring"
                          :aria-expanded="openRequirementCodes.includes(requirement.code)"
                          :aria-controls="`declare-doc-${requirement.code}`"
                          @click="toggleRequirement(requirement.code)"
                        >
                          Détails
                        </button>
                      </div>
                      <p
                        v-if="requirement.nature_preuve.trim() && openRequirementCodes.includes(requirement.code)"
                        :id="`declare-doc-${requirement.code}`"
                        class="declare-doc-details"
                      >
                        {{ requirement.nature_preuve }}
                      </p>
                    </li>
                  </ul>
                </template>
              </section>

              <OrganizationLookup
                variant="compact"
                :matches="lookupMatches"
                :load-state="lookupDisplayState === 'idle' ? 'loading' : lookupDisplayState"
                :error-message="lookupError"
                :selected-id="selectedMatchId"
                :disabled="submitting"
                @select="onClaim"
                @retry="retryLookup"
              />

              <div class="declare-check-actions">
                <UiButton
                  variant="secondary"
                  :disabled="submitting"
                  @click="wizardStep = 'informations'"
                >
                  Retour
                </UiButton>
                <UiButton
                  v-if="confirmNew && mutationState === 'succeeded' && result?.statut === 'correspondances_possibles'"
                  variant="outline"
                  :loading="submitting"
                  :disabled="submitting"
                  @click="onConfirmNew"
                >
                  Confirmer une nouvelle organisation distincte
                </UiButton>
                <UiButton
                  class="declare-continue"
                  :disabled="submitting || !selectedType"
                  :disabled-reason="!selectedType ? 'Choisissez d’abord un type d’organisation.' : undefined"
                  @click="goToConfirmation"
                >
                  Continuer
                </UiButton>
              </div>
            </div>

            <div
              v-show="wizardStep === 'confirmation' && !declarationFinished"
              class="flex flex-col gap-gutter"
            >
              <DescriptionList
                layout="rows"
                :items="recapItems"
              />

              <FormActions
                align="start"
              >
                <UiButton
                  variant="secondary"
                  :disabled="submitting"
                  @click="wizardStep = 'verification'"
                >
                  Retour au contrôle préalable
                </UiButton>
                <UiButton
                  :loading="submitting"
                  :disabled="submitting"
                  @click="onSubmit"
                >
                  Déclarer cette organisation
                  <UiIcon name="arrow-right" />
                </UiButton>
              </FormActions>
            </div>
          </template>
        </div>

        <FormActions
          v-if="declarationFinished"
          align="start"
        >
          <UiButton
            v-if="canOpenCreatedKyc && result?.organisation?.id"
            @click="navigateTo({ path: '/compte/verification', query: { sujet: result.organisation.id } })"
          >
            Déposer les pièces d’identité
            <UiIcon name="arrow-right" />
          </UiButton>
          <UiButton
            variant="secondary"
            to="/compte/organisation"
          >
            Voir mes organisations
          </UiButton>
        </FormActions>
      </div>
    </template>
    </div>
  </main>
</template>
