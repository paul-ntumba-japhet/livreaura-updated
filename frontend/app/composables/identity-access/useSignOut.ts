import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForLogoutError } from '../../utils/identity-access/errors'
import { signOutCurrentSession } from '../../services/identity-access/session'
import { useCurrentAccount } from './useCurrentAccount'

export type SignOutMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useSignOut() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<SignOutMutationState>('idle')
  const errorMessage = ref('')

  async function submit(): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''

    try {
      await signOutCurrentSession()
      clear()
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      errorMessage.value = messageForLogoutError(normalized)

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
    submit,
  }
}
