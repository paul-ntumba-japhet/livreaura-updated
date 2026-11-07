import { normalizeIdentityFetchError, statusFromUnknown } from '../../utils/identity-access/errors'
import type { IdentityRequestError } from '../../utils/identity-access/errors'

function configuredApiBase(): string {
  return String(useRuntimeConfig().public.apiBase).replace(/\/$/, '')
}

function apiBase(): string {
  if (import.meta.client && import.meta.dev) {
    return ''
  }

  return configuredApiBase()
}

function readCookie(name: string): string | null {
  if (!import.meta.client) {
    return null
  }

  const prefix = `${name}=`
  const found = document.cookie.split('; ').find(part => part.startsWith(prefix))
  return found ? decodeURIComponent(found.slice(prefix.length)) : null
}

function isIdentityRequestError(error: unknown): error is IdentityRequestError {
  return Boolean(error && typeof error === 'object' && 'kind' in error)
}

export function asIdentityCsrfSetupError(error: unknown): IdentityRequestError {
  const normalized = isIdentityRequestError(error)
    ? error
    : normalizeIdentityFetchError(error)

  if (normalized.kind === 'network') {
    return { kind: 'unavailable', status: normalized.status }
  }

  if (normalized.status === 419) {
    return { ...normalized, code: 'csrf' }
  }

  return normalized
}

export async function ensureIdentityCsrfCookie(): Promise<void> {
  try {
    await $fetch(`${apiBase()}/sanctum/csrf-cookie`, {
      credentials: 'include',
      timeout: 20000,
    })
  }
  catch (error) {
    throw asIdentityCsrfSetupError(error)
  }

  if (import.meta.client && !readCookie('XSRF-TOKEN')) {
    throw { kind: 'conflict', status: 419, code: 'csrf' } satisfies IdentityRequestError
  }
}

export function identityMutationHeaders(idempotencyKey?: string): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  const token = readCookie('XSRF-TOKEN')
  if (token) {
    headers['X-XSRF-TOKEN'] = token
  }
  if (idempotencyKey) {
    headers['Idempotency-Key'] = idempotencyKey
  }
  return headers
}

export async function withIdentityCsrf<T>(run: () => Promise<T>): Promise<T> {
  try {
    await ensureIdentityCsrfCookie()
  }
  catch (error) {
    throw asIdentityCsrfSetupError(error)
  }

  try {
    try {
      return await run()
    }
    catch (error) {
      if (statusFromUnknown(error) === 419) {
        await ensureIdentityCsrfCookie()
        return await run()
      }
      throw error
    }
  }
  catch (error) {
    if (isIdentityRequestError(error)) {
      throw error
    }
    throw normalizeIdentityFetchError(error)
  }
}

export { apiBase }

