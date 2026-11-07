<script setup lang="ts">
import type { AccountOrganization, AccountVerificationDossier } from '../../../types/identity-access/account'
import { accessScopeOf, accountStatusOf, countryLabel } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { normalizeDossierStatus } from '../../../types/identity-access/verification'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Mes organisations — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account, loadState, errorMessage, refreshing, refresh } = useCurrentAccount()
const retrying = ref(false)
const query = ref('')
const typeFilter = ref('all')
const statusFilter = ref('all')
const countryFilter = ref('all')
const sortOrder = ref('az')

const status = computed(() => (account.value ? accountStatusOf(account.value) : 'unknown'))
const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const organisations = computed(() => account.value?.organisations ?? [])
const dossiers = computed(() => account.value?.dossiers_verification ?? [])
const canCreate = computed(() => status.value === 'active' && scope.value === 'normal')
const unknownStatus = computed(() => status.value === 'unknown')
const supportOnly = computed(() => scope.value === 'support_only')

const filtersOpen = ref(false)

const typeOptions = computed(() => {
  const seen = new Map<string, string>()
  for (const item of organisations.value) {
    if (item.type?.id) {
      seen.set(item.type.id, item.type.libelle)
    }
  }
  return [
    { value: 'all', label: 'Tous les types' },
    ...[...seen.entries()].map(([value, label]) => ({ value, label })),
  ]
})
const statusOptions = computed(() => {
  const seen = new Set(organisations.value.map(item => item.statut_operationnel).filter(Boolean))
  return [
    { value: 'all', label: 'Tous les statuts' },
    ...[...seen].map(value => ({
      value,
      label: value === 'active' ? 'Active' : (value === 'restricted' ? 'Restreinte' : (value === 'closed' ? 'Clôturée' : 'Statut indisponible')),
    })),
  ]
})
const countryOptions = computed(() => {
  const seen = new Map<string, string>()
  for (const item of organisations.value) {
    seen.set(item.pays, countryLabel(item.pays) || item.pays)
  }
  return [
    { value: 'all', label: 'Pays' },
    ...[...seen.entries()].map(([value, label]) => ({ value, label })),
  ]
})
const sortOptions = [
  { value: 'az', label: 'Trier par nom' },
  { value: 'za', label: 'Nom (Z → A)' },
]

const filteredOrganisations = computed(() => {
  const needle = query.value.trim().toLocaleLowerCase('fr')
  const rows = organisations.value.filter((item) => {
    if (typeFilter.value !== 'all' && item.type?.id !== typeFilter.value) {
      return false
    }
    if (statusFilter.value !== 'all' && item.statut_operationnel !== statusFilter.value) {
      return false
    }
    if (countryFilter.value !== 'all' && item.pays !== countryFilter.value) {
      return false
    }
    if (!needle) {
      return true
    }
    const haystack = [
      item.raison_sociale,
      item.type?.libelle ?? '',
      item.ville ?? '',
      countryLabel(item.pays) || item.pays,
    ].join(' ').toLocaleLowerCase('fr')
    return haystack.includes(needle)
  })

  const sorted = [...rows].sort((left, right) => {
    return left.raison_sociale.localeCompare(right.raison_sociale, 'fr', { sensitivity: 'base' })
  })
  return sortOrder.value === 'za' ? sorted.reverse() : sorted
})

function dossierFor(organization: AccountOrganization): AccountVerificationDossier | null {
  const matches = dossiers.value.filter(item => item.sujet.organisation_id === organization.id)
  const inProgress = matches.find((item) => {
    const dossierStatus = normalizeDossierStatus(item.statut)
    return dossierStatus === 'draft' || dossierStatus === 'submitted' || dossierStatus === 'under_review'
  })
  return inProgress ?? matches[0] ?? null
}

function canDeposit(organization: AccountOrganization): boolean {
  return organization.adhesion.statut === 'active' && organization.adhesion.permissions.includes('deposer_kyc')
}

const showTools = computed(() => organisations.value.length > 1)
const showTypeFilter = computed(() => typeOptions.value.length > 2)
const showStatusFilter = computed(() => statusOptions.value.length > 2)
const showCountryFilter = computed(() => countryOptions.value.length > 2)
const countLabel = computed(() => {
  const count = filteredOrganisations.value.length
  return count === 1 ? '1 organisation' : `${count} organisations`
})
const activeFilters = computed(() => {
  const chips: Array<{ id: string, label: string, clear: () => void }> = []
  if (typeFilter.value !== 'all') {
    const label = typeOptions.value.find(option => option.value === typeFilter.value)?.label || typeFilter.value
    chips.push({ id: 'type', label, clear: () => { typeFilter.value = 'all' } })
  }
  if (statusFilter.value !== 'all') {
    const label = statusOptions.value.find(option => option.value === statusFilter.value)?.label || statusFilter.value
    chips.push({ id: 'status', label, clear: () => { statusFilter.value = 'all' } })
  }
  if (countryFilter.value !== 'all') {
    const label = countryOptions.value.find(option => option.value === countryFilter.value)?.label || countryFilter.value
    chips.push({ id: 'country', label, clear: () => { countryFilter.value = 'all' } })
  }
  if (sortOrder.value !== 'az') {
    chips.push({ id: 'sort', label: 'Nom (Z → A)', clear: () => { sortOrder.value = 'az' } })
  }
  const needle = query.value.trim()
  if (needle !== '') {
    chips.push({ id: 'query', label: needle, clear: () => { query.value = '' } })
  }
  return chips
})

function resetFilters() {
  query.value = ''
  typeFilter.value = 'all'
  statusFilter.value = 'all'
  countryFilter.value = 'all'
  sortOrder.value = 'az'
}

async function loadAccount() {
  await refresh({ allowAnonymous: false })
  if (loadState.value === 'anonymous') {
    return navigateTo('/connexion')
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
  <main class="org-page flex w-full flex-col gap-6">
    <div class="md:hidden">
      <StepBackAction label="Retour au compte" to="/compte" />
    </div>
    <PageHeader
      title="Mes organisations"
      :loading="!account && (loadState === 'idle' || loadState === 'loading')"
    >
      <template v-if="canCreate" #actions>
        <UiButton class="org-declare" to="/compte/organisation/declarer">
          <UiIcon name="plus" />
          Déclarer une organisation
        </UiButton>
      </template>
    </PageHeader>

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
        Seuls les recours et documents explicitement autorisés restent accessibles.
      </RestrictionBanner>

      <div v-if="showTools" class="org-toolbar">
        <div class="org-search">
          <label class="sr-only" for="organisation-search">Rechercher une organisation</label>
          <TextInput
            id="organisation-search"
            v-model="query"
            icon="search"
            placeholder="Rechercher une organisation"
          />
        </div>
        <button
          type="button"
          class="org-filter-toggle focus-ring"
          :aria-expanded="filtersOpen ? 'true' : 'false'"
          aria-controls="org-filter-panel"
          @click="filtersOpen = !filtersOpen"
        >
          Filtres
        </button>
        <p id="org-count" class="org-count">{{ countLabel }}</p>
      </div>
      <p v-else-if="organisations.length === 1" id="org-count" class="org-count">{{ countLabel }}</p>

      <div
        v-if="showTools && filtersOpen"
        id="org-filter-panel"
        class="org-filter-panel"
      >
        <div v-if="showTypeFilter">
          <label class="org-filter-label" for="organisation-type-filter">Type</label>
          <SelectInput
            id="organisation-type-filter"
            v-model="typeFilter"
            :options="typeOptions"
          />
        </div>
        <div v-if="showStatusFilter">
          <label class="org-filter-label" for="organisation-status-filter">Statut</label>
          <SelectInput
            id="organisation-status-filter"
            v-model="statusFilter"
            :options="statusOptions"
          />
        </div>
        <div v-if="showCountryFilter">
          <label class="org-filter-label" for="organisation-country-filter">Pays</label>
          <SelectInput
            id="organisation-country-filter"
            v-model="countryFilter"
            :options="countryOptions"
          />
        </div>
        <div>
          <label class="org-filter-label" for="organisation-sort">Tri</label>
          <SelectInput
            id="organisation-sort"
            v-model="sortOrder"
            :options="sortOptions"
          />
        </div>
      </div>

      <ul v-if="activeFilters.length > 0" class="org-chips" aria-label="Filtres actifs">
        <li v-for="chip in activeFilters" :key="chip.id">
          <button type="button" class="org-chip focus-ring" @click="chip.clear()">
            {{ chip.label }}
            <span aria-hidden="true">×</span>
            <span class="sr-only">Retirer ce filtre</span>
          </button>
        </li>
      </ul>

      <div v-if="organisations.length === 0" class="org-empty">
        <h2 class="org-empty-title">Aucune organisation associée</h2>
        <UiButton
          v-if="canCreate"
          class="org-declare"
          to="/compte/organisation/declarer"
        >
          <UiIcon name="plus" />
          Déclarer une organisation
        </UiButton>
      </div>

      <div v-else-if="filteredOrganisations.length === 0" class="org-empty">
        <h2 class="org-empty-title">Aucune organisation</h2>
        <button type="button" class="org-filter-toggle focus-ring" @click="resetFilters">
          Réinitialiser les filtres
        </button>
      </div>

      <ul v-else class="org-list" :aria-labelledby="organisations.length > 0 ? 'org-count' : undefined">
        <li v-for="item in filteredOrganisations" :key="item.id">
          <AccountOrganizationListCard
            :organization="item"
            :dossier="dossierFor(item)"
            :can-deposit="canDeposit(item)"
            @open-kyc="navigateTo({ path: '/compte/verification', query: { sujet: item.id } })"
          />
        </li>
      </ul>

    </template>
  </main>
</template>
