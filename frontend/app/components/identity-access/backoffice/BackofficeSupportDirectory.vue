<script setup lang="ts">
import {
  normalizeTaskPriority,
  type BackofficeAccountPagination,
  type BackofficeSupportRow,
  type BackofficeSupportTab,
  type BackofficeSupportTotals,
} from '../../../types/identity-access/backoffice'
import { knownSupportTypes } from '../../../types/identity-access/support'
import {
  displayPersonName,
  displayShortPersonName,
  formatRelativeDeadline,
  isTaskOverdue,
} from '../../../utils/identity-access/backoffice'
import {
  iconForIdentityState,
  iconForSupportQueueStatus,
  labelForIdentityState,
  labelForSupportPriority,
  labelForSupportQueueStatus,
  labelForSupportType,
  toneForIdentityState,
  toneForSupportQueueStatus,
} from '../../../utils/identity-access/support'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeSupportTab
  type: string
  priorite: string
  page: number
  loadState: BackofficeLoadState
  demandes: BackofficeSupportRow[]
  totaux: BackofficeSupportTotals | null
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeSupportTab]
  'update:type': [value: string]
  'update:priorite': [value: string]
  'update:page': [value: number]
  retry: []
}>()

const tabs = computed(() => {
  const counts = props.totaux
  return [
    { id: 'a_traiter' as const, label: 'À traiter', count: counts?.a_traiter },
    { id: 'mes_demandes' as const, label: 'Mes demandes', count: counts?.mes_demandes },
    { id: 'attente_demandeur' as const, label: 'En attente du demandeur', count: counts?.attente_demandeur },
    { id: 'cloturees' as const, label: 'Clôturées', count: counts?.cloturees },
  ]
})

const typeOptions = [
  { value: '', label: 'Tous les motifs' },
  ...knownSupportTypes.map(code => ({
    value: code,
    label: labelForSupportType(code),
  })),
]

const priorityOptions = [
  { value: '', label: 'Toutes les priorités' },
  { value: 'urgente', label: 'Haute' },
  { value: 'normale', label: 'Normale' },
]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && props.demandes.length > 0)
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.type !== '' || props.priorite !== '')
))

const emptyTitle = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun résultat'
  }
  if (props.tab === 'mes_demandes') {
    return 'Aucune demande qui vous est affectée'
  }
  if (props.tab === 'attente_demandeur') {
    return 'Aucune attente du demandeur'
  }
  if (props.tab === 'cloturees') {
    return 'Aucune demande clôturée'
  }
  return 'Aucune demande à traiter'
})

const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucune demande ne correspond à cette recherche. Modifiez les filtres.'
  }
  if (props.tab === 'mes_demandes') {
    return 'Aucune demande ouverte ne vous est actuellement affectée.'
  }
  if (props.tab === 'attente_demandeur') {
    return 'Aucune demande n’attend actuellement une réponse du demandeur.'
  }
  if (props.tab === 'cloturees') {
    return 'Aucune demande n’a encore été clôturée.'
  }
  return 'Aucune demande de support n’est actuellement à traiter.'
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.demandes.length
  const total = pagination.total
  if (total === 0) {
    return '0 demande'
  }
  if (shown === total) {
    return total === 1 ? '1 demande' : `${total} demandes`
  }
  const from = (pagination.page - 1) * pagination.per_page + 1
  const to = from + shown - 1
  return `${from}–${to} sur ${total}`
})

const unassignedCount = computed(() => props.totaux?.non_affectes ?? 0)
const overdueCount = computed(() => props.totaux?.reponses_en_retard ?? 0)
const hasWatchpoints = computed(() => unassignedCount.value > 0 || overdueCount.value > 0)

function requesterLabel(row: BackofficeSupportRow): string {
  const name = displayPersonName(row.demandeur.prenom, row.demandeur.nom)
  if (row.demandeur.externe && name === 'Compte') {
    return 'Contact externe'
  }
  return name
}

function assigneeLabel(row: BackofficeSupportRow): string {
  if (!row.responsable) {
    return 'Non affecté'
  }
  return displayShortPersonName(row.responsable.prenom, row.responsable.nom)
}

function rowClosed(row: BackofficeSupportRow): boolean {
  return Boolean(row.clos_a) || row.tache?.etat_travail === 'terminee'
}

function rowDeadline(row: BackofficeSupportRow): string | null {
  return props.tab === 'cloturees' ? (row.clos_a || row.echeance_a) : row.echeance_a
}

function rowOverdue(row: BackofficeSupportRow): boolean {
  return props.tab !== 'cloturees' && Boolean(row.tache && isTaskOverdue(row.tache))
}

function deadlineLabel(row: BackofficeSupportRow): string {
  if (props.tab === 'cloturees' || rowClosed(row)) {
    return 'Clôturé'
  }
  return formatRelativeDeadline(rowDeadline(row))
}

function statusLabel(row: BackofficeSupportRow): string {
  return labelForSupportQueueStatus(row.tache?.etat_travail, rowClosed(row))
}

function statusTone(row: BackofficeSupportRow) {
  return toneForSupportQueueStatus(row.tache?.etat_travail, rowClosed(row))
}

function statusIcon(row: BackofficeSupportRow) {
  return iconForSupportQueueStatus(row.tache?.etat_travail, rowClosed(row))
}

function priorityLabel(row: BackofficeSupportRow): string {
  return labelForSupportPriority(row.tache?.priorite || 'normale')
}

function priorityTone(row: BackofficeSupportRow): 'neutral' | 'warning' {
  return normalizeTaskPriority(row.tache?.priorite) === 'urgente' ? 'warning' : 'neutral'
}

function detailTo(row: BackofficeSupportRow): string {
  return `/administration/recours/${encodeURIComponent(row.reference)}`
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="support-directory-title"
  >
    <h2 id="support-directory-title" class="sr-only">
      Demandes de support
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues des demandes de support">
        <ul class="flex flex-wrap gap-x-gutter border-b border-border">
          <li
            v-for="item in tabs"
            :key="item.id"
          >
            <button
              type="button"
              class="-mb-px border-b-2 px-1 pb-2 font-sans text-sm focus-ring"
              :class="tab === item.id
                ? 'border-brand font-semibold text-brand'
                : 'border-transparent text-text-muted hover:text-text'"
              :aria-current="tab === item.id ? 'page' : undefined"
              @click="emit('update:tab', item.id)"
            >
              {{ item.label }}
              <span v-if="typeof item.count === 'number'" class="font-normal">
                ({{ item.count }})
              </span>
            </button>
          </li>
        </ul>
      </nav>

      <form
        class="grid gap-stack lg:grid-cols-[minmax(0,1fr)_16rem_14rem]"
        @submit.prevent
      >
        <FormField
          label="Recherche"
          hint="Référence, demandeur ou contact masqué."
        >
          <template #default="{ id, describedBy }">
            <TextInput
              :id="id"
              :model-value="search"
              icon="search"
              placeholder="Rechercher une demande ou un demandeur"
              :aria-describedby="describedBy"
              autocomplete="off"
              @update:model-value="emit('update:search', $event)"
            />
          </template>
        </FormField>
        <FormField label="Motif">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="type"
              :options="typeOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:type', $event)"
            />
          </template>
        </FormField>
        <FormField label="Priorité">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="priorite"
              :options="priorityOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:priorite', $event)"
            />
          </template>
        </FormField>
      </form>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger les demandes"
        :message="errorMessage || 'La lecture de la file a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || demandes.length > 0">
        <p
          v-if="resultLabel && !loading"
          class="font-sans text-sm text-text-muted"
        >
          {{ resultLabel }}
        </p>

        <div
          class="lg:hidden"
          :aria-busy="loading || undefined"
        >
          <div v-if="loading && demandes.length === 0" class="flex flex-col gap-stack">
            <Skeleton v-for="index in 4" :key="index" class="h-24 w-full" />
          </div>
          <EmptyState
            v-else-if="loadState === 'empty'"
            plain
            :title="emptyTitle"
            :message="emptyMessage"
          />
          <ul
            v-else
            class="flex flex-col divide-y divide-border"
          >
            <li
              v-for="row in demandes"
              :key="row.id"
              class="flex flex-col gap-stack py-gutter"
            >
              <div>
                <p class="font-sans text-sm font-semibold text-text">
                  {{ requesterLabel(row) }}
                </p>
                <p
                  v-if="row.demandeur.email_masque"
                  class="font-sans text-sm text-text-muted"
                >
                  {{ row.demandeur.email_masque }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ row.reference }}
                </p>
              </div>
              <StatusBadge
                :icon="iconForIdentityState(row.identite_etat, row.demandeur.externe)"
                :label="labelForIdentityState(row.identite_etat, row.demandeur.externe)"
                :tone="toneForIdentityState(row.identite_etat, row.demandeur.externe)"
              />
              <p class="font-sans text-sm text-text-muted">
                {{ labelForSupportType(row.type) }}
              </p>
              <StatusBadge
                :label="priorityLabel(row)"
                :tone="priorityTone(row)"
              />
              <StatusBadge
                :icon="statusIcon(row)"
                :label="statusLabel(row)"
                :tone="statusTone(row)"
              />
              <p class="font-sans text-sm text-text-muted">
                {{ assigneeLabel(row) }}
              </p>
              <div class="flex flex-wrap items-center gap-stack">
                <p
                  class="font-sans text-sm"
                  :class="rowOverdue(row) ? 'font-medium text-danger' : 'text-text-muted'"
                >
                  {{ deadlineLabel(row) }}
                </p>
                <StatusBadge
                  v-if="rowOverdue(row)"
                  label="En retard"
                  tone="danger"
                  icon="error"
                />
              </div>
              <UiLink
                plain
                :to="detailTo(row)"
              >
                Ouvrir
                <UiIcon name="arrow-right" />
              </UiLink>
            </li>
          </ul>
        </div>

        <div
          class="hidden overflow-x-auto lg:block"
          :aria-busy="loading || undefined"
        >
          <table class="w-full min-w-[56rem] border-collapse text-left font-sans text-sm">
            <thead>
              <tr class="border-b border-border text-text-muted">
                <th scope="col" class="py-2 pr-gutter font-medium">Demande / Demandeur</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Motif</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Priorité</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Statut</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Responsable</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Échéance</th>
                <th scope="col" class="py-2 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && demandes.length === 0">
                <td colspan="7" class="py-gutter">
                  <div class="flex flex-col gap-stack">
                    <Skeleton v-for="index in 4" :key="index" class="h-12 w-full" />
                  </div>
                </td>
              </tr>
              <tr v-else-if="loadState === 'empty'">
                <td colspan="7" class="py-gutter">
                  <EmptyState
                    plain
                    :title="emptyTitle"
                    :message="emptyMessage"
                  />
                </td>
              </tr>
              <tr
                v-for="row in demandes"
                :key="row.id"
                class="border-b border-border last:border-0"
              >
                <td class="py-gutter pr-gutter align-middle">
                  <p class="font-medium text-text">
                    {{ requesterLabel(row) }}
                  </p>
                  <p
                    v-if="row.demandeur.email_masque"
                    class="text-text-muted"
                  >
                    {{ row.demandeur.email_masque }}
                  </p>
                  <p class="text-text-muted">
                    {{ row.reference }}
                  </p>
                  <div class="mt-1">
                    <StatusBadge
                      :icon="iconForIdentityState(row.identite_etat, row.demandeur.externe)"
                      :label="labelForIdentityState(row.identite_etat, row.demandeur.externe)"
                      :tone="toneForIdentityState(row.identite_etat, row.demandeur.externe)"
                    />
                  </div>
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  {{ labelForSupportType(row.type) }}
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :label="priorityLabel(row)"
                    :tone="priorityTone(row)"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :icon="statusIcon(row)"
                    :label="statusLabel(row)"
                    :tone="statusTone(row)"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  {{ assigneeLabel(row) }}
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <p
                    :class="rowOverdue(row) ? 'font-medium text-danger' : 'text-text-muted'"
                  >
                    {{ deadlineLabel(row) }}
                  </p>
                  <div
                    v-if="rowOverdue(row)"
                    class="mt-1"
                  >
                    <StatusBadge
                      label="En retard"
                      tone="danger"
                      icon="error"
                    />
                  </div>
                </td>
                <td class="py-gutter align-middle">
                  <UiLink
                    plain
                    :to="detailTo(row)"
                  >
                    Ouvrir
                    <UiIcon name="arrow-right" />
                  </UiLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="pagination && pagination.last_page > 1 && loadState !== 'error'"
          class="flex flex-wrap items-center justify-between gap-stack"
        >
          <p class="font-sans text-sm text-text-muted">
            Page {{ pagination.page }} sur {{ pagination.last_page }}
          </p>
          <div class="flex gap-stack">
            <UiButton
              variant="secondary"
              :disabled="pagination.page <= 1"
              :disabled-reason="pagination.page <= 1 ? 'Première page.' : undefined"
              @click="emit('update:page', pagination.page - 1)"
            >
              Page précédente
            </UiButton>
            <UiButton
              variant="secondary"
              :disabled="pagination.page >= pagination.last_page"
              :disabled-reason="pagination.page >= pagination.last_page ? 'Dernière page.' : undefined"
              @click="emit('update:page', pagination.page + 1)"
            >
              Page suivante
            </UiButton>
          </div>
        </div>
      </template>

      <p
        v-if="refreshing"
        class="font-sans text-sm text-text-muted"
      >
        Actualisation de la file…
      </p>

      <div class="grid gap-gutter lg:grid-cols-2">
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-warning"
            aria-hidden="true"
          >
            <UiIcon name="warning" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              À suivre aujourd’hui
            </h3>
            <ul
              v-if="hasWatchpoints"
              class="mt-1 list-disc pl-4 font-sans text-sm text-text-muted"
            >
              <li v-if="unassignedCount > 0">
                {{ unassignedCount }} demande{{ unassignedCount === 1 ? '' : 's' }} attend{{ unassignedCount === 1 ? '' : 'ent' }} une affectation
              </li>
              <li v-if="overdueCount > 0">
                {{ overdueCount }} réponse{{ overdueCount === 1 ? '' : 's' }} {{ overdueCount === 1 ? 'est' : 'sont' }} en retard
              </li>
            </ul>
            <p
              v-else
              class="mt-1 font-sans text-sm text-text-muted"
            >
              Aucune alerte d’affectation ou d’échéance pour le moment. Un retard n’autorise rien.
            </p>
          </div>
        </article>
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
            aria-hidden="true"
          >
            <UiIcon name="lock" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Accès limité au support
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              Une session de recours, si elle est autorisée, reste limitée aux échanges et aux documents explicitement accordés. Elle n’ouvre ni paiement, ni publication, ni lecture normale.
            </p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
