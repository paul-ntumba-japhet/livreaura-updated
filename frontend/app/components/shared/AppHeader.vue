<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

const route = useRoute()

const props = withDefaults(
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

const accueilCurrent = computed(() => typeof props.homeTo === 'string' && route.path === props.homeTo)
const catalogueColumns = computed(() => props.session !== 'account' && route.path === '/catalogue')
const catalogueCurrent = computed(() => route.path === '/catalogue' || route.path.startsWith('/catalogue/'))
const inscriptionCurrent = computed(() => route.path.startsWith('/inscription'))
const connexionCurrent = computed(() => route.path.startsWith('/connexion'))
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-border bg-surface-raised">
    <div
      class="flex min-h-[var(--layout-header-height)] flex-wrap items-center gap-x-gutter gap-y-2 px-page"
      :class="catalogueColumns ? 'lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8' : undefined"
    >
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
        class="inline-flex shrink-0 rounded-sm focus-ring lg:justify-self-start"
      >
        <BrandLogo />
      </NuxtLink>
      <nav
        class="flex min-w-0 flex-1 flex-wrap items-center gap-2"
        aria-label="Menu principal"
      >
        <NuxtLink
          :to="homeTo"
          class="focus-ring inline-flex min-h-11 items-center px-2 font-sans text-sm text-text"
          :class="[accueilCurrent ? 'font-semibold' : undefined, catalogueColumns ? 'lg:-ml-2' : undefined]"
          :aria-current="accueilCurrent ? 'page' : undefined"
        >
          Accueil
        </NuxtLink>
        <NuxtLink
          to="/catalogue"
          class="focus-ring inline-flex min-h-11 items-center px-2 font-sans text-sm text-text"
          :class="catalogueCurrent ? 'font-semibold' : undefined"
          :aria-current="catalogueCurrent ? 'page' : undefined"
        >
          Catalogue
        </NuxtLink>
        <div
          v-if="$slots.search"
          class="account-header-search order-last w-full min-w-0 md:order-none md:flex-1"
        >
          <slot name="search" />
        </div>
        <div class="ml-auto flex items-center gap-2">
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
            <NuxtLink
              to="/connexion"
              class="focus-ring inline-flex min-h-11 items-center px-2 font-sans text-sm text-text"
              :class="connexionCurrent ? 'font-semibold' : undefined"
              :aria-current="connexionCurrent ? 'page' : undefined"
            >
              Connexion
            </NuxtLink>
            <UiButton
              variant="secondary"
              to="/inscription/parcours"
              class="whitespace-nowrap"
              :aria-current="inscriptionCurrent ? 'page' : undefined"
            >
              S’inscrire
            </UiButton>
          </template>
        </div>
      </nav>
    </div>
  </header>
</template>
