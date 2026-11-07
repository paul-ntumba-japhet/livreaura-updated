import type { BackofficeReview } from '../../types/identity-access/backoffice'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import {
  assignBackofficeTask,
  decideBackofficeDossier,
  decideCommercialBackofficeDossier,
  downloadBackofficeEvidence,
  fetchBackofficeDossier,
  recordBackofficeControl,
  restrictBackofficeCapability,
  takeChargeBackofficeDossier,
} from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'

export type BackofficeReviewLoadState = 'idle' | 'loading' | 'ready' | 'error'
export type BackofficeMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useBackofficeReview() {
  const { clear } = useCurrentAccount()
  const loadState = ref<BackofficeReviewLoadState>('idle')
  const mutationState = ref<BackofficeMutationState>('idle')
  const recordingId = ref<string | null>(null)
  const errorMessage = ref('')
  const mutationMessage = ref('')
  const sessionEnded = ref(false)
  const review = ref<BackofficeReview | null>(null)
  const decisionKey = ref('')
  const lastDecisionDossier = ref('')
  const restrictionKey = ref('')
  const lastRestrictionCapability = ref('')

  function keyFor(dossierId: string): string {
    if (!decisionKey.value || lastDecisionDossier.value !== dossierId) {
      decisionKey.value = crypto.randomUUID()
      lastDecisionDossier.value = dossierId
    }
    return decisionKey.value
  }

  function restrictionKeyFor(capabilityId: string): string {
    if (!restrictionKey.value || lastRestrictionCapability.value !== capabilityId) {
      restrictionKey.value = crypto.randomUUID()
      lastRestrictionCapability.value = capabilityId
    }
    return restrictionKey.value
  }

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        errorMessage.value = messageForAccountError(probe)
        return
      }
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
  }

  async function load(dossierId: string): Promise<void> {
    loadState.value = review.value?.dossier.id === dossierId ? 'ready' : 'loading'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      review.value = await fetchBackofficeDossier(dossierId)
      loadState.value = 'ready'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        loadState.value = 'error'
        await resolveUnauthorized()
        return
      }
      errorMessage.value = messageForBackofficeError(normalized)
      loadState.value = 'error'
    }
  }

  async function runMutation(action: () => Promise<BackofficeReview>): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }
    mutationState.value = 'submitting'
    mutationMessage.value = ''
    sessionEnded.value = false

    try {
      review.value = await action()
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        mutationState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      mutationMessage.value = messageForBackofficeError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        return
      }
      mutationState.value = 'failed'
    }
    finally {
      recordingId.value = null
    }
  }

  async function assign(taskId: string): Promise<void> {
    await runMutation(() => assignBackofficeTask(taskId))
  }

  async function takeCharge(dossierId: string): Promise<void> {
    await runMutation(() => takeChargeBackofficeDossier(dossierId))
  }

  async function recordControl(
    dossierId: string,
    payload: {
      controle_id: string
      resultat: 'passed' | 'failed' | 'not_applicable'
      observation?: string
      justification_non_applicable?: string
    },
  ): Promise<void> {
    recordingId.value = payload.controle_id
    await runMutation(() => recordBackofficeControl(dossierId, payload))
  }

  async function decide(
    dossierId: string,
    payload: { resultat: 'approved' | 'needs_fix' | 'rejected', motif: string },
  ): Promise<void> {
    const commercial = review.value?.niveau_decision === 'partenaire_commercial'
    await runMutation(() => commercial
      ? decideCommercialBackofficeDossier(dossierId, payload, keyFor(dossierId))
      : decideBackofficeDossier(dossierId, payload, keyFor(dossierId)))
  }

  async function restrict(
    capabilityId: string,
    payload: { etat: 'a_renouveler' | 'restreint' | 'revoked', motif: string },
  ): Promise<void> {
    await runMutation(() => restrictBackofficeCapability(capabilityId, payload, restrictionKeyFor(capabilityId)))
  }

  async function download(dossierId: string, pieceId: string, filename: string): Promise<void> {
    mutationMessage.value = ''
    try {
      await downloadBackofficeEvidence(dossierId, pieceId, filename)
    }
    catch (error) {
      mutationMessage.value = messageForBackofficeError(error as IdentityRequestError)
    }
  }

  return {
    loadState,
    mutationState,
    recordingId,
    errorMessage,
    mutationMessage,
    sessionEnded,
    review,
    load,
    assign,
    takeCharge,
    recordControl,
    decide,
    restrict,
    download,
  }
}
