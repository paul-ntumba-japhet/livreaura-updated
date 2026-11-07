import type { OrganizationSummary } from '../../types/identity-access/organization'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForOrganizationError } from '../../utils/identity-access/errors'
import { fetchOrganizationMatches } from '../../services/identity-access/organizations'

export type OrganizationMatchesLoadState = 'idle' | 'loading' | 'ready' | 'empty' | 'error'

type MatchQuery = {
  type_organisation_id: string
  raison_sociale: string
  pays: string
}

export function useOrganizationMatches() {
  const loadState = ref<OrganizationMatchesLoadState>('idle')
  const matches = ref<OrganizationSummary[]>([])
  const errorMessage = ref('')
  let generation = 0

  async function search(query: MatchQuery): Promise<void> {
    const typeId = query.type_organisation_id.trim()
    const name = query.raison_sociale.trim()
    const country = query.pays.trim().toUpperCase()

    if (!typeId || !name || country.length !== 2) {
      generation += 1
      loadState.value = 'idle'
      matches.value = []
      errorMessage.value = ''
      return
    }

    const current = generation + 1
    generation = current
    loadState.value = 'loading'
    errorMessage.value = ''

    try {
      const result = await fetchOrganizationMatches({
        type_organisation_id: typeId,
        raison_sociale: name,
        pays: country,
      })

      if (current !== generation) {
        return
      }

      matches.value = result
      loadState.value = result.length === 0 ? 'empty' : 'ready'
    }
    catch (error) {
      if (current !== generation) {
        return
      }

      const normalized = error as IdentityRequestError
      matches.value = []
      loadState.value = 'error'
      errorMessage.value = messageForOrganizationError(normalized)
    }
  }

  function reset(): void {
    generation += 1
    loadState.value = 'idle'
    matches.value = []
    errorMessage.value = ''
  }

  return {
    loadState,
    matches,
    errorMessage,
    search,
    reset,
  }
}
