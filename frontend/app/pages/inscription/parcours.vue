<script setup lang="ts">
import { isKnownRegistrationPathCode } from '../../types/identity-access/registration'
import { useRegistrationPaths } from '../../composables/identity-access/useRegistrationPaths'
import { registrationSteps } from '../../utils/identity-access/registration-steps'

definePageMeta({
  layout: 'public',
  canvas: 'plain',
  background: 'white',
})

useHead({
  title: 'Choisir un parcours — Livreaura',
})

const route = useRoute()
const { data, loadState, errorMessage, refreshing, refresh } = await useRegistrationPaths('fr')

const selected = ref('')
const retrying = ref(false)

watch(
  data,
  (payload) => {
    const fromQuery = route.query.parcours
    const codes = payload?.parcours.map(path => path.code) ?? []
    if (typeof fromQuery === 'string' && isKnownRegistrationPathCode(fromQuery) && codes.includes(fromQuery)) {
      selected.value = fromQuery
      return
    }
    if (selected.value && !codes.includes(selected.value)) {
      selected.value = ''
    }
    if (!selected.value && codes.includes('lecteur')) {
      selected.value = 'lecteur'
    }
  },
  { immediate: true },
)

const canContinue = computed(() => {
  return loadState.value === 'ready' && isKnownRegistrationPathCode(selected.value)
})

async function retry() {
  retrying.value = true
  try {
    await refresh()
  }
  finally {
    retrying.value = false
  }
}

function continueToRegistration() {
  if (!canContinue.value) {
    return
  }
  return navigateTo({
    path: '/inscription',
    query: { parcours: selected.value },
  })
}
</script>

<template>
  <main class="auth-screen inscription-screen parcours-screen registration-screen flex w-full flex-col">
    <div>
      <Stepper
        class="mb-section"
        compact
        :steps="registrationSteps('parcours')"
        label="Étapes de l’inscription"
      />
      <div class="mb-stack flex justify-center">
        <BrandLogo size="lg" />
      </div>
      <PageHeader
        align="center"
        title="Choisissez votre parcours"
        description="Un même compte peut regrouper plusieurs usages. Vous pourrez compléter votre profil par la suite."
        :loading="loadState === 'loading'"
      />

      <div class="mt-section flex flex-col gap-stack">
        <InlineAlert v-if="refreshing" tone="info" title="Actualisation de la liste">
          Les parcours déjà affichés restent visibles jusqu’à réception de la nouvelle liste.
        </InlineAlert>

        <ErrorState
          v-if="loadState === 'error'"
          title="Impossible de charger les parcours"
          :message="errorMessage"
          retry-label="Réessayer"
          :retrying="retrying"
          @retry="retry"
        />

        <EmptyState
          v-else-if="loadState === 'empty'"
          plain
          title="Aucun parcours disponible"
          message="Aucun parcours d’inscription n’est proposé pour le moment."
        >
          <template #action>
            <UiLink class="login-primary-link" to="/">Retour à l’accueil</UiLink>
          </template>
        </EmptyState>

        <div v-else-if="loadState === 'loading'" class="path-choice-grid" aria-busy="true">
          <Skeleton variant="card" />
          <Skeleton variant="card" />
          <Skeleton variant="card" />
        </div>

        <form
          v-else
          class="path-choices flex flex-col gap-section"
          @submit.prevent="continueToRegistration"
        >
          <RegistrationPathSelector
            v-model="selected"
            :paths="data?.parcours ?? []"
          />

          <div class="auth-submit">
            <UiButton
              type="submit"
              :disabled="!canContinue"
              :disabled-reason="canContinue ? undefined : 'Choisissez un parcours.'"
            >
              Continuer vers les informations personnelles
            </UiButton>
          </div>
        </form>
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
