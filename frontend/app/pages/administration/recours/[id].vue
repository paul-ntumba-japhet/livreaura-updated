<script setup lang="ts">
import { canAuthorizeAppeal, canHandleSupport } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useAuthorizeAppealDocument } from '../../../composables/identity-access/useAuthorizeAppealDocument'
import { useAuthorizeAppealSession } from '../../../composables/identity-access/useAuthorizeAppealSession'
import { useSupportDemand } from '../../../composables/identity-access/useSupportDemand'
import { labelForSupportEvidenceType, labelForSupportType } from '../../../utils/identity-access/support'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

const route = useRoute()
const demandKey = computed(() => decodeURIComponent(String(route.params.id || '')).trim())

useHead({
  title: computed(() => (
    demandKey.value
      ? `${demandKey.value} — Support et recours — Administration — Livreaura`
      : 'Support et recours — Administration — Livreaura'
  )),
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { mutationState, errorMessage, sessionEnded, result, authorize } = useAuthorizeAppealSession()
const {
  mutationState: grantState,
  errorMessage: grantError,
  fieldErrors: grantFieldErrors,
  sessionEnded: grantSessionEnded,
  result: grantResult,
  authorize: authorizeDocument,
} = useAuthorizeAppealDocument()
const {
  loadState: threadState,
  replyState,
  attachState,
  downloadState,
  errorMessage: threadError,
  replyError,
  attachError,
  downloadError,
  attachFieldErrors,
  sessionEnded: threadSessionEnded,
  demand,
  downloadingId,
  load: loadDemand,
  reply,
  attach,
  download,
} = useSupportDemand()

const authorized = computed(() => canHandleSupport(account.value))
const canAuthorize = computed(() => canAuthorizeAppeal(account.value))
const compteId = ref('')
const motif = ref('')
const methodeVerification = ref('pieces')
const confirmOpen = ref(false)

const grantDocumentType = ref('facture_vente')
const grantDocumentId = ref('')
const grantDebut = ref('')
const grantFin = ref('')
const grantMotif = ref('')
const confirmGrantOpen = ref(false)
const replyContenu = ref('')
const replyVisibilite = ref('demandeur')
const evidenceType = ref('identite')
const evidenceInternal = ref(false)
const selectedFile = ref<File | null>(null)
const threadRetrying = ref(false)

const isSelf = computed(() => Boolean(account.value?.compte?.id && account.value.compte.id === compteId.value.trim().toUpperCase()))
const submitting = computed(() => mutationState.value === 'submitting')
const grantSubmitting = computed(() => grantState.value === 'submitting')
const canOpen = computed(() => demandKey.value.length >= 8)
const ended = computed(() => sessionEnded.value || grantSessionEnded.value || threadSessionEnded.value)
const replySubmitting = computed(() => replyState.value === 'submitting')
const attachSubmitting = computed(() => attachState.value === 'submitting')

function isoFromLocal(value: string): string | null {
  const trimmed = value.trim()
  if (!trimmed) {
    return null
  }
  const date = new Date(trimmed)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  return date.toISOString()
}

function formatDate(iso: string | null | undefined): string {
  if (!iso) {
    return 'Indisponible'
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return 'Indisponible'
  }
  const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
  return `${new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone,
  }).format(date)} (${timeZone})`
}

function openConfirm() {
  if (!canAuthorize.value || isSelf.value || submitting.value) {
    return
  }
  confirmOpen.value = true
}

async function confirm() {
  if (submitting.value) {
    return
  }
  await authorize(demandKey.value, {
    compte_id: compteId.value.trim().toUpperCase(),
    motif: motif.value.trim(),
    methode_verification: methodeVerification.value.trim(),
  })
  if (mutationState.value !== 'submitting') {
    confirmOpen.value = false
  }
}

function documentPayload() {
  const id = grantDocumentId.value.trim().toUpperCase()
  const type = grantDocumentType.value
  return {
    facture_vente_id: type === 'facture_vente' ? id : null,
    facture_commission_id: type === 'facture_commission' ? id : null,
    avoir_id: type === 'avoir' ? id : null,
    recu_paiement_id: type === 'recu_paiement' ? id : null,
  }
}

function openGrantConfirm() {
  if (!canAuthorize.value || isSelf.value || grantSubmitting.value) {
    return
  }
  confirmGrantOpen.value = true
}

async function confirmGrant() {
  if (grantSubmitting.value) {
    return
  }
  const debut = isoFromLocal(grantDebut.value)
  const fin = isoFromLocal(grantFin.value)
  if (!debut || !fin) {
    return
  }
  await authorizeDocument(demandKey.value, {
    compte_id: compteId.value.trim().toUpperCase(),
    ...documentPayload(),
    debut,
    fin,
    motif: grantMotif.value.trim(),
  })
  if (grantState.value !== 'submitting') {
    confirmGrantOpen.value = false
  }
}

const grantSummaryItems = computed(() => {
  if (!grantResult.value) {
    return []
  }
  return [
    { term: 'Dossier', value: grantResult.value.demande.reference },
    { term: 'Document', value: `${grantResult.value.acces.document.type} · ${grantResult.value.acces.document.id}` },
    { term: 'Début', value: formatDate(grantResult.value.acces.debut) },
    { term: 'Fin', value: formatDate(grantResult.value.acces.fin) },
  ]
})

watch([demandKey, authorized], ([value, canLoad]) => {
  if (canLoad && value.length >= 8) {
    void loadDemand(value)
  }
}, { immediate: true })

async function retryThread() {
  threadRetrying.value = true
  try {
    await loadDemand(demandKey.value)
  }
  finally {
    threadRetrying.value = false
  }
}

async function sendReply() {
  const visibility = replyVisibilite.value === 'interne' ? 'interne' as const : 'demandeur' as const
  await reply(replyContenu.value.trim(), visibility)
  if (replyState.value === 'succeeded') {
    replyContenu.value = ''
  }
}

function onSelectFile(file: File) {
  selectedFile.value = file
}

async function deposit() {
  if (!selectedFile.value) {
    return
  }
  await attach(evidenceType.value, selectedFile.value, evidenceInternal.value ? 'interne' : 'demandeur')
  if (attachState.value === 'succeeded') {
    selectedFile.value = null
    evidenceInternal.value = false
  }
}

async function downloadPiece(id: string) {
  const piece = demand.value?.pieces.find(item => item.id === id)
  await download(id, `${labelForSupportEvidenceType(piece?.type || 'piece')}.bin`)
}
</script>

<template>
  <div class="flex max-w-4xl flex-col gap-section px-page py-section">
    <Breadcrumbs
      :items="[
        { label: 'Administration', to: '/administration' },
        { label: 'Support et recours', to: '/administration/recours' },
        { label: demand?.demande.reference || demandKey || 'Dossier' },
      ]"
    />

    <PageHeader
      :title="demand?.demande.reference ? `${demand.demande.reference}.` : 'Dossier de support.'"
      context="Espace interne"
      :description="demand
        ? labelForSupportType(demand.demande.type)
        : 'Consultez les échanges, autorisez une session de recours après vérification d’identité, puis un document commercial exact.'"
    />

    <p>
      <UiLink to="/administration/recours">Retour à la file</UiLink>
    </p>

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="Le traitement d’un recours exige l’attribution interne traiter_support, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration/recours">Retour à la file</UiLink>
      </template>
    </ForbiddenState>
    <SessionExpiredState v-else-if="ended" />

    <template v-else-if="authorized">
      <InlineAlert
        v-if="mutationState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ errorMessage }} Consultez l’état de la demande avant de répéter l’autorisation.
      </InlineAlert>
      <InlineAlert
        v-else-if="mutationState === 'failed' && errorMessage"
        tone="error"
        title="Autorisation refusée"
      >
        {{ errorMessage }}
      </InlineAlert>
      <InlineAlert
        v-else-if="mutationState === 'succeeded' && result"
        tone="success"
        title="Session de recours autorisée"
      >
        Référence {{ result.demande.reference }}. Le titulaire doit se connecter pour ouvrir une session limitée. Aucun jeton n’a été transmis par e-mail.
      </InlineAlert>

      <EmptyState
        v-if="!canOpen"
        title="Référence incomplète"
        message="La référence de suivi est trop courte pour ouvrir un dossier."
      >
        <template #action>
          <UiLink to="/administration/recours">Retour à la file</UiLink>
        </template>
      </EmptyState>
      <ErrorState
        v-else-if="threadState === 'error'"
        title="Impossible de charger les échanges"
        :message="threadError"
        retry-label="Réessayer"
        :retrying="threadRetrying"
        @retry="retryThread"
      />
      <Skeleton
        v-else-if="threadState === 'idle' || threadState === 'loading'"
        variant="detail"
      />
      <template v-else-if="demand">
        <InlineAlert
          v-if="replyState === 'uncertain'"
          tone="warning"
          title="Résultat incertain"
        >
          {{ replyError }} Consultez les échanges avant de renvoyer le message.
        </InlineAlert>
        <InlineAlert
          v-else-if="replyState === 'failed' && replyError"
          tone="error"
          title="Message refusé"
        >
          {{ replyError }}
        </InlineAlert>
        <InlineAlert
          v-else-if="replyState === 'succeeded'"
          tone="success"
          title="Message enregistré"
        >
          Le message a été ajouté au dossier. Ce n’est pas une décision.
        </InlineAlert>
        <InlineAlert
          v-if="attachState === 'uncertain'"
          tone="warning"
          title="Résultat incertain"
        >
          {{ attachError }} Consultez les pièces avant de redéposer le fichier.
        </InlineAlert>
        <InlineAlert
          v-else-if="attachState === 'failed' && attachError"
          tone="error"
          title="Dépôt refusé"
        >
          {{ attachError }}
        </InlineAlert>
        <InlineAlert
          v-else-if="attachState === 'succeeded'"
          tone="success"
          title="Pièce déposée"
        >
          Le fichier a été conservé. Ce n’est pas encore un contrôle d’identité.
        </InlineAlert>
        <InlineAlert
          v-if="downloadState === 'failed' && downloadError"
          tone="error"
          title="Téléchargement refusé"
        >
          {{ downloadError }}
        </InlineAlert>
        <SupportConversation :messages="demand.echanges" />
        <SupportEvidencePanel
          v-model:evidence-type="evidenceType"
          v-model:internal="evidenceInternal"
          :pieces="demand.pieces"
          :can-deposit="demand.capacites.deposer"
          :can-internal="demand.capacites.notes_internes"
          :submitting="attachSubmitting"
          :downloading-id="downloadingId"
          :selected-name="selectedFile?.name"
          :field-error="attachFieldErrors.fichier?.[0]"
          @select="onSelectFile"
          @deposit="deposit"
          @download="downloadPiece"
        />
        <SupportReplyComposer
          v-model:contenu="replyContenu"
          v-model:visibilite="replyVisibilite"
          :can-reply="demand.capacites.repondre"
          :can-internal="demand.capacites.notes_internes"
          :submitting="replySubmitting"
          :readonly="!demand.capacites.repondre"
          @submit="sendReply"
        />
      </template>

      <SupportSessionAuthorization
        v-if="canAuthorize && mutationState !== 'succeeded' && canOpen"
        v-model:compte-id="compteId"
        v-model:motif="motif"
        v-model:methode-verification="methodeVerification"
        :can-authorize="canAuthorize"
        :is-self="isSelf"
        :submitting="submitting"
        @submit="openConfirm"
      />

      <InlineAlert
        v-if="grantState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ grantError }} Consultez l’état de la demande avant de répéter le grant.
      </InlineAlert>
      <InlineAlert
        v-else-if="grantState === 'failed' && grantError"
        tone="error"
        title="Grant refusé"
      >
        {{ grantError }}
      </InlineAlert>
      <InlineAlert
        v-else-if="grantState === 'succeeded' && grantResult"
        tone="success"
        title="Document autorisé"
      >
        Le grant est enregistré jusqu’à {{ formatDate(grantResult.acces.fin) }}. Le téléchargement n’est pas ouvert depuis cet écran.
      </InlineAlert>

      <section
        v-if="grantResult"
        class="rounded-lg border border-border bg-surface-raised px-gutter py-stack"
      >
        <h2 class="font-serif text-lg font-semibold text-text">
          Grant consigné
        </h2>
        <DescriptionList
          class="mt-stack"
          :items="grantSummaryItems"
        />
      </section>

      <DocumentAccessGrantForm
        v-if="canAuthorize && canOpen && grantState !== 'succeeded'"
        v-model:document-type="grantDocumentType"
        v-model:document-id="grantDocumentId"
        v-model:compte-id="compteId"
        v-model:debut="grantDebut"
        v-model:fin="grantFin"
        v-model:motif="grantMotif"
        :can-authorize="authorized"
        :is-self="isSelf"
        :submitting="grantSubmitting"
        :field-errors="grantFieldErrors"
        @submit="openGrantConfirm"
      />

      <ConfirmDialog
        :open="confirmOpen"
        title="Confirmer l’autorisation"
        confirm-label="Autoriser la session de recours"
        :submitting="submitting"
        @confirm="confirm"
        @cancel="confirmOpen = false"
      >
        <p>
          Une session de recours sera créée pour le compte indiqué. Elle ne permet ni paiement, ni publication, ni lecture normale. Cette action attend la confirmation du serveur.
        </p>
      </ConfirmDialog>

      <ConfirmDialog
        :open="confirmGrantOpen"
        title="Confirmer le grant documentaire"
        confirm-label="Autoriser ce document"
        :submitting="grantSubmitting"
        @confirm="confirmGrant"
        @cancel="confirmGrantOpen = false"
      >
        <p>
          Un accès temporaire sera consigné pour ce document exact. Le téléchargement reste contrôlé à chaque usage et n’est pas proposé ici. Cette action attend la confirmation du serveur.
        </p>
      </ConfirmDialog>
    </template>
  </div>
</template>
