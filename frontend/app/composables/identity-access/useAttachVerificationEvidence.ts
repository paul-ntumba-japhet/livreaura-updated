import type { AttachVerificationEvidenceResponse } from '../../types/identity-access/verification'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForVerificationError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { attachVerificationEvidence } from '../../services/identity-access/verification'
import { useCurrentAccount } from './useCurrentAccount'

export type AttachEvidenceMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useAttachVerificationEvidence() {
  const { clear } = useCurrentAccount()
  const mutationState = ref<AttachEvidenceMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<AttachVerificationEvidenceResponse | null>(null)
  const sessionEnded = ref(false)
  const attachingType = ref('')
  const uploadProgress = ref<number | null>(null)

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      mutationState.value = 'failed'
      errorMessage.value = 'Le dépôt d’une pièce n’est pas disponible pour ce compte.'
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
      errorMessage.value = 'Le dépôt d’une pièce n’est pas disponible pour ce compte.'
    }
  }

  async function submit(dossierId: string, typeDoc: string, file: File): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    attachingType.value = typeDoc
    uploadProgress.value = null
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await attachVerificationEvidence(dossierId, typeDoc, file, (ratio) => {
        uploadProgress.value = ratio
      })
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
    finally {
      if (mutationState.value !== 'submitting') {
        attachingType.value = mutationState.value === 'succeeded' ? '' : attachingType.value
        uploadProgress.value = null
      }
    }
  }

  return {
    mutationState,
    fieldErrors,
    errorMessage,
    result,
    sessionEnded,
    attachingType,
    uploadProgress,
    submit,
  }
}
