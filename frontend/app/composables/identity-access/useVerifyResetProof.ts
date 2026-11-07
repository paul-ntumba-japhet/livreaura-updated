import type { RecoveryProofPreview } from '../../types/identity-access/recovery'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForRecoveryProofError } from '../../utils/identity-access/errors'
import { verifyResetProof } from '../../services/identity-access/recovery'

export type RecoveryProofLoadState = 'idle' | 'loading' | 'ready' | 'missing' | 'error'

export function useVerifyResetProof() {
  const loadState = ref<RecoveryProofLoadState>('idle')
  const errorMessage = ref('')
  const retryable = ref(false)
  const preview = ref<RecoveryProofPreview | null>(null)

  async function submit(challengeId: string, proof: string): Promise<void> {
    if (loadState.value === 'loading') {
      return
    }

    loadState.value = 'loading'
    errorMessage.value = ''
    retryable.value = false
    preview.value = null

    try {
      preview.value = await verifyResetProof(challengeId, proof)
      loadState.value = 'ready'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      errorMessage.value = messageForRecoveryProofError(normalized)
      retryable.value = normalized.kind === 'network'
        || normalized.kind === 'unknown'
        || normalized.kind === 'unavailable'
        || normalized.code === 'csrf'
      loadState.value = 'error'
    }
  }

  function markMissing() {
    preview.value = null
    retryable.value = false
    loadState.value = 'missing'
  }

  return {
    loadState,
    errorMessage,
    retryable,
    preview,
    submit,
    markMissing,
  }
}
