<script setup lang="ts">
import { canTransferPrincipal } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useRepresentativeTransfer } from '../../../composables/identity-access/useRepresentativeTransfer'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

const route = useRoute()
const organizationId = computed(() => String(route.params.id || '').toUpperCase())

useHead({
  title: 'Transfert de représentation — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { mutationState, errorMessage, sessionEnded, result, transfer } = useRepresentativeTransfer()

const authorized = computed(() => canTransferPrincipal(account.value))
const nouveauCompteId = ref('')
const motif = ref('')
const depart = ref(false)
const confirmOpen = ref(false)

const submitting = computed(() => mutationState.value === 'submitting')

function openConfirm() {
  if (!authorized.value || submitting.value) {
    return
  }
  confirmOpen.value = true
}

async function confirm() {
  if (submitting.value) {
    return
  }
  await transfer(organizationId.value, {
    nouveau_compte_id: nouveauCompteId.value.trim().toUpperCase(),
    motif: motif.value.trim(),
    depart: depart.value,
  })
  if (mutationState.value !== 'submitting') {
    confirmOpen.value = false
  }
}
</script>

<template>
  <div class="flex max-w-4xl flex-col gap-section px-page py-section">
    <PageHeader
      title="Transfert de représentation."
      context="Espace interne"
      description="Remplacez le représentant principal après vérification du mandat. Un seul principal actif est conservé."
    />

    <p>
      <UiLink to="/administration/organisations">Retour aux organisations et représentants</UiLink>
    </p>

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="Le transfert de représentant exige une attribution interne distincte, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour à l’accueil</UiLink>
      </template>
    </ForbiddenState>
    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <InlineAlert
        v-if="mutationState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ errorMessage }} Consultez l’état de l’organisation avant de répéter le transfert.
      </InlineAlert>
      <InlineAlert
        v-else-if="mutationState === 'failed' && errorMessage"
        tone="error"
        title="Transfert refusé"
      >
        {{ errorMessage }}
      </InlineAlert>
      <InlineAlert
        v-else-if="mutationState === 'succeeded' && result"
        tone="success"
        title="Transfert enregistré"
      >
        Le nouveau représentant est en place. Référence {{ result.demande?.reference || 'indisponible' }}.
        Les sessions du contexte précédent ont été révoquées.
      </InlineAlert>

      <RepresentativeTransferComparison
        v-if="result"
        :ancien="result.ancien"
        :nouveau="result.nouveau"
        :depart="result.depart"
      />

      <RepresentativeTransferRequest
        v-if="mutationState !== 'succeeded'"
        v-model:nouveau-compte-id="nouveauCompteId"
        v-model:motif="motif"
        v-model:depart="depart"
        :can-transfer="authorized"
        :submitting="submitting"
        @submit="openConfirm"
      />

      <RepresentativeTransferConfirmation
        :open="confirmOpen"
        :submitting="submitting"
        :depart="depart"
        @confirm="confirm"
        @cancel="confirmOpen = false"
      />
    </template>
  </div>
</template>
