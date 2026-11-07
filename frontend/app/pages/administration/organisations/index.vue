<script setup lang="ts">
import { canAccessOrganizationBackoffice, canTransferPrincipal } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeOrganizations } from '../../../composables/identity-access/useBackofficeOrganizations'
import BackofficeOrganizationsStats from '../../../components/identity-access/backoffice/BackofficeOrganizationsStats.vue'
import BackofficeOrganizationsDirectory from '../../../components/identity-access/backoffice/BackofficeOrganizationsDirectory.vue'
import type { BackofficeOrganizationTab } from '../../../types/identity-access/backoffice'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Organisations et représentants — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeOrganizations()

const authorized = computed(() => canAccessOrganizationBackoffice(account.value))
const canTransfer = computed(() => canTransferPrincipal(account.value))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeOrganizationTab>('organisations')
const typeId = ref('')
const verification = ref('')
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

watch([debouncedSearch, tab, typeId, verification], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  onglet: tab.value,
  type_organisation_id: typeId.value,
  verification: tab.value === 'organisations' ? verification.value : '',
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
    type_organisation_id: typeId.value || undefined,
    verification: tab.value === 'organisations' ? (verification.value || undefined) : undefined,
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
      type_organisation_id: typeId.value || undefined,
      verification: tab.value === 'organisations' ? (verification.value || undefined) : undefined,
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
      title="Organisations et représentants."
      description="Consultez les organisations, leurs représentants et les demandes en cours."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La consultation des organisations et le transfert de représentant exigent une attribution interne distincte, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <BackofficeOrganizationsStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :organisations="list?.totaux.organisations"
        :verifiees="list?.totaux.verifiees"
        :rattachements="list?.totaux.rattachements"
        :transferts="list?.totaux.transferts"
      />
      <BackofficeOrganizationsDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:type-id="typeId"
        v-model:verification="verification"
        v-model:page="page"
        :load-state="loadState"
        :organisations="list?.organisations ?? []"
        :demandes="list?.demandes ?? []"
        :types="list?.types ?? []"
        :totaux="list?.totaux ?? null"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        :can-transfer="canTransfer"
        @retry="retry"
      />
    </template>
  </div>
</template>
