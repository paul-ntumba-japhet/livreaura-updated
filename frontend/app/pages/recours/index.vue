<script setup lang="ts">
import { useOpenSuspensionAppeal } from '../../composables/identity-access/useOpenSuspensionAppeal'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
  ssr: false,
})

useHead({
  title: 'Recours après suspension — Livreaura',
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
} = useOpenSuspensionAppeal()

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
        title="Déposer un recours"
        description="Ce formulaire est accessible sans session normale. L’accusé ne révèle pas si un compte existe."
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
          title="Recours enregistré"
        >
          Conservez la référence {{ result.reference }}. Elle permet le suivi. Aucun accès n’est ouvert par cet accusé. Aucun jeton n’a été envoyé par e-mail.
        </RecoveryRequestResult>

        <PublicSupportForm
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
        Vous n’avez pas de compte ?
        <UiLink class="login-primary-link" to="/inscription/parcours">Créer un compte</UiLink>
      </p>
    </div>

    <footer class="mt-section flex flex-col items-center gap-2 text-center">
      <nav
        class="flex flex-wrap items-center justify-center gap-x-gutter gap-y-1"
        aria-label="Assistance"
      >
        <UiLink class="login-secondary" to="/recours" current>Déposer un recours</UiLink>
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
