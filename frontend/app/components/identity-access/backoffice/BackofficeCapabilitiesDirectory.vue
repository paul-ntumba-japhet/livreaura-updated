<script setup lang="ts">
import type {
  BackofficeAccountPagination,
  BackofficeCapabilityRow,
  BackofficeCapabilityTab,
  BackofficeCapabilityTotals,
} from '../../../types/identity-access/backoffice'
import { normalizeCapabilityState } from '../../../types/identity-access/backoffice'
import {
  formatFrenchDay,
  iconForCapabilityState,
  iconForHabilitationRenewal,
  labelForCapabilityState,
  labelForHabilitationRenewal,
  organizationInitials,
  personInitials,
  toneForCapabilityState,
  toneForHabilitationRenewal,
} from '../../../utils/identity-access/backoffice'
import { labelForVerificationLevel } from '../../../utils/identity-access/verification'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeCapabilityTab
  niveau: string
  echeance: string
  page: number
  loadState: BackofficeLoadState
  habilitations: BackofficeCapabilityRow[]
  totaux: BackofficeCapabilityTotals | null
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
  canManageInternal?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeCapabilityTab]
  'update:niveau': [value: string]
  'update:echeance': [value: string]
  'update:page': [value: number]
  retry: []
}>()

const tabs = computed(() => {
  const counts = props.totaux
  return [
    { id: 'toutes' as const, label: 'Toutes', count: counts?.toutes },
    { id: 'valides' as const, label: 'Valides', count: counts?.valides },
    { id: 'restreintes' as const, label: 'Restreintes', count: counts?.restreintes },
    { id: 'expirees' as const, label: 'Expirées', count: counts?.expirees },
    { id: 'revoquees' as const, label: 'Révoquées', count: counts?.revoquees },
  ]
})

const niveauOptions = [
  { value: '', label: 'Tous les niveaux' },
  { value: 'organisation_verifiee', label: 'Organisation vérifiée' },
  { value: 'partenaire_commercial', label: 'Partenaire commercial' },
]

const echeanceOptions = [
  { value: '', label: 'Toutes les échéances' },
  { value: '30j', label: 'Sous 30 jours' },
  { value: 'depassee', label: 'Dépassées' },
]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && props.habilitations.length > 0)
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.niveau !== '' || props.echeance !== '' || props.tab !== 'toutes')
))

const emptyTitle = computed(() => (filteredEmpty.value ? 'Aucun résultat' : 'Aucune habilitation'))
const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucune habilitation ne correspond à cette recherche. Modifiez les filtres.'
  }
  return 'Aucune habilitation de vérification n’est encore enregistrée.'
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.habilitations.length
  const total = pagination.total
  if (total === 0) {
    return '0 habilitation'
  }
  if (shown === total) {
    return total === 1 ? '1 habilitation' : `${total} habilitations`
  }
  return `${shown} sur ${total} habilitation${total > 1 ? 's' : ''}`
})

const upcomingCount = computed(() => props.totaux?.expirent_30j ?? 0)

function beneficiaryKind(row: BackofficeCapabilityRow): string {
  return row.beneficiaire.type === 'organisation' ? 'Organisation' : 'Particulier'
}

function initials(row: BackofficeCapabilityRow): string {
  if (row.beneficiaire.type === 'organisation') {
    return organizationInitials(row.beneficiaire.libelle)
  }
  const parts = row.beneficiaire.libelle.split(/\s+/).filter(Boolean)
  return personInitials(parts[0] || null, parts[1] || null)
}

function showSoonHint(row: BackofficeCapabilityRow): boolean {
  const state = normalizeCapabilityState(row.etat)
  const days = row.renouvellement.jours_restants
  return (state === 'valid' || state === 'a_renouveler')
    && typeof days === 'number'
    && days >= 0
    && days <= 30
}

function soonLabel(row: BackofficeCapabilityRow): string {
  const days = row.renouvellement.jours_restants
  if (days === 0) {
    return 'Aujourd’hui'
  }
  if (typeof days === 'number' && days > 0) {
    return `Dans ${days} jour${days === 1 ? '' : 's'}`
  }
  return ''
}

function dossierLink(row: BackofficeCapabilityRow): string | null {
  return row.dossier_id ? `/administration/verification/${row.dossier_id}` : null
}

function showRenewalBadge(row: BackofficeCapabilityRow): boolean {
  return row.renouvellement.code === 'en_examen' || row.renouvellement.code === 'a_corriger'
}

function openUpcoming() {
  emit('update:tab', 'valides')
  emit('update:echeance', '30j')
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="capabilities-directory-title"
  >
    <h2 id="capabilities-directory-title" class="sr-only">
      Habilitations
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues des habilitations">
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
        class="grid gap-stack lg:grid-cols-[minmax(0,1fr)_14rem_14rem]"
        @submit.prevent
      >
        <FormField
          label="Recherche"
          hint="Nom d’une personne ou d’une organisation."
        >
          <template #default="{ id, describedBy }">
            <TextInput
              :id="id"
              :model-value="search"
              icon="search"
              placeholder="Rechercher une personne ou une organisation"
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
        <FormField label="Échéance">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="echeance"
              :options="echeanceOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:echeance', $event)"
            />
          </template>
        </FormField>
      </form>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger les habilitations"
        :message="errorMessage || 'La lecture du répertoire a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || habilitations.length > 0">
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
          <div v-if="loading && habilitations.length === 0" class="flex flex-col gap-stack">
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
              v-for="row in habilitations"
              :key="row.id"
              class="flex flex-col gap-stack py-gutter"
            >
              <div class="flex items-start gap-stack">
                <span
                  class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                  aria-hidden="true"
                >
                  {{ initials(row) }}
                </span>
                <div class="min-w-0">
                  <p class="font-sans text-sm font-semibold text-text">
                    {{ row.beneficiaire.libelle }}
                  </p>
                  <p class="font-sans text-sm text-text-muted">
                    {{ beneficiaryKind(row) }}
                  </p>
                </div>
              </div>
              <p class="font-sans text-sm text-text-muted">
                {{ labelForVerificationLevel(row.niveau) }}
              </p>
              <StatusBadge
                :icon="iconForCapabilityState(row.etat)"
                :label="labelForCapabilityState(row.etat)"
                :tone="toneForCapabilityState(row.etat)"
              />
              <p class="font-sans text-sm text-text-muted">
                {{ formatFrenchDay(row.valide_jusqua) }}
                <span
                  v-if="showSoonHint(row)"
                  class="mt-1 block font-medium text-warning"
                >
                  {{ soonLabel(row) }}
                </span>
              </p>
              <StatusBadge
                v-if="showRenewalBadge(row)"
                :icon="iconForHabilitationRenewal(row.renouvellement.code)"
                :label="labelForHabilitationRenewal(row.renouvellement.code, row.renouvellement.jours_restants)"
                :tone="toneForHabilitationRenewal(row.renouvellement.code)"
              />
              <p
                v-else
                class="font-sans text-sm text-text-muted"
              >
                {{ labelForHabilitationRenewal(row.renouvellement.code, row.renouvellement.jours_restants) }}
              </p>
              <UiLink
                v-if="dossierLink(row)"
                plain
                :to="dossierLink(row)!"
              >
                Voir
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
                <th scope="col" class="py-2 pr-gutter font-medium">Bénéficiaire</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Niveau</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Statut</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Valable jusqu’au</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Renouvellement</th>
                <th scope="col" class="py-2 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && habilitations.length === 0">
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
                v-for="row in habilitations"
                :key="row.id"
                class="border-b border-border last:border-0"
              >
                <td class="py-gutter pr-gutter align-middle">
                  <div class="flex items-center gap-stack">
                    <span
                      class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                      aria-hidden="true"
                    >
                      {{ initials(row) }}
                    </span>
                    <div class="min-w-0">
                      <p class="font-medium text-text">
                        {{ row.beneficiaire.libelle }}
                      </p>
                      <p class="text-text-muted">
                        {{ beneficiaryKind(row) }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  {{ labelForVerificationLevel(row.niveau) }}
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :icon="iconForCapabilityState(row.etat)"
                    :label="labelForCapabilityState(row.etat)"
                    :tone="toneForCapabilityState(row.etat)"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  {{ formatFrenchDay(row.valide_jusqua) }}
                  <span
                    v-if="showSoonHint(row)"
                    class="mt-1 block font-medium text-warning"
                  >
                    {{ soonLabel(row) }}
                  </span>
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    v-if="showRenewalBadge(row)"
                    :icon="iconForHabilitationRenewal(row.renouvellement.code)"
                    :label="labelForHabilitationRenewal(row.renouvellement.code, row.renouvellement.jours_restants)"
                    :tone="toneForHabilitationRenewal(row.renouvellement.code)"
                  />
                  <span
                    v-else
                    class="text-text-muted"
                  >
                    {{ labelForHabilitationRenewal(row.renouvellement.code, row.renouvellement.jours_restants) }}
                  </span>
                </td>
                <td class="py-gutter align-middle">
                  <UiLink
                    v-if="dossierLink(row)"
                    plain
                    :to="dossierLink(row)!"
                  >
                    Voir
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
        Actualisation du répertoire…
      </p>

      <div class="grid gap-gutter lg:grid-cols-2">
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-warning"
            aria-hidden="true"
          >
            <UiIcon name="calendar" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Renouvellements à anticiper
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              {{ upcomingCount }} habilitation{{ upcomingCount === 1 ? '' : 's' }} arrive{{ upcomingCount === 1 ? '' : 'nt' }} à échéance sous 30 jours.
            </p>
            <button
              type="button"
              class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
              @click="openUpcoming"
            >
              Consulter les échéances
              <UiIcon name="arrow-right" />
            </button>
          </div>
        </article>
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
            aria-hidden="true"
          >
            <UiIcon name="clock" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Décisions et historique
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              Retrouvez les dossiers à l’origine des habilitations et les changements de statut.
            </p>
            <UiLink
              plain
              class="mt-stack"
              to="/administration/verification"
            >
              Consulter l’historique
              <UiIcon name="arrow-right" />
            </UiLink>
          </div>
        </article>
      </div>

      <article
        v-if="canManageInternal"
        class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter"
      >
        <span
          class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
          aria-hidden="true"
        >
          <UiIcon name="shield-check" size="md" />
        </span>
        <div class="min-w-0">
          <h3 class="font-sans text-sm font-semibold text-text">
            Attributions internes
          </h3>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Les permissions de service restent distinctes des habilitations de vérification.
          </p>
          <UiLink
            plain
            class="mt-stack"
            to="/administration/habilitations/internes"
          >
            Gérer une attribution interne
            <UiIcon name="arrow-right" />
          </UiLink>
        </div>
      </article>

      <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
        <UiIcon name="info" class="mt-0.5 shrink-0" />
        <span>Une habilitation commerciale ne remplace pas la vérification de l’organisation.</span>
      </p>
    </div>
  </section>
</template>
