import type { RegisterAccountPayload, RegisterAccountResult } from '../../types/identity-access/registration'
import type { IdentityRequestError } from '../../utils/identity-access/errors'
import { messageForRegistrationError } from '../../utils/identity-access/errors'
import { registerAccount } from '../../services/identity-access/registration'
import {
  clearRegistrationAttempt,
  emailsMatch,
  readRegistrationAttempt,
  recoveredRegistrationResult,
  registrationIntentionFingerprint,
  writeRegistrationAttempt,
} from '../../utils/identity-access/registration-attempt'

export type MutationState = 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'

export function useRegisterAccount() {
  const mutationState = ref<MutationState>('idle')
  const fieldErrors = ref<Record<string, string[]>>({})
  const errorMessage = ref('')
  const errorCode = ref('')
  const result = ref<RegisterAccountResult | null>(null)
  const idempotencyKey = ref('')
  const lastFingerprint = ref('')

  function resetMutation() {
    if (mutationState.value === 'submitting') {
      return
    }
    mutationState.value = 'idle'
    fieldErrors.value = {}
    errorMessage.value = ''
    errorCode.value = ''
    result.value = null
  }

  async function submit(payload: RegisterAccountPayload): Promise<void> {
    if (mutationState.value === 'submitting' || mutationState.value === 'succeeded') {
      return
    }

    const nextFingerprint = registrationIntentionFingerprint(payload)
    const stored = readRegistrationAttempt()
    const sameEmail = stored !== null && emailsMatch(stored.email, payload.email)
    const recoverOnDuplicate = mutationState.value === 'uncertain'
      || (sameEmail && stored.recoverOnDuplicate && !stored.lastKnownConflict)

    if (stored !== null && stored.fingerprint === nextFingerprint) {
      idempotencyKey.value = stored.key
      lastFingerprint.value = nextFingerprint
    }
    else if (!idempotencyKey.value || lastFingerprint.value !== nextFingerprint) {
      idempotencyKey.value = crypto.randomUUID()
      lastFingerprint.value = nextFingerprint
    }

    writeRegistrationAttempt({
      key: idempotencyKey.value,
      fingerprint: nextFingerprint,
      email: payload.email,
      recoverOnDuplicate,
      lastKnownConflict: sameEmail ? stored.lastKnownConflict : false,
    })

    mutationState.value = 'submitting'
    fieldErrors.value = {}
    errorMessage.value = ''
    errorCode.value = ''

    try {
      result.value = await registerAccount(payload, idempotencyKey.value)
      mutationState.value = 'succeeded'
      clearRegistrationAttempt()
    }
    catch (error) {
      const normalized = error as IdentityRequestError
      fieldErrors.value = normalized.fields ?? {}
      errorCode.value = normalized.code ?? ''

      if (normalized.kind === 'network' || normalized.kind === 'unknown') {
        mutationState.value = 'uncertain'
        errorMessage.value = messageForRegistrationError(normalized)
        writeRegistrationAttempt({
          key: idempotencyKey.value,
          fingerprint: nextFingerprint,
          email: payload.email,
          recoverOnDuplicate: !sameEmail || !stored?.lastKnownConflict,
          lastKnownConflict: sameEmail ? stored.lastKnownConflict : false,
        })
        return
      }

      if (normalized.code === 'compte_existant' && recoverOnDuplicate) {
        result.value = recoveredRegistrationResult(payload.email)
        mutationState.value = 'succeeded'
        errorMessage.value = ''
        errorCode.value = ''
        clearRegistrationAttempt()
        return
      }

      mutationState.value = 'failed'
      errorMessage.value = messageForRegistrationError(normalized)
      writeRegistrationAttempt({
        key: idempotencyKey.value,
        fingerprint: nextFingerprint,
        email: payload.email,
        recoverOnDuplicate: false,
        lastKnownConflict: normalized.code === 'compte_existant',
      })
    }
  }

  return {
    mutationState,
    fieldErrors,
    errorMessage,
    errorCode,
    result,
    submit,
    resetMutation,
  }
}
