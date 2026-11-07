<script setup lang="ts">
import { canHandleSupport } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeSupportDemands } from '../../../composables/identity-access/useBackofficeSupportDemands'
import BackofficeSupportStats from '../../../components/identity-access/backoffice/BackofficeSupportStats.vue'
import BackofficeSupportDirectory from '../../../components/identity-access/backoffice/BackofficeSupportDirectory.vue'
import type { BackofficeSupportTab } from '../../../types/identity-access/backoffice'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Support et recours — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeSupportDemands()

const authorized = computed(() => canHandleSupport(account.value))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeSupportTab>('a_traiter')
const type = ref('')
const priorite = ref('')
const page = ref(1)
const retrying = ref(false)

const statsLoading = computed(() => (
  (loadState.value === 'idle' || loadState.value === 'loading')
  && !list.value
))
const statsUnavailable = computed(() => loadState.value === 'error' && !list.value)

watch(search, (value) => {
  window.setTimeout(() => {
    if (search.value === value) {
      debouncedSearch.value = value
    }
  }, 300)
})

watch([debouncedSearch, tab, type, priorite], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  onglet: tab.value,
  type: type.value,
  priorite: priorite.value,
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
    type: type.value || undefined,
    priorite: priorite.value || undefined,
    page: page.value,
    per_page: 5,
  })
}, { immediate: true })

async function retry() {
  retrying.value = true
  try {
    await refresh({
      q: debouncedSearch.value,
      onglet: tab.value,
      type: type.value || undefined,
      priorite: priorite.value || undefined,
      page: page.value,
      per_page: 5,
    })
  }
  finally {
    retrying.value = false
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
      title="Support et recours."
      description="Traitez les demandes, suivez les affectations et ouvrez le dossier pour les échanges."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La consultation de la file de support exige l’attribution interne traiter_support, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <BackofficeSupportStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :ouvertes="list?.totaux.ouvertes"
        :non-affectes="list?.totaux.non_affectes"
        :priorite-haute="list?.totaux.priorite_haute"
        :reponses-en-retard="list?.totaux.reponses_en_retard"
      />
      <BackofficeSupportDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:type="type"
        v-model:priorite="priorite"
        v-model:page="page"
        :load-state="loadState"
        :demandes="list?.demandes ?? []"
        :totaux="list?.totaux ?? null"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        @retry="retry"
      />
    </template>
  </div>
</template>
