<script setup lang="ts">
import type { OwnAuthenticationSession } from '../../../types/identity-access/account'
import {
  labelForAccessScope,
  labelForSessionStatus,
  normalizeAccessScope,
  normalizeSessionStatus,
  sessionExpiryLabel,
  toneForSessionStatus,
} from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    session: OwnAuthenticationSession
    embedded?: boolean
    hideStatus?: boolean
  }>(),
  {
    embedded: false,
    hideStatus: false,
  },
)

const status = computed(() => normalizeSessionStatus(props.session.statut))
const scope = computed(() => normalizeAccessScope(props.session.portee))
const title = computed(() => (props.session.courante ? 'Session courante' : 'Autre session'))
</script>

<template>
  <section
    :class="embedded ? '' : 'rounded-md border border-border bg-surface-raised px-page py-section shadow-sm'"
  >
    <div
      v-if="!embedded || !hideStatus"
      class="flex flex-wrap items-start justify-between gap-gutter"
    >
      <h3
        v-if="!embedded"
        class="font-serif text-xl font-semibold text-text"
      >
        {{ title }}
      </h3>
      <StatusBadge
        v-if="!hideStatus"
        :label="labelForSessionStatus(status)"
        :tone="toneForSessionStatus(status)"
      />
    </div>
    <DescriptionList
      class="mt-stack"
      :items="[
        { term: 'Portée', value: labelForAccessScope(scope), missing: scope === 'unknown' },
        { term: 'Ouverte', value: sessionExpiryLabel(session.ouverte_a), missing: !session.ouverte_a },
        { term: 'Dernière activité', value: sessionExpiryLabel(session.derniere_activite_a), missing: !session.derniere_activite_a },
        { term: 'Échéance absolue', value: sessionExpiryLabel(session.expire_absolue_a), missing: !session.expire_absolue_a },
        { term: 'Échéance d’inactivité', value: sessionExpiryLabel(session.expire_inactivite_a), missing: !session.expire_inactivite_a },
        { term: 'Contexte', value: session.contexte || 'Non communiqué', missing: !session.contexte },
      ]"
    />
    <p
      v-if="scope === 'unknown' || status === 'unknown'"
      class="mt-stack font-sans text-sm text-text-muted"
    >
      Un statut ou une portée indisponible n’autorise aucune action supplémentaire.
    </p>
    <div v-if="$slots.default" class="mt-stack">
      <slot />
    </div>
  </section>
</template>
