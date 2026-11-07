<script setup lang="ts">
import { readEmailActivationContext } from '../../utils/identity-access/email-activation'
import { readRecoveryEmail } from '../../utils/identity-access/recovery-email'
import { isLimitedSession } from '../../types/identity-access/session'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useSignIn } from '../../composables/identity-access/useSignIn'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
})

useHead({
  title: 'Connexion — Livreaura',
})

const route = useRoute()
const { mutationState, fieldErrors, errorMessage, result, submit } = useSignIn()
const { refresh } = useCurrentAccount()

const fromActivation = computed(() => route.query.origine === 'activation')
const fromRecovery = computed(() => route.query.origine === 'recuperation')
const fromPasswordChange = computed(() => route.query.origine === 'mot-de-passe')
const fromSession = computed(() => route.query.origine === 'session')
const initialEmail = ref('')
const confirmingSession = ref(false)
const busy = computed(() => mutationState.value === 'submitting' || confirmingSession.value)

onMounted(() => {
  if (fromRecovery.value) {
    initialEmail.value = readRecoveryEmail() ?? ''
    return
  }
  initialEmail.value = readEmailActivationContext()?.email ?? ''
})

async function openAuthenticatedPath(path: string) {
  try {
    const navigation = await navigateTo(path)
    if (navigation === false && import.meta.client) {
      window.location.assign(path)
    }
  }
  catch {
    if (import.meta.client) {
      window.location.assign(path)
    }
  }
}

function nextPathAfterLogin(): string {
  if (result.value && isLimitedSession(result.value.session.portee)) {
    return '/inscription/verification'
  }
  return '/compte'
}

async function onSubmit(payload: { email: string, password: string }) {
  confirmingSession.value = true
  try {
    await submit(payload)
    if (mutationState.value !== 'succeeded' || !result.value) {
      return
    }

    void refresh({ allowAnonymous: false, force: true })
    await openAuthenticatedPath(nextPathAfterLogin())
  }
  finally {
    confirmingSession.value = false
  }
}
</script>

<template>
  <main class="auth-screen mx-auto flex w-full max-w-xl flex-col px-page py-stack sm:py-gutter">
    <div>
      <div class="mb-stack flex justify-center">
        <BrandLogo size="lg" />
      </div>
      <PageHeader
        align="center"
        title="Connectez-vous"
        description="Accédez à votre espace personnel pour lire, publier et faire vivre vos idées."
      />

      <div class="mt-section flex flex-col gap-stack">
        <InlineAlert
          v-if="fromSession && mutationState === 'idle'"
          tone="warning"
          title="Session non ouverte"
        >
          La connexion n’a pas établi une session utilisable. Saisissez à nouveau l’e-mail et le mot de passe.
        </InlineAlert>

        <InlineAlert
          v-else-if="fromActivation && mutationState === 'idle'"
          tone="success"
          title="Compte activé"
        >
          Votre compte est actif. Saisissez l’e-mail et le mot de passe choisis à l’inscription pour ouvrir votre espace.
        </InlineAlert>

        <InlineAlert
          v-else-if="fromRecovery && mutationState === 'idle'"
          tone="success"
          title="Mot de passe enregistré"
        >
          Les sessions ouvertes ont été révoquées. Connectez-vous avec le nouveau mot de passe.
        </InlineAlert>

        <InlineAlert
          v-else-if="fromPasswordChange && mutationState === 'idle'"
          tone="success"
          title="Mot de passe modifié"
        >
          Toutes les sessions ont été révoquées. Connectez-vous avec le nouveau mot de passe.
        </InlineAlert>

        <InlineAlert
          v-if="mutationState === 'failed' || mutationState === 'uncertain'"
          :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
          :title="mutationState === 'uncertain' ? 'Connexion non confirmée' : 'Connexion refusée'"
        >
          {{ errorMessage }}
        </InlineAlert>

        <LoginForm
          :initial-email="initialEmail"
          :disabled="busy"
          :submitting="busy"
          :field-errors="fieldErrors"
          @submit="onSubmit"
        />

        <p class="login-note text-center">
          Vous n’avez pas de compte ?
          <UiLink class="login-primary-link" to="/inscription/parcours">Créer un compte</UiLink>
        </p>
      </div>
    </div>

    <footer class="mt-section flex flex-col items-center gap-2 text-center">
      <nav
        class="flex flex-wrap items-center justify-center gap-x-gutter gap-y-1"
        aria-label="Assistance"
      >
        <UiLink class="login-secondary" to="/recours">Déposer un recours</UiLink>
        <span class="login-note" aria-hidden="true">·</span>
        <UiLink class="login-secondary" to="/cloture">Demander une clôture</UiLink>
      </nav>
      <p class="login-note flex items-center justify-center gap-2">
        <UiIcon
          name="lock"
          class="size-3.5 shrink-0"
        />
        <span>Connexion sécurisée · Vos données sont protégées</span>
      </p>
    </footer>
  </main>
</template>
