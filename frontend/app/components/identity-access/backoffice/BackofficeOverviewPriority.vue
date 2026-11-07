<script setup lang="ts">
import type { BackofficeTaskRow } from '../../../types/identity-access/backoffice'
import { normalizeTaskState } from '../../../types/identity-access/backoffice'
import {
  assigneeLabel,
  dossierSubjectLabel,
  formatRelativeDeadline,
  isTaskOverdue,
  labelForTaskState,
  taskDeadlineIso,
  toneForTaskState,
} from '../../../utils/identity-access/backoffice'
import {
  iconForVerificationLevel,
  labelForVerificationLevel,
} from '../../../utils/identity-access/verification'

defineProps<{
  rows: BackofficeTaskRow[]
  currentAccountId?: string | null
  loading?: boolean
  restricted?: boolean
  error?: boolean
  errorMessage?: string
  retrying?: boolean
  totalCount: number
}>()

const emit = defineEmits<{
  open: [dossierId: string]
  retry: []
}>()

function stateTone(row: BackofficeTaskRow) {
  if (isTaskOverdue(row.tache)) {
    return 'danger' as const
  }
  if (normalizeTaskState(row.tache.etat_travail) === 'en_cours') {
    return 'success' as const
  }
  return toneForTaskState(row.tache.etat_travail)
}

function stateLabel(row: BackofficeTaskRow) {
  if (isTaskOverdue(row.tache)) {
    return 'En retard'
  }
  return labelForTaskState(row.tache.etat_travail)
}
</script>

<template>
  <SectionCard title="À traiter en priorité">
    <template #action>
      <UiLink v-if="!restricted" to="/administration/verification">
        Voir les vérifications
      </UiLink>
    </template>

    <p
      v-if="!loading && !restricted && !error && rows.length > 0"
      class="mb-stack font-sans text-sm text-text-muted"
    >
      Vérifications ({{ totalCount }})
    </p>

    <div v-if="loading" class="flex flex-col gap-stack" aria-busy="true">
      <Skeleton v-for="index in 4" :key="index" class="h-14 w-full" />
    </div>

    <EmptyState
      v-else-if="restricted"
      title="File non consultable"
      message="Le contrôle des dossiers exige une attribution interne, pas seulement le rôle administration."
    />

    <ErrorState
      v-else-if="error"
      title="Impossible de charger la file"
      :message="errorMessage || 'La lecture des tâches a échoué. Vous pouvez réessayer.'"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="emit('retry')"
    />

    <EmptyState
      v-else-if="rows.length === 0"
      title="Aucune tâche ouverte"
      message="Aucun dossier de vérification n’a encore été soumis. Se connecter à l’administration n’en crée aucune."
    />

    <template v-else>
      <ul class="flex flex-col gap-stack lg:hidden">
        <li
          v-for="row in rows"
          :key="row.tache.id"
        >
          <button
            type="button"
            class="flex w-full flex-col gap-2 rounded-md border border-border px-gutter py-stack text-left focus-ring"
            @click="emit('open', row.dossier.id)"
          >
            <span class="flex items-start justify-between gap-stack">
              <span class="min-w-0 font-sans text-sm font-semibold text-text">
                {{ dossierSubjectLabel(row.dossier) }}
              </span>
              <StatusBadge
                dot
                :label="stateLabel(row)"
                :tone="stateTone(row)"
              />
            </span>
            <span class="inline-flex items-center gap-2 font-sans text-sm text-text">
              <UiIcon
                :name="iconForVerificationLevel(row.dossier.niveau)"
                class="text-text-muted"
              />
              {{ labelForVerificationLevel(row.dossier.niveau) }}
            </span>
            <span class="font-sans text-sm text-text-muted">
              {{ formatRelativeDeadline(taskDeadlineIso(row.tache)) }}
            </span>
            <span class="font-sans text-sm text-text-muted">
              {{ assigneeLabel(row.tache, currentAccountId) }}
            </span>
          </button>
        </li>
      </ul>

      <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-[40rem] border-collapse text-left font-sans text-sm">
          <thead>
            <tr class="border-b border-border text-text-muted">
              <th scope="col" class="py-2 pr-gutter font-medium">Dossier</th>
              <th scope="col" class="py-2 pr-gutter font-medium">Type</th>
              <th scope="col" class="py-2 pr-gutter font-medium">État</th>
              <th scope="col" class="py-2 pr-gutter font-medium">Échéance</th>
              <th scope="col" class="py-2 pr-gutter font-medium">Responsable</th>
              <th scope="col" class="py-2 font-medium">
                <span class="sr-only">Ouvrir</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.tache.id"
              class="border-b border-border last:border-b-0"
            >
              <td class="py-3 pr-gutter align-middle font-medium text-text">
                {{ dossierSubjectLabel(row.dossier) }}
              </td>
              <td class="py-3 pr-gutter align-middle">
                <span class="inline-flex items-center gap-2 text-text">
                  <UiIcon
                    :name="iconForVerificationLevel(row.dossier.niveau)"
                    class="text-text-muted"
                  />
                  {{ labelForVerificationLevel(row.dossier.niveau) }}
                </span>
              </td>
              <td class="py-3 pr-gutter align-middle">
                <StatusBadge
                  dot
                  :label="stateLabel(row)"
                  :tone="stateTone(row)"
                />
              </td>
              <td class="py-3 pr-gutter align-middle text-text-muted">
                {{ formatRelativeDeadline(taskDeadlineIso(row.tache)) }}
              </td>
              <td class="py-3 pr-gutter align-middle text-text">
                {{ assigneeLabel(row.tache, currentAccountId) }}
              </td>
              <td class="py-3 align-middle">
                <UiButton
                  variant="quiet"
                  :aria-label="`Ouvrir le dossier ${dossierSubjectLabel(row.dossier)}`"
                  @click="emit('open', row.dossier.id)"
                >
                  <UiIcon name="chevron-right" />
                </UiButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </SectionCard>
</template>
