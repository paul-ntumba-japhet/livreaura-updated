<script setup lang="ts">
import { accessScopeOf } from '../../utils/identity-access/account'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useSupportDemand } from '../../composables/identity-access/useSupportDemand'
import { labelForSupportEvidenceType } from '../../utils/identity-access/support'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Recours — Compte — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const {
  loadState,
  replyState,
  attachState,
  downloadState,
  errorMessage,
  replyError,
  attachError,
  downloadError,
  attachFieldErrors,
  sessionEnded,
  demand,
  downloadingId,
  load,
  reply,
  attach,
  download,
} = useSupportDemand()

const retrying = ref(false)
const contenu = ref('')
const selectedFile = ref<File | null>(null)
const evidenceType = ref('identite')

const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const supportOnly = computed(() => scope.value === 'support_only')
const demandKey = computed(() => account.value?.recours?.reference || '')
const canOpen = computed(() => Boolean(demandKey.value) && (supportOnly.value || scope.value === 'normal'))
const replySubmitting = computed(() => replyState.value === 'submitting')
const attachSubmitting = computed(() => attachState.value === 'submitting')

watch(canOpen, (value) => {
  if (value && demandKey.value && (loadState.value === 'idle' || loadState.value === 'error')) {
    void load(demandKey.value)
  }
}, { immediate: true })

async function retry() {
  if (!demandKey.value) {
    return
  }
  retrying.value = true
  try {
    await load(demandKey.value)
  }
  finally {
    retrying.value = false
  }
}

async function sendReply() {
  await reply(contenu.value.trim())
  if (replyState.value === 'succeeded') {
    contenu.value = ''
  }
}

function onSelectFile(file: File) {
  selectedFile.value = file
}

async function deposit() {
  if (!selectedFile.value) {
    return
  }
  await attach(evidenceType.value, selectedFile.value, 'demandeur')
  if (attachState.value === 'succeeded') {
    selectedFile.value = null
  }
}

async function downloadPiece(id: string) {
  const piece = demand.value?.pieces.find(item => item.id === id)
  await download(id, `${labelForSupportEvidenceType(piece?.type || 'piece')}.bin`)
}
</script>

<template>
  <div class="flex w-full flex-col gap-gutter py-gutter lg:py-section">
    <PageHeader
      title="Dossier de recours."
      description="Échangez avec le support et déposez les pièces demandées. Aucune action commerciale n’est proposée ici."
    />

    <p>
      <UiLink to="/compte">Retour au compte</UiLink>
    </p>

    <ForbiddenState
      v-if="account && !canOpen"
      title="Recours indisponible"
      message="Un dossier de recours n’est visible qu’avec une session autorisée, liée à cette demande."
    >
      <template #action>
        <UiLink to="/compte">Retour au compte</UiLink>
      </template>
    </ForbiddenState>
    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="canOpen">
      <Skeleton v-if="loadState === 'idle' || loadState === 'loading'" variant="detail" />
      <ErrorState
        v-else-if="loadState === 'error'"
        title="Impossible de charger le recours"
        :message="errorMessage"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="retry"
      />

      <template v-else-if="demand">
        <section class="rounded-md border border-border bg-surface-raised px-gutter py-stack">
          <h2 class="font-serif text-lg font-semibold text-text">
            Demande
          </h2>
          <DescriptionList
            class="mt-stack"
            :items="[
              { term: 'Référence', value: demand.demande.reference },
              { term: 'Identité', value: demand.demande.identite_etat === 'verifiee' ? 'Vérifiée' : (demand.demande.identite_etat === 'non_verifiee' ? 'Non vérifiée' : (demand.demande.identite_etat === 'refusee' ? 'Refusée' : 'Statut indisponible')) },
            ]"
          />
        </section>

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
          title="Message envoyé"
        >
          Le message a été enregistré. Il n’est pas encore une décision.
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
          :pieces="demand.pieces"
          :can-deposit="demand.capacites.deposer"
          :submitting="attachSubmitting"
          :downloading-id="downloadingId"
          :selected-name="selectedFile?.name"
          :field-error="attachFieldErrors.fichier?.[0]"
          @select="onSelectFile"
          @deposit="deposit"
          @download="downloadPiece"
        />
        <SupportReplyComposer
          v-model:contenu="contenu"
          :can-reply="demand.capacites.repondre"
          :submitting="replySubmitting"
          :readonly="!demand.capacites.repondre"
          @submit="sendReply"
        />
      </template>
    </template>
  </div>
</template>
