<script setup lang="ts">
import type {
  BackofficeAccountPagination,
  BackofficeSupervisionTab,
  SupervisionJournalEntry,
  SupervisionRetryEntry,
  SupervisionTransmission,
} from '../../../types/identity-access/backoffice'
import {
  actorLabel,
  formatClockTime,
  formatLastSeen,
  labelForOutgoingMessageType,
  labelForSupervisionJournalType,
  labelForSupervisionState,
  labelForTransmissionCanal,
  supervisionCanalOptions,
  supervisionDayOptions,
  supervisionStateOptions,
  toneForSupervisionState,
} from '../../../utils/identity-access/backoffice'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeSupervisionTab
  canal: string
  etat: string
  jour: string
  page: number
  loadState: BackofficeLoadState
  transmissions: SupervisionTransmission[]
  evenements: SupervisionJournalEntry[]
  reprises: SupervisionRetryEntry[]
  journalRecent: SupervisionJournalEntry[]
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeSupervisionTab]
  'update:canal': [value: string]
  'update:etat': [value: string]
  'update:jour': [value: string]
  'update:page': [value: number]
  examine: [row: SupervisionTransmission]
  retry: []
}>()

const tabs = [
  { id: 'supervision' as const, label: 'Supervision' },
  { id: 'journal' as const, label: 'Journal des événements' },
  { id: 'reprises' as const, label: 'Historique des reprises' },
]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => {
  if (!loading.value) {
    return false
  }
  if (props.tab === 'journal') {
    return props.evenements.length > 0
  }
  if (props.tab === 'reprises') {
    return props.reprises.length > 0
  }
  return props.transmissions.length > 0
})

const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (
    props.search.trim() !== ''
    || props.canal !== ''
    || props.etat !== ''
    || (props.tab === 'supervision' && props.jour !== 'aujourdhui')
  )
))

const emptyTitle = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun résultat'
  }
  if (props.tab === 'journal') {
    return 'Aucun événement'
  }
  if (props.tab === 'reprises') {
    return 'Aucune reprise'
  }
  return 'Aucune transmission à surveiller'
})

const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucune ligne ne correspond à cette recherche. Modifiez les filtres.'
  }
  if (props.tab === 'journal') {
    return 'Les interventions de compte et de vérification apparaîtront ici.'
  }
  if (props.tab === 'reprises') {
    return 'Aucune reprise de transmission n’a encore été enregistrée.'
  }
  return 'Aucune transmission du jour n’est actuellement à surveiller.'
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.tab === 'journal'
    ? props.evenements.length
    : (props.tab === 'reprises' ? props.reprises.length : props.transmissions.length)
  const total = pagination.total
  const unit = props.tab === 'journal' ? 'événement' : (props.tab === 'reprises' ? 'reprise' : 'transmission')
  if (total === 0) {
    return `0 ${unit}`
  }
  if (shown === total) {
    return total === 1 ? `1 ${unit}` : `${total} ${unit}s`
  }
  const from = (pagination.page - 1) * pagination.per_page + 1
  const to = from + shown - 1
  return `${from}–${to} sur ${total}`
})

function eventTitle(row: SupervisionTransmission): string {
  return labelForOutgoingMessageType(row.type)
}

function journalActor(row: SupervisionJournalEntry): string {
  return actorLabel(row.systeme, row.auteur)
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="supervision-directory-title"
  >
    <h2 id="supervision-directory-title" class="sr-only">
      Supervision des transmissions
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues de la supervision">
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
            </button>
          </li>
        </ul>
      </nav>

      <div class="flex flex-col gap-gutter lg:flex-row lg:items-end lg:justify-between">
        <h3 class="font-serif text-lg font-semibold text-text">
          <template v-if="tab === 'journal'">Journal des événements</template>
          <template v-else-if="tab === 'reprises'">Historique des reprises</template>
          <template v-else>Transmissions à surveiller</template>
        </h3>
        <form
          class="grid w-full gap-stack lg:max-w-4xl"
          :class="tab === 'supervision' ? 'lg:grid-cols-5' : 'lg:grid-cols-2'"
          @submit.prevent
        >
          <FormField
            class="lg:col-span-2"
            label="Recherche"
            hint="Référence, événement ou destination masquée."
          >
            <template #default="{ id, describedBy }">
              <TextInput
                :id="id"
                :model-value="search"
                icon="search"
                placeholder="Rechercher…"
                :aria-describedby="describedBy"
                autocomplete="off"
                @update:model-value="emit('update:search', $event)"
              />
            </template>
          </FormField>
          <FormField
            v-if="tab === 'supervision'"
            label="Canal"
          >
            <template #default="{ id, describedBy }">
              <SelectInput
                :id="id"
                :model-value="canal"
                :options="[...supervisionCanalOptions]"
                :aria-describedby="describedBy"
                @update:model-value="emit('update:canal', $event)"
              />
            </template>
          </FormField>
          <FormField
            v-if="tab === 'supervision'"
            label="État"
          >
            <template #default="{ id, describedBy }">
              <SelectInput
                :id="id"
                :model-value="etat"
                :options="[...supervisionStateOptions]"
                :aria-describedby="describedBy"
                @update:model-value="emit('update:etat', $event)"
              />
            </template>
          </FormField>
          <FormField
            v-if="tab === 'supervision'"
            label="Jour"
          >
            <template #default="{ id, describedBy }">
              <SelectInput
                :id="id"
                :model-value="jour"
                :options="[...supervisionDayOptions]"
                :aria-describedby="describedBy"
                @update:model-value="emit('update:jour', $event)"
              />
            </template>
          </FormField>
        </form>
      </div>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger la supervision"
        :message="errorMessage || 'La lecture de la supervision a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || transmissions.length > 0 || evenements.length > 0 || reprises.length > 0">
        <p
          v-if="resultLabel && !loading"
          class="font-sans text-sm text-text-muted"
        >
          {{ resultLabel }}
        </p>

        <template v-if="tab === 'supervision'">
          <div class="lg:hidden" :aria-busy="loading || undefined">
            <div v-if="loading && transmissions.length === 0" class="flex flex-col gap-stack">
              <Skeleton v-for="index in 4" :key="index" class="h-24 w-full" />
            </div>
            <EmptyState
              v-else-if="loadState === 'empty'"
              :title="emptyTitle"
              :message="emptyMessage"
            />
            <ul v-else class="flex flex-col gap-stack">
              <li
                v-for="row in transmissions"
                :key="row.id"
                class="rounded-md border border-border px-gutter py-stack"
              >
                <p class="font-sans text-xs font-medium text-text-muted">
                  {{ row.reference || 'Référence indisponible' }}
                </p>
                <p class="mt-1 font-sans text-sm font-medium text-text">
                  {{ eventTitle(row) }}
                </p>
                <p class="mt-1 font-sans text-sm text-text-muted">
                  {{ labelForTransmissionCanal(row.canal) }}
                  ·
                  {{ row.destination_masquee || 'Destination masquée indisponible' }}
                </p>
                <div class="mt-stack flex flex-wrap items-center justify-between gap-stack">
                  <StatusBadge
                    :label="labelForSupervisionState(row.etat)"
                    :tone="toneForSupervisionState(row.etat)"
                    :icon="row.etat === 'failed' ? 'circle-x' : (row.etat === 'sent' ? 'success' : 'clock')"
                  />
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                    @click="emit('examine', row)"
                  >
                    Examiner
                    <UiIcon name="arrow-right" />
                  </button>
                </div>
              </li>
            </ul>
          </div>

          <div class="hidden overflow-x-auto lg:block" :aria-busy="loading || undefined">
            <table class="w-full min-w-[48rem] border-collapse text-left font-sans text-sm">
              <thead>
                <tr class="border-b border-border text-text-muted">
                  <th scope="col" class="py-2 pr-gutter font-medium">Référence / Événement</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Destination</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">État</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Dernière tentative</th>
                  <th scope="col" class="py-2 font-medium">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading && transmissions.length === 0">
                  <td colspan="5" class="py-gutter">
                    <div class="flex flex-col gap-stack">
                      <Skeleton v-for="index in 4" :key="index" class="h-12 w-full" />
                    </div>
                  </td>
                </tr>
                <tr v-else-if="loadState === 'empty'">
                  <td colspan="5" class="py-gutter">
                    <EmptyState
                      plain
                      :title="emptyTitle"
                      :message="emptyMessage"
                    />
                  </td>
                </tr>
                <tr
                  v-for="row in transmissions"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle">
                    <p class="font-medium text-text">
                      {{ row.reference || 'Référence indisponible' }}
                    </p>
                    <p class="text-text-muted">
                      {{ eventTitle(row) }}
                    </p>
                  </td>
                  <td class="py-gutter pr-gutter align-middle">
                    <p class="text-text">
                      {{ labelForTransmissionCanal(row.canal) }}
                    </p>
                    <p class="text-text-muted">
                      {{ row.destination_masquee || 'Destination masquée indisponible' }}
                    </p>
                  </td>
                  <td class="py-gutter pr-gutter align-middle">
                    <StatusBadge
                      :label="labelForSupervisionState(row.etat)"
                      :tone="toneForSupervisionState(row.etat)"
                      :icon="row.etat === 'failed' ? 'circle-x' : (row.etat === 'sent' ? 'success' : 'clock')"
                    />
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ row.derniere_tentative_a ? formatLastSeen(row.derniere_tentative_a) : 'Aucune tentative' }}
                  </td>
                  <td class="py-gutter align-middle">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                      @click="emit('examine', row)"
                    >
                      Examiner
                      <UiIcon name="arrow-right" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <template v-else-if="tab === 'journal'">
          <div class="overflow-x-auto" :aria-busy="loading || undefined">
            <table class="w-full min-w-[40rem] border-collapse text-left font-sans text-sm">
              <thead>
                <tr class="border-b border-border text-text-muted">
                  <th scope="col" class="py-2 pr-gutter font-medium">Heure</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Intervention</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Effectué par</th>
                  <th scope="col" class="py-2 font-medium">Référence</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading && evenements.length === 0">
                  <td colspan="4" class="py-gutter">
                    <Skeleton class="h-12 w-full" />
                  </td>
                </tr>
                <tr v-else-if="loadState === 'empty'">
                  <td colspan="4" class="py-gutter">
                    <EmptyState plain :title="emptyTitle" :message="emptyMessage" />
                  </td>
                </tr>
                <tr
                  v-for="row in evenements"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ formatClockTime(row.date) }}
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text">
                    {{ labelForSupervisionJournalType(row.source, row.type) }}
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ journalActor(row) }}
                  </td>
                  <td class="py-gutter align-middle">
                    <CopyReference
                      v-if="row.reference"
                      compact
                      :value="row.reference"
                      label="Copier la référence de suivi"
                    />
                    <span v-else class="text-text-muted">Référence indisponible</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <template v-else>
          <div class="overflow-x-auto" :aria-busy="loading || undefined">
            <table class="w-full min-w-[40rem] border-collapse text-left font-sans text-sm">
              <thead>
                <tr class="border-b border-border text-text-muted">
                  <th scope="col" class="py-2 pr-gutter font-medium">Heure</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Intervention</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Destination</th>
                  <th scope="col" class="py-2 font-medium">Référence</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading && reprises.length === 0">
                  <td colspan="4" class="py-gutter">
                    <Skeleton class="h-12 w-full" />
                  </td>
                </tr>
                <tr v-else-if="loadState === 'empty'">
                  <td colspan="4" class="py-gutter">
                    <EmptyState plain :title="emptyTitle" :message="emptyMessage" />
                  </td>
                </tr>
                <tr
                  v-for="row in reprises"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ formatClockTime(row.date) }}
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text">
                    {{ labelForOutgoingMessageType(row.type) }}
                    <span
                      v-if="row.etat"
                      class="mt-1 block text-text-muted"
                    >
                      {{ labelForSupervisionState(row.etat) }}
                    </span>
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ row.destination_masquee || 'Destination masquée indisponible' }}
                  </td>
                  <td class="py-gutter align-middle">
                    <CopyReference
                      v-if="row.reference"
                      compact
                      :value="row.reference"
                      label="Copier la référence de suivi"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

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
        Actualisation de la supervision…
      </p>

      <div
        v-if="tab === 'supervision'"
        class="rounded-md bg-surface-muted px-gutter py-gutter"
      >
        <h3 class="font-sans text-sm font-semibold text-text">
          Journal récent
        </h3>
        <ul
          v-if="journalRecent.length > 0"
          class="mt-stack flex flex-col gap-stack"
        >
          <li
            v-for="row in journalRecent"
            :key="row.id"
            class="flex flex-wrap items-baseline justify-between gap-stack font-sans text-sm"
          >
            <span class="text-text-muted">{{ formatClockTime(row.date) }}</span>
            <span class="min-w-0 flex-1 text-text">{{ labelForSupervisionJournalType(row.source, row.type) }}</span>
            <span class="text-text-muted">{{ journalActor(row) }}</span>
            <span class="text-text-muted">{{ row.reference }}</span>
          </li>
        </ul>
        <p
          v-else
          class="mt-stack font-sans text-sm text-text-muted"
        >
          Aucune intervention récente n’est disponible.
        </p>
        <button
          type="button"
          class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
          @click="emit('update:tab', 'journal')"
        >
          Ouvrir le journal
          <UiIcon name="arrow-right" />
        </button>
      </div>

      <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
        <UiIcon name="lock" class="mt-0.5 shrink-0" />
        <span>Une reprise ne rejoue pas une décision. Les secrets d’envoi ne sont pas affichés.</span>
      </p>
    </div>
  </section>
</template>
