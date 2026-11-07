export type AppealDepositResult = {
  statut: string
  prochaine_action: string
  reference: string
}

export type AppealAuthorizationResult = {
  statut: string
  prochaine_action: string
  demande: {
    id: string
    reference: string
    identite_etat: string
  }
  compte: {
    id: string
    statut: string
    email_masque: string | null
  }
  session: {
    portee: string
    expire_absolue_a: string | null
  }
}

export type AppealDocumentGrantResult = {
  statut: string
  prochaine_action: string
  demande: {
    id: string
    reference: string
  }
  acces: {
    id: string
    debut: string
    fin: string
    document: {
      type: string
      id: string
    }
  }
}

export const knownSupportTypes = [
  'recours_suspension',
  'recuperation_canal',
  'revendication_auteur',
  'representation',
  'transfert_principal',
  'reactivation',
  'cloture',
  'incident_technique',
  'examen_compte_conformite',
] as const
export type KnownSupportType = (typeof knownSupportTypes)[number]
export type SupportType = KnownSupportType | 'unknown'

export const knownIdentityStates = ['non_verifiee', 'verifiee', 'refusee', 'non_requise'] as const
export type KnownIdentityState = (typeof knownIdentityStates)[number]
export type IdentityState = KnownIdentityState | 'unknown'

export const knownSupportOrigins = ['demandeur', 'agent', 'systeme'] as const
export type KnownSupportOrigin = (typeof knownSupportOrigins)[number]
export type SupportOrigin = KnownSupportOrigin | 'unknown'

export const knownSupportVisibilities = ['demandeur', 'interne'] as const
export type KnownSupportVisibility = (typeof knownSupportVisibilities)[number]
export type SupportVisibility = KnownSupportVisibility | 'unknown'

export type SupportExchange = {
  id: string
  origine: string
  visibilite: string
  contenu: string
  date: string | null
  canal: string
}

export type SupportEvidence = {
  id: string
  type: string
  acces: string
  echange_id: string | null
  depose_a: string | null
}

export type SupportDemandCapabilities = {
  repondre: boolean
  deposer: boolean
  notes_internes: boolean
  consulter_internes: boolean
}

export type SupportDemand = {
  demande: {
    id: string
    reference: string
    type: string
    identite_etat: string
    clos_a: string | null
  }
  echanges: SupportExchange[]
  pieces: SupportEvidence[]
  capacites: SupportDemandCapabilities
}

export function isKnownSupportType(value: string): value is KnownSupportType {
  return (knownSupportTypes as readonly string[]).includes(value)
}

export function normalizeSupportType(value: string | null | undefined): SupportType {
  return value && isKnownSupportType(value) ? value : 'unknown'
}

export function isKnownIdentityState(value: string): value is KnownIdentityState {
  return (knownIdentityStates as readonly string[]).includes(value)
}

export function normalizeIdentityState(value: string | null | undefined): IdentityState {
  return value && isKnownIdentityState(value) ? value : 'unknown'
}

export function isKnownSupportOrigin(value: string): value is KnownSupportOrigin {
  return (knownSupportOrigins as readonly string[]).includes(value)
}

export function normalizeSupportOrigin(value: string | null | undefined): SupportOrigin {
  return value && isKnownSupportOrigin(value) ? value : 'unknown'
}

export function isKnownSupportVisibility(value: string): value is KnownSupportVisibility {
  return (knownSupportVisibilities as readonly string[]).includes(value)
}

export function normalizeSupportVisibility(value: string | null | undefined): SupportVisibility {
  return value && isKnownSupportVisibility(value) ? value : 'unknown'
}
