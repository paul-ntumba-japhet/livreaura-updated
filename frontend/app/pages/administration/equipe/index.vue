<script setup lang="ts">
import {
  canAccessTeamBackoffice,
  canAssignServiceSubstitutes,
  canConfigureService,
  canManageInternalPermissions,
  labelForAccountStatus,
} from '../../../utils/identity-access/account'
import { normalizeAccountStatus } from '../../../types/identity-access/account'
import {
  labelForInternalPermission,
  labelForSubstituteState,
  toneForSubstituteState,
} from '../../../utils/identity-access/backoffice'
import { useCurrentAccount } from '../../../composables/identity-access/useCurrentAccount'
import { useBackofficeTeam } from '../../../composables/identity-access/useBackofficeTeam'
import { useInternalPermissions } from '../../../composables/identity-access/useInternalPermissions'
import { useServiceExploitation } from '../../../composables/identity-access/useServiceExploitation'
import BackofficeTeamStats from '../../../components/identity-access/backoffice/BackofficeTeamStats.vue'
import BackofficeTeamDirectory from '../../../components/identity-access/backoffice/BackofficeTeamDirectory.vue'
import type { BackofficeTeamAgent, BackofficeTeamTab } from '../../../types/identity-access/backoffice'
import type { CalendarDraft } from '../../../components/identity-access/backoffice/ServiceCalendarEditor.vue'

definePageMeta({
  layout: 'backoffice',
  ssr: false,
})

useHead({
  title: 'Équipe et paramètres — Administration — Livreaura',
  meta: [
    { name: 'robots', content: 'noindex, nofollow, noarchive' },
  ],
})

const { account } = useCurrentAccount()
const { loadState, errorMessage, sessionEnded, list, refresh } = useBackofficeTeam()
const permissions = useInternalPermissions()
const exploitation = useServiceExploitation()

const authorized = computed(() => canAccessTeamBackoffice(account.value))
const canPermissions = computed(() => canManageInternalPermissions(account.value))
const canAssign = computed(() => canAssignServiceSubstitutes(account.value))
const canApprove = computed(() => canConfigureService(account.value))

const search = ref('')
const debouncedSearch = ref('')
const tab = ref<BackofficeTeamTab>('equipe')
const fonction = ref('')
const page = ref(1)
const retrying = ref(false)
const compteId = ref('')
const permission = ref('')
const motif = ref('')
const expireAt = ref('')
const revokeMotif = ref('')
const pendingRevokeId = ref<string | null>(null)
const confirmGrantOpen = ref(false)
const confirmRevokeOpen = ref(false)
const lookupRetrying = ref(false)
const titulaireId = ref('')
const suppleantId = ref('')
const substituteFonction = ref('kyc')
const debutAt = ref('')
const finAt = ref('')
const substituteMotif = ref('')
const code = ref('A1_delais')
const effectAt = ref('')
const access = ref({
  inactivite_secondes_normal: '3600',
  absolue_secondes_normal: '43200',
  inactivite_secondes_limited: '900',
  absolue_secondes_limited: '3600',
})
const delays = ref({
  activation_heures: '24',
  reset_minutes: '15',
  prise_heures_ouvre: '8',
  reponse_heures_ouvre: '40',
})
const calendar = ref<CalendarDraft>({
  timezone: 'Africa/Kinshasa',
  debut: '09:00',
  fin: '17:00',
  jours: [1, 2, 3, 4, 5],
  jours_non_travailles: '',
})
const rules = ref(['securite', 'conformite', 'decision_humaine'])
const confirmApproveOpen = ref(false)

const statsLoading = computed(() => (
  (loadState.value === 'idle' || loadState.value === 'loading')
  && !list.value
))
const statsUnavailable = computed(() => loadState.value === 'error' && !list.value)
const ended = computed(() => sessionEnded.value || permissions.sessionEnded.value || exploitation.sessionEnded.value)
const isSelf = computed(() => Boolean(account.value?.compte?.id && account.value.compte.id === compteId.value.trim().toUpperCase()))
const grantSubmitting = computed(() => permissions.grantState.value === 'submitting')
const revokeSubmitting = computed(() => permissions.revokeState.value === 'submitting')
const canLookup = computed(() => compteId.value.trim().length === 26)
const lookedUp = computed(() => Boolean(permissions.catalog.value?.compte?.id))
const accountStatusLabel = computed(() => {
  const status = permissions.catalog.value?.compte?.statut
  if (!status) {
    return 'Statut indisponible'
  }
  return labelForAccountStatus(normalizeAccountStatus(status))
})

watch(search, (value) => {
  window.setTimeout(() => {
    if (search.value === value) {
      debouncedSearch.value = value
    }
  }, 300)
})

watch([debouncedSearch, fonction], () => {
  if (page.value !== 1) {
    page.value = 1
  }
})

const requestKey = computed(() => JSON.stringify({
  q: debouncedSearch.value.trim(),
  fonction: fonction.value,
  page: page.value,
  authorized: authorized.value,
}))

watch(requestKey, () => {
  if (!authorized.value) {
    return
  }
  void refresh({
    q: debouncedSearch.value,
    fonction: fonction.value || undefined,
    page: page.value,
    per_page: 4,
  })
}, { immediate: true })

watch(tab, (value) => {
  if (!authorized.value) {
    return
  }
  if (value === 'permissions' && canPermissions.value) {
    void permissions.loadCatalog()
  }
  if (value === 'suppleances' && canAssign.value) {
    void exploitation.loadSubstitutes()
  }
  if (value === 'parametres' && canApprove.value) {
    void exploitation.loadConfigurations()
  }
})

function isoFromLocal(value: string): string | null {
  const trimmed = value.trim()
  if (!trimmed) {
    return null
  }
  const date = new Date(trimmed)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  return date.toISOString()
}

function positiveInt(value: string): number {
  return Number.parseInt(value, 10)
}

function valeurs(): Record<string, unknown> {
  if (code.value === 'A1_acces') {
    return {
      inactivite_secondes_normal: positiveInt(access.value.inactivite_secondes_normal),
      absolue_secondes_normal: positiveInt(access.value.absolue_secondes_normal),
      inactivite_secondes_limited: positiveInt(access.value.inactivite_secondes_limited),
      absolue_secondes_limited: positiveInt(access.value.absolue_secondes_limited),
    }
  }
  if (code.value === 'A1_delais') {
    return {
      activation_heures: positiveInt(delays.value.activation_heures),
      reset_minutes: positiveInt(delays.value.reset_minutes),
      prise_heures_ouvre: positiveInt(delays.value.prise_heures_ouvre),
      reponse_heures_ouvre: positiveInt(delays.value.reponse_heures_ouvre),
    }
  }
  if (code.value === 'A1_calendrier') {
    return {
      timezone: calendar.value.timezone.trim(),
      plages: [{
        jours: calendar.value.jours,
        debut: calendar.value.debut,
        fin: calendar.value.fin,
      }],
      jours_non_travailles: calendar.value.jours_non_travailles
        .split(',')
        .map(item => item.trim())
        .filter(Boolean),
    }
  }
  return {
    regles_autorisees: rules.value,
  }
}

async function retry() {
  retrying.value = true
  try {
    await refresh({
      q: debouncedSearch.value,
      fonction: fonction.value || undefined,
      page: page.value,
      per_page: 4,
    })
  }
  finally {
    retrying.value = false
  }
}

function openAccess(agent: BackofficeTeamAgent) {
  if (!canPermissions.value) {
    return
  }
  compteId.value = agent.id
  tab.value = 'permissions'
  void consult()
}

async function consult() {
  if (!canPermissions.value || !canLookup.value) {
    return
  }
  lookupRetrying.value = true
  try {
    await permissions.lookup(compteId.value)
  }
  finally {
    lookupRetrying.value = false
  }
}

async function confirmGrant() {
  if (grantSubmitting.value) {
    return
  }
  await permissions.grant({
    compte_id: compteId.value.trim().toUpperCase(),
    permission: permission.value.trim(),
    motif: motif.value.trim(),
    expire_a: isoFromLocal(expireAt.value),
  })
  if (permissions.grantState.value !== 'submitting') {
    confirmGrantOpen.value = false
    void refresh({
      q: debouncedSearch.value,
      fonction: fonction.value || undefined,
      page: page.value,
      per_page: 4,
    })
  }
}

async function confirmRevoke() {
  if (revokeSubmitting.value || !pendingRevokeId.value || revokeMotif.value.trim().length < 8) {
    return
  }
  const id = pendingRevokeId.value
  await permissions.revoke(id, revokeMotif.value.trim())
  if (permissions.revokeState.value !== 'submitting') {
    confirmRevokeOpen.value = false
    pendingRevokeId.value = null
    void refresh({
      q: debouncedSearch.value,
      fonction: fonction.value || undefined,
      page: page.value,
      per_page: 4,
    })
  }
}

async function submitSubstitute() {
  const debut = isoFromLocal(debutAt.value)
  const fin = isoFromLocal(finAt.value)
  if (!debut || !fin) {
    return
  }
  await exploitation.assign({
    titulaire_id: titulaireId.value.trim().toUpperCase(),
    suppleant_id: suppleantId.value.trim().toUpperCase(),
    fonction: substituteFonction.value,
    debut_a: debut,
    fin_a: fin,
    motif: substituteMotif.value.trim(),
  })
  if (exploitation.assignState.value === 'succeeded') {
    void refresh({
      q: debouncedSearch.value,
      fonction: fonction.value || undefined,
      page: page.value,
      per_page: 4,
    })
  }
}

async function confirmApprove() {
  const effet = isoFromLocal(effectAt.value)
  if (!effet || exploitation.approveState.value === 'submitting') {
    return
  }
  await exploitation.approve({
    code: code.value,
    valeurs: valeurs(),
    effet_a: effet,
  })
  if (exploitation.approveState.value !== 'submitting') {
    confirmApproveOpen.value = false
    void refresh({
      q: debouncedSearch.value,
      fonction: fonction.value || undefined,
      page: page.value,
      per_page: 4,
    })
  }
}
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
      title="Équipe et paramètres."
      description="Organisez les responsabilités et configurez le fonctionnement du service."
    />

    <ForbiddenState
      v-if="account && !authorized"
      title="Attribution insuffisante"
      message="La consultation de l’équipe exige une attribution interne (habilitations, affectation ou configuration), pas seulement le rôle administration."
    >
      <template #action>
        <UiLink to="/administration">Retour au tableau de bord</UiLink>
      </template>
    </ForbiddenState>

    <SessionExpiredState v-else-if="ended" />

    <template v-else-if="authorized">
      <BackofficeTeamStats
        :loading="statsLoading"
        :unavailable="statsUnavailable"
        :agents-actifs="list?.totaux.agents_actifs"
        :attributions-actives="list?.totaux.attributions_actives"
        :suppleances-planifiees="list?.totaux.suppleances_planifiees"
        :configurations-a-approuver="list?.totaux.configurations_a_approuver"
      />
      <BackofficeTeamDirectory
        v-model:search="search"
        v-model:tab="tab"
        v-model:fonction="fonction"
        v-model:page="page"
        :load-state="loadState"
        :agents="list?.agents ?? []"
        :totaux="list?.totaux ?? null"
        :pagination="list?.pagination ?? null"
        :upcoming="list?.suppleances_a_venir ?? []"
        :configurations="list?.configurations ?? []"
        :can-permissions="canPermissions"
        :error-message="errorMessage"
        :retrying="retrying"
        @open="openAccess"
        @retry="retry"
      >
        <template v-if="tab === 'permissions'">
          <RestrictionBanner
            v-if="!canPermissions"
            title="Permissions non attribuées"
          >
            Attribuer ou révoquer une période exige gerer_habilitations_internes. Une fonction d’équipe n’accorde pas toutes les permissions.
          </RestrictionBanner>
          <div v-else class="flex flex-col gap-gutter">
            <p class="font-sans text-sm text-text-muted">
              Consultez un compte, puis ouvrez une nouvelle période. Une attribution n’en remplace pas une autre.
            </p>
            <form
              class="grid gap-stack md:grid-cols-[minmax(0,1fr)_auto] md:items-end"
              @submit.prevent="consult"
            >
              <FormField
                label="Compte"
                required
                hint="Référence du compte à consulter."
              >
                <template #default="{ id, describedBy }">
                  <TextInput
                    :id="id"
                    v-model="compteId"
                    :aria-describedby="describedBy"
                    autocomplete="off"
                  />
                </template>
              </FormField>
              <UiButton
                type="submit"
                :disabled="!canLookup || lookupRetrying"
                :disabled-reason="canLookup ? undefined : 'Indiquez la référence du compte.'"
                :loading="lookupRetrying"
              >
                Consulter les périodes
              </UiButton>
            </form>
            <InlineAlert
              v-if="permissions.lookupState.value === 'error' && permissions.lookupError.value"
              tone="error"
              title="Consultation refusée"
            >
              {{ permissions.lookupError.value }}
            </InlineAlert>
            <section
              v-else-if="lookedUp"
              class="rounded-md border border-border px-gutter py-stack"
            >
              <h3 class="font-sans text-sm font-semibold text-text">
                Compte consulté
              </h3>
              <DescriptionList
                class="mt-stack"
                :items="[{ term: 'Statut', value: accountStatusLabel }]"
              />
            </section>
            <InternalPermissionHistory
              v-if="permissions.lookupState.value === 'ready' && permissions.catalog.value"
              :rows="permissions.catalog.value.attributions"
              :can-revoke="canPermissions"
              :is-self="Boolean(account?.compte?.id && account.compte.id === permissions.catalog.value.compte?.id)"
              :submitting-id="revokeSubmitting ? pendingRevokeId : null"
              @revoke="pendingRevokeId = $event; revokeMotif = ''; confirmRevokeOpen = true"
            />
            <InternalPermissionGrantForm
              v-model:compte-id="compteId"
              v-model:permission="permission"
              v-model:motif="motif"
              v-model:expire-at="expireAt"
              :options="permissions.catalog.value?.permissions ?? []"
              :can-grant="canPermissions"
              :is-self="isSelf"
              :submitting="grantSubmitting"
              :field-errors="permissions.grantFieldErrors.value"
              @submit="confirmGrantOpen = true"
            />
          </div>
        </template>

        <template v-else-if="tab === 'suppleances'">
          <RestrictionBanner
            v-if="!canAssign"
            title="Suppléance non attribuée"
          >
            Enregistrer une suppléance exige affecter_taches. Elle n’accorde aucun pouvoir de décision.
          </RestrictionBanner>
          <div v-else class="flex flex-col gap-gutter">
            <InlineAlert
              v-if="exploitation.assignState.value === 'succeeded'"
              tone="success"
              title="Suppléance enregistrée"
            >
              Aucune permission n’a été accordée au suppléant. Les droits propres restent exigés pour agir.
            </InlineAlert>
            <ul
              v-if="exploitation.substitutes.value.length > 0"
              class="grid gap-stack"
            >
              <li
                v-for="row in exploitation.substitutes.value"
                :key="row.id"
                class="rounded-md border border-border px-gutter py-stack"
              >
                <div class="flex flex-wrap items-center gap-gutter">
                  <StatusBadge
                    :label="labelForSubstituteState(row.etat)"
                    :tone="toneForSubstituteState(row.etat)"
                  />
                  <p class="font-sans text-sm text-text">
                    Fonction {{ row.fonction }}
                  </p>
                </div>
                <p class="mt-2 font-sans text-sm text-text-muted">
                  Titulaire et suppléant distincts. Permission implicite : non.
                </p>
              </li>
            </ul>
            <EmptyState
              v-else-if="exploitation.substituteLoadState.value === 'empty'"
              title="Aucune suppléance"
              message="Aucune période n’est enregistrée pour le moment."
            />
            <SubstituteAssignmentForm
              v-model:titulaire-id="titulaireId"
              v-model:suppleant-id="suppleantId"
              v-model:fonction="substituteFonction"
              v-model:debut-at="debutAt"
              v-model:fin-at="finAt"
              v-model:motif="substituteMotif"
              can-assign
              :submitting="exploitation.assignState.value === 'submitting'"
              :field-errors="exploitation.assignFieldErrors.value"
              @submit="submitSubstitute"
            />
          </div>
        </template>

        <template v-else-if="tab === 'parametres'">
          <RestrictionBanner
            v-if="!canApprove"
            title="Configuration non attribuée"
          >
            Relire ou approuver une version exige configurer_service.
          </RestrictionBanner>
          <div v-else class="flex flex-col gap-gutter">
            <InlineAlert
              v-if="exploitation.approveState.value === 'succeeded' && exploitation.approveResult.value"
              tone="success"
              title="Version approuvée"
            >
              Version {{ exploitation.approveResult.value.configuration.version }} enregistrée.
              {{ exploitation.approveResult.value.courante ? 'Elle est déjà la version en vigueur.' : 'Elle prendra effet à l’échéance indiquée.' }}
            </InlineAlert>
            <ServiceConfigurationEditor
              v-model:code="code"
              v-model:effect-at="effectAt"
              v-model:access="access"
              v-model:delays="delays"
              v-model:calendar="calendar"
              v-model:rules="rules"
              :groups="exploitation.catalog.value?.configurations ?? []"
              can-approve
              :submitting="exploitation.approveState.value === 'submitting'"
              :field-errors="exploitation.approveFieldErrors.value"
              @submit="confirmApproveOpen = true"
            />
          </div>
        </template>
      </BackofficeTeamDirectory>
    </template>

    <ConfirmDialog
      :open="confirmGrantOpen"
      title="Confirmer l’attribution"
      confirm-label="Attribuer cette permission"
      :submitting="grantSubmitting"
      @confirm="confirmGrant"
      @cancel="confirmGrantOpen = false"
    >
      <p>
        Une nouvelle période sera ouverte pour ce compte. Elle n’annule pas une période précédente. Cette action attend la confirmation du serveur.
      </p>
    </ConfirmDialog>
    <ConfirmDialog
      :open="confirmRevokeOpen"
      title="Confirmer la révocation"
      confirm-label="Révoquer cette période"
      :submitting="revokeSubmitting"
      :disabled="revokeMotif.trim().length < 8"
      disabled-reason="Indiquez un motif d’au moins 8 caractères."
      destructive
      @confirm="confirmRevoke"
      @cancel="confirmRevokeOpen = false; pendingRevokeId = null"
    >
      <p>
        Le droit cessera immédiatement. L’historique de la période est conservé. Cette action attend la confirmation du serveur.
      </p>
      <FormField
        class="mt-stack"
        label="Motif"
        required
        :error="permissions.revokeFieldErrors.value.motif?.[0]"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextArea
            :id="id"
            v-model="revokeMotif"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="revokeSubmitting"
          />
        </template>
      </FormField>
    </ConfirmDialog>
    <ConfirmDialog
      :open="confirmApproveOpen"
      title="Approuver cette version ?"
      confirm-label="Approuver cette version"
      :submitting="exploitation.approveState.value === 'submitting'"
      @confirm="confirmApprove"
      @cancel="confirmApproveOpen = false"
    >
      <p class="font-sans text-sm text-text">
        La version devient immuable. Elle ne s’applique qu’à partir de la date d’effet.
      </p>
    </ConfirmDialog>
  </div>
</template>
