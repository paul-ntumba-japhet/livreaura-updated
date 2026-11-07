import type {
  RecoveryConfirmResult,
  RecoveryProofPreview,
  RecoveryRequestResult,
} from '../../types/identity-access/recovery'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function requestPasswordReset(email: string): Promise<RecoveryRequestResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RecoveryRequestResult>>(
      `${apiBase()}/api/v1/identity-access/recuperations`,
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

export async function verifyResetProof(
  challengeId: string,
  proof: string,
): Promise<RecoveryProofPreview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RecoveryProofPreview>>(
      `${apiBase()}/api/v1/identity-access/recuperations/verification`,
      {
        method: 'POST',
        body: {
          defi_id: challengeId,
          preuve: proof,
        },
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    if (response?.data?.formulaire !== 'autorise' || !response.data.defi_id) {
      throw { statusCode: 503 }
    }

    return response.data
  })
}

export async function confirmPasswordReset(
  challengeId: string,
  proof: string,
  password: string,
): Promise<RecoveryConfirmResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RecoveryConfirmResult>>(
      `${apiBase()}/api/v1/identity-access/recuperations/confirmation`,
      {
        method: 'POST',
        body: {
          defi_id: challengeId,
          preuve: proof,
          password,
        },
        credentials: 'include',
        timeout: 30000,
        headers: identityMutationHeaders(),
      },
    )

    if (response?.data?.prochaine_action !== 'connexion') {
      throw { statusCode: 503 }
    }

    return response.data
  })
}
