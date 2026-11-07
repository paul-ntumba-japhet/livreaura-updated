import type { OwnAuthenticationSession } from '../../types/identity-access/account'
import type { LoginPayload, LoginResult } from '../../types/identity-access/session'
import { asOwnAuthenticationSessions } from '../../utils/identity-access/account'
import { normalizeIdentityFetchError, statusFromUnknown } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

async function postLogin(payload: LoginPayload): Promise<LoginResult> {
  const response = await $fetch<ApiSuccess<LoginResult>>(
    `${apiBase()}/api/v1/identity-access/connexion`,
    {
      method: 'POST',
      body: payload,
      credentials: 'include',
      timeout: 30000,
      headers: identityMutationHeaders(),
    },
  )

  if (!response?.data?.compte || !response.data.session?.portee) {
    throw { statusCode: 503 }
  }

  return response.data
}

export async function signOutCurrentSession(): Promise<void> {
  try {
    await withIdentityCsrf(() => deleteCurrentSession())
  }
  catch (error) {
    if (statusFromUnknown(error) === 401) {
      return
    }
    throw error
  }
}

async function deleteCurrentSession(): Promise<void> {
  await $fetch(`${apiBase()}/api/v1/identity-access/moi/sessions/courante`, {
    method: 'DELETE',
    credentials: 'include',
    timeout: 20000,
    headers: identityMutationHeaders(),
  })
}

async function deleteAllSessions(): Promise<void> {
  await $fetch(`${apiBase()}/api/v1/identity-access/moi/sessions`, {
    method: 'DELETE',
    credentials: 'include',
    timeout: 20000,
    headers: identityMutationHeaders(),
  })
}

export async function revokeAllSessions(): Promise<void> {
  try {
    await withIdentityCsrf(() => deleteAllSessions())
  }
  catch (error) {
    if (statusFromUnknown(error) === 401) {
      return
    }
    throw error
  }
}

export async function fetchOwnSessions(): Promise<OwnAuthenticationSession[]> {
  try {
    const response = await $fetch<ApiSuccess<{ sessions?: unknown }>>(
      `${apiBase()}/api/v1/identity-access/moi/sessions`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return asOwnAuthenticationSessions(response?.data?.sessions)
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function signIn(payload: LoginPayload): Promise<LoginResult> {
  return withIdentityCsrf(() => postLogin(payload))
}
