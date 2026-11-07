import type { ActivationPreview, ActivationResult } from '../../types/identity-access/activation'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function fetchActivationPreview(challengeId: string): Promise<ActivationPreview> {
  try {
    const response = await $fetch<ApiSuccess<ActivationPreview>>(
      `${apiBase()}/api/v1/identity-access/activation`,
      {
        method: 'GET',
        query: { defi_id: challengeId },
        credentials: 'include',
        timeout: 20000,
      },
    )

    if (!response?.data?.action) {
      throw { statusCode: 503 }
    }

    return response.data
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function activateAccount(
  challengeId: string,
  proof: string,
): Promise<ActivationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ActivationResult>>(
      `${apiBase()}/api/v1/identity-access/activation`,
      {
        method: 'POST',
        body: {
          defi_id: challengeId,
          preuve: proof,
        },
        credentials: 'include',
        timeout: 30000,
        headers: identityMutationHeaders(),
      },
    )

    if (!response?.data?.compte || !response.data.prochaine_action) {
      throw { statusCode: 503 }
    }

    return response.data
  })
}
