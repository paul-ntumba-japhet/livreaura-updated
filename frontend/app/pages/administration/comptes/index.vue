<script setup lang="ts">
import { canCloseAccount, canHandleSupport, canReactivateAccount, canSuspendAccount } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeAccounts } from '../../../composables/identity-access/useBackofficeAccounts'
import BackofficeAccountsStats from '../../../components/identity-access/backoffice/BackofficeAccountsStats.vue'
import BackofficeAccountsDirectory from '../../../components/identity-access/backoffice/BackofficeAccountsDirectory.vue'
import type { AccountDirectoryTab } from '../../../components/identity-access/backoffice/BackofficeAccountsDirectory.vue'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Comptes et accès — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeAccounts()

const authorized = computed(() => (
  canSuspendAccount(account.value)
  || canReactivateAccount(account.value)
  || canCloseAccount(account.value)
  || canHandleSupport(account.value)
))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<AccountDirectoryTab>('all')
const role = ref('')
const emailVerified = ref('')
const page = ref(1)
const lookupError = ref('')
const retrying = ref(false)

const statut = computed(() => (tab.value === 'all' ? '' : tab.value))
const statsLoading = computed(() => (
  (loadState.value === 'idle' || loadState.value === 'loading')
  && !list.value
))
const statsUnavailable = computed(() => loadState.value === 'error' && !list.value)

watch(search, (value) => {
  lookupError.value = ''
  window.setTimeout(() => {
    if (search.value === value) {
      debouncedSearch.value = value
    }
  }, 300)
})

watch([debouncedSearch, tab, role, emailVerified], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  statut: statut.value,
  role: role.value,
  email_verifie: emailVerified.value,
  page: page.value,
  authorized: authorized.value,
}))

watch(requestKey, () => {
  if (!authorized.value) {
    return
  }
  void refresh({
    q: debouncedSearch.value,
    statut: statut.value || undefined,
    role: role.value || undefined,
    email_verifie: emailVerified.value || undefined,
    page: page.value,
  })
}, { immediate: true })

async function retry() {
  retrying.value = true
  try {
    await refresh({
      q: debouncedSearch.value,
      statut: statut.value || undefined,
      role: role.value || undefined,
      email_verifie: emailVerified.value || undefined,
      page: page.value,
    })
  }
  finally {
    retrying.value = false
  }
}

function openAccount() {
  lookupError.value = ''
  if (!authorized.value) {
    return
  }
  const id = search.value.trim().toUpperCase()
  if (id.length !== 26) {
    lookupError.value = 'Indiquez la référence de suivi du compte pour l’ouvrir directement.'
    return
  }
  return navigateTo(`/administration/comptes/${id}`)
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
      title="Comptes et accès."
      description="Consultez les comptes, leurs rôles et leurs autorisations d’accès."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La suspension, la réactivation, la vérification d’une clôture ou la clôture d’un compte exige une attribution interne distincte, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <BackofficeAccountsStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :total="list?.totaux.total"
        :active="list?.totaux.active"
        :pending="list?.totaux.pending"
        :suspended="list?.totaux.suspended"
      />
      <BackofficeAccountsDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:role="role"
        v-model:email-verified="emailVerified"
        v-model:page="page"
        :load-state="loadState"
        :accounts="list?.comptes ?? []"
        :totaux="list?.totaux ?? null"
        :pagination="list?.pagination ?? null"
        :error-message="errorMessage"
        :retrying="retrying"
        :lookup-error="lookupError"
        @open="openAccount"
        @retry="retry"
      />
    </template>
  </div>
</template>
