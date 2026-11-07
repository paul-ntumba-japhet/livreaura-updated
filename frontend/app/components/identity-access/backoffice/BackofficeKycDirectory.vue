<script setup lang="ts">
import type {
  BackofficeAccountPagination,
  BackofficeKycRow,
  BackofficeKycTab,
  BackofficeKycTotals,
  BackofficeOrganizationPerson,
} from '../../../types/identity-access/backoffice'
import {
  displayPersonName,
  displayShortPersonName,
  formatRelativeDeadline,
  isTaskOverdue,
} from '../../../utils/identity-access/backoffice'
import {
  iconForKycQueueStatus,
  iconForVerificationLevel,
  labelForKycQueueStatus,
  labelForVerificationLevelShort,
  toneForKycQueueStatus,
} from '../../../utils/identity-access/verification'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeKycTab
  niveau: string
  responsableId: string
  page: number
  loadState: BackofficeLoadState
  dossiers: BackofficeKycRow[]
  totaux: BackofficeKycTotals | null
  responsables: BackofficeOrganizationPerson[]
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeKycTab]
  'update:niveau': [value: string]
  'update:responsableId': [value: string]
  'update:page': [value: number]
  retry: []
}>()

const tabs = computed(() => {
  const counts = props.totaux
  return [
    { id: 'a_traiter' as const, label: 'À traiter', count: counts?.a_traiter },
    { id: 'mes_dossiers' as const, label: 'Mes dossiers', count: counts?.mes_dossiers },
    { id: 'a_corriger' as const, label: 'À corriger', count: counts?.a_corriger },
    { id: 'historique' as const, label: 'Historique', count: undefined },
  ]
})

const niveauOptions = [
  { value: '', label: 'Tous les niveaux' },
  { value: 'organisation_verifiee', label: 'Organisation' },
  { value: 'partenaire_commercial', label: 'Commercial' },
]

const assigneeOptions = computed(() => [
  { value: '', label: 'Tous les responsables' },
  ...props.responsables.map(person => ({
    value: person.id,
    label: displayPersonName(person.prenom, person.nom),
  })),
])

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && props.dossiers.length > 0)
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.niveau !== '' || props.responsableId !== '')
))

const emptyTitle = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun résultat'
  }
  if (props.tab === 'mes_dossiers') {
    return 'Aucun dossier qui vous est affecté'
  }
  if (props.tab === 'a_corriger') {
    return 'Aucun dossier à corriger'
  }
  if (props.tab === 'historique') {
    return 'Aucun historique'
  }
  return 'Aucun dossier à traiter'
})

const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun dossier ne correspond à cette recherche. Modifiez les filtres.'
  }
  if (props.tab === 'mes_dossiers') {
    return 'Aucun dossier ouvert ne vous est actuellement affecté.'
  }
  if (props.tab === 'a_corriger') {
    return 'Aucune correction n’est attendue du demandeur.'
  }
  if (props.tab === 'historique') {
    return 'Aucune décision n’a encore été enregistrée. Chaque décision concerne une version précise du dossier.'
  }
  return 'Aucun dossier de vérification n’a encore été soumis.'
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.dossiers.length
  const total = pagination.total
  if (total === 0) {
    return '0 dossier'
  }
  if (shown === total) {
    return total === 1 ? '1 dossier' : `${total} dossiers`
  }
  return `${shown} sur ${total} dossiers`
})

const unassignedCount = computed(() => props.totaux?.non_affectes ?? 0)
const overdueCount = computed(() => props.totaux?.en_retard ?? 0)
const hasWatchpoints = computed(() => unassignedCount.value > 0 || overdueCount.value > 0)

function assigneeLabel(row: BackofficeKycRow): string {
  if (!row.responsable) {
    return 'Non affecté'
  }
  return displayShortPersonName(row.responsable.prenom, row.responsable.nom)
}

function rowDeadline(row: BackofficeKycRow): string | null {
  return props.tab === 'historique' ? (row.clos_a || row.echeance_a) : row.echeance_a
}

function rowOverdue(row: BackofficeKycRow): boolean {
  return props.tab !== 'historique' && Boolean(row.tache && isTaskOverdue(row.tache))
}

function statusLabel(row: BackofficeKycRow): string {
  return labelForKycQueueStatus(row.statut, rowOverdue(row))
}

function statusTone(row: BackofficeKycRow) {
  return toneForKycQueueStatus(row.statut, rowOverdue(row))
}

function statusIcon(row: BackofficeKycRow) {
  return iconForKycQueueStatus(row.statut, rowOverdue(row))
}

function actionLabel(): string {
  return props.tab === 'historique' ? 'Consulter' : 'Examiner'
}

const deadlineColumnLabel = computed(() => (props.tab === 'historique' ? 'Décision' : 'Échéance'))
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="kyc-directory-title"
  >
    <h2 id="kyc-directory-title" class="sr-only">
      Dossiers de vérification
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues des dossiers KYC">
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
        class="grid gap-stack lg:grid-cols-[minmax(0,1fr)_12rem_14rem]"
        @submit.prevent
      >
        <FormField
          label="Recherche"
          hint="Numéro de dossier, personne ou organisation."
        >
          <template #default="{ id, describedBy }">
            <TextInput
              :id="id"
              :model-value="search"
              icon="search"
              placeholder="Rechercher un dossier, une personne ou une organisation"
              :aria-describedby="describedBy"
              autocomplete="off"
              @update:model-value="emit('update:search', $event)"
            />
          </template>
        </FormField>
        <FormField label="Niveau">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="niveau"
              :options="niveauOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:niveau', $event)"
            />
          </template>
        </FormField>
        <FormField label="Responsable">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="responsableId"
              :options="assigneeOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:responsableId', $event)"
            />
          </template>
        </FormField>
      </form>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger les dossiers"
        :message="errorMessage || 'La lecture de la file a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || dossiers.length > 0">
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
          <div v-if="loading && dossiers.length === 0" class="flex flex-col gap-stack">
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
              v-for="row in dossiers"
              :key="row.id"
              class="flex flex-col gap-stack py-gutter"
            >
              <div>
                <p class="font-sans text-sm font-semibold text-text">
                  {{ row.numero || 'Dossier' }} · V{{ row.version }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ row.sujet.libelle }}
                </p>
              </div>
              <p class="flex items-center gap-2 font-sans text-sm text-text-muted">
                <UiIcon
                  :name="iconForVerificationLevel(row.niveau)"
                  size="sm"
                />
                {{ labelForVerificationLevelShort(row.niveau) }}
              </p>
              <StatusBadge
                :icon="statusIcon(row)"
                :label="statusLabel(row)"
                :tone="statusTone(row)"
              />
              <p class="font-sans text-sm text-text-muted">
                {{ assigneeLabel(row) }}
              </p>
              <p
                class="font-sans text-sm"
                :class="rowOverdue(row) ? 'font-medium text-danger' : 'text-text-muted'"
              >
                {{ formatRelativeDeadline(rowDeadline(row)) }}
              </p>
              <p
                v-if="row.conflit_interet"
                class="font-sans text-sm text-warning"
              >
                Dossier lié à ce compte : réaffectation nécessaire.
              </p>
              <UiLink
                plain
                :to="`/administration/verification/${row.id}`"
              >
                {{ actionLabel() }}
                <UiIcon name="arrow-right" />
              </UiLink>
            </li>
          </ul>
        </div>

        <div
          class="hidden overflow-x-auto lg:block"
          :aria-busy="loading || undefined"
        >
          <table class="w-full min-w-[48rem] border-collapse text-left font-sans text-sm">
            <thead>
              <tr class="border-b border-border text-text-muted">
                <th scope="col" class="py-2 pr-gutter font-medium">Dossier / Sujet</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Niveau</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Statut</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Responsable</th>
                <th scope="col" class="py-2 pr-gutter font-medium">{{ deadlineColumnLabel }}</th>
                <th scope="col" class="py-2 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && dossiers.length === 0">
                <td colspan="6" class="py-gutter">
                  <div class="flex flex-col gap-stack">
                    <Skeleton v-for="index in 4" :key="index" class="h-12 w-full" />
                  </div>
                </td>
              </tr>
              <tr v-else-if="loadState === 'empty'">
                <td colspan="6" class="py-gutter">
                  <EmptyState
                    plain
                    :title="emptyTitle"
                    :message="emptyMessage"
                  />
                </td>
              </tr>
              <tr
                v-for="row in dossiers"
                :key="row.id"
                class="border-b border-border last:border-0"
              >
                <td class="py-gutter pr-gutter align-middle">
                  <p class="font-medium text-text">
                    {{ row.numero || 'Dossier' }} · V{{ row.version }}
                  </p>
                  <p class="text-text-muted">
                    {{ row.sujet.libelle }}
                  </p>
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  <span class="inline-flex items-center gap-2">
                    <UiIcon
                      :name="iconForVerificationLevel(row.niveau)"
                      size="sm"
                    />
                    {{ labelForVerificationLevelShort(row.niveau) }}
                  </span>
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :icon="statusIcon(row)"
                    :label="statusLabel(row)"
                    :tone="statusTone(row)"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  <p>{{ assigneeLabel(row) }}</p>
                  <p
                    v-if="row.conflit_interet"
                    class="text-warning"
                  >
                    Réaffectation nécessaire
                  </p>
                </td>
                <td
                  class="py-gutter pr-gutter align-middle"
                  :class="rowOverdue(row) ? 'font-medium text-danger' : 'text-text-muted'"
                >
                  {{ formatRelativeDeadline(rowDeadline(row)) }}
                </td>
                <td class="py-gutter align-middle">
                  <UiLink
                    plain
                    :to="`/administration/verification/${row.id}`"
                  >
                    {{ actionLabel() }}
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
              Points de vigilance
            </h3>
            <ul
              v-if="hasWatchpoints"
              class="mt-1 list-disc pl-4 font-sans text-sm text-text-muted"
            >
              <li v-if="unassignedCount > 0">
                {{ unassignedCount }} dossier{{ unassignedCount === 1 ? '' : 's' }} attend{{ unassignedCount === 1 ? '' : 'ent' }} une affectation
              </li>
              <li v-if="overdueCount > 0">
                {{ overdueCount }} échéance{{ overdueCount === 1 ? '' : 's' }} de traitement {{ overdueCount === 1 ? 'est' : 'sont' }} dépassée{{ overdueCount === 1 ? '' : 's' }}
              </li>
            </ul>
            <p
              v-else
              class="mt-1 font-sans text-sm text-text-muted"
            >
              Aucun point de vigilance pour le moment. Un dépassement d’échéance n’approuve rien.
            </p>
          </div>
        </article>
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
            aria-hidden="true"
          >
            <UiIcon name="file-text" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Traçabilité des décisions
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              Chaque décision concerne une version précise du dossier.
            </p>
            <button
              type="button"
              class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
              @click="emit('update:tab', 'historique')"
            >
              Consulter l’historique
              <UiIcon name="arrow-right" />
            </button>
          </div>
        </article>
      </div>

      <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
        <UiIcon name="info" class="mt-0.5 shrink-0" />
        <span>L’approbation d’une organisation ne vaut pas habilitation commerciale.</span>
      </p>
    </div>
  </section>
</template>
