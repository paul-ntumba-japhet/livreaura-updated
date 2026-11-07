<script setup lang="ts">
import { accountInitials, canAccessBackoffice, canAccessKycBackoffice, canAccessOrganizationBackoffice, canAccessTeamBackoffice, canAuthorizeAppeal, canCloseAccount, canManageInternalPermissions, canHandleSupport, canReactivateAccount, canRetryTransmissions, canSuspendAccount } from '../../utils/identity-access/account'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useSignOut } from '../../composables/identity-access/useSignOut'
import type { SidebarItem } from './AppSidebar.vue'

withDefaults(
  defineProps<{
    unavailable?: boolean
  }>(),
  {
    unavailable: false,
  },
)

const { account, loadState, errorMessage, refresh } = useCurrentAccount()
const { mutationState, errorMessage: logoutError, submit } = useSignOut()
const route = useRoute()
const retrying = ref(false)

const headerSession = computed(() => {
  if (account.value) {
    return 'account' as const
  }
  if (loadState.value === 'anonymous') {
    return 'visitor' as const
  }
  if (loadState.value === 'error') {
    return 'account' as const
  }
  return 'loading' as const
})

const displayLabel = computed(() => {
  const prenom = account.value?.profil?.prenom?.trim()
  return prenom || 'Mon compte'
})

const authorized = computed(() => canAccessBackoffice(account.value))
const sessionExpired = computed(() => loadState.value === 'anonymous')
const accessDenied = computed(() => Boolean(account.value) && !authorized.value)

const navItems = computed<SidebarItem[]>(() => {
  const items: SidebarItem[] = [
    {
      id: 'accueil',
      label: 'Tableau de bord',
      to: '/administration',
      icon: 'house',
      current: route.path === '/administration',
    },
  ]

  if (canAccessKycBackoffice(account.value)) {
    items.push({
      id: 'kyc',
      label: 'Vérifications KYC',
      to: '/administration/verification',
      icon: 'file-check',
      current: route.path.startsWith('/administration/verification')
        || route.path.startsWith('/administration/taches'),
    })
  }

  if (canAccessOrganizationBackoffice(account.value)) {
    items.push({
      id: 'organisations',
      label: 'Organisations',
      to: '/administration/organisations',
      icon: 'building-2',
      current: route.path.startsWith('/administration/organisations'),
    })
  }

  if (canSuspendAccount(account.value) || canReactivateAccount(account.value) || canCloseAccount(account.value) || canHandleSupport(account.value)) {
    items.push({
      id: 'comptes',
      label: 'Comptes et accès',
      to: '/administration/comptes',
      icon: 'user',
      current: route.path.startsWith('/administration/comptes'),
    })
  }

  if (canHandleSupport(account.value) || canAuthorizeAppeal(account.value)) {
    items.push({
      id: 'recours',
      label: 'Support et recours',
      to: '/administration/recours',
      icon: 'message-circle',
      current: route.path.startsWith('/administration/recours'),
    })
  }

  if (canAccessKycBackoffice(account.value) || canManageInternalPermissions(account.value)) {
    items.push({
      id: 'habilitations',
      label: 'Habilitations',
      to: '/administration/habilitations',
      icon: 'shield-check',
      current: route.path.startsWith('/administration/habilitations'),
    })
  }

  if (canAccessTeamBackoffice(account.value)) {
    items.push({
      id: 'equipe',
      label: 'Équipe et paramètres',
      to: '/administration/equipe',
      icon: 'settings',
      current: route.path.startsWith('/administration/equipe'),
    })
  }

  if (canRetryTransmissions(account.value)) {
    items.push({
      id: 'supervision',
      label: 'Supervision et historique',
      to: '/administration/supervision',
      icon: 'clock',
      current: route.path.startsWith('/administration/supervision')
        || route.path.startsWith('/administration/exploitation'),
    })
  }

  return items
})

async function loadAccount() {
  await refresh({ allowAnonymous: false })
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

async function onLogout() {
  await submit()
  if (mutationState.value === 'succeeded') {
    return navigateTo('/connexion')
  }
}

onMounted(() => {
  void loadAccount()
})
</script>

<template>
  <div class="min-h-screen bg-surface text-text font-sans">
    <AppHeader
      :session="headerSession"
      :display-label="displayLabel"
      :initials="account ? accountInitials(account) : ''"
      :account-current="false"
      :backoffice-current="true"
      :can-access-backoffice="authorized"
      :logging-out="mutationState === 'submitting'"
      @logout="onLogout"
    />
    <div
      v-if="mutationState === 'failed' || mutationState === 'uncertain'"
      class="px-page pt-stack"
    >
      <InlineAlert
        :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
        :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Déconnexion refusée'"
      >
        {{ logoutError }}
      </InlineAlert>
    </div>

    <div v-if="unavailable" class="px-page py-section">
      <slot name="unavailable">
        <ErrorState
          title="Service indisponible"
          message="Cette page n’est pas accessible pour le moment. Réessayez plus tard."
        />
      </slot>
    </div>

    <main v-else class="md:grid md:grid-cols-[16rem_minmax(0,1fr)]">
      <AppSidebar
        v-if="authorized"
        label="Administration"
        :items="navItems"
      />
      <div class="min-w-0">
        <div
          v-if="!account && (loadState === 'idle' || loadState === 'loading')"
          class="px-page py-section"
          aria-busy="true"
        >
          <Skeleton variant="detail" />
        </div>
        <div
          v-else-if="loadState === 'error' && !account"
          class="px-page py-section"
        >
          <ErrorState
            title="Impossible de charger le compte"
            :message="errorMessage"
            retry-label="Réessayer"
            :retrying="retrying"
            @retry="retry"
          />
        </div>
        <div v-else-if="sessionExpired" class="px-page py-section">
          <SessionExpiredState />
        </div>
        <div v-else-if="accessDenied" class="px-page py-section">
          <ForbiddenState
            title="Accès refusé"
            message="L’administration n’est disponible que pour un compte actif, avec le rôle administration et un accès normal."
          >
            <template #action>
              <UiLink to="/compte">Retour au compte</UiLink>
            </template>
          </ForbiddenState>
        </div>
        <slot v-else-if="authorized" />
      </div>
    </main>
  </div>
</template>
