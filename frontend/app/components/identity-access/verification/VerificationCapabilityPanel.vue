<script setup lang="ts">
import type { BackofficeCapability } from '../../../types/identity-access/backoffice'
import { labelForCapabilityState, toneForCapabilityState } from '../../../utils/identity-access/backoffice'
import { labelForVerificationLevel } from '../../../utils/identity-access/verification'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'

defineProps<{
  capability?: BackofficeCapability | null
}>()
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Habilitation
    </h2>
    <p v-if="!capability" class="mt-stack font-sans text-sm text-text-muted">
      Aucune habilitation n’est créée tant que le dossier n’est pas approuvé.
    </p>
    <div v-else class="mt-stack flex flex-col gap-stack">
      <div class="flex flex-wrap items-center gap-gutter">
        <p class="font-sans text-sm text-text">
          {{ labelForVerificationLevel(capability.niveau) }}
        </p>
        <StatusBadge
          :label="labelForCapabilityState(capability.etat)"
          :tone="toneForCapabilityState(capability.etat)"
        />
      </div>
      <p class="font-sans text-sm text-text-muted">
        Obtenue
        {{ capability.obtenue_a ? formatActivationExpiry(capability.obtenue_a) : 'à une date non communiquée' }}
        <template v-if="capability.valide_jusqua">
          · valable jusqu’au {{ formatActivationExpiry(capability.valide_jusqua) }}
        </template>
      </p>
      <p v-if="capability.motif_restriction" class="font-sans text-sm text-text-muted">
        {{ capability.motif_restriction }}
      </p>
    </div>
  </section>
</template>
