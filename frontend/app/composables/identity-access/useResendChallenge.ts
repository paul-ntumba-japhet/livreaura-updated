import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForResendError } from '../../utils/identity-access/errors'
import { resendWaitMs } from '../../types/identity-access/challenges'
import { resendEmailChallenge } from '../../services/identity-access/challenges'

export type ResendMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useResendChallenge() {
  const mutationState = ref<ResendMutationState>('idle')
  const errorMessage = ref('')
  const waitUntil = ref(0)
  const waitSeconds = ref(0)
  let timer: ReturnType<typeof setInterval> | undefined

  function syncWait() {
    const remaining = Math.ceil((waitUntil.value - Date.now()) / 1000)
    waitSeconds.value = remaining > 0 ? remaining : 0
    if (waitSeconds.value === 0 && timer) {
      clearInterval(timer)
      timer = undefined
    }
  }

  function startWait() {
    waitUntil.value = Date.now() + resendWaitMs
    syncWait()
    if (timer) {
      clearInterval(timer)
    }
    timer = setInterval(syncWait, 1000)
  }

  onUnmounted(() => {
    if (timer) {
      clearInterval(timer)
    }
  })

  async function submit(email: string): Promise<void> {
    if (mutationState.value === 'submitting' || waitSeconds.value > 0) {
      return
    }

    mutationState.value = 'submitting'
    errorMessage.value = ''

    try {
      await resendEmailChallenge(email)
      mutationState.value = 'succeeded'
      startWait()
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      errorMessage.value = messageForResendError(normalized)
      if (normalized.kind === 'network') {
        mutationState.value = 'uncertain'
        return
      }
      mutationState.value = 'failed'
      if (normalized.kind === 'quota') {
        startWait()
      }
    }
  }

  return {
    mutationState,
    errorMessage,
    waitSeconds,
    submit,
  }
}
