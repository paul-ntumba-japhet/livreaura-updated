<script setup lang="ts">
import { useOpenAccountClosure } from '../../composables/identity-access/useOpenAccountClosure'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
  ssr: false,
})

useHead({
  title: 'Demande de clôture — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
    { name: 'referrer', content: 'no-referrer' },
  ],
})

const {
  mutationState,
  fieldErrors,
  errorMessage,
  result,
  submit,
} = useOpenAccountClosure()

const email = ref('')
const message = ref('')
const submitting = computed(() => mutationState.value === 'submitting')

async function onSubmit() {
  await submit({
    email: email.value.trim(),
    message: message.value.trim(),
  })
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
        title="Demander la clôture"
        description="Ce formulaire enregistre une demande. Il ne ferme pas le compte. L’accusé ne révèle pas si un compte existe."
      />

      <div class="mt-section flex flex-col gap-stack">
        <InlineAlert
          v-if="mutationState === 'uncertain'"
          tone="warning"
          title="Résultat incertain"
        >
          {{ errorMessage }} Conservez votre saisie, puis consultez éventuellement une référence déjà reçue avant de redéposer.
        </InlineAlert>
        <InlineAlert
          v-else-if="mutationState === 'failed' && errorMessage"
          tone="error"
          title="Dépôt refusé"
        >
          {{ errorMessage }}
        </InlineAlert>
        <RecoveryRequestResult
          v-else-if="mutationState === 'succeeded' && result"
          title="Demande de clôture enregistrée"
        >
          Conservez la référence {{ result.reference }}. L’identité reste à vérifier. Aucune fermeture n’est effectuée par cet accusé. Aucun jeton n’a été envoyé par e-mail.
        </RecoveryRequestResult>

        <AccountClosureRequest
          v-if="mutationState !== 'succeeded'"
          v-model:email="email"
          v-model:message="message"
          block
          :submitting="submitting"
          :field-errors="fieldErrors"
          @submit="onSubmit"
        />
      </div>

      <p class="login-note mt-stack text-center">
        <UiLink class="login-primary-link" to="/connexion">Retour à la connexion</UiLink>
      </p>
      <p class="login-note mt-2 text-center">
        <UiLink class="login-primary-link" to="/compte">Retour au compte</UiLink>
      </p>
    </div>

    <footer class="mt-section flex flex-col items-center gap-2 text-center">
      <nav
        class="flex flex-wrap items-center justify-center gap-x-gutter gap-y-1"
        aria-label="Assistance"
      >
        <UiLink class="login-secondary" to="/recours">Déposer un recours</UiLink>
        <span class="login-note" aria-hidden="true">·</span>
        <UiLink class="login-secondary" to="/cloture" current>Demander une clôture</UiLink>
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
