<script setup lang="ts">
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useChangePassword } from '../../composables/identity-access/useChangePassword'

definePageMeta({
  layout: 'account',
  ssr: false,
})

useHead({
  title: 'Changer le mot de passe — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account, loadState, errorMessage, refresh } = useCurrentAccount()
const {
  mutationState,
  fieldErrors,
  errorMessage: changeError,
  sessionEnded,
  submit,
} = useChangePassword()

const retrying = ref(false)

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

async function onSubmit(payload: { password_actuel: string, password: string }) {
  await submit(payload)
  if (sessionEnded.value) {
    return navigateTo('/connexion')
  }
  if (mutationState.value === 'succeeded') {
    return navigateTo({
      path: '/connexion',
      query: { origine: 'mot-de-passe' },
    })
  }
}

onMounted(() => {
  void loadAccount()
})
</script>

<template>
  <main class="flex w-full flex-col gap-gutter py-gutter lg:py-section">
    <StepBackAction label="Retour au compte" to="/compte" />
    <PageHeader
      title="Changer le mot de passe."
      description="Indiquez le mot de passe actuel, puis le nouveau. Toutes les sessions seront révoquées."
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
      <Skeleton variant="form" />
    </div>

    <InlineAlert
      v-else-if="mutationState === 'succeeded'"
      tone="success"
      title="Mot de passe modifié"
    >
      Toutes les sessions ont été révoquées. Aucune connexion automatique n’a lieu. Redirection vers la connexion.
    </InlineAlert>

    <section
      v-else-if="account"
      class="password-change"
    >
      <InlineAlert
        v-if="mutationState === 'failed' || mutationState === 'uncertain'"
        :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
        :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Changement refusé'"
      >
        {{ changeError }}
      </InlineAlert>

      <ChangePasswordForm
        class="mt-stack"
        :disabled="mutationState === 'submitting' || mutationState === 'succeeded'"
        :submitting="mutationState === 'submitting'"
        :field-errors="fieldErrors"
        @submit="onSubmit"
      />
    </section>
  </main>
</template>
