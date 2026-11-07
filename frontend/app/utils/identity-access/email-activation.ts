export type EmailActivationContext = {
  email: string
  emailMasque: string
}

export function formatActivationExpiry(iso?: string | null): string {
  if (!iso) {
    return ''
  }

  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  return `${new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone: 'UTC',
  }).format(date)} UTC`
}

export function maskEmail(email: string): string {
  const parts = email.split('@')
  if (parts.length !== 2 || parts[0] === '' || parts[1] === '') {
    return '***'
  }

  const [local, domain] = parts
  const first = Array.from(local)[0] ?? ''

  return `${first}***@${domain}`
}

const STORAGE_KEY = 'livreaura.a1.inscription.activation'

let context: EmailActivationContext | null = null

function readStoredContext(): EmailActivationContext | null {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw) as Partial<EmailActivationContext>
    if (typeof parsed.email !== 'string' || typeof parsed.emailMasque !== 'string') {
      return null
    }

    return { email: parsed.email, emailMasque: parsed.emailMasque }
  }
  catch {
    return null
  }
}

export function rememberEmailActivationContext(next: EmailActivationContext): void {
  if (!import.meta.client) {
    return
  }
  context = next
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(next))
}

export function readEmailActivationContext(): EmailActivationContext | null {
  if (!import.meta.client) {
    return null
  }
  if (context) {
    return context
  }
  context = readStoredContext()
  return context
}
