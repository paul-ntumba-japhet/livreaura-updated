<script setup lang="ts">
import { canAccessBackoffice, accountInitials, displayFullNameForAccount } from '../../utils/identity-access/account'
import { accountNavItems, filterAccountNavItems, isAccountNavCurrent } from '../../utils/identity-access/account-nav'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useSignOut } from '../../composables/identity-access/useSignOut'
import type { SidebarItem } from './AppSidebar.vue'
import type { AccountNavItem } from '../../utils/identity-access/account-nav'

withDefaults(
  defineProps<{
    unavailable?: boolean
  }>(),
  {
    unavailable: false,
  },
)

const { account, loadState } = useCurrentAccount()
const { mutationState, errorMessage, submit } = useSignOut()
const route = useRoute()
const drawerOpen = ref(false)
const searchQuery = ref('')

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
  if (!account.value) {
    return 'Mon compte'
  }
  return displayFullNameForAccount(account.value)
})

const initials = computed(() => (account.value ? accountInitials(account.value) : ''))
const showBackoffice = computed(() => canAccessBackoffice(account.value))
const navSource = computed(() => accountNavItems(account.value))
const navItems = computed<SidebarItem[]>(() => navSource.value.map(toSidebarItem))
const searchResults = computed(() => filterAccountNavItems(navSource.value, searchQuery.value))

function toSidebarItem(item: AccountNavItem): SidebarItem {
  const children = item.children?.map(toSidebarItem)
  return {
    id: item.id,
    label: item.label,
    icon: item.icon,
    to: children?.length ? undefined : item.to,
    current: isAccountNavCurrent(item, route.path, route.hash),
    children,
  }
}

watch(() => route.fullPath, () => {
  drawerOpen.value = false
  searchQuery.value = ''
})

async function onLogout() {
  await submit()
  if (mutationState.value === 'succeeded') {
    return navigateTo('/connexion')
  }
}

async function goToNav(item: AccountNavItem) {
  searchQuery.value = ''
  drawerOpen.value = false
  await navigateTo(item.to)
}

async function onSearchSubmit() {
  const first = searchResults.value[0]
  if (first) {
    await goToNav(first)
  }
}
</script>

<template>
  <div class="account-shell flex min-h-dvh flex-col bg-white text-text font-sans">
    <AppHeader
      :session="headerSession"
      :display-label="displayLabel"
      :initials="initials"
      :account-current="route.path.startsWith('/compte')"
      :can-access-backoffice="showBackoffice"
      :logging-out="mutationState === 'submitting'"
      home-to="/compte"
      show-menu-toggle
      show-notifications
      :menu-open="drawerOpen"
      @logout="onLogout"
      @toggle-menu="drawerOpen = !drawerOpen"
    >
      <template #search>
        <AccountSearch
          :query="searchQuery"
          :results="searchResults"
          @update:query="searchQuery = $event"
          @select="goToNav"
          @submit="onSearchSubmit"
        />
      </template>
    </AppHeader>
    <div
      v-if="unavailable"
      class="account-main"
    >
      <slot name="unavailable">
        <ErrorState
          title="Service indisponible"
          message="Cette page n’est pas accessible pour le moment. Réessayez plus tard."
        />
      </slot>
    </div>
    <div
      v-else
      class="account-body"
    >
      <aside
        id="account-navigation"
        class="account-aside hidden flex-col md:flex"
      >
        <AppSidebar
          class="account-nav"
          label="Espace client"
          :items="navItems"
          compact
        />
      </aside>
      <NavigationDrawer
        :open="drawerOpen"
        title="Espace client"
        panel-class="account-drawer"
        @close="drawerOpen = false"
      >
        <AppSidebar
          class="account-nav"
          label="Espace client"
          :items="navItems"
          compact
          @navigate="drawerOpen = false"
        />
      </NavigationDrawer>
      <div class="account-main">
        <InlineAlert
          v-if="mutationState === 'failed' || mutationState === 'uncertain'"
          :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
          :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Déconnexion refusée'"
        >
          {{ errorMessage }}
        </InlineAlert>
        <slot />
      </div>
    </div>
  </div>
</template>
