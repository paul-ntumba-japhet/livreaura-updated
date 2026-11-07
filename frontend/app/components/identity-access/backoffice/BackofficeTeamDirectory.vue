<script setup lang="ts">
import type {
  BackofficeAccountPagination,
  BackofficeTeamAgent,
  BackofficeTeamConfiguration,
  BackofficeTeamSubstitute,
  BackofficeTeamTab,
  BackofficeTeamTotals,
} from '../../../types/identity-access/backoffice'
import {
  displayPersonName,
  formatFrenchDay,
  formatTeamValidity,
  labelForTeamConfiguration,
  labelForTeamFunction,
  personInitials,
  teamFunctionOptions,
} from '../../../utils/identity-access/backoffice'
import type { BackofficeLoadState } from '../../../composables/identity-access/useBackofficeTasks'

const props = defineProps<{
  search: string
  tab: BackofficeTeamTab
  fonction: string
  page: number
  loadState: BackofficeLoadState
  agents: BackofficeTeamAgent[]
  totaux: BackofficeTeamTotals | null
  pagination: BackofficeAccountPagination | null
  upcoming: BackofficeTeamSubstitute[]
  configurations: BackofficeTeamConfiguration[]
  canPermissions?: boolean
  errorMessage?: string
  retrying?: boolean
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:tab': [value: BackofficeTeamTab]
  'update:fonction': [value: string]
  'update:page': [value: number]
  open: [agent: BackofficeTeamAgent]
  retry: []
}>()

const tabs = [
  { id: 'equipe' as const, label: 'Équipe active' },
  { id: 'permissions' as const, label: 'Permissions' },
  { id: 'suppleances' as const, label: 'Suppléances' },
  { id: 'parametres' as const, label: 'Paramètres du service' },
]

const functionOptions = [...teamFunctionOptions]

const loading = computed(() => props.loadState === 'idle' || props.loadState === 'loading')
const refreshing = computed(() => loading.value && props.agents.length > 0)
const filteredEmpty = computed(() => (
  props.loadState === 'empty'
  && (props.search.trim() !== '' || props.fonction !== '')
))

const emptyTitle = computed(() => (filteredEmpty.value ? 'Aucun résultat' : 'Aucun agent interne'))
const emptyMessage = computed(() => (
  filteredEmpty.value
    ? 'Aucun membre ne correspond à cette recherche. Modifiez les filtres.'
    : 'Aucun compte actif n’a encore une permission interne courante.'
))

const resultLabel = computed(() => {
  const pagination = props.pagination
  if (!pagination) {
    return ''
  }
  const shown = props.agents.length
  const total = pagination.total
  if (total === 0) {
    return '0 agent'
  }
  if (shown === total) {
    return total === 1 ? '1 agent' : `${total} agents`
  }
  const from = (pagination.page - 1) * pagination.per_page + 1
  const to = from + shown - 1
  return `${from}–${to} sur ${total}`
})

function permissionCountLabel(count: number): string {
  return count === 1 ? '1 permission' : `${count} permissions`
}

function substitutePeriod(row: BackofficeTeamSubstitute): string {
  if (!row.debut_a || !row.fin_a) {
    return 'Période indisponible'
  }
  return `Du ${formatFrenchDay(row.debut_a)} au ${formatFrenchDay(row.fin_a)}`
}

function footerConfigurations(): BackofficeTeamConfiguration[] {
  return props.configurations.filter(item => (
    item.code === 'A1_calendrier' || item.code === 'A1_delais' || item.code === 'A1_acces'
  ))
}
</script>

<template>
  <section
    class="rounded-md border border-border bg-surface-raised px-gutter py-gutter shadow-sm"
    aria-labelledby="team-directory-title"
  >
    <h2 id="team-directory-title" class="sr-only">
      Membres de l’équipe
    </h2>
    <div class="flex flex-col gap-gutter">
      <nav aria-label="Vues de l’équipe et des paramètres">
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

      <slot />

      <template v-if="tab === 'equipe'">
        <div class="flex flex-col gap-gutter lg:flex-row lg:items-end lg:justify-between">
          <h3 class="font-serif text-lg font-semibold text-text">
            Membres de l’équipe
          </h3>
          <form
            class="grid w-full gap-stack lg:max-w-xl lg:grid-cols-[minmax(0,1fr)_12rem]"
            @submit.prevent
          >
            <FormField
              label="Recherche"
              hint="Nom, prénom ou contact masqué."
            >
              <template #default="{ id, describedBy }">
                <TextInput
                  :id="id"
                  :model-value="search"
                  icon="search"
                  placeholder="Rechercher un agent…"
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
                  :model-value="fonction"
                  :options="functionOptions"
                  :aria-describedby="describedBy"
                  @update:model-value="emit('update:fonction', $event)"
                />
              </template>
            </FormField>
          </form>
        </div>

        <ErrorState
          v-if="loadState === 'error'"
          title="Impossible de charger l’équipe"
          :message="errorMessage || 'La lecture de l’équipe a échoué. Vous pouvez réessayer.'"
          retry-label="Réessayer"
          :retrying="retrying"
          @retry="emit('retry')"
        />

        <template v-if="loadState !== 'error' || agents.length > 0">
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
            <div v-if="loading && agents.length === 0" class="flex flex-col gap-stack">
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
                v-for="row in agents"
                :key="row.id"
                class="flex flex-col gap-stack py-gutter"
              >
                <div class="flex items-start gap-stack">
                  <span
                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                    aria-hidden="true"
                  >
                    {{ personInitials(row.prenom, row.nom) }}
                  </span>
                  <div class="min-w-0">
                    <p class="font-sans text-sm font-semibold text-text">
                      {{ displayPersonName(row.prenom, row.nom) }}
                    </p>
                    <p class="font-sans text-sm text-text-muted">
                      {{ row.email_masque || 'Contact masqué indisponible' }}
                    </p>
                  </div>
                </div>
                <p class="font-sans text-sm text-text-muted">
                  {{ labelForTeamFunction(row.permissions) }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ permissionCountLabel(row.permissions_actives) }}
                </p>
                <p class="font-sans text-sm text-text-muted">
                  {{ formatTeamValidity(row.expire_a) }}
                </p>
                <button
                  v-if="canPermissions"
                  type="button"
                  class="inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                  @click="emit('open', row)"
                >
                  Voir les accès
                  <UiIcon name="arrow-right" />
                </button>
                <p
                  v-else
                  class="font-sans text-sm text-text-muted"
                >
                  Consultation des périodes réservée à gerer_habilitations_internes.
                </p>
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
                  <th scope="col" class="py-2 pr-gutter font-medium">Agent</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Rôle</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Permissions actives</th>
                  <th scope="col" class="py-2 pr-gutter font-medium">Validité</th>
                  <th scope="col" class="py-2 font-medium">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading && agents.length === 0">
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
                  v-for="row in agents"
                  :key="row.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="py-gutter pr-gutter align-middle">
                    <div class="flex items-center gap-stack">
                      <span
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-semibold text-text"
                        aria-hidden="true"
                      >
                        {{ personInitials(row.prenom, row.nom) }}
                      </span>
                      <div class="min-w-0">
                        <p class="font-medium text-text">
                          {{ displayPersonName(row.prenom, row.nom) }}
                        </p>
                        <p class="text-text-muted">
                          {{ row.email_masque || 'Contact masqué indisponible' }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ labelForTeamFunction(row.permissions) }}
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ permissionCountLabel(row.permissions_actives) }}
                  </td>
                  <td class="py-gutter pr-gutter align-middle text-text-muted">
                    {{ formatTeamValidity(row.expire_a) }}
                  </td>
                  <td class="py-gutter align-middle">
                    <button
                      v-if="canPermissions"
                      type="button"
                      class="inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                      @click="emit('open', row)"
                    >
                      Voir les accès
                      <UiIcon name="arrow-right" />
                    </button>
                    <span
                      v-else
                      class="text-text-muted"
                    >
                      Consultation restreinte
                    </span>
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
          Actualisation de l’équipe…
        </p>

        <div class="grid gap-gutter lg:grid-cols-2">
          <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
            <span
              class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
              aria-hidden="true"
            >
              <UiIcon name="calendar" size="md" />
            </span>
            <div class="min-w-0">
              <h3 class="font-sans text-sm font-semibold text-text">
                Suppléances à venir
              </h3>
              <ul
                v-if="upcoming.length > 0"
                class="mt-stack flex flex-col gap-stack"
              >
                <li
                  v-for="row in upcoming"
                  :key="row.id"
                >
                  <p class="font-sans text-sm text-text">
                    <span class="font-medium">{{ displayPersonName(row.titulaire?.prenom, row.titulaire?.nom) }}</span>
                    →
                    <span class="font-medium">{{ displayPersonName(row.suppleant?.prenom, row.suppleant?.nom) }}</span>
                  </p>
                  <p class="font-sans text-sm text-text-muted">
                    {{ substitutePeriod(row) }}
                  </p>
                </li>
              </ul>
              <p
                v-else
                class="mt-1 font-sans text-sm text-text-muted"
              >
                Aucune période à venir. Le suppléant n’obtient aucune permission.
              </p>
              <p class="mt-1 font-sans text-sm text-text-muted">
                Les permissions du remplaçant sont vérifiées séparément.
              </p>
              <button
                type="button"
                class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                @click="emit('update:tab', 'suppleances')"
              >
                Gérer les suppléances
                <UiIcon name="arrow-right" />
              </button>
            </div>
          </article>
          <article class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
            <span
              class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand"
              aria-hidden="true"
            >
              <UiIcon name="settings" size="md" />
            </span>
            <div class="min-w-0">
              <h3 class="font-sans text-sm font-semibold text-text">
                Configuration du service
              </h3>
              <ul class="mt-stack flex flex-col gap-stack">
                <li
                  v-for="item in footerConfigurations()"
                  :key="item.code"
                  class="flex flex-wrap items-center justify-between gap-stack"
                >
                  <span class="font-sans text-sm text-text">
                    {{ labelForTeamConfiguration(item.code) }}
                    <span class="text-text-muted">
                      Version {{ item.version ?? '—' }}
                    </span>
                  </span>
                  <StatusBadge
                    :label="item.courante ? 'Active' : 'À approuver'"
                    :tone="item.courante ? 'success' : 'warning'"
                    :icon="item.courante ? 'success' : 'warning'"
                  />
                </li>
              </ul>
              <button
                type="button"
                class="mt-stack inline-flex items-center gap-1 font-sans text-sm font-medium text-brand focus-ring"
                @click="emit('update:tab', 'parametres')"
              >
                Ouvrir les paramètres
                <UiIcon name="arrow-right" />
              </button>
            </div>
          </article>
        </div>

        <p class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-stack font-sans text-sm text-text-muted">
          <UiIcon name="lock" class="mt-0.5 shrink-0" />
          <span>Une fonction dans l’équipe ne donne pas automatiquement toutes les permissions.</span>
        </p>
      </template>
    </div>
  </section>
</template>
