<script setup lang="ts">
import type { VerificationDossier } from '../../../types/identity-access/verification'
import { normalizeDossierStatus } from '../../../types/identity-access/verification'
import { normalizeCapabilityState } from '../../../types/identity-access/backoffice'
import { deadlineKind, labelForCapabilityState } from '../../../utils/identity-access/backoffice'
import {
  canOpenSuccessorVersion,
  followUpStatusLabel,
  followUpStatusMessage,
  labelForGap,
  labelForRequirementCode,
  toneForDossierStatus,
} from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    dossier: VerificationDossier
    subjectLine?: string
    otherDossiers?: boolean
    canDeposit?: boolean
    opening?: boolean
    downloadingId?: string | null
    downloadError?: string
    detailsOpen?: boolean
  }>(),
  {
    subjectLine: '',
    otherDossiers: false,
    canDeposit: false,
    opening: false,
    detailsOpen: false,
  },
)

const emit = defineEmits<{
  'view-dossiers': []
  'toggle-details': []
  renew: []
  download: [pieceId: string]
}>()

const status = computed(() => normalizeDossierStatus(props.dossier.statut))
const statusLabel = computed(() => followUpStatusLabel(props.dossier.statut))
const statusMessage = computed(() => followUpStatusMessage(props.dossier.statut))
const submittedOn = computed(() => formatDossierDate(props.dossier.soumis_a))
const actionLabel = computed(() => {
  if (!props.canDeposit || !canOpenSuccessorVersion(props.dossier.statut)) {
    return ''
  }
  if (status.value === 'needs_fix') {
    return 'Compléter le dossier'
  }
  return 'Ouvrir une nouvelle version'
})
const requests = computed(() => status.value === 'needs_fix' ? props.dossier.manques : [])
const capabilityLabel = computed(() => {
  if (!props.dossier.habilitation) {
    return ''
  }
  return labelForCapabilityState(props.dossier.habilitation.etat)
})
const capabilityDeadline = computed(() => formatDossierDate(props.dossier.habilitation?.valide_jusqua))
const confirmedCapability = computed(() => {
  return status.value === 'approved'
    && normalizeCapabilityState(props.dossier.habilitation?.etat) === 'valid'
})
const history = computed(() => {
  const events: Array<{ id: string, label: string, at: string }> = []
  for (const piece of props.dossier.pieces) {
    const at = formatDossierDate(piece.depose_a)
    if (!at) {
      continue
    }
    events.push({
      id: piece.id,
      label: labelForRequirementCode(piece.type_doc),
      at,
    })
  }
  const submitted = formatDossierDate(props.dossier.soumis_a)
  if (submitted) {
    events.push({
      id: 'soumission',
      label: 'Soumission du dossier',
      at: submitted,
    })
  }
  return events
})
const showCapabilityRenew = computed(() => {
  const capability = props.dossier.habilitation
  if (!props.canDeposit || !capability || canOpenSuccessorVersion(props.dossier.statut)) {
    return false
  }
  if (normalizeCapabilityState(capability.etat) !== 'valid') {
    return true
  }
  const kind = deadlineKind(capability.valide_jusqua)
  return kind === 'soon' || kind === 'overdue'
})

function formatDossierDate(iso?: string | null): string {
  if (!iso) {
    return ''
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(date)
}
</script>

<template>
  <article id="dossier-suivi" class="dossier-follow">
    <header>
      <h1 class="dossier-title">Dossier de vérification</h1>
      <p v-if="subjectLine" class="dossier-subject">{{ subjectLine }}</p>
      <div class="dossier-status">
        <StatusBadge
          :label="statusLabel"
          :tone="toneForDossierStatus(dossier.statut)"
        />
      </div>
      <p class="dossier-message">{{ statusMessage }}</p>
      <p v-if="confirmedCapability" class="dossier-note">
        L’habilitation associée est {{ capabilityLabel.toLowerCase() }}.
      </p>
      <ul v-if="requests.length > 0" class="dossier-requests">
        <li v-for="gap in requests" :key="`${gap.code}-${gap.motif}`">
          {{ labelForGap(gap) }}
        </li>
      </ul>
      <p v-if="status === 'rejected' && dossier.habilitation?.motif_restriction" class="dossier-note">
        {{ dossier.habilitation.motif_restriction }}
      </p>
      <p v-if="status === 'rejected'">
        <UiLink to="/compte/recours" muted>
          Consulter les recours
        </UiLink>
      </p>
    </header>

    <dl class="dossier-facts">
      <div v-if="dossier.numero">
        <dt>Référence</dt>
        <dd>
          <CopyReference :value="dossier.numero" />
        </dd>
      </div>
      <div v-else>
        <dt>Référence</dt>
        <dd>Non communiquée</dd>
      </div>
      <div v-if="submittedOn">
        <dt>Date de soumission</dt>
        <dd>{{ submittedOn }}</dd>
      </div>
    </dl>

    <div v-if="actionLabel">
      <UiButton
        :loading="opening"
        :disabled="opening"
        @click="emit('renew')"
      >
        {{ actionLabel }}
      </UiButton>
    </div>

    <div>
      <button
        type="button"
        class="dossier-disclosure focus-ring"
        :aria-expanded="detailsOpen ? 'true' : 'false'"
        aria-controls="dossier-details"
        @click="emit('toggle-details')"
      >
        Détails du dossier
      </button>
      <div
        v-if="detailsOpen"
        id="dossier-details"
        class="dossier-details"
      >
        <dl class="dossier-facts">
          <div>
            <dt>Version</dt>
            <dd>{{ dossier.version }}</dd>
          </div>
        </dl>

        <div v-if="history.length > 0">
          <h2 class="dossier-details-title">Historique</h2>
          <ol class="dossier-events">
            <li v-for="event in history" :key="event.id">
              <p class="dossier-event-label">{{ event.label }}</p>
              <p class="dossier-event-time">{{ event.at }}</p>
            </li>
          </ol>
        </div>

        <div class="dossier-pieces">
          <h2 class="dossier-details-title">Pièces</h2>
          <div v-if="dossier.pieces.length > 0" class="dossier-piece-list">
            <EvidenceFileRow
              v-for="piece in dossier.pieces"
              :key="piece.id"
              :piece="piece"
              :downloading="downloadingId === piece.id"
              :disabled="Boolean(downloadingId)"
              @download="emit('download', $event)"
            />
          </div>
          <p v-else class="dossier-note">
            Aucune pièce n’est associée à cette version.
          </p>
          <p v-if="downloadError" class="dossier-error">
            {{ downloadError }}
          </p>
        </div>

        <div v-if="dossier.habilitation" class="dossier-capability">
          <h2 class="dossier-details-title">Habilitation</h2>
          <p class="dossier-message">{{ capabilityLabel }}</p>
          <p v-if="dossier.habilitation.motif_restriction" class="dossier-note">
            {{ dossier.habilitation.motif_restriction }}
          </p>
          <p v-if="capabilityDeadline" class="dossier-note">
            Échéance : {{ capabilityDeadline }}
          </p>
          <UiButton
            v-if="showCapabilityRenew"
            class="dossier-secondary-action"
            variant="secondary"
            :loading="opening"
            :disabled="opening"
            @click="emit('renew')"
          >
            Ouvrir une nouvelle version
          </UiButton>
        </div>
      </div>
    </div>

    <p v-if="otherDossiers">
      <UiButton variant="quiet" @click="emit('view-dossiers')">
        Autres dossiers
      </UiButton>
    </p>
  </article>
</template>
