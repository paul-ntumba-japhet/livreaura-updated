import type {
  OpenVerificationDossierPayload,
  OpenVerificationDossierResponse,
} from '../../types/identity-access/verification'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForVerificationError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { openVerificationDossier } from '../../services/identity-access/verification'
import { useCurrentAccount } from './useCurrentAccount'

export type OpenVerificationMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

function intentionOf(payload: OpenVerificationDossierPayload): string {
  return [payload.niveau, payload.organisation_id ?? 'particulier'].join(':')
}

export function useOpenVerificationDossier() {
  const { refresh, clear } = useCurrentAccount()
  const mutationState = ref<OpenVerificationMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<OpenVerificationDossierResponse | null>(null)
  const sessionEnded = ref(false)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(payload: OpenVerificationDossierPayload): string {
    const intention = intentionOf(payload)
    if (!idempotencyKey.value || lastIntention.value !== intention) {
      idempotencyKey.value = crypto.randomUUID()
      lastIntention.value = intention
    }
    return idempotencyKey.value
  }

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      mutationState.value = 'failed'
      errorMessage.value = 'Le dépôt d’un dossier n’est pas disponible pour ce compte.'
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
      errorMessage.value = 'Le dépôt d’un dossier n’est pas disponible pour ce compte.'
    }
  }

  async function submit(payload: OpenVerificationDossierPayload): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await openVerificationDossier(payload, keyFor(payload))
      await refresh({ allowAnonymous: false, force: true })
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
