<script setup lang="ts">
import { isKnownRegistrationPathCode } from '../../types/identity-access/registration'
import type { KnownRegistrationPathCode, RegisterAccountPayload } from '../../types/identity-access/registration'
import { rememberEmailActivationContext } from '../../utils/identity-access/email-activation'
import { useRegistrationPaths } from '../../composables/identity-access/useRegistrationPaths'
import { useRegisterAccount } from '../../composables/identity-access/useRegisterAccount'
import { registrationSteps } from '../../utils/identity-access/registration-steps'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
})

useHead({
  title: 'Créer un compte — Livreaura',
})

const route = useRoute()
const { data, loadState, errorMessage, refreshing, refresh } = await useRegistrationPaths('fr')
const { mutationState, fieldErrors, errorMessage: mutationError, errorCode, result, submit } = useRegisterAccount()

const retrying = ref(false)
const formPanel = ref<'informations' | 'securite'>('informations')

const requestedPath = computed(() => {
  const value = route.query.parcours
  return typeof value === 'string' ? value : ''
})

const path = computed<KnownRegistrationPathCode | null>(() => {
  const codes = data.value?.parcours.map(item => item.code) ?? []
  if (isKnownRegistrationPathCode(requestedPath.value) && codes.includes(requestedPath.value)) {
    return requestedPath.value
  }
  return null
})

const pathLabel = computed(() => {
  return data.value?.parcours.find(item => item.code === path.value)?.libelle ?? ''
})

const currentStep = computed(() => {
  if (mutationState.value === 'succeeded') {
    return 'verification'
  }
  return formPanel.value
})

watch(
  [loadState, requestedPath, data],
  () => {
    if (loadState.value !== 'ready') {
      return
    }
    if (!path.value) {
      return navigateTo({
        path: '/inscription/parcours',
        query: isKnownRegistrationPathCode(requestedPath.value)
          ? { parcours: requestedPath.value }
          : {},
      })
    }
  },
  { immediate: true },
)

async function retry() {
  retrying.value = true
  try {
    await refresh()
  }
  finally {
    retrying.value = false
  }
}

async function onSubmit(payload: RegisterAccountPayload) {
  await submit(payload)
  if (mutationState.value !== 'succeeded' || !result.value) {
    return
  }
  rememberEmailActivationContext({
    email: payload.email,
    emailMasque: result.value.compte.email_masque,
  })
  return navigateTo({
    path: '/inscription/verification',
    query: path.value ? { parcours: path.value } : {},
  })
}

const showExistingAccountHelp = computed(() => {
  return mutationState.value === 'failed' && errorCode.value === 'compte_existant'
})
</script>

<template>
  <main
    class="auth-screen inscription-screen registration-screen flex w-full flex-col"
    :class="{
      'informations-screen': formPanel === 'informations',
      'security-screen': formPanel === 'securite' && path === 'lecteur',
    }"
  >
    <div>
      <Stepper
        class="mb-section"
        compact
        :steps="registrationSteps(currentStep)"
        label="Étapes de l’inscription"
      />
      <div class="mb-stack flex justify-center">
        <BrandLogo size="lg" />
      </div>
      <StepBackAction
        v-if="path && mutationState !== 'succeeded' && formPanel === 'informations'"
        class="login-primary-link mb-stack"
        label="Retour au choix du parcours"
        :to="{ path: '/inscription/parcours', query: { parcours: path } }"
      />
      <StepBackAction
        v-else-if="path && mutationState !== 'succeeded' && formPanel === 'securite'"
        class="login-primary-link mb-stack"
        label="Retour aux informations personnelles"
        :disabled="mutationState === 'submitting'"
        @click="formPanel = 'informations'"
      />
      <PageHeader
        align="center"
        :title="formPanel === 'securite'
          ? 'Sécurisez votre compte'
          : 'Indiquez vos informations personnelles'"
        :context="pathLabel ? `Parcours : ${pathLabel}` : undefined"
        :loading="loadState === 'loading'"
      />

      <div class="mt-section flex flex-col gap-stack">
        <InlineAlert v-if="refreshing" tone="info" title="Actualisation des textes">
          Les informations déjà affichées restent visibles jusqu’à réception de la nouvelle liste.
        </InlineAlert>

        <ErrorState
          v-if="loadState === 'error'"
          title="Impossible de préparer l’inscription"
          :message="errorMessage"
          retry-label="Réessayer"
          :retrying="retrying"
          @retry="retry"
        />

        <EmptyState
          v-else-if="loadState === 'empty'"
          plain
          title="Inscription indisponible"
          message="Aucun parcours d’inscription n’est proposé pour le moment."
        >
          <template #action>
            <UiLink class="login-primary-link" to="/inscription/parcours">Retour au choix du parcours</UiLink>
          </template>
        </EmptyState>

        <div v-else-if="loadState === 'loading'" aria-busy="true">
          <Skeleton variant="form" />
        </div>

        <template v-else-if="path && mutationState !== 'succeeded'">
          <InlineAlert
            v-if="path === 'representant'"
            tone="info"
            title="Organisation plus tard"
          >
            Ce parcours prépare un compte personnel. La création ou la reconnaissance d’une organisation se fait après activation, pas à l’inscription.
          </InlineAlert>

          <InlineAlert
            v-if="mutationState === 'failed' || mutationState === 'uncertain'"
            :tone="mutationState === 'uncertain' ? 'warning' : 'error'"
            :title="mutationState === 'uncertain' ? 'Résultat incertain' : 'Création refusée'"
          >
            {{ mutationError }}
          </InlineAlert>
          <p v-if="showExistingAccountHelp" class="login-note text-center">
            <UiLink class="login-primary-link" to="/connexion">Connexion</UiLink>
            ou
            <UiLink class="login-primary-link" to="/recuperation">Récupérer l’accès</UiLink>
          </p>

          <RegistrationAccountForm
            v-model:panel="formPanel"
            :path="path"
            :texts="data?.textes_requis ?? []"
            :disabled="mutationState === 'submitting'"
            :submitting="mutationState === 'submitting'"
            :field-errors="fieldErrors"
            @submit="onSubmit"
          />
        </template>
      </div>

      <p class="login-note mt-stack text-center">
        Vous avez déjà un compte ?
        <UiLink class="login-primary-link" to="/connexion">Connexion</UiLink>
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
