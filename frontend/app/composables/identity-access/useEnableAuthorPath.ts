import type {
  EnableAuthorPathPayload,
  EnableAuthorPathResponse,
} from '../../types/identity-access/account'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForAuthorPathError } from '../../utils/identity-access/errors'
import { enableAuthorPath, fetchCurrentAccount } from '../../services/identity-access/account'
import { useCurrentAccount } from './useCurrentAccount'

export type EnableAuthorPathMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useEnableAuthorPath() {
  const { refresh, clear } = useCurrentAccount()
  const mutationState = ref<EnableAuthorPathMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<EnableAuthorPathResponse | null>(null)
  const sessionEnded = ref(false)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function intentionOf(payload: EnableAuthorPathPayload): string {
    return payload.auteur_id?.trim() || 'nouvelle_fiche'
  }

  function keyFor(payload: EnableAuthorPathPayload): string {
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
      errorMessage.value = 'Le parcours auteur n’est pas disponible pour ce compte.'
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
      errorMessage.value = 'Le parcours auteur n’est pas disponible pour ce compte.'
    }
  }

  async function submit(payload: EnableAuthorPathPayload = {}): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await enableAuthorPath(payload, keyFor(payload))
      await refresh({ allowAnonymous: false })
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}

      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        await resolveUnauthorized()
        return
      }

      errorMessage.value = messageForAuthorPathError(normalized)

      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        return
      }

      mutationState.value = 'failed'
    }
  }

  function reset(): void {
    if (mutationState.value === 'submitting') {
      return
    }
    mutationState.value = 'idle'
    errorMessage.value = ''
    fieldErrors.value = {}
    result.value = null
  }

  return {
    mutationState,
    fieldErrors,
    errorMessage,
    result,
    sessionEnded,
    submit,
    reset,
  }
}
