export type OrganizationSummary = {
  id: string
  raison_sociale: string
  pays: string
  ville: string | null
  statut_operationnel: string
  type: {
    id?: string
    code: string
    libelle: string
  } | null
}

export type OrganizationMembershipSummary = {
  id?: string
  principal: boolean
  statut: string
  fonction: string | null
  permissions: string[]
}

export type OrganizationFormDraft = {
  type_organisation_id: string
  raison_sociale: string
  pays: string
  ville: string
  email_pro: string
  site: string
}

export type CreateOrganizationPayload = {
  type_organisation_id: string
  raison_sociale: string
  pays: string
  ville?: string | null
  email_pro?: string | null
  site?: string | null
  organisation_id?: string
  confirmer_nouvelle?: boolean
}

export type CreateOrganizationResponse = {
  statut: string
  prochaine_action: string
  organisation: OrganizationSummary | null
  adhesion: OrganizationMembershipSummary | null
  demande: { reference: string } | null
  correspondances: OrganizationSummary[]
}

export type OrganizationMatchesResponse = {
  correspondances: OrganizationSummary[]
}

export type RepresentativeTransferParty = {
  compte_id: string
  email_masque: string | null
  prenom: string | null
  nom: string | null
  adhesion: OrganizationMembershipSummary
  sessions_revoquees: number
}

export type RepresentativeTransferResult = {
  statut: string
  prochaine_action: string
  organisation: {
    id: string
    raison_sociale: string
    statut_operationnel: string
  }
  ancien: RepresentativeTransferParty | null
  nouveau: RepresentativeTransferParty
  demande: { reference: string } | null
  depart: boolean
}

export type KnownOrganizationStatus = 'active' | 'restricted' | 'closed'

export function isKnownOrganizationStatus(value: string): value is KnownOrganizationStatus {
  return value === 'active' || value === 'restricted' || value === 'closed'
}

export function normalizeOrganizationStatus(value: string | null | undefined): KnownOrganizationStatus | 'unknown' {
  if (value && isKnownOrganizationStatus(value)) {
    return value
  }
  return 'unknown'
}
