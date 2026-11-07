import type {
  AccountConsent,
  CurrentAccount,
  PendingLegalText,
} from '../../types/identity-access/account'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'

export type AccountLoadState = 'idle' | 'loading' | 'ready' | 'anonymous' | 'error'

type RefreshOptions = {
  allowAnonymous?: boolean
  force?: boolean
}

let inFlight: Promise<void> | null = null

export function useCurrentAccount() {
  const account = useState<CurrentAccount | null>('a1-current-account', () => null)
  const loadState = useState<AccountLoadState>('a1-current-account-load', () => 'idle')
  const errorMessage = useState('a1-current-account-error', () => '')
  const refreshing = useState('a1-current-account-refreshing', () => false)

  function clear(): void {
    account.value = null
    loadState.value = 'anonymous'
    errorMessage.value = ''
    refreshing.value = false
  }

  function applyConsentState(
    consentements: AccountConsent[],
    textesEnAttente: PendingLegalText[],
  ): void {
    if (!account.value) {
      return
    }

    account.value = {
      ...account.value,
      consentements,
      textes_en_attente: textesEnAttente,
    }
  }

  async function refresh(options: RefreshOptions = {}): Promise<void> {
    if (inFlight) {
      try {
        await inFlight
      }
      catch {
        inFlight = null
      }
      if (options.force !== true) {
        return
      }
    }

    inFlight = runRefresh(options).finally(() => {
      inFlight = null
    })
    await inFlight
  }

  async function runRefresh(options: RefreshOptions): Promise<void> {
    const allowAnonymous = options.allowAnonymous === true
    if (account.value) {
      refreshing.value = true
    }
    else {
      loadState.value = 'loading'
    }

    try {
      account.value = await fetchCurrentAccount()
      loadState.value = 'ready'
      errorMessage.value = ''
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      const unauthorized = normalized.status === 401 || normalized.code === 'authentification_refusee'

      if (unauthorized || (allowAnonymous && normalized.kind === 'forbidden')) {
        account.value = null
        loadState.value = 'anonymous'
        errorMessage.value = ''
        return
      }

      errorMessage.value = messageForAccountError(normalized)
      if (!account.value) {
        loadState.value = 'error'
      }
    }
    finally {
      refreshing.value = false
    }
  }

  return {
    account,
    loadState,
    errorMessage,
    refreshing,
    refresh,
    applyConsentState,
    clear,
  }
}
