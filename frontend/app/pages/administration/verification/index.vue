<script setup lang="ts">
import { canAccessKycBackoffice } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeKycDossiers } from '../../../composables/identity-access/useBackofficeKycDossiers'
import BackofficeKycStats from '../../../components/identity-access/backoffice/BackofficeKycStats.vue'
import BackofficeKycDirectory from '../../../components/identity-access/backoffice/BackofficeKycDirectory.vue'
import type { BackofficeKycTab } from '../../../types/identity-access/backoffice'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Vérifications KYC — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeKycDossiers()

const authorized = computed(() => canAccessKycBackoffice(account.value))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeKycTab>('a_traiter')
const niveau = ref('')
const responsableId = ref('')
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

watch([debouncedSearch, tab, niveau, responsableId], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  onglet: tab.value,
  niveau: niveau.value,
  responsable_id: responsableId.value,
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
    niveau: niveau.value || undefined,
    responsable_id: responsableId.value || undefined,
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
      niveau: niveau.value || undefined,
      responsable_id: responsableId.value || undefined,
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
      title="Vérifications KYC."
      description="Examinez les justificatifs et suivez les décisions de vérification."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="Le contrôle des dossiers exige une attribution interne (affectation, contrôle ou décision), pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <BackofficeKycStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :a-traiter="list?.totaux.a_traiter"
        :non-affectes="list?.totaux.non_affectes"
        :en-retard="list?.totaux.en_retard"
        :a-corriger="list?.totaux.a_corriger"
      />
      <BackofficeKycDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:niveau="niveau"
        v-model:responsable-id="responsableId"
        v-model:page="page"
        :load-state="loadState"
        :dossiers="list?.dossiers ?? []"
        :totaux="list?.totaux ?? null"
        :responsables="list?.responsables ?? []"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        @retry="retry"
      />
    </template>
  </div>
</template>
