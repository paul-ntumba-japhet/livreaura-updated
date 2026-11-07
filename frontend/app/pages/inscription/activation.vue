<script setup lang="ts">
import type { ActivationPreview } from '../../types/identity-access/activation'
import { registrationSteps } from '../../utils/identity-access/registration-steps'
import { isActivationChallengeId } from '../../types/identity-access/activation'
import { fetchActivationPreview } from '../../services/identity-access/activation'
import { useActivateAccount } from '../../composables/identity-access/useActivateAccount'
import { messageForIdentityError } from '../../utils/identity-access/errors'
import type { IdentityRequestError } from '../../utils/identity-access/errors'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
  ssr: false,
})

useHead({
  title: 'Activation du compte — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
    { name: 'referrer', content: 'no-referrer' },
  ],
})

type PreviewLoadState = 'loading' | 'ready' | 'missing' | 'error'

const route = useRoute()
const router = useRouter()
const { mutationState, errorMessage, errorCode, submit } = useActivateAccount()

const challengeId = ref('')
const proof = ref('')
const preview = ref<ActivationPreview | null>(null)
const loadState = ref<PreviewLoadState>('loading')
const loadError = ref('')
const retrying = ref(false)

const challengeOpen = computed(() => Boolean(preview.value?.destination_masquee))
const proofReady = computed(() => challengeId.value !== '' && proof.value !== '')
const working = computed(() => loadState.value === 'loading' || mutationState.value === 'submitting')
const alreadyUsed = computed(() => errorCode.value === 'defi_invalide' || errorCode.value === 'compte_ineligible')

async function completeActivation() {
  if (!proofReady.value) {
    return
  }

  await submit(challengeId.value, proof.value)
  if (mutationState.value !== 'succeeded') {
    return
  }

  return navigateTo({
    path: '/connexion',
    query: { origine: 'activation' },
  })
}

async function loadPreview() {
  if (!isActivationChallengeId(challengeId.value)) {
    loadState.value = 'missing'
    return
  }

  loadState.value = 'loading'
  loadError.value = ''

  try {
    preview.value = await fetchActivationPreview(challengeId.value)
    loadState.value = 'ready'
    if (preview.value.destination_masquee && proofReady.value) {
      await completeActivation()
    }
  }
  catch (error) {
    loadError.value = messageForIdentityError(error as IdentityRequestError)
    loadState.value = 'error'
  }
}

async function retry() {
  retrying.value = true
  try {
    if (loadState.value === 'error' || loadState.value === 'missing') {
      await loadPreview()
      return
    }
    await completeActivation()
  }
  finally {
    retrying.value = false
  }
}

onMounted(() => {
  const requestedId = typeof route.query.defi_id === 'string' ? route.query.defi_id : ''
  const requestedProof = typeof route.query.preuve === 'string' ? route.query.preuve : ''

  challengeId.value = isActivationChallengeId(requestedId) ? requestedId : ''
  proof.value = requestedProof

  if (requestedProof) {
    const nextQuery = { ...route.query }
    delete nextQuery.preuve
    void router.replace({ query: nextQuery })
  }

  if (!challengeId.value || !proof.value) {
    loadState.value = 'missing'
    return
  }

  return loadPreview()
})
</script>

<template>
  <main class="auth-screen inscription-screen mx-auto flex w-full max-w-xl flex-col px-page py-stack sm:py-gutter">
    <div>
      <Stepper
        class="mb-section"
        compact
        :steps="registrationSteps('activation')"
        label="Étapes de l’inscription"
      />
      <div class="mb-stack flex justify-center">
        <BrandLogo size="lg" />
      </div>
      <PageHeader
        align="center"
        :title="working ? 'Activation en cours' : 'Activation du compte'"
        :description="working
          ? 'Votre adresse e-mail est en cours de vérification.'
          : 'Ouvrez le lien reçu pour activer le compte, puis connectez-vous.'"
      />

      <div class="mt-section flex flex-col gap-stack">
        <InlineAlert
          v-if="mutationState === 'failed' || mutationState === 'uncertain'"
          :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
          :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Activation refusée'"
        >
          {{ errorMessage }}
        </InlineAlert>

        <ErrorState
          v-if="loadState === 'error' && mutationState === 'idle'"
          title="Impossible de préparer l’activation"
          :message="loadError"
          retry-label="Réessayer"
          :retrying="retrying"
          @retry="retry"
        />

        <EmptyState
          v-else-if="loadState === 'missing'"
          plain
          title="Lien d’activation incomplet"
          message="Ce lien ne contient pas les informations nécessaires. Ouvrez le message reçu, ou demandez un nouvel envoi."
        >
          <template #action>
            <UiLink class="login-primary-link" to="/inscription/verification">Retour à la vérification e-mail</UiLink>
          </template>
        </EmptyState>

        <div
          v-else-if="working || mutationState === 'succeeded'"
          class="flex flex-col gap-stack"
          :aria-busy="working || undefined"
        >
          <Skeleton v-if="working" variant="form" />
          <InlineAlert
            v-else
            tone="success"
            title="Compte activé"
          >
            Votre adresse e-mail est vérifiée. Redirection vers la connexion…
          </InlineAlert>
          <p v-if="working" class="login-note text-center">
            Activation du compte, redirection vers la connexion…
          </p>
          <UiButton v-else to="/connexion?origine=activation" block>
            Se connecter
          </UiButton>
        </div>

        <EmptyState
          v-else-if="!challengeOpen || alreadyUsed"
          plain
          title="Lien déjà utilisé"
          message="Ce compte est déjà activé, ou le lien n’est plus valable. Connectez-vous pour ouvrir votre espace."
        >
          <template #action>
            <UiButton to="/connexion" block>Se connecter</UiButton>
          </template>
        </EmptyState>

        <UiButton
          v-else-if="mutationState === 'failed' || mutationState === 'uncertain'"
          block
          :loading="retrying"
          :disabled="retrying"
          @click="retry"
        >
          Réessayer l’activation
        </UiButton>
      </div>

      <p v-if="loadState !== 'missing'" class="login-note mt-stack text-center">
        <UiLink class="login-primary-link" to="/inscription/verification">Retour à la vérification e-mail</UiLink>
      </p>
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
