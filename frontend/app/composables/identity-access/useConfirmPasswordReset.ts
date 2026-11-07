import type { RecoveryConfirmResult } from '../../types/identity-access/recovery'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForRecoveryConfirmError } from '../../utils/identity-access/errors'
import { confirmPasswordReset } from '../../services/identity-access/recovery'

export type RecoveryConfirmMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useConfirmPasswordReset() {
  const mutationState = ref<RecoveryConfirmMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<RecoveryConfirmResult | null>(null)

  async function submit(challengeId: string, proof: string, password: string): Promise<void> {
    if (mutationState.value === 'submitting' || mutationState.value === 'succeeded') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''

    try {
      result.value = await confirmPasswordReset(challengeId, proof, password)
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}
      errorMessage.value = messageForRecoveryConfirmError(normalized)

      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        return
      }

      mutationState.value = 'failed'
    }
  }

  return {
    mutationState,
    fieldErrors,
    errorMessage,
    result,
    submit,
  }
}
