import type { ResendChallengeResult } from '../../types/identity-access/challenges'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function resendEmailChallenge(email: string): Promise<ResendChallengeResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ResendChallengeResult>>(
      `${apiBase()}/api/v1/identity-access/defis/renvoi`,
      {
        method: 'POST',
        body: { email },
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    if (!response?.data?.statut) {
      throw { statusCode: 503 }
    }

    return response.data
  })
}
