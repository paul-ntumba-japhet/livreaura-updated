export type RecoveryRequestResult = {
  statut: string
}

export type RecoveryProofPreview = {
  formulaire: string
  defi_id: string
  expire_a?: string | null
  destination_masquee: string
}

export type RecoveryConfirmResult = {
  prochaine_action: string
}

/** Délai d’attente UI aligné sur le quota serveur A1-05 (60 s entre deux demandes). */
export const recoveryWaitMs = 60_000

export function isRecoveryChallengeId(value: string): boolean {
  return /^[0-9A-HJKMNPQRSTVWXYZ]{26}$/i.test(value)
}
