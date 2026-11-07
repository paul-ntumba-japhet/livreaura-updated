import type { AppealDocumentGrantResult } from '../../types/identity-access/support'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { authorizeAppealDocument } from '../../services/identity-access/support'
import { useCurrentAccount } from './useCurrentAccount'

export type AppealDocumentGrantMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export type AppealDocumentGrantPayload = {
  compte_id: string
  facture_vente_id?: string | null
  facture_commission_id?: string | null
  avoir_id?: string | null
  recu_paiement_id?: string | null
  debut: string
  fin: string
  motif: string
}

export function useAuthorizeAppealDocument() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<AppealDocumentGrantMutationState>('idle')
  const errorMessage = ref('')
  const sessionEnded = ref(false)
  const result = ref<AppealDocumentGrantResult | null>(null)
  const fieldErrors = ref<Record<string, string[]>>({})
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(demandKey: string, payload: AppealDocumentGrantPayload): string {
    const intention = JSON.stringify([demandKey, payload])
    if (!idempotencyKey.value || lastIntention.value !== intention) {
      idempotencyKey.value = crypto.randomUUID()
      lastIntention.value = intention
    }
    return idempotencyKey.value
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

  async function authorize(demandKey: string, payload: AppealDocumentGrantPayload): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    fieldErrors.value = {}
    sessionEnded.value = false

    try {
      result.value = await authorizeAppealDocument(demandKey, payload, keyFor(demandKey, payload))
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        mutationState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      errorMessage.value = messageForBackofficeError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        return
      }
      mutationState.value = 'failed'
    }
  }

  return {
    mutationState,
    errorMessage,
    fieldErrors,
    sessionEnded,
    result,
    authorize,
  }
}
