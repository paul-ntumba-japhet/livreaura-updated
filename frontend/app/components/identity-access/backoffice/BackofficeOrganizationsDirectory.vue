<script setup lang="ts">
import type {
  BackofficeAccountPagination,
  BackofficeOrganizationDemand,
  BackofficeOrganizationRow,
  BackofficeOrganizationTab,
  BackofficeOrganizationTotals,
  BackofficeOrganizationType,
} from '../../../types/identity-access/backoffice'
import { countryLabel } from '../../../utils/identity-access/account'
import { iconForOrganizationTypeCode } from '../../../utils/identity-access/organization-display'
import {
  displayPersonName,
  iconForOrganizationVerification,
  labelForOrganizationVerification,
  labelForRepresentationState,
  organizationInitials,
  personInitials,
  toneForOrganizationVerification,
} from '../../../utils/identity-access/backoffice'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeOrganizationTab
  typeId: string
  verification: string
  page: number
  loadState: BackofficeLoadState
  organisations: BackofficeOrganizationRow[]
  demandes: BackofficeOrganizationDemand[]
  types: BackofficeOrganizationType[]
  totaux: BackofficeOrganizationTotals | null
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
  canTransfer?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeOrganizationTab]
  'update:typeId': [value: string]
  'update:verification': [value: string]
  'update:page': [value: number]
  retry: []
}>()

const tabs = computed(() => {
  const counts = props.totaux
  return [
    { id: 'organisations' as const, label: 'Organisations', count: counts?.organisations },
    { id: 'representation' as const, label: 'Demandes de rattachement', count: counts?.rattachements },
    { id: 'transfert' as const, label: 'Transferts', count: counts?.transferts },
  ]
})

const typeOptions = computed(() => [
  { value: '', label: 'Tous les types' },
  ...props.types.map(type => ({ value: type.id, label: type.libelle || type.code })),
])

const verificationOptions = [
  { value: '', label: 'Vérification organisation' },
  { value: 'verifiee', label: 'Vérifiée' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'non_verifiee', label: 'Non vérifiée' },
  { value: 'expiree', label: 'Expirée' },
  { value: 'a_renouveler', label: 'À renouveler' },
]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && (props.organisations.length > 0 || props.demandes.length > 0))
const isOrganizationsTab = computed(() => props.tab === 'organisations')
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.typeId !== '' || props.verification !== '')
))

const emptyTitle = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun résultat'
  }
  if (props.tab === 'representation') {
    return 'Aucune demande de rattachement'
  }
  if (props.tab === 'transfert') {
    return 'Aucun transfert en attente'
  }
  return 'Aucune organisation'
})

const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucune organisation ni demande ne correspond à cette recherche. Modifiez les filtres.'
  }
  if (props.tab === 'representation') {
    return 'Aucune demande de représentation n’est ouverte. Une correspondance de nom n’accorde aucun droit.'
  }
  if (props.tab === 'transfert') {
    return 'Aucun transfert de représentant n’est en attente. Un transfert enregistré n’apparaît plus ici.'
  }
  return 'Aucune organisation n’est encore enregistrée dans ce service.'
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = isOrganizationsTab.value ? props.organisations.length : props.demandes.length
  const total = pagination.total
  const noun = isOrganizationsTab.value ? 'organisation' : 'demande'
  if (total === 0) {
    return `0 ${noun}`
  }
  if (shown === total) {
    return total === 1 ? `1 ${noun}` : `${total} ${noun}s`
  }
  return `${shown} sur ${total} ${noun}${total > 1 ? 's' : ''}`
})

function location(ville: string | null | undefined, pays: string | null | undefined): string {
  const city = ville?.trim()
  const country = countryLabel(pays)
  if (city && country) {
    return `${city} · ${country}`
  }
  return city || country || ''
}

function demandLabel(type: string): string {
  return type === 'transfert_principal' ? 'Transfert de représentant' : 'Demande de rattachement'
}

function demandIcon(type: string): 'refresh' | 'inbox' {
  return type === 'transfert_principal' ? 'refresh' : 'inbox'
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="organizations-directory-title"
  >
    <h2 id="organizations-directory-title" class="sr-only">
      Organisations
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues des organisations">
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
          hint="Nom d’organisation, représentant ou référence de demande."
        >
          <template #default="{ id, describedBy }">
            <TextInput
              :id="id"
              :model-value="search"
              icon="search"
              placeholder="Rechercher une organisation ou un représentant"
              :aria-describedby="describedBy"
              autocomplete="off"
              @update:model-value="emit('update:search', $event)"
            />
          </template>
        </FormField>
        <FormField label="Type">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="typeId"
              :options="typeOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:typeId', $event)"
            />
          </template>
        </FormField>
        <FormField
          v-if="isOrganizationsTab"
          label="Vérification organisation"
        >
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="verification"
              :options="verificationOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:verification', $event)"
            />
          </template>
        </FormField>
      </form>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger les organisations"
        :message="errorMessage || 'La lecture du répertoire a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || organisations.length > 0 || demandes.length > 0">
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
          <div v-if="loading && organisations.length === 0 && demandes.length === 0" class="flex flex-col gap-stack">
            <Skeleton v-for="index in 4" :key="index" class="h-24 w-full" />
          </div>
          <EmptyState
            v-else-if="loadState === 'empty'"
            plain
            :title="emptyTitle"
            :message="emptyMessage"
          />
          <ul
            v-else-if="isOrganizationsTab"
            class="flex flex-col divide-y divide-border"
          >
            <li
              v-for="row in organisations"
              :key="row.id"
              class="flex flex-col gap-stack py-gutter"
            >
              <div class="flex items-start gap-stack">
                <span
                  class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                  aria-hidden="true"
                >
                  {{ organizationInitials(row.raison_sociale) }}
                </span>
                <div class="min-w-0">
                  <p class="font-sans text-sm font-semibold text-text">
                    {{ row.raison_sociale }}
                  </p>
                  <p class="font-sans text-sm text-text-muted">
                    {{ location(row.ville, row.pays) }}
                  </p>
                </div>
              </div>
              <p class="flex items-center gap-2 font-sans text-sm text-text-muted">
                <UiIcon
                  :name="iconForOrganizationTypeCode(row.type?.code || '')"
                  size="sm"
                />
                {{ row.type?.libelle || 'Type indisponible' }}
              </p>
              <div>
                <p class="font-sans text-sm text-text">
                  {{ row.representant ? displayPersonName(row.representant.prenom, row.representant.nom) : 'Aucun représentant' }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ labelForRepresentationState(row.representation) }}
                </p>
              </div>
              <StatusBadge
                :icon="iconForOrganizationVerification(row.verification)"
                :label="labelForOrganizationVerification(row.verification)"
                :tone="toneForOrganizationVerification(row.verification)"
              />
              <UiLink
                v-if="canTransfer"
                plain
                :to="`/administration/organisations/${row.id}`"
              >
                Voir la fiche
                <UiIcon name="arrow-right" />
              </UiLink>
            </li>
          </ul>
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
                  {{ row.organisation?.raison_sociale || 'Organisation' }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ row.reference }}
                </p>
              </div>
              <p class="flex items-center gap-2 font-sans text-sm text-text-muted">
                <UiIcon
                  :name="demandIcon(row.type)"
                  size="sm"
                />
                {{ demandLabel(row.type) }}
              </p>
              <p class="font-sans text-sm text-text">
                {{ row.demandeur ? displayPersonName(row.demandeur.prenom, row.demandeur.nom) : 'Demandeur indisponible' }}
              </p>
              <UiLink
                v-if="row.organisation?.id && canTransfer"
                plain
                :to="`/administration/organisations/${row.organisation.id}`"
              >
                Voir la fiche
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
                <template v-if="isOrganizationsTab">
                  <th scope="col" class="py-2 pr-gutter font-medium">Organisation</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Type</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Représentant principal</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Vérification organisation</th>
                  <th scope="col" class="py-2 font-medium">Action</th>
                </template>
                <template v-else>
                  <th scope="col" class="py-2 pr-gutter font-medium">Demande</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Type</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Demandeur</th>
                  <th scope="col" class="py-2 font-medium">Action</th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && organisations.length === 0 && demandes.length === 0">
                <td
                  :colspan="isOrganizationsTab ? 5 : 4"
                  class="py-gutter"
                >
                  <div class="flex flex-col gap-stack">
                    <Skeleton v-for="index in 4" :key="index" class="h-12 w-full" />
                  </div>
                </td>
              </tr>
              <tr v-else-if="loadState === 'empty'">
                <td
                  :colspan="isOrganizationsTab ? 5 : 4"
                  class="py-gutter"
                >
                  <EmptyState
                    plain
                    :title="emptyTitle"
                    :message="emptyMessage"
                  />
                </td>
              </tr>
              <template v-else-if="isOrganizationsTab">
                <tr
                  v-for="row in organisations"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle">
                    <div class="flex items-center gap-stack">
                      <span
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                        aria-hidden="true"
                      >
                        {{ organizationInitials(row.raison_sociale) }}
                      </span>
                      <div class="min-w-0">
                        <p class="font-medium text-text">
                          {{ row.raison_sociale }}
                        </p>
                        <p class="text-text-muted">
                          {{ location(row.ville, row.pays) }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    <span class="inline-flex items-center gap-2">
                      <UiIcon
                        :name="iconForOrganizationTypeCode(row.type?.code || '')"
                        size="sm"
                      />
                      {{ row.type?.libelle || 'Type indisponible' }}
                    </span>
                  </td>
                  <td class="py-gutter pr-gutter align-middle">
                    <div
                      v-if="row.representant"
                      class="flex items-center gap-stack"
                    >
                      <span
                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-xs font-semibold text-text"
                        aria-hidden="true"
                      >
                        {{ personInitials(row.representant.prenom, row.representant.nom) }}
                      </span>
                      <div class="min-w-0">
                        <p class="font-medium text-text">
                          {{ displayPersonName(row.representant.prenom, row.representant.nom) }}
                        </p>
                        <p class="text-text-muted">
                          {{ labelForRepresentationState(row.representation) }}
                        </p>
                      </div>
                    </div>
                    <p v-else class="text-text-muted">
                      Aucun représentant
                    </p>
                  </td>
                  <td class="py-gutter pr-gutter align-middle">
                    <StatusBadge
                      :icon="iconForOrganizationVerification(row.verification)"
                      :label="labelForOrganizationVerification(row.verification)"
                      :tone="toneForOrganizationVerification(row.verification)"
                    />
                  </td>
                  <td class="py-gutter align-middle">
                    <UiLink
                      v-if="canTransfer"
                      plain
                      :to="`/administration/organisations/${row.id}`"
                    >
                      Voir la fiche
                      <UiIcon name="arrow-right" />
                    </UiLink>
                  </td>
                </tr>
              </template>
              <template v-else>
                <tr
                  v-for="row in demandes"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle">
                    <p class="font-medium text-text">
                      {{ row.organisation?.raison_sociale || 'Organisation' }}
                    </p>
                    <p class="text-text-muted">
                      {{ row.reference }}
                    </p>
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    <span class="inline-flex items-center gap-2">
                      <UiIcon
                        :name="demandIcon(row.type)"
                        size="sm"
                      />
                      {{ demandLabel(row.type) }}
                    </span>
                  </td>
                  <td class="py-gutter pr-gutter align-middle">
                    <p class="font-medium text-text">
                      {{ row.demandeur ? displayPersonName(row.demandeur.prenom, row.demandeur.nom) : 'Demandeur indisponible' }}
                    </p>
                    <p
                      v-if="row.demandeur?.email_masque"
                      class="text-text-muted"
                    >
                      {{ row.demandeur.email_masque }}
                    </p>
                  </td>
                  <td class="py-gutter align-middle">
                    <UiLink
                      v-if="row.organisation?.id && canTransfer"
                      plain
                      :to="`/administration/organisations/${row.organisation.id}`"
                    >
                      Voir la fiche
                      <UiIcon name="arrow-right" />
                    </UiLink>
                  </td>
                </tr>
              </template>
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
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
            aria-hidden="true"
          >
            <UiIcon name="inbox" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Demandes de rattachement
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              {{ totaux?.rattachements ?? 0 }} demande{{ (totaux?.rattachements ?? 0) === 1 ? '' : 's' }} à examiner
            </p>
            <button
              type="button"
              class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
              @click="emit('update:tab', 'representation')"
            >
              Examiner les demandes
              <UiIcon name="arrow-right" />
            </button>
          </div>
        </article>
        <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
            aria-hidden="true"
          >
            <UiIcon name="refresh" size="md" />
          </span>
          <div class="min-w-0">
            <h3 class="font-sans text-sm font-semibold text-text">
              Transferts de représentation
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              {{ totaux?.transferts ?? 0 }} changement{{ (totaux?.transferts ?? 0) === 1 ? '' : 's' }} de représentant en attente
            </p>
            <button
              type="button"
              class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
              @click="emit('update:tab', 'transfert')"
            >
              Examiner les transferts
              <UiIcon name="arrow-right" />
            </button>
          </div>
        </article>
      </div>

      <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
        <UiIcon name="info" class="mt-0.5 shrink-0" />
        <span>La vérification de l’organisation et l’habilitation commerciale sont distinctes.</span>
      </p>
    </div>
  </section>
</template>
