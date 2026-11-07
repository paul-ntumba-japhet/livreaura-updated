import type {
  InternalPermissionCatalog,
  InternalPermissionMutationResult,
} from '../../types/identity-access/backoffice'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForBackofficeError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import {
  fetchInternalPermissionCatalog,
  grantInternalPermission,
  revokeInternalPermission,
} from '../../services/identity-access/backoffice'
import { useCurrentAccount } from './useCurrentAccount'

export type InternalPermissionLoadState = 'idle' | 'loading' | 'ready' | 'empty' | 'error'
export type InternalPermissionMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useInternalPermissions() {
  const { clear } = useCurrentAccount()
  const loadState = ref<InternalPermissionLoadState>('idle')
  const lookupState = ref<InternalPermissionLoadState>('idle')
  const grantState = ref<InternalPermissionMutationState>('idle')
  const revokeState = ref<InternalPermissionMutationState>('idle')
  const errorMessage = ref('')
  const lookupError = ref('')
  const grantError = ref('')
  const revokeError = ref('')
  const grantFieldErrors = ref<Record<string, string[]>>({})
  const revokeFieldErrors = ref<Record<string, string[]>>({})
  const sessionEnded = ref(false)
  const catalog = ref<InternalPermissionCatalog | null>(null)
  const grantResult = ref<InternalPermissionMutationResult | null>(null)
  const revokeResult = ref<InternalPermissionMutationResult | null>(null)
  const lookedUpAccountId = ref('')
  const lookupGeneration = ref(0)
  const grantKey = ref('')
  const grantIntention = ref('')
  const revokeKey = ref('')
  const revokeIntention = ref('')

  function grantIdempotency(compteId: string, permission: string, motif: string, expireAt: string | null): string {
    const intention = ['grant', compteId, permission, motif, expireAt || ''].join(':')
    if (!grantKey.value || grantIntention.value !== intention) {
      grantKey.value = crypto.randomUUID()
      grantIntention.value = intention
    }
    return grantKey.value
  }

  function revokeIdempotency(attributionId: string, motif: string): string {
    const intention = ['revoke', attributionId, motif].join(':')
    if (!revokeKey.value || revokeIntention.value !== intention) {
      revokeKey.value = crypto.randomUUID()
      revokeIntention.value = intention
    }
    return revokeKey.value
  }

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        errorMessage.value = messageForAccountError(probe)
        return
      }
      errorMessage.value = 'Cette opération interne n’est pas autorisée pour ce compte.'
    }
  }

  async function loadCatalog(): Promise<void> {
    loadState.value = catalog.value ? 'ready' : 'loading'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      const next = await fetchInternalPermissionCatalog()
      catalog.value = {
        permissions: next.permissions,
        compte: catalog.value?.compte ?? null,
        attributions: catalog.value?.attributions ?? [],
      }
      if (!lookedUpAccountId.value) {
        catalog.value.compte = next.compte
        catalog.value.attributions = next.attributions
      }
      loadState.value = next.permissions.length === 0 ? 'empty' : 'ready'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        loadState.value = 'error'
        await resolveUnauthorized()
        return
      }
      errorMessage.value = messageForBackofficeError(normalized)
      loadState.value = 'error'
    }
  }

  async function lookup(compteId: string): Promise<void> {
    const id = compteId.trim().toUpperCase()
    const generation = lookupGeneration.value + 1
    lookupGeneration.value = generation
    lookupState.value = catalog.value?.compte?.id === id ? 'ready' : 'loading'
    lookupError.value = ''
    sessionEnded.value = false

    try {
      const next = await fetchInternalPermissionCatalog(id)
      if (generation !== lookupGeneration.value) {
        return
      }
      lookedUpAccountId.value = id
      catalog.value = {
        permissions: next.permissions.length > 0 ? next.permissions : catalog.value?.permissions ?? [],
        compte: next.compte,
        attributions: next.attributions,
      }
      lookupState.value = next.attributions.length === 0 ? 'empty' : 'ready'
    }
    catch (error) {
      if (generation !== lookupGeneration.value) {
        return
      }
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        lookupState.value = 'error'
        await resolveUnauthorized()
        return
      }
      lookupError.value = messageForBackofficeError(normalized)
      lookupState.value = 'error'
    }
  }

  async function refreshLookedUpAccount(): Promise<void> {
    if (!lookedUpAccountId.value) {
      return
    }
    await lookup(lookedUpAccountId.value)
  }

  async function grant(payload: { compte_id: string, permission: string, motif: string, expire_a: string | null }): Promise<void> {
    if (grantState.value === 'submitting') {
      return
    }

    grantState.value = 'submitting'
    grantError.value = ''
    grantFieldErrors.value = {}
    sessionEnded.value = false

    try {
      grantResult.value = await grantInternalPermission(
        payload,
        grantIdempotency(payload.compte_id, payload.permission, payload.motif, payload.expire_a),
      )
      grantState.value = 'succeeded'
      await lookup(payload.compte_id)
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      grantFieldErrors.value = normalized.fields ?? {}
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        grantState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      grantError.value = messageForBackofficeError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        grantState.value = 'uncertain'
        return
      }
      grantState.value = 'failed'
      if (normalized.kind === 'conflict') {
        await lookup(payload.compte_id)
      }
    }
  }

  async function revoke(attributionId: string, motif: string): Promise<void> {
    if (revokeState.value === 'submitting') {
      return
    }

    revokeState.value = 'submitting'
    revokeError.value = ''
    revokeFieldErrors.value = {}
    sessionEnded.value = false

    try {
      revokeResult.value = await revokeInternalPermission(
        attributionId,
        { motif },
        revokeIdempotency(attributionId, motif),
      )
      revokeState.value = 'succeeded'
      await refreshLookedUpAccount()
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      revokeFieldErrors.value = normalized.fields ?? {}
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        revokeState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      revokeError.value = messageForBackofficeError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        revokeState.value = 'uncertain'
        return
      }
      revokeState.value = 'failed'
      if (normalized.kind === 'conflict') {
        await refreshLookedUpAccount()
      }
    }
  }

  return {
    loadState,
    lookupState,
    grantState,
    revokeState,
    errorMessage,
    lookupError,
    grantError,
    revokeError,
    grantFieldErrors,
    revokeFieldErrors,
    sessionEnded,
    catalog,
    grantResult,
    revokeResult,
    lookedUpAccountId,
    loadCatalog,
    lookup,
    grant,
    revoke,
  }
}
