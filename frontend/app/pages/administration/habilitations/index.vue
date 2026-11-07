<script setup lang="ts">
import { canAccessKycBackoffice, canManageInternalPermissions } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeCapabilities } from '../../../composables/identity-access/useBackofficeCapabilities'
import BackofficeCapabilitiesStats from '../../../components/identity-access/backoffice/BackofficeCapabilitiesStats.vue'
import BackofficeCapabilitiesDirectory from '../../../components/identity-access/backoffice/BackofficeCapabilitiesDirectory.vue'
import type { BackofficeCapabilityTab } from '../../../types/identity-access/backoffice'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Habilitations — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeCapabilities()

const authorized = computed(() => canAccessKycBackoffice(account.value))
const canManageInternal = computed(() => canManageInternalPermissions(account.value))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeCapabilityTab>('toutes')
const niveau = ref('')
const echeance = ref('')
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

watch([debouncedSearch, tab, niveau, echeance], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

watch([account, authorized, canManageInternal], ([current, kyc, intern]) => {
  if (current && !kyc && intern) {
    void navigateTo('/administration/habilitations/internes')
  }
}, { immediate: true })

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  onglet: tab.value,
  niveau: niveau.value,
  echeance: echeance.value,
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
    echeance: echeance.value || undefined,
    page: page.value,
    per_page: 6,
  })
}, { immediate: true })

async function retry() {
  retrying.value = true
  try {
    await refresh({
      q: debouncedSearch.value,
      onglet: tab.value,
      niveau: niveau.value || undefined,
      echeance: echeance.value || undefined,
      page: page.value,
      per_page: 6,
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
      title="Habilitations."
      description="Suivez les droits accordés, leur validité et les renouvellements."
    />

    <ForbiddenState
      v-if="account && !authorized && !canManageInternal"
      title="Attribution insuffisante"
      message="La consultation des habilitations de vérification exige une attribution interne (contrôle, décision, affectation ou restriction), pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <BackofficeCapabilitiesStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :valides="list?.totaux.valides"
        :expirent-30j="list?.totaux.expirent_30j"
        :restreintes="list?.totaux.restreintes"
        :expirees="list?.totaux.expirees"
      />
      <BackofficeCapabilitiesDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:niveau="niveau"
        v-model:echeance="echeance"
        v-model:page="page"
        :load-state="loadState"
        :habilitations="list?.habilitations ?? []"
        :totaux="list?.totaux ?? null"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        :can-manage-internal="canManageInternal"
        @retry="retry"
      />
    </template>
  </div>
</template>
