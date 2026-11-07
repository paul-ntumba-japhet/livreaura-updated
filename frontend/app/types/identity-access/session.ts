export type LoginPayload = {
  email: string
  password: string
}

export type LoginResult = {
  compte: {
    id: string
    statut: string
  }
  session: {
    portee: string
    expire_absolue_a?: string | null
  }
}

export function isLimitedSession(portee: string): boolean {
  return portee === 'limited'
}
