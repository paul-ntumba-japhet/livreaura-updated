<script setup lang="ts">
import { canRetryTransmissions } from '../../../utils/identity-access/account'
import {
  formatLastSeen,
  labelForAcknowledgement,
  labelForAttemptResult,
  labelForOutgoingMessageType,
  labelForSupervisionState,
  labelForTransmissionCanal,
  toneForSupervisionState,
} from '../../../utils/identity-access/backoffice'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeSupervision } from '../../../composables/identity-access/useBackofficeSupervision'
import { useServiceExploitation } from '../../../composables/identity-access/useServiceExploitation'
import BackofficeSupervisionStats from '../../../components/identity-access/backoffice/BackofficeSupervisionStats.vue'
import BackofficeSupervisionDirectory from '../../../components/identity-access/backoffice/BackofficeSupervisionDirectory.vue'
import type { BackofficeSupervisionTab, SupervisionTransmission } from '../../../types/identity-access/backoffice'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Supervision et historique — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeSupervision()
const exploitation = useServiceExploitation()

const authorized = computed(() => canRetryTransmissions(account.value))
const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeSupervisionTab>('supervision')
const canal = ref('')
const etat = ref('')
const jour = ref('aujourdhui')
const page = ref(1)
const retrying = ref(false)
const examining = ref<SupervisionTransmission | null>(null)

const statsLoading = computed(() => (
  (loadState.value === 'idle' || loadState.value === 'loading')
  && !list.value
))
const statsUnavailable = computed(() => loadState.value === 'error' && !list.value)
const ended = computed(() => sessionEnded.value || exploitation.sessionEnded.value)
const retrySubmitting = computed(() => exploitation.retryState.value === 'submitting')
const canRetryExamined = computed(() => (
  Boolean(examining.value?.eligible_reprise) && authorized.value
))

watch(search, (value) => {
  window.setTimeout(() => {
    if (search.value === value) {
      debouncedSearch.value = value
    }
  }, 300)
})

watch([debouncedSearch, tab, canal, etat, jour], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  onglet: tab.value,
  canal: canal.value,
  etat: etat.value,
  jour: jour.value,
  page: page.value,
  authorized: authorized.value,
}))

watch(requestKey, () => {
  if (!authorized.value) {
    return
  }
  void refresh({
    q: debouncedSearch.value,
    onglet: tab.value,
    canal: canal.value || undefined,
    etat: etat.value || undefined,
    jour: jour.value,
    page: page.value,
    per_page: 5,
  })
}, { immediate: true })

async function retryLoad() {
  retrying.value = true
  try {
    await refresh({
      q: debouncedSearch.value,
      onglet: tab.value,
      canal: canal.value || undefined,
      etat: etat.value || undefined,
      jour: jour.value,
      page: page.value,
      per_page: 5,
    })
  }
  finally {
    retrying.value = false
  }
}

function examine(row: SupervisionTransmission) {
  examining.value = row
}

async function confirmRetry() {
  if (!examining.value || retrySubmitting.value || !canRetryExamined.value) {
    return
  }
  const id = examining.value.id
  await exploitation.retry(id)
  if (exploitation.retryState.value === 'succeeded') {
    examining.value = null
    await retryLoad()
  }
  else if (exploitation.retryState.value !== 'submitting') {
    await retryLoad()
    const next = list.value?.transmissions.find(item => item.id === id) ?? null
    examining.value = next
  }
}
</script>

<template>
  <div class="flex flex-col gap-gutter px-page py-gutter lg:py-section">
    <Breadcrumbs
      :items="[
        { label: 'Administration', to: '/administration' },
        { label: 'Identité et accès' },
      ]"
    />

    <PageHeader
      title="Supervision et historique."
      description="Surveillez les transmissions, le journal des interventions et les reprises d’envoi."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La consultation de la supervision exige l’attribution interne reprendre_transmissions."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="ended" />

    <template v-else-if="authorized">
      <BackofficeSupervisionStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :echecs="list?.totaux.echecs"
        :attente-accuse="list?.totaux.attente_accuse"
        :messages-en-attente="list?.totaux.messages_en_attente"
        :evenements-aujourdhui="list?.totaux.evenements_aujourdhui"
      />
      <InlineAlert
        v-if="exploitation.retryState.value === 'succeeded' && exploitation.retryResult.value"
        tone="success"
        title="Reprise enregistrée"
      >
        L’envoi a été remis en file. Cette action ne rejoue pas une décision.
      </InlineAlert>
      <InlineAlert
        v-else-if="exploitation.retryState.value === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        La reprise n’a pas pu être confirmée. Consultez l’état de la transmission avant de réessayer.
      </InlineAlert>
      <InlineAlert
        v-else-if="exploitation.retryState.value === 'failed' && exploitation.retryError.value"
        tone="error"
        title="Reprise refusée"
      >
        {{ exploitation.retryError.value }}
      </InlineAlert>
      <BackofficeSupervisionDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:canal="canal"
        v-model:etat="etat"
        v-model:jour="jour"
        v-model:page="page"
        :load-state="loadState"
        :transmissions="list?.transmissions ?? []"
        :evenements="list?.evenements ?? []"
        :reprises="list?.reprises ?? []"
        :journal-recent="list?.journal_recent ?? []"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        @examine="examine"
        @retry="retryLoad"
      />
    </template>

    <ConfirmDialog
      :open="Boolean(examining)"
      :title="examining ? `Examiner ${examining.reference || 'cette transmission'}` : 'Examiner la transmission'"
      :confirm-label="canRetryExamined ? 'Reprendre l’envoi' : 'Fermer'"
      :submitting="retrySubmitting"
      @confirm="canRetryExamined ? confirmRetry() : examining = null"
      @cancel="examining = null"
    >
      <div
        v-if="examining"
        class="flex flex-col gap-stack font-sans text-sm text-text"
      >
        <p>
          {{ labelForOutgoingMessageType(examining.type) }}
          vers
          {{ examining.destination_masquee || 'une destination masquée' }}
          ({{ labelForTransmissionCanal(examining.canal) }}).
        </p>
        <StatusBadge
          :label="labelForSupervisionState(examining.etat)"
          :tone="toneForSupervisionState(examining.etat)"
        />
        <p class="text-text-muted">
          Dernière tentative :
          {{ examining.derniere_tentative_a ? formatLastSeen(examining.derniere_tentative_a) : 'aucune' }}.
          Tentatives : {{ examining.nombre_tentatives }}.
          {{ examining.niveau_acquittement ? `Acquittement : ${labelForAcknowledgement(examining.niveau_acquittement)}.` : '' }}
        </p>
        <ul
          v-if="examining.tentatives.length > 0"
          class="flex flex-col gap-stack"
        >
          <li
            v-for="attempt in examining.tentatives"
            :key="`${examining.id}-${attempt.numero}`"
            class="text-text-muted"
          >
            Tentative {{ attempt.numero }} · {{ labelForAttemptResult(attempt.resultat) }}
            <span v-if="attempt.code_erreur_masque"> · {{ attempt.code_erreur_masque }}</span>
          </li>
        </ul>
        <p class="text-text-muted">
          Une reprise remet le même envoi en file. Elle ne rejoue pas une décision.
        </p>
      </div>
    </ConfirmDialog>
  </div>
</template>
