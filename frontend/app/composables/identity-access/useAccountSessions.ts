import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForLogoutError } from '../../utils/identity-access/errors'
import type { OwnAuthenticationSession } from '../../types/identity-access/account'
import { fetchOwnSessions, revokeAllSessions } from '../../services/identity-access/session'
import { useCurrentAccount } from './useCurrentAccount'

export type SessionListLoadState = 'idle' | 'loading' | 'ready' | 'empty' | 'error'
export type RevokeAllMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useAccountSessions() {
  const { account, clear } = useCurrentAccount()
  const sessions = ref<OwnAuthenticationSession[]>([])
  const loadState = ref<SessionListLoadState>('idle')
  const errorMessage = ref('')
  const retrying = ref(false)
  let requestId = 0

  async function load(): Promise<void> {
    const id = ++requestId
    if (loadState.value === 'ready' || loadState.value === 'empty') {
      retrying.value = true
    }
    else {
      loadState.value = 'loading'
    }
    errorMessage.value = ''

    try {
      const rows = await fetchOwnSessions()
      if (id !== requestId) {
        return
      }
      sessions.value = rows
      loadState.value = rows.length === 0 ? 'empty' : 'ready'
    }
    catch (error) {
      if (id !== requestId) {
        return
      }
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        sessions.value = []
        clear()
        loadState.value = 'error'
        errorMessage.value = messageForAccountError(normalized)
        return
      }
      errorMessage.value = messageForAccountError(normalized)
      loadState.value = 'error'
    }
    finally {
      if (id === requestId) {
        retrying.value = false
      }
    }
  }

  function reset(): void {
    requestId += 1
    sessions.value = []
    loadState.value = 'idle'
    errorMessage.value = ''
    retrying.value = false
  }

  watch(
    () => account.value?.compte.id ?? null,
    (current, previous) => {
      if (current !== previous) {
        reset()
      }
    },
  )

  return {
    sessions,
    loadState,
    errorMessage,
    retrying,
    load,
    reset,
  }
}

export function useRevokeAllSessions() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<RevokeAllMutationState>('idle')
  const errorMessage = ref('')

  async function submit(): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''

    try {
      await revokeAllSessions()
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
