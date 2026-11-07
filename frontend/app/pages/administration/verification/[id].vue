<script setup lang="ts">
import { canAccessKycBackoffice } from '../../../utils/identity-access/account'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeReview } from '../../../composables/identity-access/useBackofficeReview'
import { labelForDossierStatus, labelForVerificationLevel } from '../../../utils/identity-access/verification'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

const route = useRoute()
const dossierId = computed(() => String(route.params.id || ''))

useHead({
  title: 'Dossier de vérification — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const {
  loadState,
  mutationState,
  recordingId,
  errorMessage,
  mutationMessage,
  sessionEnded,
  review,
  load,
  assign,
  takeCharge,
  recordControl,
  decide,
  download,
  restrict,
} = useBackofficeReview()

const retrying = ref(false)
const authorized = computed(() => canAccessKycBackoffice(account.value))
const controlsComplete = computed(() => review.value?.controles.every(control => control.resultat !== 'pending') === true)
const assignedToSelf = computed(() => Boolean(review.value?.tache?.responsable_id && account.value && review.value.tache.responsable_id === account.value.compte.id))

async function retry() {
  retrying.value = true
  try {
    await load(dossierId.value)
  }
  finally {
    retrying.value = false
  }
}

onMounted(() => {
  if (authorized.value && dossierId.value) {
    void load(dossierId.value)
  }
})

watch([authorized, dossierId], ([canLoad, id]) => {
  if (canLoad && id) {
    void load(id)
  }
})
</script>

<template>
  <div class="flex max-w-4xl flex-col gap-section px-page py-section">
    <PageHeader
      :title="review ? `${review.dossier.numero || 'Dossier'}.` : 'Dossier de vérification.'"
      context="Espace interne"
      :description="review ? `${labelForVerificationLevel(review.dossier.niveau)} · ${labelForDossierStatus(review.dossier.statut)} · version ${review.dossier.version}` : 'Examinez les preuves, consignez les contrôles, puis décidez.'"
    />

    <p>
      <UiLink to="/administration/verification">Retour aux vérifications KYC</UiLink>
    </p>

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="Le contrôle des dossiers exige une attribution interne, pas seulement le rôle administration."
    />
    <SessionExpiredState v-else-if="sessionEnded" />
    <template v-else-if="authorized">
      <Skeleton v-if="loadState === 'idle' || loadState === 'loading'" variant="detail" />
      <ErrorState
        v-else-if="loadState === 'error'"
        title="Impossible de charger le dossier"
        :message="errorMessage"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="retry"
      />
      <template v-else-if="review">
        <InlineAlert
          v-if="mutationState === 'uncertain'"
          tone="warning"
          title="Résultat incertain"
        >
          {{ mutationMessage }} Consultez l’état du dossier avant de répéter une décision.
        </InlineAlert>
        <InlineAlert
          v-else-if="mutationState === 'failed' && mutationMessage"
          tone="error"
          title="Action refusée"
        >
          {{ mutationMessage }}
        </InlineAlert>
        <InlineAlert
          v-else-if="mutationState === 'succeeded' && review.habilitation && review.habilitation.etat !== 'valid'"
          tone="success"
          title="Restriction enregistrée"
        >
          L’habilitation a été mise à jour. Le rétablissement exige une nouvelle version approuvée.
        </InlineAlert>
        <InlineAlert
          v-else-if="mutationState === 'succeeded' && review.decision"
          tone="success"
          title="Décision enregistrée"
        >
          La décision est consultable avec cette version. Elle n’est plus modifiable.
        </InlineAlert>

        <ConflictOfInterestAlert :visible="review.conflit_interet" />

        <DescriptionList
          :items="[
            { term: 'Sujet', value: review.dossier.sujet.raison_sociale || (review.dossier.sujet.type === 'particulier' ? 'Compte personnel' : 'Sujet indisponible'), missing: !review.dossier.sujet.raison_sociale && review.dossier.sujet.type !== 'particulier' },
            { term: 'Statut du dossier', value: labelForDossierStatus(review.dossier.statut) },
            { term: 'Niveau', value: labelForVerificationLevel(review.dossier.niveau) },
          ]"
        />

        <ServiceDeadlinePanel v-if="review.tache" :task="review.tache" />

        <AssignmentPanel
          v-if="review.tache && review.tache.etat_travail !== 'terminee'"
          :can-assign="review.capacites.affecter"
          :assigned-to-self="assignedToSelf"
          :assigning="mutationState === 'submitting' && !recordingId"
          :disabled-reason="review.conflit_interet ? 'Ce dossier est lié à ce compte.' : undefined"
          @assign="review.tache && assign(review.tache.id)"
        />

        <FormActions v-if="review.capacites.prendre_en_charge" align="start">
          <UiButton
            :loading="mutationState === 'submitting' && !recordingId"
            :disabled="mutationState === 'submitting'"
            @click="takeCharge(review.dossier.id)"
          >
            Prendre en charge ce dossier
          </UiButton>
        </FormActions>

        <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
          <h2 class="font-serif text-lg font-semibold text-text">
            Pièces
          </h2>
          <ul v-if="review.dossier.pieces.length > 0" class="mt-stack flex flex-col gap-stack">
            <li v-for="piece in review.dossier.pieces" :key="piece.id">
              <EvidenceFileRow
                :piece="piece"
                :disabled="!review.capacites.consulter_pieces"
                @download="download(review.dossier.id, $event, `${piece.type_doc}.bin`)"
              />
            </li>
          </ul>
          <p v-else class="mt-stack font-sans text-sm text-text-muted">
            Aucune pièce n’est associée à ce dossier.
          </p>
        </section>

        <VerificationControlGrid
          :controls="review.controles"
          :can-record="review.capacites.controler"
          :recording-id="recordingId"
          :disabled-reason="review.conflit_interet ? 'Ce dossier est lié à ce compte.' : undefined"
          @record="recordControl(review.dossier.id, $event)"
        />

        <VerificationDecisionPanel
          :can-decide="review.capacites.decider"
          :controls-complete="controlsComplete"
          :commercial-only="review.niveau_decision === 'hors_perimetre'"
          :commercial-decision="review.niveau_decision === 'partenaire_commercial'"
          :conflict="review.conflit_interet"
          :submitting="mutationState === 'submitting' && !recordingId"
          :decision="review.decision"
          :disabled-reason="review.niveau_decision === 'partenaire_commercial' && !review.capacites.decider
            ? 'La décision commerciale exige une attribution interne distincte, pas seulement le rôle administration.'
            : undefined"
          @decide="decide(review.dossier.id, $event)"
        />

        <VerificationCapabilityPanel :capability="review.habilitation" />

        <VerificationRestrictionPanel
          :capability="review.habilitation"
          :can-restrict="review.capacites.restreindre"
          :submitting="mutationState === 'submitting' && !recordingId"
          :disabled-reason="!review.capacites.restreindre
            ? 'La restriction exige une attribution interne distincte, pas seulement le rôle administration.'
            : undefined"
          @restrict="review.habilitation && restrict(review.habilitation.id, $event)"
        />
      </template>
    </template>
  </div>
</template>
