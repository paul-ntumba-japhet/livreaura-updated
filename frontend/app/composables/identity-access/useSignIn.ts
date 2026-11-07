import type { LoginPayload, LoginResult } from '../../types/identity-access/session'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForLoginError } from '../../utils/identity-access/errors'
import { signIn } from '../../services/identity-access/session'

export type SignInMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

function asLoginError(error: unknown): IdentityRequestError {
  if (error && typeof error === 'object' && 'kind' in error) {
    return error as IdentityRequestError
  }
  return { kind: 'unknown', status: null }
}

export function useSignIn() {
  const mutationState = ref<SignInMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<LoginResult | null>(null)

  async function submit(payload: LoginPayload): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    result.value = null

    try {
      result.value = await signIn(payload)
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = asLoginError(error)
      fieldErrors.value = normalized.fields ?? {}
      errorMessage.value = messageForLoginError(normalized)

      if (normalized.kind === 'network') {
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
