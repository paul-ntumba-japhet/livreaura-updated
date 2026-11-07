import type {
  CreateOrganizationPayload,
  CreateOrganizationResponse,
} from '../../types/identity-access/organization'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForOrganizationError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { createOrRecognizeOrganization } from '../../services/identity-access/organizations'
import { useCurrentAccount } from './useCurrentAccount'

export type CreateOrganizationMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

function intentionOf(payload: CreateOrganizationPayload): string {
  return [
    payload.type_organisation_id,
    payload.raison_sociale.trim().toLowerCase(),
    payload.pays.trim().toUpperCase(),
    payload.organisation_id ?? 'nouvelle',
    payload.confirmer_nouvelle === true ? 'confirmer' : 'proposer',
  ].join(':')
}

export function useCreateOrganization() {
  const { refresh, clear } = useCurrentAccount()
  const mutationState = ref<CreateOrganizationMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const result = ref<CreateOrganizationResponse | null>(null)
  const sessionEnded = ref(false)
  const idempotencyKey = ref('')
  const lastIntention = ref('')

  function keyFor(payload: CreateOrganizationPayload): string {
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
      errorMessage.value = 'La création d’une organisation n’est pas disponible pour ce compte.'
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
      errorMessage.value = 'La création d’une organisation n’est pas disponible pour ce compte.'
    }
  }

  async function submit(payload: CreateOrganizationPayload): Promise<void> {
    if (mutationState.value === 'submitting') {
      return
    }

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      result.value = await createOrRecognizeOrganization(payload, keyFor(payload))
      if (result.value.statut !== 'correspondances_possibles') {
        await refresh({ allowAnonymous: false })
      }
      mutationState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}

      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        await resolveUnauthorized()
        return
      }

      errorMessage.value = messageForOrganizationError(normalized)

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
