export type ActivationPreview = {
  action: string
  destination_masquee?: string | null
  expire_a?: string | null
}

export type ActivationResult = {
  compte: {
    id: string
    statut: string
  }
  prochaine_action: string
}

export function isActivationChallengeId(value: string): boolean {
  return /^[0-9A-HJKMNPQRSTVWXYZ]{26}$/i.test(value)
}
