<script setup lang="ts">
import type { OrganizationSummary } from '../../../types/identity-access/organization'
import { normalizeOrganizationStatus } from '../../../types/identity-access/organization'
import {
  countryLabel,
  labelForMembershipStatus,
  labelForOrganizationPermission,
  labelForOrganizationStatus,
} from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    organization: OrganizationSummary
    selected?: boolean
    selectable?: boolean
    principal?: boolean
    membershipStatus?: string | null
    permissions?: string[]
    disabled?: boolean
  }>(),
  {
    selected: false,
    selectable: false,
    principal: false,
    membershipStatus: null,
    permissions: () => [],
    disabled: false,
  },
)

const emit = defineEmits<{
  select: [id: string]
}>()

const status = computed(() => normalizeOrganizationStatus(props.organization.statut_operationnel))
const badgeTone = computed(() => {
  switch (status.value) {
    case 'active':
      return 'success' as const
    case 'restricted':
      return 'warning' as const
    case 'closed':
      return 'danger' as const
    default:
      return 'neutral' as const
  }
})

const location = computed(() => {
  const country = countryLabel(props.organization.pays) || props.organization.pays
  const city = props.organization.ville?.trim()
  return city ? `${city}, ${country}` : country
})

function onSelect() {
  if (!props.selectable || props.disabled) {
    return
  }
  emit('select', props.organization.id)
}
</script>

<template>
  <article
    class="rounded-md border-2 bg-surface-raised px-gutter py-stack"
    :class="{
      'border-brand shadow-sm': selected && !disabled,
      'border-border': !selected || disabled,
      'opacity-50': disabled,
    }"
  >
    <div class="flex items-start justify-between gap-gutter">
      <div class="min-w-0">
        <h3 class="font-serif text-lg font-semibold text-text">
          {{ organization.raison_sociale }}
        </h3>
        <p class="mt-1 font-sans text-sm text-text-muted">
          {{ organization.type?.libelle || 'Type indisponible' }}
          <span v-if="location"> · {{ location }}</span>
        </p>
      </div>
      <StatusBadge :label="labelForOrganizationStatus(status)" :tone="badgeTone" />
    </div>

    <ul v-if="principal || membershipStatus || permissions.length > 0" class="mt-stack flex flex-wrap gap-stack">
      <li v-if="principal">
        <StatusBadge label="Représentant déclarant" tone="info" />
      </li>
      <li v-if="membershipStatus">
        <StatusBadge :label="labelForMembershipStatus(membershipStatus)" />
      </li>
      <li v-for="permission in permissions" :key="permission">
        <StatusBadge :label="labelForOrganizationPermission(permission)" />
      </li>
    </ul>

    <p v-if="selectable" class="mt-stack">
      <UiButton
        variant="secondary"
        :disabled="disabled || selected"
        :disabled-reason="selected ? 'Cette organisation est déjà sélectionnée.' : undefined"
        @click="onSelect"
      >
        {{ selected ? 'Organisation sélectionnée' : 'Demander l’examen de cette organisation' }}
      </UiButton>
    </p>
  </article>
</template>
