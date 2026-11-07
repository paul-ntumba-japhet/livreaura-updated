<script setup lang="ts">
import { isRecoveryChallengeId } from '../../types/identity-access/recovery'
import { useRequestPasswordReset } from '../../composables/identity-access/useRequestPasswordReset'
import { useVerifyResetProof } from '../../composables/identity-access/useVerifyResetProof'
import { useConfirmPasswordReset } from '../../composables/identity-access/useConfirmPasswordReset'
import { formatActivationExpiry } from '../../utils/identity-access/email-activation'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
  ssr: false,
})

useHead({
  title: 'Récupération du mot de passe — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
    { name: 'referrer', content: 'no-referrer' },
  ],
})

const route = useRoute()
const router = useRouter()

const {
  mutationState: requestState,
  fieldErrors: requestFieldErrors,
  errorMessage: requestError,
  waitSeconds,
  submit: submitRequest,
} = useRequestPasswordReset()

const {
  loadState,
  errorMessage: proofError,
  retryable: proofRetryable,
  preview,
  submit: verifyProof,
  markMissing,
} = useVerifyResetProof()

const {
  mutationState: confirmState,
  fieldErrors: confirmFieldErrors,
  errorMessage: confirmError,
  submit: submitConfirm,
} = useConfirmPasswordReset()

const challengeId = ref('')
const proof = ref('')
const retrying = ref(false)

const resetMode = computed(() => {
  return Boolean(route.query.defi_id) || challengeId.value !== ''
})

const emailMasque = computed(() => preview.value?.destination_masquee ?? '')
const expireLabel = computed(() => formatActivationExpiry(preview.value?.expire_a))
const formAuthorized = computed(() => preview.value?.formulaire === 'autorise')

async function loadProof() {
  if (!isRecoveryChallengeId(challengeId.value) || !proof.value) {
    markMissing()
    return
  }
  await verifyProof(challengeId.value, proof.value)
}

async function retryProof() {
  retrying.value = true
  try {
    await loadProof()
  }
  finally {
    retrying.value = false
  }
}

async function onConfirm(password: string) {
  await submitConfirm(challengeId.value, proof.value, password)
  if (confirmState.value !== 'succeeded') {
    return
  }
  proof.value = ''
  return navigateTo({
    path: '/connexion',
    query: { origine: 'recuperation' },
  })
}

onMounted(() => {
  const requestedId = typeof route.query.defi_id === 'string' ? route.query.defi_id : ''
  const requestedProof = typeof route.query.preuve === 'string' ? route.query.preuve : ''

  challengeId.value = isRecoveryChallengeId(requestedId) ? requestedId : ''
  proof.value = requestedProof

  if (requestedProof) {
    const nextQuery = { ...route.query }
    delete nextQuery.preuve
    void router.replace({ query: nextQuery })
  }

  if (!resetMode.value && !requestedId) {
    return
  }

  return loadProof()
})
</script>

<template>
  <main class="auth-screen mx-auto flex w-full max-w-xl flex-col px-page py-stack sm:py-gutter">
    <div>
      <div class="mb-stack flex justify-center">
        <BrandLogo size="lg" />
      </div>

      <template v-if="!resetMode">
        <PageHeader
          align="center"
          title="Mot de passe oublié"
          description="Indiquez l’adresse e-mail du compte. La réponse est identique que le compte existe ou non."
        />

        <div class="mt-section flex flex-col gap-stack">
          <RecoveryRequestResult v-if="requestState === 'succeeded'" />

          <InlineAlert
            v-if="requestState === 'failed' || requestState === 'uncertain'"
            :tone="requestState === 'uncertain' ? 'warning' : 'error'"
            :title="requestState === 'uncertain' ? 'Résultat incertain' : 'Demande refusée'"
          >
            {{ requestError }}
          </InlineAlert>

          <RecoveryRequestForm
            :disabled="requestState === 'submitting'"
            :submitting="requestState === 'submitting'"
            :wait-seconds="waitSeconds"
            :field-errors="requestFieldErrors"
            @submit="submitRequest"
          />
        </div>
      </template>

      <template v-else>
        <PageHeader
          align="center"
          title="Choisissez un nouveau mot de passe"
          description="Confirmez le lien reçu, puis enregistrez un mot de passe. Cette page ne consomme pas le lien à elle seule."
          context="Étape : réinitialisation"
          :loading="loadState === 'loading'"
        />

        <div class="mt-section flex flex-col gap-stack">
          <InlineAlert
            v-if="confirmState === 'failed' || confirmState === 'uncertain'"
            :tone="confirmState === 'uncertain' ? 'warning' : 'error'"
            :title="confirmState === 'uncertain' ? 'Résultat incertain' : 'Changement refusé'"
          >
            {{ confirmError }}
          </InlineAlert>

          <template v-if="loadState === 'error' && confirmState !== 'succeeded'">
            <ErrorState
              title="Lien non utilisable"
              :message="proofError"
              :retry-label="proofRetryable ? 'Réessayer' : undefined"
              :retrying="retrying"
              @retry="retryProof"
            />
            <p
              v-if="!proofRetryable"
              class="login-note text-center"
            >
              <UiLink class="login-primary-link" to="/recuperation">Demander une nouvelle récupération</UiLink>
            </p>
          </template>

          <EmptyState
            v-else-if="loadState === 'missing' && confirmState !== 'succeeded'"
            title="Lien de récupération incomplet"
            message="Ce lien ne contient pas les informations nécessaires. Ouvrez le message reçu, ou demandez une nouvelle récupération."
          >
            <template #action>
              <UiLink class="login-primary-link" to="/recuperation">Demander une récupération</UiLink>
            </template>
          </EmptyState>

          <div v-else-if="loadState === 'loading'" aria-busy="true">
            <Skeleton variant="form" />
          </div>

          <PasswordResetForm
            v-else-if="loadState === 'ready' && formAuthorized && confirmState !== 'succeeded'"
            :email-masque="emailMasque || undefined"
            :expire-label="expireLabel || undefined"
            :disabled="confirmState === 'submitting'"
            :submitting="confirmState === 'submitting'"
            :field-errors="confirmFieldErrors"
            @submit="onConfirm"
          />
        </div>
      </template>

      <p class="login-note mt-stack text-center">
        <UiLink class="login-primary-link" to="/connexion">Retour à la connexion</UiLink>
      </p>
      <p class="login-note mt-2 text-center">
        Vous n’avez pas de compte ?
        <UiLink class="login-primary-link" to="/inscription/parcours">Créer un compte</UiLink>
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
