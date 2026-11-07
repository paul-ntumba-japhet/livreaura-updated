import type { PendingLegalText } from '../../types/identity-access/account'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { fetchRegistrationPaths } from '../../services/identity-access/registration-paths'
import {
  mergePendingLegalContent,
  type PendingLegalTextView,
} from '../../utils/identity-access/account'

export type PendingLegalTextsLoadState = 'idle' | 'loading' | 'ready' | 'error'

export function usePendingLegalTexts() {
  const texts = ref<PendingLegalTextView[]>([])
  const loadState = ref<PendingLegalTextsLoadState>('idle')
  const errorMessage = ref('')

  async function load(pending: PendingLegalText[], locale = 'fr'): Promise<void> {
    if (pending.length === 0) {
      texts.value = []
      loadState.value = 'ready'
      errorMessage.value = ''
      return
    }

    loadState.value = 'loading'
    errorMessage.value = ''

    try {
      const paths = await fetchRegistrationPaths(locale || 'fr')
      texts.value = mergePendingLegalContent(pending, paths.textes_requis)
      loadState.value = 'ready'
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      texts.value = mergePendingLegalContent(pending, [])
      loadState.value = 'error'
      errorMessage.value = normalized.kind === 'network'
        ? 'Les textes n’ont pas pu être chargés. Vérifiez votre connexion, puis réessayez.'
        : 'Les textes n’ont pas pu être chargés. L’acceptation reste bloquée tant que le contenu n’est pas consultable.'
    }
  }

  return {
    texts,
    loadState,
    errorMessage,
    load,
  }
}
