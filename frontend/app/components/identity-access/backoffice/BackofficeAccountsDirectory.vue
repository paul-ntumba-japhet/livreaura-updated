<script setup lang="ts">
import type { BackofficeAccountPagination, BackofficeAccountRow, BackofficeAccountTotals } from '../../../types/identity-access/backoffice'
import { normalizeAccountStatus } from '../../../types/identity-access/account'
import {
  iconForAccountStatus,
  iconForEmailVerification,
  labelForAccountStatus,
  labelForRole,
  toneForAccountStatus,
} from '../../../utils/identity-access/account'
import { formatLastSeen } from '../../../utils/identity-access/backoffice'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

export type AccountDirectoryTab = 'all' | 'pending' | 'suspended' | 'closed'

const props = defineProps<{
  search: string
  tab: AccountDirectoryTab
  role: string
  emailVerified: string
  page: number
  loadState: BackofficeLoadState
  accounts: BackofficeAccountRow[]
  totaux: BackofficeAccountTotals | null
  pagination: BackofficeAccountPagination | null
  errorMessage?: string
  retrying?: boolean
  lookupError?: string
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: AccountDirectoryTab]
  'update:role': [value: string]
  'update:emailVerified': [value: string]
  'update:page': [value: number]
  open: []
  retry: []
}>()

const tabs = computed(() => {
  const counts = props.totaux
  return [
    { id: 'all' as const, label: 'Tous les comptes', count: counts?.total },
    { id: 'pending' as const, label: 'En attente', count: counts?.pending },
    { id: 'suspended' as const, label: 'Suspendus', count: counts?.suspended },
    { id: 'closed' as const, label: 'Fermés', count: counts?.closed },
  ]
})

const roleOptions = [
  { value: '', label: 'Tous les rôles' },
  { value: 'reader', label: 'Lecteur' },
  { value: 'author', label: 'Auteur' },
  { value: 'admin', label: 'Administration' },
]
const verificationOptions = [
  { value: '', label: 'Tous les e-mails' },
  { value: 'oui', label: 'Vérifié' },
  { value: 'non', label: 'Non vérifié' },
]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && props.accounts.length > 0)
const canOpenLookup = computed(() => props.search.trim().length === 26)
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.tab !== 'all' || props.role !== '' || props.emailVerified !== '')
))

const emptyTitle = computed(() => (filteredEmpty.value ? 'Aucun résultat' : 'Aucun compte'))
const emptyMessage = computed(() => {
  if (filteredEmpty.value) {
    return 'Aucun compte ne correspond à cette recherche. Modifiez les filtres ou ouvrez un compte par sa référence de suivi.'
  }
  switch (props.tab) {
    case 'pending':
      return 'Aucun compte n’est en attente d’activation.'
    case 'suspended':
      return 'Aucun compte n’est suspendu.'
    case 'closed':
      return 'Aucun compte n’est clôturé.'
    default:
      return 'Aucun compte n’est encore enregistré dans ce service.'
  }
})

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.accounts.length
  const total = pagination.total
  if (total === 0) {
    return '0 compte'
  }
  if (shown === total) {
    return total === 1 ? '1 compte' : `${total} comptes`
  }
  return `${shown} sur ${total} comptes`
})

function displayName(row: BackofficeAccountRow): string {
  const prenom = row.prenom?.trim()
  const nom = row.nom?.trim()
  if (prenom && nom) {
    return `${prenom} ${nom}`
  }
  return prenom || nom || 'Compte'
}

function initials(row: BackofficeAccountRow): string {
  const prenom = row.prenom?.trim()
  const nom = row.nom?.trim()
  const value = `${prenom ? prenom.slice(0, 1) : ''}${nom ? nom.slice(0, 1) : ''}`.toUpperCase()
  return value || '?'
}

function roleLabels(row: BackofficeAccountRow): string[] {
  if (row.roles.length === 0) {
    return ['Aucun rôle']
  }
  return row.roles.map(code => labelForRole(code))
}

function onSubmit() {
  emit('open')
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="accounts-directory-title"
  >
    <h2 id="accounts-directory-title" class="sr-only">
      Comptes
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Statut des comptes">
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
        @submit.prevent="onSubmit"
      >
        <FormField
          label="Compte"
          :error="lookupError"
          hint="Nom, adresse e-mail ou référence de suivi."
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              :model-value="search"
              icon="search"
              placeholder="Rechercher un compte"
              :invalid="invalid"
              :aria-describedby="describedBy"
              autocomplete="off"
              @update:model-value="emit('update:search', $event)"
            />
          </template>
        </FormField>
        <FormField label="Rôle">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="role"
              :options="roleOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:role', $event)"
            />
          </template>
        </FormField>
        <FormField label="Vérification e-mail">
          <template #default="{ id, describedBy }">
            <SelectInput
              :id="id"
              :model-value="emailVerified"
              :options="verificationOptions"
              :aria-describedby="describedBy"
              @update:model-value="emit('update:emailVerified', $event)"
            />
          </template>
        </FormField>
      </form>

      <ErrorState
        v-if="loadState === 'error'"
        title="Impossible de charger les comptes"
        :message="errorMessage || 'La lecture du répertoire a échoué. Vous pouvez réessayer.'"
        retry-label="Réessayer"
        :retrying="retrying"
        @retry="emit('retry')"
      />

      <template v-if="loadState !== 'error' || accounts.length > 0">
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
          <div v-if="loading && accounts.length === 0" class="flex flex-col gap-stack">
            <Skeleton v-for="index in 4" :key="index" class="h-24 w-full" />
          </div>
          <EmptyState
            v-else-if="loadState === 'empty'"
            plain
            :title="emptyTitle"
            :message="emptyMessage"
          >
            <template v-if="canOpenLookup" #action>
              <UiButton type="button" @click="emit('open')">
                Ouvrir le compte
              </UiButton>
            </template>
          </EmptyState>
          <ul
            v-else
            class="flex flex-col divide-y divide-border"
          >
            <li
              v-for="row in accounts"
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
                    {{ displayName(row) }}
                  </p>
                  <p class="font-sans text-sm text-text-muted">
                    {{ row.email_masque }}
                  </p>
                  <p v-if="row.organisation" class="font-sans text-sm text-text-muted">
                    {{ row.organisation }}
                  </p>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-stack">
                <StatusBadge
                  :icon="iconForAccountStatus(normalizeAccountStatus(row.statut))"
                  :label="labelForAccountStatus(normalizeAccountStatus(row.statut))"
                  :tone="toneForAccountStatus(normalizeAccountStatus(row.statut))"
                />
                <StatusBadge
                  :icon="iconForEmailVerification(row.email_verifie)"
                  :label="row.email_verifie ? 'Vérifié' : 'Non vérifié'"
                  :tone="row.email_verifie ? 'success' : 'warning'"
                />
              </div>
              <p class="font-sans text-sm text-text-muted">
                {{ roleLabels(row).join(', ') }}
              </p>
              <p class="font-sans text-sm text-text-muted">
                Dernière connexion : {{ formatLastSeen(row.derniere_connexion_a) }}
              </p>
              <UiLink plain :to="`/administration/comptes/${row.id}`">
                Voir le compte
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
                <th scope="col" class="py-2 pr-gutter font-medium">Compte</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Rôles</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Statut</th>
                <th scope="col" class="py-2 pr-gutter font-medium">E-mail</th>
                <th scope="col" class="py-2 pr-gutter font-medium">Dernière connexion</th>
                <th scope="col" class="py-2 font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading && accounts.length === 0">
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
                  >
                    <template v-if="canOpenLookup" #action>
                      <UiButton type="button" @click="emit('open')">
                        Ouvrir le compte
                      </UiButton>
                    </template>
                  </EmptyState>
                </td>
              </tr>
              <tr
                v-for="row in accounts"
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
                        {{ displayName(row) }}
                      </p>
                      <p class="text-text-muted">
                        {{ row.email_masque }}
                      </p>
                      <p v-if="row.organisation" class="text-text-muted">
                        {{ row.organisation }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <div class="flex flex-wrap gap-stack">
                    <span
                      v-for="label in roleLabels(row)"
                      :key="label"
                      class="rounded-full bg-surface-muted px-2.5 py-0.5 text-xs text-text"
                    >
                      {{ label }}
                    </span>
                  </div>
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :icon="iconForAccountStatus(normalizeAccountStatus(row.statut))"
                    :label="labelForAccountStatus(normalizeAccountStatus(row.statut))"
                    :tone="toneForAccountStatus(normalizeAccountStatus(row.statut))"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle">
                  <StatusBadge
                    :icon="iconForEmailVerification(row.email_verifie)"
                    :label="row.email_verifie ? 'Vérifié' : 'Non vérifié'"
                    :tone="row.email_verifie ? 'success' : 'warning'"
                  />
                </td>
                <td class="py-gutter pr-gutter align-middle text-text-muted">
                  {{ formatLastSeen(row.derniere_connexion_a) }}
                </td>
                <td class="py-gutter align-middle">
                  <UiLink plain :to="`/administration/comptes/${row.id}`">
                    Voir le compte
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

      <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
        <UiIcon name="lock" class="mt-0.5 shrink-0" />
        <span>Les actions sensibles sont accessibles depuis la fiche du compte, selon vos permissions. La levée n’est jamais automatique.</span>
      </p>
    </div>
  </section>
</template>
