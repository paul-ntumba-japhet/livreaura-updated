<script setup lang="ts">
import {
  accessScopeOf,
  accountStatusOf,
  displayNameForAccount,
} from '../../utils/identity-access/account'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Votre compte — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account, loadState, errorMessage, refresh } = useCurrentAccount()

const status = computed(() => (account.value ? accountStatusOf(account.value) : 'unknown'))
const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const title = computed(() => {
  if (!account.value) {
    return 'Votre compte.'
  }
  const name = displayNameForAccount(account.value)
  return name === 'Compte' ? 'Votre compte.' : `Bonjour, ${name}.`
})
const supportOnly = computed(() => scope.value === 'support_only')

const retrying = ref(false)

async function loadAccount() {
  await refresh({ allowAnonymous: false })
  if (loadState.value === 'anonymous') {
    return navigateTo({
      path: '/connexion',
      query: { origine: 'session' },
    })
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

onMounted(() => {
  void loadAccount()
})
</script>

<template>
  <main class="account-home flex w-full flex-col gap-6">
    <PageHeader
      :title="title"
      :loading="!account && (loadState === 'idle' || loadState === 'loading')"
    />

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
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Skeleton v-for="index in 4" :key="index" variant="card" />
      </div>
    </div>

    <template v-else-if="account">
      <RestrictionBanner
        v-if="status === 'unknown'"
        title="Statut indisponible"
      >
        Le statut du compte n’est pas reconnu. Les décisions qui en dépendent restent bloquées.
      </RestrictionBanner>
      <RestrictionBanner
        v-else-if="status === 'pending'"
        title="Activation encore nécessaire"
      >
        Confirmez l’adresse e-mail avant d’utiliser les fonctions du compte.
      </RestrictionBanner>
      <RestrictionBanner
        v-else-if="supportOnly"
        title="Accès restreint"
      >
        Seuls les recours et documents explicitement autorisés restent accessibles.
      </RestrictionBanner>

      <AccountSummaryStrip
        :account="account"
        :status="status"
        :scope="scope"
      />
    </template>
  </main>
</template>
