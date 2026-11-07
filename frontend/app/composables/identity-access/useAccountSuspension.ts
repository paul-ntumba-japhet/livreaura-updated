import type { AccountSuspensionResult } from '../../types/identity-access/backoffice'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { suspendAccount } from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'

export type AccountSuspensionMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useAccountSuspension() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<AccountSuspensionMutationState>('idle')
  const errorMessage = ref('')
  const sessionEnded = ref(false)
  const result = ref<AccountSuspensionResult | null>(null)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(accountId: string, motif: string, finPrevue: string | null): string {
    const intention = [accountId, motif, finPrevue || ''].join(':')
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

  async function suspend(
    accountId: string,
    payload: { motif: string, fin_prevue: string | null },
  ): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await suspendAccount(
        accountId,
        payload,
        keyFor(accountId, payload.motif, payload.fin_prevue),
      )
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
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
    sessionEnded,
    result,
    suspend,
  }
}
