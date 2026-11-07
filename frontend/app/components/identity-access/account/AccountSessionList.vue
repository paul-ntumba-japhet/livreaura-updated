<script setup lang="ts">
import type { OwnAuthenticationSession } from '../../../types/identity-access/account'
import { labelForSessionStatus, normalizeSessionStatus, toneForSessionStatus } from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    sessions: OwnAuthenticationSession[]
    loadState: 'idle' | 'loading' | 'ready' | 'empty' | 'error'
    errorMessage?: string
    retrying?: boolean
    revokingCurrent?: boolean
    revokingAll?: boolean
    confirmingAll?: boolean
  }>(),
  {
    errorMessage: '',
    retrying: false,
    revokingCurrent: false,
    revokingAll: false,
    confirmingAll: false,
  },
)

const emit = defineEmits<{
  retry: []
  'revoke-current': []
  'revoke-all': []
  'confirm-all': []
  'cancel-all': []
}>()

const current = computed(() => props.sessions.find(session => session.courante) ?? null)
const others = computed(() => props.sessions.filter(session => !session.courante))
const canRevokeCurrent = computed(() => current.value !== null && normalizeSessionStatus(current.value.statut) === 'active')
const busy = computed(() => props.revokingCurrent || props.revokingAll)
</script>

<template>
  <SectionCard
    id="sessions"
    icon="layers"
    title="Session courante"
  >
    <template #action>
      <StatusBadge
        v-if="current"
        :label="labelForSessionStatus(normalizeSessionStatus(current.statut))"
        :tone="toneForSessionStatus(normalizeSessionStatus(current.statut))"
      />
    </template>
    <ErrorState
      v-if="loadState === 'error'"
      title="Sessions indisponibles"
      :message="errorMessage || 'La liste des sessions n’a pas pu être lue. Réessayez.'"
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="emit('retry')"
    />

    <div
      v-else-if="loadState === 'loading' || loadState === 'idle'"
      class="font-sans text-sm text-text-muted"
    >
      Chargement des sessions…
    </div>

    <EmptyState
      v-else-if="loadState === 'empty'"
      title="Aucune session visible"
      message="Aucune session n’est associée à ce compte pour le moment."
    />

    <template v-else>
      <SessionCard
        v-if="current"
        embedded
        hide-status
        :session="current"
      />
      <p
        v-else
        class="font-sans text-sm text-text-muted"
      >
        La session courante n’est pas identifiable pour le moment.
      </p>

      <div
        v-if="current"
        class="mt-gutter flex flex-col gap-stack sm:flex-row sm:flex-wrap"
      >
        <UiButton
          variant="outline"
          pill
          :loading="revokingCurrent"
          :disabled="busy || !canRevokeCurrent"
          :disabled-reason="!canRevokeCurrent ? 'Cette session n’est plus active.' : undefined"
          @click="emit('revoke-current')"
        >
          <UiIcon name="log-out" />
          Se déconnecter de cette session
        </UiButton>
        <UiButton
          v-if="sessions.length > 0"
          variant="destructive"
          pill
          :loading="revokingAll"
          :disabled="busy"
          @click="emit('confirm-all')"
        >
          <UiIcon name="trash" />
          Révoquer toutes les sessions
        </UiButton>
      </div>

      <div
        v-if="others.length > 0"
        class="mt-section flex flex-col gap-gutter"
      >
        <h3 class="font-sans text-sm font-medium text-text">
          Autres sessions
        </h3>
        <SessionCard
          v-for="session in others"
          :key="session.id"
          :session="session"
        />
      </div>
    </template>

    <ConfirmDialog
      :open="confirmingAll"
      title="Révoquer toutes les sessions ?"
      confirm-label="Révoquer toutes les sessions"
      cancel-label="Conserver les sessions"
      destructive
      :submitting="revokingAll"
      @confirm="emit('revoke-all')"
      @cancel="emit('cancel-all')"
    >
      Cette action ferme toutes les sessions, y compris celle-ci. Une nouvelle connexion sera nécessaire. Les licences acquises ne sont pas révoquées.
    </ConfirmDialog>
  </SectionCard>
</template>
