import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForRecoveryRequestError } from '../../utils/identity-access/errors'
import { rememberRecoveryEmail } from '../../utils/identity-access/recovery-email'
import { recoveryWaitMs } from '../../types/identity-access/recovery'
import { requestPasswordReset } from '../../services/identity-access/recovery'

export type RecoveryRequestMutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useRequestPasswordReset() {
  const mutationState = ref<RecoveryRequestMutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
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
    waitUntil.value = Date.now() + recoveryWaitMs
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
    fieldErrors.value = {}
    errorMessage.value = ''

    try {
      await requestPasswordReset(email)
      rememberRecoveryEmail(email)
      mutationState.value = 'succeeded'
      startWait()
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}
      errorMessage.value = messageForRecoveryRequestError(normalized)

      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
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
    fieldErrors,
    errorMessage,
    waitSeconds,
    submit,
  }
}
