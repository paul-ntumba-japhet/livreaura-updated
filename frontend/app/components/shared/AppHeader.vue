<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

const route = useRoute()

withDefaults(
  defineProps<{
    session?: 'visitor' | 'account' | 'loading'
    displayLabel?: string
    initials?: string
    accountCurrent?: boolean
    backofficeCurrent?: boolean
    canAccessBackoffice?: boolean
    loggingOut?: boolean
    homeTo?: RouteLocationRaw
    showMenuToggle?: boolean
    menuOpen?: boolean
    showNotifications?: boolean
  }>(),
  {
    session: 'visitor',
    displayLabel: 'Mon compte',
    initials: '',
    accountCurrent: false,
    backofficeCurrent: false,
    canAccessBackoffice: false,
    loggingOut: false,
    homeTo: '/',
    showMenuToggle: false,
    menuOpen: false,
    showNotifications: false,
  },
)

const emit = defineEmits<{
  logout: []
  'toggle-menu': []
}>()

const inscriptionCurrent = computed(() => route.path.startsWith('/inscription'))
const connexionCurrent = computed(() => route.path.startsWith('/connexion'))
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-border bg-surface-raised">
    <div class="flex flex-wrap items-center gap-x-gutter gap-y-stack px-page py-stack">
      <div
        v-if="showMenuToggle"
        class="md:hidden"
      >
        <UiButton
          variant="quiet"
          :aria-expanded="menuOpen"
          aria-controls="account-navigation"
          @click="emit('toggle-menu')"
        >
          <UiIcon :name="menuOpen ? 'close' : 'menu'" :label="menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'" />
        </UiButton>
      </div>
      <NuxtLink
        :to="homeTo"
        class="inline-flex shrink-0 rounded-sm focus-ring"
      >
        <BrandLogo />
      </NuxtLink>
      <div
        v-if="$slots.search"
        class="account-header-search order-last w-full min-w-0 md:order-none md:flex-1"
      >
        <slot name="search" />
      </div>
      <nav
        class="ml-auto flex items-center gap-stack"
        aria-label="Menu principal"
      >
          <button
            v-if="showNotifications && session === 'account'"
            type="button"
            class="inline-flex size-11 items-center justify-center rounded-full text-text-muted focus-ring"
            aria-label="Notifications. Aucune notification n’est proposée dans cet espace pour le moment."
            disabled
          >
            <UiIcon name="bell" />
          </button>
          <span
            v-if="session === 'loading'"
            class="inline-flex min-h-11 items-center"
          >
            <Skeleton class="h-4 w-28" />
            <span class="sr-only">Vérification de la session</span>
          </span>
          <AccountMenu
            v-else-if="session === 'account'"
            :display-label="displayLabel"
            :initials="initials"
            :account-current="accountCurrent"
            :backoffice-current="backofficeCurrent"
            :can-access-backoffice="canAccessBackoffice"
            :logging-out="loggingOut"
            @logout="emit('logout')"
          />
          <template v-else>
            <UiLink
              to="/connexion"
              :current="connexionCurrent"
            >
              Connexion
            </UiLink>
            <UiLink
              to="/inscription/parcours"
              :current="inscriptionCurrent"
            >
              Créer un compte
            </UiLink>
          </template>
        </nav>
    </div>
  </header>
</template>
