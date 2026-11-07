export type ResendChallengeResult = {
  statut: string
}

/** Délai d’attente UI aligné sur le quota serveur A1-03a (60 s entre deux renvois). */
export const resendWaitMs = 60_000
