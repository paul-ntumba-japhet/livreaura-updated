import type { ChangePasswordRequest, ChangePasswordResponse } from '../../types/identity-access/account'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForChangePasswordError } from '../../utils/identity-access/errors'
import { changePassword, fetchCurrentAccount } from '../../services/identity-access/account'
import { useCurrentAccount } from './useCurrentAccount'

export type ChangePasswordMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useChangePassword() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<ChangePasswordMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<ChangePasswordResponse | null>(null)
  const sessionEnded = ref(false)

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      mutationState.value = 'failed'
      errorMessage.value = 'Le mot de passe actuel est incorrect.'
      fieldErrors.value = {
        password_actuel: ['Le mot de passe actuel est incorrect.'],
      }
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        mutationState.value = 'failed'
        errorMessage.value = messageForAccountError(probe)
        return
      }

      mutationState.value = 'failed'
      errorMessage.value = 'Le mot de passe actuel est incorrect.'
      fieldErrors.value = {
        password_actuel: ['Le mot de passe actuel est incorrect.'],
      }
    }
  }

  async function submit(payload: ChangePasswordRequest): Promise<void> {
    if (mutationState.value === 'submitting' || mutationState.value === 'succeeded') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await changePassword(payload)
      clear()
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}

      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        await resolveUnauthorized()
        return
      }

      errorMessage.value = messageForChangePasswordError(normalized)

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
    sessionEnded,
    submit,
  }
}
