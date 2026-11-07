<script setup lang="ts">
import type { AccountOrganization, AccountVerificationDossier } from '../../../types/identity-access/account'
import { normalizeOrganizationStatus } from '../../../types/identity-access/organization'
import { countryLabel, labelForOrganizationStatus } from '../../../utils/identity-access/account'
import { iconForOrganizationTypeCode } from '../../../utils/identity-access/organization-display'
import { normalizeDossierStatus } from '../../../types/identity-access/verification'
import {
  labelForDossierStatus,
  toneForDossierStatus,
} from '../../../utils/identity-access/verification'

const props = defineProps<{
  organization: AccountOrganization
  dossier: AccountVerificationDossier | null
  canDeposit: boolean
}>()

const emit = defineEmits<{
  openKyc: []
}>()

const relationLabel = computed(() => {
  if (props.organization.adhesion.statut === 'revoked') {
    return 'Adhésion révoquée'
  }
  if (props.organization.adhesion.statut !== 'active') {
    return 'Relation indisponible'
  }
  if (props.organization.adhesion.principal) {
    return 'Représentant déclarant'
  }
  return 'Membre'
})

const location = computed(() => {
  const country = countryLabel(props.organization.pays) || props.organization.pays
  const city = props.organization.ville?.trim()
  return city ? `${city}, ${country}` : country
})

const meta = computed(() => {
  const type = props.organization.type?.libelle?.trim()
  const name = props.organization.raison_sociale.trim()
  const parts: string[] = []
  if (type && type.localeCompare(name, 'fr', { sensitivity: 'base' }) !== 0) {
    parts.push(type)
  }
  if (location.value) {
    parts.push(location.value)
  }
  const operational = normalizeOrganizationStatus(props.organization.statut_operationnel)
  if (operational !== 'active') {
    parts.push(labelForOrganizationStatus(operational))
  }
  return parts.join(' · ')
})

const typeIcon = computed(() => iconForOrganizationTypeCode(props.organization.type?.code || ''))

const verificationLabel = computed(() => {
  if (!props.dossier) {
    return 'Non commencée'
  }
  return labelForDossierStatus(props.dossier.statut)
})

const verificationTone = computed(() => {
  if (!props.dossier) {
    return 'neutral' as const
  }
  return toneForDossierStatus(props.dossier.statut)
})

const actionLabel = computed(() => {
  if (!props.canDeposit) {
    return ''
  }
  if (!props.dossier) {
    return 'Ouvrir le dossier'
  }
  const dossierStatus = normalizeDossierStatus(props.dossier.statut)
  if (dossierStatus === 'draft' || dossierStatus === 'needs_fix') {
    return 'Compléter le dossier'
  }
  return 'Voir le dossier'
})
</script>

<template>
  <article class="org-row">
    <span class="org-mark" aria-hidden="true">
      <UiIcon :name="typeIcon" />
    </span>
    <div class="org-identity">
      <h2 class="org-name">{{ organization.raison_sociale }}</h2>
      <p v-if="meta" class="org-meta">{{ meta }}</p>
      <p
        class="org-relation"
        :class="{ 'is-warning': organization.adhesion.statut === 'revoked' }"
      >
        {{ relationLabel }}
      </p>
    </div>
    <div class="org-side">
      <StatusBadge :label="verificationLabel" :tone="verificationTone" />
      <UiButton
        v-if="actionLabel"
        variant="secondary"
        class="org-action"
        @click="emit('openKyc')"
      >
        {{ actionLabel }}
      </UiButton>
      <p
        v-else-if="organization.adhesion.statut === 'active'"
        class="org-note"
      >
        Dépôt non accordé
      </p>
    </div>
  </article>
</template>
