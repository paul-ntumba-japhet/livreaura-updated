<script setup lang="ts">
import { readEmailActivationContext } from '../../utils/identity-access/email-activation'
import { useResendChallenge } from '../../composables/identity-access/useResendChallenge'
import { registrationSteps } from '../../utils/identity-access/registration-steps'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
})

useHead({
  title: 'Vérifiez votre e-mail — Livreaura',
})

const { mutationState, errorMessage, waitSeconds, submit } = useResendChallenge()

const contextReady = ref(false)
const knownEmail = ref('')
const emailMasque = ref('')
const typedEmail = ref('')
const emailError = ref('')

onMounted(() => {
  const stored = readEmailActivationContext()
  knownEmail.value = stored?.email ?? ''
  emailMasque.value = stored?.emailMasque ?? ''
  contextReady.value = true
})

const needsEmail = computed(() => knownEmail.value === '')

function onResend() {
  const email = knownEmail.value || typedEmail.value.trim()
  if (!email) {
    emailError.value = 'Indiquez l’adresse e-mail utilisée à l’inscription.'
    return
  }
  emailError.value = ''
  return submit(email)
}

const statusMessage = computed(() => {
  if (mutationState.value === 'submitting') {
    return 'Demande de renvoi en cours.'
  }
  if (mutationState.value === 'succeeded') {
    return 'La demande de renvoi a été prise en charge. Cela ne confirme pas l’envoi ni la livraison du message.'
  }
  if (mutationState.value === 'uncertain') {
    return errorMessage.value
  }
  if (mutationState.value === 'failed') {
    const message = errorMessage.value
    if (!message || message === 'Impossible de charger les parcours.') {
      return 'Nous n’avons pas pu envoyer votre e-mail de vérification. Veuillez réessayer.'
    }
    return message
  }
  if (emailMasque.value) {
    return 'L’envoi de votre e-mail de vérification est en cours.'
  }
  return ''
})

const statusTone = computed(() => {
  if (mutationState.value === 'failed') {
    return 'error'
  }
  if (mutationState.value === 'uncertain') {
    return 'warning'
  }
  return 'neutral'
})
</script>

<template>
  <main class="auth-screen verification-screen flex w-full flex-col">
    <Stepper
      compact
      :steps="registrationSteps('verification')"
      label="Étapes de l’inscription"
    />
    <div class="verification-logo flex justify-center">
      <BrandLogo size="lg" />
    </div>
    <div class="verification-mark" aria-hidden="true">
      <UiIcon name="mail" size="md" />
    </div>
    <PageHeader
      align="center"
      title="Votre compte a été créé"
      description="Vérifiez votre adresse e-mail pour activer votre compte Livreaura."
    />

    <p
      v-if="statusMessage"
      class="verification-status"
      :class="`is-${statusTone}`"
      role="status"
    >
      {{ statusMessage }}
    </p>

    <div v-if="emailMasque" class="verification-address">
      <p class="verification-address-label">Adresse à vérifier</p>
      <p class="verification-address-value">{{ emailMasque }}</p>
    </div>

    <p class="verification-instruction">
      <UiIcon name="info" class="verification-instruction-icon" />
      <span>Consultez votre boîte de réception et vos courriers indésirables, puis ouvrez le lien de vérification reçu.</span>
    </p>

    <EmailActivationPanel
      v-if="contextReady"
      hide-copy
      :needs-email="needsEmail"
      :email="typedEmail"
      :email-error="emailError"
      :loading="mutationState === 'submitting'"
      :wait-seconds="waitSeconds"
      :disabled="mutationState === 'submitting'"
      @update:email="(value) => { typedEmail = value; emailError = '' }"
      @resend="onResend"
    />

    <p class="verification-login">
      Vous avez déjà activé votre compte ?
      <UiLink class="login-primary-link" to="/connexion">Se connecter</UiLink>
    </p>

    <footer class="verification-footer">
      <nav aria-label="Assistance">
        <UiLink class="login-secondary" to="/recours">Déposer un recours</UiLink>
        <span class="login-note" aria-hidden="true">·</span>
        <UiLink class="login-secondary" to="/cloture">Demander une clôture</UiLink>
      </nav>
    </footer>
  </main>
</template>
