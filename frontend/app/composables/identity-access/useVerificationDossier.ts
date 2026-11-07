import type { VerificationDossier } from '../../types/identity-access/verification'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForVerificationError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import { fetchVerificationDossier } from '../../services/identity-access/verification'
import { useCurrentAccount } from './useCurrentAccount'

export type VerificationDossierLoadState = 'idle' | 'loading' | 'ready' | 'empty' | 'error'

export function useVerificationDossier() {
  const { clear } = useCurrentAccount()
  const dossier = ref<VerificationDossier | null>(null)
  const loadState = ref<VerificationDossierLoadState>('idle')
  const errorMessage = ref('')
  const errorKind = ref<IdentityRequestError['kind'] | null>(null)
  const sessionEnded = ref(false)
  let requestId = 0

  function applyDossier(next: VerificationDossier | null): void {
    dossier.value = next
  }

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      loadState.value = 'error'
      errorMessage.value = 'Ce dossier n’est pas visible.'
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        dossier.value = null
        loadState.value = 'error'
        errorMessage.value = messageForAccountError(probe)
        return
      }

      loadState.value = 'error'
      errorMessage.value = 'Ce dossier n’est pas visible.'
    }
  }

  async function load(dossierId: string | null): Promise<void> {
    const current = ++requestId
    sessionEnded.value = false

    if (!dossierId) {
      dossier.value = null
      loadState.value = 'empty'
      errorMessage.value = ''
      errorKind.value = null
      return
    }

    loadState.value = 'loading'
    errorMessage.value = ''
    errorKind.value = null

    try {
      const next = await fetchVerificationDossier(dossierId)
      if (current !== requestId) {
        return
      }
      dossier.value = next
      loadState.value = 'ready'
      errorKind.value = null
    }
    catch (error) {
      if (current !== requestId) {
        return
      }

      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        errorKind.value = 'forbidden'
        await resolveUnauthorized()
        return
      }

      errorKind.value = normalized.kind
      if (normalized.kind === 'forbidden') {
        errorMessage.value = 'Vous n’êtes pas autorisé à consulter ce dossier.'
      }
      else if (normalized.kind === 'not_found' || normalized.code === 'dossier_inconnu') {
        errorMessage.value = 'Impossible de charger les détails de ce dossier. Réessayez.'
        errorKind.value = 'not_found'
      }
      else {
        errorMessage.value = messageForVerificationError(normalized)
      }
      if (!dossier.value) {
        loadState.value = 'error'
      }
    }
  }

  function reset(): void {
    requestId += 1
    dossier.value = null
    loadState.value = 'idle'
    errorMessage.value = ''
    errorKind.value = null
    sessionEnded.value = false
  }

  return {
    dossier,
    loadState,
    errorMessage,
    errorKind,
    sessionEnded,
    load,
    applyDossier,
    reset,
  }
}
