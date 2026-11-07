import type { RecordConsentRequest } from '../../types/identity-access/account'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForConsentError } from '../../utils/identity-access/errors'
import { recordConsent } from '../../services/identity-access/account'
import { useCurrentAccount } from './useCurrentAccount'

export type ConsentMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useRecordConsent() {
  const { applyConsentState, clear } = useCurrentAccount()
  const mutationState = ref<ConsentMutationState>('idle')
  const errorMessage = ref('')
  const submittingTextId = ref<string | null>(null)
  const sessionEnded = ref(false)

  async function submit(payload: RecordConsentRequest): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''
    sessionEnded.value = false
    submittingTextId.value = payload.texte_version_id

    try {
      const result = await recordConsent(payload)
      applyConsentState(result.consentements, result.textes_en_attente)
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      errorMessage.value = messageForConsentError(normalized)

      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        mutationState.value = 'failed'
        return
      }

      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        return
      }

      mutationState.value = 'failed'
    }
    finally {
      submittingTextId.value = null
    }
  }

  return {
    mutationState,
    errorMessage,
    submittingTextId,
    sessionEnded,
    submit,
  }
}
