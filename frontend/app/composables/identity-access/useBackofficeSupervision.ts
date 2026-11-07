import type { BackofficeSupervisionFilters, BackofficeSupervisionList } from '../../types/identity-access/backoffice'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { fetchBackofficeSupervision } from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'
import type { BackofficeLoadState } from './useBackofficeTasks'

export function useBackofficeSupervision() {
  const { clear } = useCurrentAccount()
  const loadState = ref<BackofficeLoadState>('idle')
  const errorMessage = ref('')
  const sessionEnded = ref(false)
  const list = ref<BackofficeSupervisionList | null>(null)
  let generation = 0

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      loadState.value = 'error'
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        loadState.value = 'error'
        errorMessage.value = messageForAccountError(probe)
        return
      }
      loadState.value = 'error'
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
  }

  function emptyForTab(result: BackofficeSupervisionList): boolean {
    if (result.onglet === 'journal') {
      return result.evenements.length === 0
    }
    if (result.onglet === 'reprises') {
      return result.reprises.length === 0
    }
    return result.transmissions.length === 0
  }

  async function refresh(filters: BackofficeSupervisionFilters = {}): Promise<void> {
    const current = ++generation
    loadState.value = list.value ? 'ready' : 'loading'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      const result = await fetchBackofficeSupervision(filters)
      if (current !== generation) {
        return
      }
      list.value = result
      loadState.value = emptyForTab(result) ? 'empty' : 'ready'
    }
    catch (error) {
      if (current !== generation) {
        return
      }
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        await resolveUnauthorized()
        return
      }
      errorMessage.value = messageForBackofficeError(normalized)
      loadState.value = 'error'
    }
  }

  return {
    loadState,
    errorMessage,
    sessionEnded,
    list,
    refresh,
  }
}
