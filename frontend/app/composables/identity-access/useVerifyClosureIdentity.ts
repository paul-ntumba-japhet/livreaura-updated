import type { ClosureIdentityResult } from '../../types/identity-access/backoffice'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { verifyClosureIdentity } from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'

export type ClosureIdentityMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useVerifyClosureIdentity() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<ClosureIdentityMutationState>('idle')
  const errorMessage = ref('')
  const sessionEnded = ref(false)
  const result = ref<ClosureIdentityResult | null>(null)
  const fieldErrors = ref<Record<string, string[]>>({})
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(demandKey: string, compteId: string, motif: string, methode: string): string {
    const intention = [demandKey, compteId, motif, methode].join(':')
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

  async function verify(
    demandKey: string,
    payload: { compte_id: string, motif: string, methode_verification: string },
  ): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    fieldErrors.value = {}
    sessionEnded.value = false

    try {
      result.value = await verifyClosureIdentity(
        demandKey,
        payload,
        keyFor(demandKey, payload.compte_id, payload.motif, payload.methode_verification),
      )
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
    verify,
  }
}
