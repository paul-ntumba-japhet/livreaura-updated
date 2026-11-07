import type { RepresentativeTransferResult } from '../../types/identity-access/organization'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { transferPrimaryRepresentative } from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'

export type RepresentativeTransferMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useRepresentativeTransfer() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<RepresentativeTransferMutationState>('idle')
  const errorMessage = ref('')
  const sessionEnded = ref(false)
  const result = ref<RepresentativeTransferResult | null>(null)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(organizationId: string, nouveauCompteId: string, motif: string, depart: boolean): string {
    const intention = [organizationId, nouveauCompteId, motif, depart ? 'depart' : 'reste'].join(':')
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

  async function transfer(
    organizationId: string,
    payload: { nouveau_compte_id: string, motif: string, depart: boolean, demande_id?: string | null },
  ): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await transferPrimaryRepresentative(
        organizationId,
        payload,
        keyFor(organizationId, payload.nouveau_compte_id, payload.motif, payload.depart),
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
    transfer,
  }
}
