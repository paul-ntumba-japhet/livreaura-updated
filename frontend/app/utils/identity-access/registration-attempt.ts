import type { RegisterAccountPayload, RegisterAccountResult } from '../../types/identity-access/registration'
import { maskEmail } from './email-activation'

const STORAGE_KEY = 'livreaura.a1.inscription.attempt'

export type RegistrationAttempt = {
  key: string
  fingerprint: string
  email: string
  recoverOnDuplicate: boolean
  lastKnownConflict: boolean
}

export function registrationIntentionFingerprint(payload: RegisterAccountPayload): string {
  return JSON.stringify({
    parcours: payload.parcours,
    email: payload.email,
    prenom: payload.prenom,
    nom: payload.nom,
    pays: payload.pays,
    consentements: payload.consentements,
  })
}

export function emailsMatch(left: string, right: string): boolean {
  return left.trim().toLowerCase() === right.trim().toLowerCase()
}

export function readRegistrationAttempt(): RegistrationAttempt | null {
  if (!import.meta.client) {
    return null
  }

  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw) as Partial<RegistrationAttempt>
    if (typeof parsed.key !== 'string' || parsed.key === '') {
      return null
    }
    if (typeof parsed.fingerprint !== 'string' || typeof parsed.email !== 'string') {
      return null
    }

    return {
      key: parsed.key,
      fingerprint: parsed.fingerprint,
      email: parsed.email,
      recoverOnDuplicate: parsed.recoverOnDuplicate === true,
      lastKnownConflict: parsed.lastKnownConflict === true,
    }
  }
  catch {
    return null
  }
}

export function writeRegistrationAttempt(attempt: RegistrationAttempt): void {
  if (!import.meta.client) {
    return
  }

  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(attempt))
}

export function clearRegistrationAttempt(): void {
  if (!import.meta.client) {
    return
  }

  sessionStorage.removeItem(STORAGE_KEY)
}

export function recoveredRegistrationResult(email: string): RegisterAccountResult {
  return {
    compte: {
      id: '',
      statut: 'pending',
      email_masque: maskEmail(email),
    },
    prochaine_action: 'verification_email',
  }
}
