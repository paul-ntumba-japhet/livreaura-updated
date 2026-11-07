import type {
  SaveVerificationDraftPayload,
  SaveVerificationDraftResponse,
} from '../../types/identity-access/verification'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForVerificationError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { saveVerificationDraft } from '../../services/identity-access/verification'
import { useCurrentAccount } from './useCurrentAccount'

export type SaveVerificationDraftMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useSaveVerificationDraft() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<SaveVerificationDraftMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<SaveVerificationDraftResponse | null>(null)
  const sessionEnded = ref(false)

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      mutationState.value = 'failed'
      errorMessage.value = 'L’enregistrement du brouillon n’est pas disponible pour ce compte.'
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
      errorMessage.value = 'L’enregistrement du brouillon n’est pas disponible pour ce compte.'
    }
  }

  async function submit(dossierId: string, payload: SaveVerificationDraftPayload): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await saveVerificationDraft(dossierId, payload)
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}

      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        await resolveUnauthorized()
        return
      }

      errorMessage.value = messageForVerificationError(normalized)

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
