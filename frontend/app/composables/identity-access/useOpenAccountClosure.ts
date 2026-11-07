import type { AppealDepositResult } from '../../types/identity-access/support'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForRecoveryRequestError } from '../../utils/identity-access/errors'
import { openAccountClosure } from '../../services/identity-access/support'

export type ClosureDepositMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useOpenAccountClosure() {
  const mutationState = ref<ClosureDepositMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<AppealDepositResult | null>(null)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(email: string, message: string): string {
    const intention = `${email}:${message}`
    if (!idempotencyKey.value || lastIntention.value !== intention) {
      idempotencyKey.value = crypto.randomUUID()
      lastIntention.value = intention
    }
    return idempotencyKey.value
  }

  async function submit(payload: { email: string, message: string }): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''

    try {
      result.value = await openAccountClosure(payload, keyFor(payload.email, payload.message))
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}
      errorMessage.value = messageForRecoveryRequestError(normalized)
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
