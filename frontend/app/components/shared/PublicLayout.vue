<script setup lang="ts">
import { canAccessBackoffice, accountInitials } from '../../utils/identity-access/account'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useSignOut } from '../../composables/identity-access/useSignOut'

const props = defineProps<{
  unavailable?: boolean
  canvas?: 'default' | 'plain'
  background?: 'image' | 'white'
}>()

const { account, loadState, refresh } = useCurrentAccount()
const { mutationState, errorMessage, submit } = useSignOut()
const route = useRoute()

const headerSession = computed(() => {
  if (loadState.value === 'ready' && account.value) {
    return 'account' as const
  }
  if (loadState.value === 'loading') {
    return 'loading' as const
  }
  return 'visitor' as const
})

const displayLabel = computed(() => {
  const prenom = account.value?.profil?.prenom?.trim()
  return prenom || 'Mon compte'
})

const showBackoffice = computed(() => canAccessBackoffice(account.value))

onMounted(() => {
  if (props.canvas !== 'plain') {
    void refresh({ allowAnonymous: true })
  }
})

async function onLogout() {
  await submit()
  if (mutationState.value === 'succeeded') {
    return navigateTo('/connexion')
  }
}
</script>

<template>
  <div
    class="flex min-h-screen flex-col text-text font-sans"
    :class="background === 'white' ? 'public-canvas-white' : 'public-canvas'"
  >
    <AppHeader
      v-if="canvas !== 'plain'"
      :session="headerSession"
      :display-label="displayLabel"
      :initials="account ? accountInitials(account) : ''"
      :account-current="route.path.startsWith('/compte')"
      :can-access-backoffice="showBackoffice"
      :logging-out="mutationState === 'submitting'"
      @logout="onLogout"
    />
    <div
      v-if="(mutationState === 'failed' || mutationState === 'uncertain') && canvas !== 'plain'"
      class="shrink-0 px-page pt-stack"
    >
      <InlineAlert
        :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
        :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Déconnexion refusée'"
      >
        {{ errorMessage }}
      </InlineAlert>
    </div>
    <div
      v-if="$slots.banner"
      class="shrink-0 px-page pt-stack"
    >
      <slot name="banner" />
    </div>
    <div
      v-if="unavailable"
      class="flex min-h-0 flex-1 flex-col"
    >
      <div class="m-auto w-full px-page py-section">
        <slot name="unavailable">
          <ErrorState
            title="Service indisponible"
            message="Cette page n’est pas accessible pour le moment. Réessayez plus tard."
          />
        </slot>
      </div>
    </div>
    <div
      v-else
      class="flex min-h-0 flex-1 flex-col"
    >
      <div class="m-auto w-full">
        <slot />
      </div>
    </div>
  </div>
</template>
