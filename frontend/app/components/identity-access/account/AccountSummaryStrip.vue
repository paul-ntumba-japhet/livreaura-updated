<script setup lang="ts">
import type { AccessScope, AccountStatus, CurrentAccount } from '../../../types/identity-access/account'
import {
  labelForAccessScope,
  latestVerificationDossier,
  primaryDisplayedRole,
  toneForAccountStatus,
} from '../../../utils/identity-access/account'
import {
  labelForDossierStatus,
  toneForDossierStatus,
} from '../../../utils/identity-access/verification'

const props = defineProps<{
  account: CurrentAccount
  status: AccountStatus
  scope: AccessScope
}>()

const role = computed(() => primaryDisplayedRole(props.account))
const dossier = computed(() => latestVerificationDossier(props.account))

function accountValue(status: AccountStatus): string {
  switch (status) {
    case 'pending':
      return 'Compte en attente'
    case 'active':
      return 'Compte actif'
    case 'suspended':
      return 'Compte suspendu'
    case 'closed':
      return 'Compte clôturé'
    default:
      return 'Statut indisponible'
  }
}

const verificationValue = computed(() => (
  dossier.value ? labelForDossierStatus(dossier.value.statut) : 'Aucun dossier'
))

function scopeTone(scope: AccessScope): 'neutral' | 'warning' {
  if (scope === 'limited' || scope === 'support_only') {
    return 'warning'
  }
  return 'neutral'
}
</script>

<template>
  <div class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <SummaryStatCard
      title="État du compte"
      icon="user"
      :value="accountValue(status)"
      :value-tone="toneForAccountStatus(status)"
    />
    <SummaryStatCard
      title="Portée de la session"
      icon="layers"
      :value="labelForAccessScope(scope)"
      :value-tone="scopeTone(scope)"
    />
    <SummaryStatCard
      title="Rôle principal"
      icon="user"
      :value="role?.label || 'Aucun rôle'"
    />
    <SummaryStatCard
      title="Dossier de vérification"
      icon="shield-check"
      :value="verificationValue"
      :value-tone="dossier ? toneForDossierStatus(dossier.statut) : 'neutral'"
    />
  </div>
</template>
