import type { ActivationResult } from '../../types/identity-access/activation'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForActivationError } from '../../utils/identity-access/errors'
import { activateAccount } from '../../services/identity-access/activation'

export type ActivationMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useActivateAccount() {
  const mutationState = ref<ActivationMutationState>('idle')
  const errorMessage = ref('')
  const errorCode = ref('')
  const result = ref<ActivationResult | null>(null)

  async function submit(challengeId: string, proof: string): Promise<void> {
    if (mutationState.value === 'submitting' || mutationState.value === 'succeeded') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    errorCode.value = ''

    try {
      result.value = await activateAccount(challengeId, proof)
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      errorCode.value = normalized.code ?? ''
      errorMessage.value = messageForActivationError(normalized)

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
    errorCode,
    result,
    submit,
  }
}
