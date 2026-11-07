const STORAGE_KEY = 'livreaura.a1.recuperation.email'

let rememberedEmail: string | null = null

function readStoredEmail(): string | null {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }
    const parsed = JSON.parse(raw) as { email?: string }
    return typeof parsed.email === 'string' ? parsed.email : null
  }
  catch {
    return null
  }
}

export function rememberRecoveryEmail(email: string): void {
  if (!import.meta.client) {
    return
  }
  rememberedEmail = email
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ email }))
}

export function readRecoveryEmail(): string | null {
  if (!import.meta.client) {
    return null
  }
  if (rememberedEmail) {
    return rememberedEmail
  }
  rememberedEmail = readStoredEmail()
  return rememberedEmail
}
