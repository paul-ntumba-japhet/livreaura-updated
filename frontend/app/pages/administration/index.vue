<script setup lang="ts">
import {
  canAccessKycBackoffice,
  canAccessTeamBackoffice,
  canAuthorizeAppeal,
  canCloseAccount,
  canHandleSupport,
  canManageInternalPermissions,
  canReactivateAccount,
  canRetryTransmissions,
  canSuspendAccount,
  canAccessOrganizationBackoffice,
} from '../../utils/identity-access/account'
import {
  isTaskOverdue,
  isTaskUnassigned,
  isTaskUrgent,
  sortPriorityTasks,
} from '../../utils/identity-access/backoffice'
import { useCurrentAccount } from '../../composables/identity-access/useCurrentAccount'
import { useBackofficeTasks } from '../../composables/identity-access/useBackofficeTasks'
import { useServiceExploitation } from '../../composables/identity-access/useServiceExploitation'
import type { OverviewAlert } from '../../components/identity-access/backoffice/BackofficeOverviewAlerts.vue'
import type { OverviewShortcut } from '../../components/identity-access/backoffice/BackofficeOverviewShortcuts.vue'
import BackofficeOverviewStats from '../../components/identity-access/backoffice/BackofficeOverviewStats.vue'
import BackofficeOverviewPriority from '../../components/identity-access/backoffice/BackofficeOverviewPriority.vue'
import BackofficeOverviewAlerts from '../../components/identity-access/backoffice/BackofficeOverviewAlerts.vue'
import BackofficeOverviewShortcuts from '../../components/identity-access/backoffice/BackofficeOverviewShortcuts.vue'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Tableau de bord — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const {
  loadState: tasksLoadState,
  errorMessage: tasksErrorMessage,
  sessionEnded: tasksSessionEnded,
  list,
  refresh: refreshTasks,
} = useBackofficeTasks()
const {
  transmissionLoadState,
  sessionEnded: transmissionsSessionEnded,
  transmissions,
  loadTransmissions,
} = useServiceExploitation()

const retrying = ref(false)
const refreshing = ref(false)

const canReview = computed(() => canAccessKycBackoffice(account.value))
const canRetry = computed(() => canRetryTransmissions(account.value))
const canOrganizations = computed(() => canAccessOrganizationBackoffice(account.value))
const canManageAccounts = computed(() => (
  canSuspendAccount(account.value)
  || canReactivateAccount(account.value)
  || canCloseAccount(account.value)
  || canHandleSupport(account.value)
))
const canAppeal = computed(() => canAuthorizeAppeal(account.value) || canHandleSupport(account.value))
const canManagePermissions = computed(() => canManageInternalPermissions(account.value))
const canTeam = computed(() => canAccessTeamBackoffice(account.value))

const kycLoading = computed(() => (
  canReview.value
  && !list.value
  && (tasksLoadState.value === 'idle' || tasksLoadState.value === 'loading')
))
const kycError = computed(() => canReview.value && tasksLoadState.value === 'error')
const kycRestricted = computed(() => !canReview.value)
const taskRows = computed(() => list.value?.taches ?? [])
const unassignedCount = computed(() => taskRows.value.filter(row => isTaskUnassigned(row.tache)).length)
const overdueCount = computed(() => taskRows.value.filter(row => isTaskOverdue(row.tache)).length)
const urgentCount = computed(() => taskRows.value.filter(row => isTaskUrgent(row.tache)).length)
const priorityRows = computed(() => sortPriorityTasks(taskRows.value).slice(0, 8))

const transmissionsLoading = computed(() => (
  canRetry.value
  && transmissions.value.length === 0
  && (transmissionLoadState.value === 'idle' || transmissionLoadState.value === 'loading')
))
const transmissionsError = computed(() => canRetry.value && transmissionLoadState.value === 'error')
const transmissionsRestricted = computed(() => !canRetry.value)

const todayLabel = computed(() => new Intl.DateTimeFormat('fr-FR', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date()))

const sessionEnded = computed(() => tasksSessionEnded.value || transmissionsSessionEnded.value)

const alerts = computed<OverviewAlert[]>(() => {
  const items: OverviewAlert[] = []
  if (canRetry.value && transmissionLoadState.value === 'ready' && transmissions.value.length > 0) {
    const count = transmissions.value.length
    items.push({
      id: 'transmissions',
      tone: 'danger',
      title: count === 1 ? '1 transmission à reprendre' : `${count} transmissions à reprendre`,
      message: 'Des envois sont en échec ou en attente de nouvelle tentative. Une reprise ne rejoue pas une décision.',
      to: '/administration/supervision',
      action: 'Examiner',
    })
  }
  if (canReview.value && tasksLoadState.value === 'ready') {
    const conflicts = taskRows.value.filter(row => row.conflit_interet).length
    if (conflicts > 0) {
      items.push({
        id: 'conflits',
        tone: 'warning',
        title: conflicts === 1 ? '1 conflit d’intérêt' : `${conflicts} conflits d’intérêt`,
        message: 'Ces dossiers sont liés à ce compte : une réaffectation est nécessaire avant décision.',
        to: '/administration/verification',
        action: 'Ouvrir la file',
      })
    }
    if (overdueCount.value > 0) {
      items.push({
        id: 'retards',
        tone: 'warning',
        title: overdueCount.value === 1 ? '1 tâche en retard' : `${overdueCount.value} tâches en retard`,
        message: 'Un dépassement d’échéance n’approuve rien. Prenez en charge ou réaffectez.',
        to: '/administration/verification',
        action: 'Voir les échéances',
      })
    }
    if (unassignedCount.value > 0) {
      items.push({
        id: 'non-affectees',
        tone: 'info',
        title: unassignedCount.value === 1 ? '1 tâche non affectée' : `${unassignedCount.value} tâches non affectées`,
        message: 'Les dossiers soumis restent à affecter. L’affectation ne décide pas du dossier.',
        to: '/administration/verification',
        action: 'Affecter',
      })
    }
  }
  return items
})

const alertsLoading = computed(() => kycLoading.value || transmissionsLoading.value)

const shortcuts = computed<OverviewShortcut[]>(() => {
  const items: OverviewShortcut[] = []
  if (canManageAccounts.value) {
    items.push({
      id: 'comptes',
      label: 'Ouvrir un compte',
      to: '/administration/comptes',
      icon: 'user',
    })
  }
  if (canReview.value) {
    items.push({
      id: 'taches',
      label: 'Ouvrir la file KYC',
      to: '/administration/verification',
      icon: 'file-check',
    })
  }
  if (canAppeal.value) {
    items.push({
      id: 'recours',
      label: 'Ouvrir le support',
      to: '/administration/recours',
      icon: 'message-circle',
    })
  }
  if (canOrganizations.value) {
    items.push({
      id: 'organisations',
      label: 'Organisations',
      to: '/administration/organisations',
      icon: 'building-2',
    })
  }
  if (canReview.value || canManagePermissions.value) {
    items.push({
      id: 'habilitations',
      label: 'Ouvrir les habilitations',
      to: '/administration/habilitations',
      icon: 'shield-check',
    })
  }
  if (canTeam.value) {
    items.push({
      id: 'equipe',
      label: 'Ouvrir l’équipe',
      to: '/administration/equipe',
      icon: 'settings',
    })
  }
  if (canRetry.value) {
    items.push({
      id: 'supervision',
      label: 'Ouvrir la supervision',
      to: '/administration/supervision',
      icon: 'clock',
    })
  }
  return items
})

async function loadOverview() {
  const jobs: Array<Promise<void>> = []
  if (canReview.value) {
    jobs.push(refreshTasks())
  }
  if (canRetry.value) {
    jobs.push(loadTransmissions())
  }
  await Promise.all(jobs)
}

async function retry() {
  retrying.value = true
  try {
    await loadOverview()
  }
  finally {
    retrying.value = false
  }
}

async function refreshOverview() {
  if (refreshing.value) {
    return
  }
  refreshing.value = true
  try {
    await loadOverview()
  }
  finally {
    refreshing.value = false
  }
}

function openDossier(id: string) {
  return navigateTo(`/administration/verification/${id}`)
}

watch(
  [canReview, canRetry],
  ([review, retryTransmissions]) => {
    if (review && (tasksLoadState.value === 'idle' || tasksLoadState.value === 'error')) {
      void refreshTasks()
    }
    if (retryTransmissions && (transmissionLoadState.value === 'idle' || transmissionLoadState.value === 'error')) {
      void loadTransmissions()
    }
  },
  { immediate: true },
)
</script>

<template>
  <div class="flex flex-col gap-gutter px-page py-gutter lg:py-section">
    <Breadcrumbs
      :items="[
        { label: 'Administration', to: '/administration' },
        { label: 'Identité et accès' },
      ]"
    />

    <PageHeader
      title="Tableau de bord."
      description="Les vérifications, demandes et alertes à suivre aujourd’hui."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-stack">
          <p class="font-sans text-sm text-text-muted">
            {{ todayLabel }}
          </p>
          <UiButton
            variant="secondary"
            :loading="refreshing"
            :disabled="refreshing || (!canReview && !canRetry)"
            :disabled-reason="(!canReview && !canRetry) ? 'Aucune file n’est consultable avec les attributions actuelles.' : undefined"
            aria-label="Actualiser la vue d’ensemble"
            @click="refreshOverview"
          >
            <UiIcon name="refresh" />
            Actualiser
          </UiButton>
        </div>
      </template>
    </PageHeader>

    <RestrictionBanner
      v-if="account && !canReview"
      title="Contrôle des dossiers non attribué"
    >
      Le rôle administration ouvre cet espace. Contrôler ou décider un dossier exige une attribution interne distincte.
    </RestrictionBanner>

    <SessionExpiredState v-if="sessionEnded" />

    <template v-else>
      <BackofficeOverviewStats
        :kyc-loading="kycLoading"
        :kyc-restricted="kycRestricted"
        :kyc-error="kycError"
        :kyc-count="taskRows.length"
        :unassigned-count="unassignedCount"
        :overdue-count="overdueCount"
        :urgent-count="urgentCount"
        :transmissions-loading="transmissionsLoading"
        :transmissions-restricted="transmissionsRestricted"
        :transmissions-error="transmissionsError"
        :transmissions-count="transmissions.length"
      />

      <div class="grid gap-gutter lg:grid-cols-3">
        <div class="lg:col-span-2">
          <BackofficeOverviewPriority
            :rows="priorityRows"
            :current-account-id="account?.compte.id"
            :loading="kycLoading"
            :restricted="kycRestricted"
            :error="kycError"
            :error-message="tasksErrorMessage"
            :retrying="retrying"
            :total-count="taskRows.length"
            @open="openDossier"
            @retry="retry"
          />
        </div>
        <div class="flex flex-col gap-gutter">
          <BackofficeOverviewAlerts
            :alerts="alerts"
            :loading="alertsLoading"
          />
          <BackofficeOverviewShortcuts :items="shortcuts" />
        </div>
      </div>
    </template>
  </div>
</template>
