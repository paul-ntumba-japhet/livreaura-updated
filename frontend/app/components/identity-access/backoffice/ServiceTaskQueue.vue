<script setup lang="ts">
import type { BackofficeTaskRow } from '../../../types/identity-access/backoffice'
import { normalizeTaskState } from '../../../types/identity-access/backoffice'
import { labelForTaskState, toneForTaskState } from '../../../utils/identity-access/backoffice'
import {
  labelForDossierStatus,
  labelForVerificationLevel,
} from '../../../utils/identity-access/verification'

defineProps<{
  rows: BackofficeTaskRow[]
}>()

const emit = defineEmits<{
  open: [dossierId: string]
}>()
</script>

<template>
  <ul class="flex flex-col gap-stack">
    <li
      v-for="row in rows"
      :key="row.tache.id"
    >
      <article class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
        <div class="flex flex-wrap items-start justify-between gap-gutter">
          <div class="min-w-0">
            <h2 class="font-serif text-lg font-semibold text-text">
              {{ row.dossier.numero || 'Dossier sans numéro' }}
            </h2>
            <p class="mt-1 font-sans text-sm text-text-muted">
              {{ labelForVerificationLevel(row.dossier.niveau) }}
              · {{ labelForDossierStatus(row.dossier.statut) }}
              · version {{ row.dossier.version }}
            </p>
          </div>
          <div class="flex flex-wrap items-start gap-2">
            <StatusBadge
              :label="labelForTaskState(row.tache.etat_travail)"
              :tone="toneForTaskState(row.tache.etat_travail)"
            />
            <PriorityBadge :priorite="row.tache.priorite" :motif="row.tache.motif_priorite" />
          </div>
        </div>
        <DeadlineIndicator
          class="mt-stack"
          label="Prise en charge"
          :iso="row.tache.echeance_prise_a"
        />
        <p v-if="row.conflit_interet" class="mt-stack font-sans text-sm text-warning">
          Dossier lié à ce compte : réaffectation nécessaire.
        </p>
        <p v-else-if="normalizeTaskState(row.tache.etat_travail) === 'unknown'" class="mt-stack font-sans text-sm text-text-muted">
          Statut de travail indisponible. Aucune action n’est proposée.
        </p>
        <FormActions class="mt-stack" align="start">
          <UiButton variant="secondary" @click="emit('open', row.dossier.id)">
            Ouvrir le dossier
          </UiButton>
        </FormActions>
      </article>
    </li>
  </ul>
</template>
