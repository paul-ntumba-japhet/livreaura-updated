<script setup lang="ts">
import { normalizeAuthorPathState } from '../../types/identity-access/account'
import {
  accessScopeOf,
  accountInitials,
  accountStatusOf,
  countryLabel,
  displayFullNameForAccount,
  labelForAccountStatus,
  localeLabel,
  needsEmailConfirmation,
} from '../../utils/identity-access/account'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Mon profil — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account, loadState, errorMessage, refresh } = useCurrentAccount()

const retrying = ref(false)
const status = computed(() => (account.value ? accountStatusOf(account.value) : 'unknown'))
const scope = computed(() => (account.value ? accessScopeOf(account.value) : 'unknown'))
const unknownStatus = computed(() => status.value === 'unknown')
const supportOnly = computed(() => scope.value === 'support_only')
const showSkeleton = computed(() => !account.value && (loadState.value === 'idle' || loadState.value === 'loading'))
const initials = computed(() => (account.value ? accountInitials(account.value) : ''))
const fullName = computed(() => (account.value ? displayFullNameForAccount(account.value) : ''))
const email = computed(() => account.value?.compte.email_masque?.trim() || '')
const emailState = computed(() => {
  if (!email.value) {
    return ''
  }
  if (status.value === 'unknown' || scope.value === 'unknown') {
    return 'Statut indisponible'
  }
  return needsEmailConfirmation(status.value, scope.value) ? 'Non vérifié' : 'Vérifié'
})
const location = computed(() => {
  const profile = account.value?.profil
  const country = countryLabel(profile?.pays) || profile?.pays?.trim() || ''
  const city = profile?.ville?.trim() || ''
  if (!city || !country) {
    return ''
  }
  return `${city}, ${country}`
})
const language = computed(() => localeLabel(account.value?.compte.locale))
const headlineRole = computed(() => {
  const current = account.value
  if (!current) {
    return ''
  }
  const roles = Array.isArray(current.roles) ? current.roles : []
  if (roles.includes('admin')) {
    return 'Administration'
  }
  if (roles.includes('reader')) {
    return 'Lecteur'
  }
  if (roles.includes('author') || normalizeAuthorPathState(current.parcours_auteur?.etat) === 'active') {
    return 'Auteur'
  }
  return ''
})
const declaringRepresentative = computed(() => {
  const organisations = account.value?.organisations
  if (!Array.isArray(organisations)) {
    return false
  }
  return organisations.some(item => item.adhesion?.principal && item.adhesion.statut === 'active')
})
const accessNotes = computed(() => {
  const current = account.value
  if (!current) {
    return []
  }
  const roles = Array.isArray(current.roles) ? current.roles : []
  const notes: string[] = []
  if (roles.includes('reader') && headlineRole.value !== 'Lecteur') {
    notes.push('Lecteur')
  }
  const authorActive = roles.includes('author') || normalizeAuthorPathState(current.parcours_auteur?.etat) === 'active'
  if (authorActive && headlineRole.value !== 'Auteur') {
    notes.push('Auteur')
  }
  if (roles.includes('admin') && headlineRole.value !== 'Administration') {
    notes.push('Administration')
  }
  if (declaringRepresentative.value) {
    notes.push('Représentant déclarant')
  }
  return notes
})

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
  <main class="flex w-full flex-col gap-gutter py-gutter lg:py-section">
    <div class="profile-back">
      <StepBackAction label="Retour au compte" to="/compte" />
    </div>

    <div class="profile-page">
      <h1>Mon profil</h1>

      <ErrorState
        v-if="loadState === 'error' && !account"
        title="Impossible de charger le compte"
        :message="errorMessage || 'La lecture du compte a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="retry"
      />

      <div
        v-else-if="showSkeleton"
        aria-busy="true"
      >
        <Skeleton variant="detail" />
      </div>

      <template v-else-if="account">
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

        <header class="profile-identity">
          <span class="profile-avatar" aria-hidden="true">{{ initials }}</span>
          <div class="min-w-0">
            <p class="profile-name">{{ fullName }}</p>
            <p v-if="headlineRole" class="profile-role">{{ headlineRole }}</p>
          </div>
        </header>

        <section class="profile-facts" aria-labelledby="profile-facts-title">
          <h2 id="profile-facts-title">Informations du compte</h2>
          <dl>
            <div>
              <dt>E-mail</dt>
              <dd>
                <span class="profile-email">{{ email || 'Non disponible' }}</span>
                <span
                  v-if="emailState"
                  class="profile-email-state"
                  :class="{
                    'is-verified': emailState === 'Vérifié',
                    'is-pending': emailState === 'Non vérifié',
                  }"
                >
                  {{ emailState }}
                </span>
              </dd>
            </div>
            <div v-if="location">
              <dt>Localisation</dt>
              <dd>{{ location }}</dd>
            </div>
            <div v-if="language">
              <dt>Langue</dt>
              <dd>{{ language }}</dd>
            </div>
            <div v-if="status !== 'active'">
              <dt>Statut du compte</dt>
              <dd>{{ labelForAccountStatus(status) }}</dd>
            </div>
          </dl>
        </section>

        <section
          v-if="accessNotes.length > 0"
          class="profile-access"
          aria-labelledby="profile-access-title"
        >
          <h2 id="profile-access-title">Accès et parcours</h2>
          <p>{{ accessNotes.join(' · ') }}</p>
          <p v-if="declaringRepresentative" class="profile-note">
            Représentant déclarant désigne la relation déclarée auprès d’une organisation. Ce n’est pas une habilitation vérifiée.
          </p>
        </section>
      </template>
    </div>
  </main>
</template>
