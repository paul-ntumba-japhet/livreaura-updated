import { fetchRegistrationPaths } from '../../services/identity-access/registration-paths'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForIdentityError } from '../../utils/identity-access/errors'
import type { RegistrationPathsData } from '../../types/identity-access/registration'

export type RegistrationPathsLoadState = 'loading' | 'ready' | 'empty' | 'error'

type PathsResult =
  | { ok: true, data: RegistrationPathsData }
  | { ok: false, error: IdentityRequestError }

export async function useRegistrationPaths(langue = 'fr') {
  const request = await useAsyncData(
    `a1-registration-paths:${langue}`,
    async (): Promise<PathsResult> => {
      try {
        return {
          ok: true,
          data: await fetchRegistrationPaths(langue),
        }
      }
      catch (error) {
        if (error && typeof error === 'object' && 'kind' in error) {
          return { ok: false, error: error as IdentityRequestError }
        }
        return { ok: false, error: { kind: 'unknown', status: null } }
      }
    },
    {
      getCachedData(key, nuxtApp) {
        const cached = nuxtApp.payload.data[key] as PathsResult | undefined
        if (!cached || cached.ok === false) {
          return undefined
        }
        return cached
      },
    },
  )

  const payload = computed(() => (request.data.value?.ok ? request.data.value.data : null))

  const loadState = computed<RegistrationPathsLoadState>(() => {
    if (request.pending.value && !payload.value) {
      return 'loading'
    }
    if (!request.data.value) {
      return request.pending.value ? 'loading' : 'error'
    }
    if (!request.data.value.ok) {
      return 'error'
    }
    if (request.data.value.data.parcours.length === 0) {
      return 'empty'
    }
    return 'ready'
  })

  const errorMessage = computed(() => {
    if (!request.data.value || request.data.value.ok) {
      return ''
    }
    return messageForIdentityError(request.data.value.error)
  })

  const refreshing = computed(() => request.pending.value && Boolean(payload.value))

  return {
    data: payload,
    loadState,
    errorMessage,
    refreshing,
    refresh: request.refresh,
  }
}
