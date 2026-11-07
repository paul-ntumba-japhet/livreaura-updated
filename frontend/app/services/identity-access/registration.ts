import type { RegisterAccountPayload, RegisterAccountResult } from '../../types/identity-access/registration'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function registerAccount(
  payload: RegisterAccountPayload,
  idempotencyKey: string,
): Promise<RegisterAccountResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RegisterAccountResult>>(
      `${apiBase()}/api/v1/identity-access/inscriptions`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 30000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.compte || !response.data.prochaine_action) {
      throw { statusCode: 503 }
    }

    return response.data
  })
}
