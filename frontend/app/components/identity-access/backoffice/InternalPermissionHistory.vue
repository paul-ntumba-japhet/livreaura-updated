<script setup lang="ts">
import type { InternalAttribution } from '../../../types/identity-access/backoffice'
import { normalizeAttributionState } from '../../../types/identity-access/backoffice'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'
import { labelForAttributionState, labelForInternalPermission, toneForAttributionState } from '../../../utils/identity-access/backoffice'

const props = withDefaults(
  defineProps<{
    rows?: InternalAttribution[]
    canRevoke?: boolean
    isSelf?: boolean
    submittingId?: string | null
  }>(),
  {
    rows: () => [],
    canRevoke: false,
    isSelf: false,
    submittingId: null,
  },
)

const emit = defineEmits<{
  revoke: [id: string]
}>()

function canRevokeRow(row: InternalAttribution): boolean {
  const etat = normalizeAttributionState(row.etat)
  return props.canRevoke && !props.isSelf && (etat === 'effective' || etat === 'a_venir')
}

function dateLabel(iso: string | null, fallback: string): string {
  return formatActivationExpiry(iso) || fallback
}
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Périodes d’attribution
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Chaque ligne est une période. La révocation prend effet immédiatement. Une nouvelle attribution n’écrase pas l’historique.
    </p>
    <InlineAlert
      v-if="isSelf"
      class="mt-stack"
      tone="warning"
      title="Révocation indisponible"
    >
      Cette session ne peut pas révoquer sa propre attribution.
    </InlineAlert>
    <InlineAlert
      v-else-if="!canRevoke"
      class="mt-stack"
      tone="warning"
      title="Révocation indisponible"
    >
      La révocation exige gerer_habilitations_internes.
    </InlineAlert>
    <ul class="mt-stack grid gap-stack">
      <li
        v-for="row in rows"
        :key="row.id"
        class="rounded-md border border-border px-gutter py-stack"
      >
        <div class="flex flex-wrap items-start justify-between gap-gutter">
          <div class="min-w-0">
            <p class="font-sans text-sm font-medium text-text">
              {{ labelForInternalPermission(row.permission, row.libelle) }}
            </p>
            <p class="mt-1 font-sans text-sm text-text-muted">
              Attribuée {{ dateLabel(row.attribue_a, 'à une date non communiquée') }}
              <template v-if="row.expire_a">
                · échéance {{ dateLabel(row.expire_a, 'non communiquée') }}
              </template>
              <template v-if="row.revoque_a">
                · révoquée {{ dateLabel(row.revoque_a, 'à une date non communiquée') }}
              </template>
            </p>
          </div>
          <StatusBadge
            :label="labelForAttributionState(row.etat)"
            :tone="toneForAttributionState(row.etat)"
          />
        </div>
        <p
          v-if="row.motif"
          class="mt-stack font-sans text-sm text-text"
        >
          Motif : {{ row.motif }}
        </p>
        <FormActions
          v-if="canRevokeRow(row)"
          class="mt-stack"
          align="start"
        >
          <UiButton
            variant="destructive"
            :loading="submittingId === row.id"
            :disabled="submittingId !== null"
            @click="emit('revoke', row.id)"
          >
            Révoquer cette période
          </UiButton>
        </FormActions>
      </li>
    </ul>
  </section>
</template>
