<script setup lang="ts">
import { canCloseAccount, canHandleSupport, canReactivateAccount, canSuspendAccount, labelForAccountStatus } from '../../../utils/identity-access/account'
import { normalizeAccountStatus } from '../../../types/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useAccountClosure } from '../../../composables/identity-access/useAccountClosure'
import { useAccountReactivation } from '../../../composables/identity-access/useAccountReactivation'
import { useAccountSuspension } from '../../../composables/identity-access/useAccountSuspension'
import { useVerifyClosureIdentity } from '../../../composables/identity-access/useVerifyClosureIdentity'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

const route = useRoute()
const targetId = computed(() => String(route.params.id || '').toUpperCase())

useHead({
  title: 'Compte — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const {
  mutationState: suspendState,
  errorMessage: suspendError,
  sessionEnded: suspendSessionEnded,
  result: suspendResult,
  suspend,
} = useAccountSuspension()
const {
  mutationState: reactivateState,
  errorMessage: reactivateError,
  fieldErrors: reactivateFieldErrors,
  sessionEnded: reactivateSessionEnded,
  result: reactivateResult,
  reactivate,
} = useAccountReactivation()

const {
  mutationState: closeState,
  errorMessage: closeError,
  fieldErrors: closeFieldErrors,
  sessionEnded: closeSessionEnded,
  result: closeResult,
  close,
} = useAccountClosure()
const {
  mutationState: identityState,
  errorMessage: identityError,
  fieldErrors: identityFieldErrors,
  sessionEnded: identitySessionEnded,
  result: identityResult,
  verify,
} = useVerifyClosureIdentity()

const canSuspend = computed(() => canSuspendAccount(account.value))
const canReactivate = computed(() => canReactivateAccount(account.value))
const canClose = computed(() => canCloseAccount(account.value))
const canVerifyClosure = computed(() => canHandleSupport(account.value))
const authorized = computed(() => canSuspend.value || canReactivate.value || canClose.value || canVerifyClosure.value)
const isSelf = computed(() => Boolean(account.value?.compte?.id && account.value.compte.id === targetId.value))
const sessionEnded = computed(() => suspendSessionEnded.value || reactivateSessionEnded.value || closeSessionEnded.value || identitySessionEnded.value)

const motif = ref('')
const finPrevue = ref('')
const confirmSuspendOpen = ref(false)

const demandeId = ref('')
const reactivateMotif = ref('')
const avisSecurite = ref('')
const confirmReactivateOpen = ref(false)

const closeDemandeId = ref('')
const closeMotif = ref('')
const confirmCloseOpen = ref(false)

const identityDemandeId = ref('')
const identityMotif = ref('')
const identityMethode = ref('pieces')
const confirmIdentityOpen = ref(false)

const suspendSubmitting = computed(() => suspendState.value === 'submitting')
const reactivateSubmitting = computed(() => reactivateState.value === 'submitting')
const closeSubmitting = computed(() => closeState.value === 'submitting')
const identitySubmitting = computed(() => identityState.value === 'submitting')

function isoFromLocal(value: string): string | null {
  const trimmed = value.trim()
  if (!trimmed) {
    return null
  }
  const date = new Date(trimmed)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  return date.toISOString()
}

function formatDate(iso: string | null | undefined): string {
  if (!iso) {
    return 'Aucune'
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return 'Indisponible'
  }
  const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
  return `${new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone,
  }).format(date)} (${timeZone})`
}

function conditionLabel(value: boolean): string {
  return value ? 'Réunie' : 'Manquante'
}

function openSuspendConfirm() {
  if (!canSuspend.value || isSelf.value || suspendSubmitting.value) {
    return
  }
  confirmSuspendOpen.value = true
}

async function confirmSuspend() {
  if (suspendSubmitting.value) {
    return
  }
  await suspend(targetId.value, {
    motif: motif.value.trim(),
    fin_prevue: isoFromLocal(finPrevue.value),
  })
  if (suspendState.value !== 'submitting') {
    confirmSuspendOpen.value = false
  }
}

function openReactivateConfirm() {
  if (!canReactivate.value || isSelf.value || reactivateSubmitting.value) {
    return
  }
  confirmReactivateOpen.value = true
}

async function confirmReactivate() {
  if (reactivateSubmitting.value) {
    return
  }
  await reactivate(targetId.value, {
    demande_id: demandeId.value.trim(),
    motif: reactivateMotif.value.trim(),
    avis_securite: avisSecurite.value.trim() || null,
  })
  if (reactivateState.value !== 'submitting') {
    confirmReactivateOpen.value = false
  }
}

function openCloseConfirm() {
  if (!canClose.value || isSelf.value || closeSubmitting.value) {
    return
  }
  confirmCloseOpen.value = true
}

async function confirmClose() {
  if (closeSubmitting.value) {
    return
  }
  await close(targetId.value, {
    demande_id: closeDemandeId.value.trim(),
    motif: closeMotif.value.trim(),
  })
  if (closeState.value !== 'submitting') {
    confirmCloseOpen.value = false
  }
}

function openIdentityConfirm() {
  if (!canVerifyClosure.value || isSelf.value || identitySubmitting.value) {
    return
  }
  confirmIdentityOpen.value = true
}

async function confirmIdentity() {
  if (identitySubmitting.value) {
    return
  }
  await verify(identityDemandeId.value.trim(), {
    compte_id: targetId.value,
    motif: identityMotif.value.trim(),
    methode_verification: identityMethode.value.trim(),
  })
  if (identityState.value === 'succeeded' && identityResult.value?.demande.reference) {
    closeDemandeId.value = identityResult.value.demande.reference
  }
  if (identityState.value !== 'submitting') {
    confirmIdentityOpen.value = false
  }
}

const suspendSummaryItems = computed(() => {
  if (!suspendResult.value) {
    return []
  }
  const organisations = suspendResult.value.organisations_restreintes
    .map(item => item.raison_sociale)
    .filter(Boolean)
    .join(', ')
  return [
    {
      term: 'Compte',
      value: suspendResult.value.compte.email_masque || 'Adresse masquée indisponible',
      missing: !suspendResult.value.compte.email_masque,
    },
    {
      term: 'Statut',
      value: labelForAccountStatus(normalizeAccountStatus(suspendResult.value.compte.statut)),
    },
    {
      term: 'Sessions révoquées',
      value: String(suspendResult.value.sessions_revoquees),
    },
    {
      term: 'Fin prévue',
      value: formatDate(suspendResult.value.fin_prevue),
      missing: !suspendResult.value.fin_prevue,
    },
    {
      term: 'Organisations restreintes',
      value: organisations || 'Aucune',
      missing: organisations === '',
    },
    {
      term: 'Propagation',
      value: suspendResult.value.propagation === 'en_cours' ? 'En cours' : (suspendResult.value.propagation || 'Indisponible'),
      missing: suspendResult.value.propagation !== 'en_cours',
    },
  ]
})

const reactivateSummaryItems = computed(() => {
  if (!reactivateResult.value) {
    return []
  }
  return [
    {
      term: 'Compte',
      value: reactivateResult.value.compte.email_masque || 'Adresse masquée indisponible',
      missing: !reactivateResult.value.compte.email_masque,
    },
    {
      term: 'Statut',
      value: labelForAccountStatus(normalizeAccountStatus(reactivateResult.value.compte.statut)),
    },
    {
      term: 'Dossier',
      value: reactivateResult.value.demande.reference,
    },
    {
      term: 'Sessions révoquées',
      value: String(reactivateResult.value.sessions_revoquees),
    },
    {
      term: 'E-mail vérifié',
      value: conditionLabel(reactivateResult.value.conditions.email_verifie),
    },
    {
      term: 'Profil complet',
      value: conditionLabel(reactivateResult.value.conditions.profil_complet),
    },
    {
      term: 'Textes requis',
      value: conditionLabel(reactivateResult.value.conditions.textes_requis),
    },
    {
      term: 'Avis de sécurité',
      value: reactivateResult.value.avis_securite_consigne ? 'Consigné' : 'Non requis ou non consigné',
      missing: !reactivateResult.value.avis_securite_consigne,
    },
    {
      term: 'Propagation',
      value: reactivateResult.value.propagation === 'en_cours' ? 'En cours' : (reactivateResult.value.propagation || 'Indisponible'),
      missing: reactivateResult.value.propagation !== 'en_cours',
    },
  ]
})

const closeSummaryItems = computed(() => {
  if (!closeResult.value) {
    return []
  }
  return [
    {
      term: 'Compte',
      value: closeResult.value.compte.email_masque || 'Adresse masquée indisponible',
      missing: !closeResult.value.compte.email_masque,
    },
    {
      term: 'Statut',
      value: labelForAccountStatus(normalizeAccountStatus(closeResult.value.compte.statut)),
    },
    {
      term: 'Dossier',
      value: closeResult.value.demande.reference,
    },
    {
      term: 'Fermé le',
      value: formatDate(closeResult.value.compte.ferme_a),
      missing: !closeResult.value.compte.ferme_a,
    },
    {
      term: 'Sessions révoquées',
      value: String(closeResult.value.sessions_revoquees),
    },
    {
      term: 'Défis révoqués',
      value: String(closeResult.value.defis_revoques),
    },
    {
      term: 'Propagation',
      value: closeResult.value.propagation === 'en_cours' ? 'En cours' : (closeResult.value.propagation || 'Indisponible'),
      missing: closeResult.value.propagation !== 'en_cours',
    },
  ]
})
</script>

<template>
  <div class="flex max-w-4xl flex-col gap-section px-page py-section">
    <PageHeader
      title="Compte."
      context="Espace interne"
      description="La suspension coupe les accès. La vérification d’identité d’une clôture, puis la clôture, restent des actes distincts. Les habilitations KYC révoquées ne sont pas rétablies."
    />

    <p>
      <UiLink to="/administration/comptes">Retour aux comptes et accès</UiLink>
    </p>

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La suspension, la réactivation, la vérification d’une clôture ou la clôture d’un compte exige une attribution interne distincte, pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour à l’accueil</UiLink>
      </template>
    </ForbiddenState>
    <SessionExpiredState v-else-if="sessionEnded" />

    <template v-else-if="authorized">
      <InlineAlert
        v-if="suspendState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ suspendError }} Consultez l’état du compte avant de répéter la suspension.
      </InlineAlert>
      <InlineAlert
        v-else-if="suspendState === 'failed' && suspendError"
        tone="error"
        title="Suspension refusée"
      >
        {{ suspendError }}
      </InlineAlert>
      <InlineAlert
        v-else-if="suspendState === 'succeeded' && suspendResult"
        tone="success"
        title="Suspension enregistrée"
      >
        Le compte est suspendu. Les sessions d’authentification ont été révoquées. La propagation vers la lecture reste en cours tant que l’effet n’est pas confirmé.
      </InlineAlert>

      <section
        v-if="suspendResult"
        class="rounded-lg border border-border bg-surface-raised px-gutter py-stack"
      >
        <h2 class="font-serif text-lg font-semibold text-text">
          Suspension consignée
        </h2>
        <DescriptionList
          class="mt-stack"
          :items="suspendSummaryItems"
        />
      </section>

      <AccountAdministrationPanel
        v-if="canSuspend && suspendState !== 'succeeded'"
        v-model:motif="motif"
        v-model:fin-prevue="finPrevue"
        :can-suspend="canSuspend"
        :is-self="isSelf"
        :submitting="suspendSubmitting"
        @submit="openSuspendConfirm"
      />

      <InlineAlert
        v-if="reactivateState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ reactivateError }} Consultez l’état du compte avant de répéter la réactivation.
      </InlineAlert>
      <InlineAlert
        v-else-if="reactivateState === 'failed' && reactivateError"
        tone="error"
        title="Réactivation refusée"
      >
        {{ reactivateError }}
      </InlineAlert>
      <InlineAlert
        v-else-if="reactivateState === 'succeeded' && reactivateResult"
        tone="success"
        title="Réactivation enregistrée"
      >
        La suspension a été levée. Une nouvelle connexion est nécessaire. Les habilitations de vérification ne sont pas rétablies automatiquement.
      </InlineAlert>

      <section
        v-if="reactivateResult"
        class="rounded-lg border border-border bg-surface-raised px-gutter py-stack"
      >
        <h2 class="font-serif text-lg font-semibold text-text">
          Réactivation consignée
        </h2>
        <DescriptionList
          class="mt-stack"
          :items="reactivateSummaryItems"
        />
      </section>

      <AccountReactivationPanel
        v-if="canReactivate && reactivateState !== 'succeeded'"
        v-model:demande-id="demandeId"
        v-model:motif="reactivateMotif"
        v-model:avis-securite="avisSecurite"
        :can-reactivate="canReactivate"
        :is-self="isSelf"
        :submitting="reactivateSubmitting"
        :field-errors="reactivateFieldErrors"
        @submit="openReactivateConfirm"
      />

      <InlineAlert
        v-if="closeState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ closeError }} Consultez l’état du compte avant de répéter la clôture.
      </InlineAlert>
      <InlineAlert
        v-else-if="closeState === 'failed' && closeError"
        tone="error"
        title="Clôture refusée"
      >
        {{ closeError }}
      </InlineAlert>
      <InlineAlert
        v-else-if="closeState === 'succeeded' && closeResult"
        tone="success"
        title="Clôture enregistrée"
      >
        Le compte est fermé. Les sessions et défis ouverts ont été révoqués. Aucun ancien lien ne restaure un accès normal.
      </InlineAlert>

      <section
        v-if="closeResult"
        class="rounded-lg border border-border bg-surface-raised px-gutter py-stack"
      >
        <h2 class="font-serif text-lg font-semibold text-text">
          Clôture consignée
        </h2>
        <DescriptionList
          class="mt-stack"
          :items="closeSummaryItems"
        />
      </section>

      <InlineAlert
        v-if="identityState === 'uncertain'"
        tone="warning"
        title="Résultat incertain"
      >
        {{ identityError }} Consultez l’état de la demande avant de répéter la vérification.
      </InlineAlert>
      <InlineAlert
        v-else-if="identityState === 'failed' && identityError"
        tone="error"
        title="Vérification refusée"
      >
        {{ identityError }}
      </InlineAlert>
      <InlineAlert
        v-else-if="identityState === 'succeeded' && identityResult"
        tone="success"
        title="Identité consignée"
      >
        Référence {{ identityResult.demande.reference }}. La fermeture du compte n’est pas encore décidée.
      </InlineAlert>

      <ClaimantIdentityReview
        v-if="canVerifyClosure && identityState !== 'succeeded'"
        v-model:demande-id="identityDemandeId"
        :compte-id="targetId"
        v-model:motif="identityMotif"
        v-model:methode-verification="identityMethode"
        :can-verify="canVerifyClosure"
        :is-self="isSelf"
        :compte-locked="true"
        :submitting="identitySubmitting"
        :field-errors="identityFieldErrors"
        @submit="openIdentityConfirm"
      />

      <AccountClosurePanel
        v-if="canClose && closeState !== 'succeeded'"
        v-model:demande-id="closeDemandeId"
        v-model:motif="closeMotif"
        :can-close="canClose"
        :is-self="isSelf"
        :submitting="closeSubmitting"
        :field-errors="closeFieldErrors"
        @submit="openCloseConfirm"
      />

      <ConfirmDialog
        :open="confirmSuspendOpen"
        title="Confirmer la suspension"
        confirm-label="Suspendre ce compte"
        :submitting="suspendSubmitting"
        destructive
        @confirm="confirmSuspend"
        @cancel="confirmSuspendOpen = false"
      >
        <p>
          Toutes les sessions d’authentification de ce compte seront révoquées. Les nouvelles connexions normales seront refusées. Cette action attend la confirmation du serveur.
        </p>
        <p class="mt-stack">
          Si ce compte est représentant principal d’une organisation active, l’organisation sera restreinte jusqu’au transfert du principal. Les autres membres ne seront pas suspendus.
        </p>
      </ConfirmDialog>

      <ConfirmDialog
        :open="confirmReactivateOpen"
        title="Confirmer la réactivation"
        confirm-label="Réactiver ce compte"
        :submitting="reactivateSubmitting"
        @confirm="confirmReactivate"
        @cancel="confirmReactivateOpen = false"
      >
        <p>
          La suspension sera levée si le dossier est vérifié et les conditions examinées. Une nouvelle connexion sera exigée. Les habilitations KYC révoquées ne seront pas rétablies. Cette action attend la confirmation du serveur.
        </p>
      </ConfirmDialog>

      <ConfirmDialog
        :open="confirmCloseOpen"
        title="Confirmer la clôture"
        confirm-label="Clôturer ce compte"
        :submitting="closeSubmitting"
        destructive
        @confirm="confirmClose"
        @cancel="confirmCloseOpen = false"
      >
        <p>
          Le compte sera fermé définitivement. Les sessions et défis ouverts seront révoqués. L’historique est conservé. Aucune réouverture par récupération. Cette action attend la confirmation du serveur.
        </p>
        <p class="mt-stack">
          Si ce compte est encore représentant principal, la clôture sera refusée jusqu’au transfert.
        </p>
      </ConfirmDialog>

      <ConfirmDialog
        :open="confirmIdentityOpen"
        title="Confirmer la vérification d’identité"
        confirm-label="Consigner la vérification d’identité"
        :submitting="identitySubmitting"
        @confirm="confirmIdentity"
        @cancel="confirmIdentityOpen = false"
      >
        <p>
          L’identité du titulaire sera consignée sur cette demande de clôture. Le compte ne sera pas fermé par cette action. Cette action attend la confirmation du serveur.
        </p>
      </ConfirmDialog>
    </template>
  </div>
</template>
