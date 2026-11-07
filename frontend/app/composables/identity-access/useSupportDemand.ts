import type { SupportDemand } from '../../types/identity-access/support'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForAccountError, messageForSupportError } from '../../utils/identity-access/errors'
import { fetchCurrentAccount } from '../../services/identity-access/account'
import {
  attachSupportEvidence,
  downloadSupportEvidence,
  fetchSupportDemand,
  replyToSupport,
} from '../../services/identity-access/support'
import { useCurrentAccount } from './useCurrentAccount'

export type SupportDemandLoadState = 'idle' | 'loading' | 'ready' | 'empty' | 'error'
export type SupportDemandMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useSupportDemand() {
  const { clear } = useCurrentAccount()
  const loadState = ref<SupportDemandLoadState>('idle')
  const replyState = ref<SupportDemandMutationState>('idle')
  const attachState = ref<SupportDemandMutationState>('idle')
  const downloadState = ref<SupportDemandMutationState>('idle')
  const errorMessage = ref('')
  const replyError = ref('')
  const attachError = ref('')
  const downloadError = ref('')
  const attachFieldErrors = ref<Record<string, string[]>>({})
  const sessionEnded = ref(false)
  const demand = ref<SupportDemand | null>(null)
  const currentKey = ref('')
  const lookupGeneration = ref(0)
  const replyKey = ref('')
  const replyIntention = ref('')
  const downloadingId = ref<string | null>(null)

  function replyIdempotency(demandKey: string, contenu: string, visibilite: string): string {
    const intention = [demandKey, contenu, visibilite].join(':')
    if (!replyKey.value || replyIntention.value !== intention) {
      replyKey.value = crypto.randomUUID()
      replyIntention.value = intention
    }
    return replyKey.value
  }

  async function resolveUnauthorized(): Promise<void> {
    try {
      await fetchCurrentAccount()
      errorMessage.value = 'Cette demande n’est pas visible.'
    }
    catch (error) {
      const probe = error as IdentityRequestError
      if (probe.status === 401 || probe.code === 'authentification_refusee') {
        sessionEnded.value = true
        clear()
        errorMessage.value = messageForAccountError(probe)
        return
      }
      errorMessage.value = 'Cette demande n’est pas visible.'
    }
  }

  async function load(demandKey: string): Promise<void> {
    const key = demandKey.trim()
    const generation = lookupGeneration.value + 1
    lookupGeneration.value = generation
    loadState.value = demand.value?.demande.reference === key || demand.value?.demande.id === key.toUpperCase()
      ? 'ready'
      : 'loading'
    errorMessage.value = ''
    sessionEnded.value = false

    try {
      const next = await fetchSupportDemand(key)
      if (generation !== lookupGeneration.value) {
        return
      }
      currentKey.value = key
      demand.value = next
      loadState.value = 'ready'
    }
    catch (error) {
      if (generation !== lookupGeneration.value) {
        return
      }
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        loadState.value = 'error'
        await resolveUnauthorized()
        return
      }
      errorMessage.value = messageForSupportError(normalized)
      loadState.value = 'error'
    }
  }

  async function reply(contenu: string, visibilite: 'demandeur' | 'interne' = 'demandeur'): Promise<void> {
    if (replyState.value === 'submitting' || !currentKey.value) {
      return
    }

    replyState.value = 'submitting'
    replyError.value = ''
    sessionEnded.value = false

    try {
      demand.value = await replyToSupport(
        currentKey.value,
        { contenu, visibilite },
        replyIdempotency(currentKey.value, contenu, visibilite),
      )
      replyState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        replyState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      replyError.value = messageForSupportError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        replyState.value = 'uncertain'
        return
      }
      replyState.value = 'failed'
    }
  }

  async function attach(type: string, file: File, acces: 'demandeur' | 'interne' = 'demandeur'): Promise<void> {
    if (attachState.value === 'submitting' || !currentKey.value) {
      return
    }

    attachState.value = 'submitting'
    attachError.value = ''
    attachFieldErrors.value = {}
    sessionEnded.value = false

    try {
      demand.value = await attachSupportEvidence(currentKey.value, { type, acces, file })
      attachState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      attachFieldErrors.value = normalized.fields ?? {}
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        attachState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      attachError.value = messageForSupportError(normalized)
      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        attachState.value = 'uncertain'
        return
      }
      attachState.value = 'failed'
    }
  }

  async function download(pieceId: string, filename: string): Promise<void> {
    if (downloadState.value === 'submitting' || !currentKey.value) {
      return
    }

    downloadState.value = 'submitting'
    downloadError.value = ''
    downloadingId.value = pieceId

    try {
      await downloadSupportEvidence(currentKey.value, pieceId, filename)
      downloadState.value = 'succeeded'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      if (normalized.status === 401 || normalized.code === 'authentification_refusee') {
        downloadState.value = 'failed'
        await resolveUnauthorized()
        return
      }
      downloadError.value = messageForSupportError(normalized)
      downloadState.value = normalized.kind === 'network' || normalized.kind === 'unknown' ? 'uncertain' : 'failed'
    }
    finally {
      downloadingId.value = null
    }
  }

  return {
    loadState,
    replyState,
    attachState,
    downloadState,
    errorMessage,
    replyError,
    attachError,
    downloadError,
    attachFieldErrors,
    sessionEnded,
    demand,
    downloadingId,
    load,
    reply,
    attach,
    download,
  }
}
