export const knownAccountStatuses = ['pending', 'active', 'suspended', 'closed'] as const
export type KnownAccountStatus = (typeof knownAccountStatuses)[number]
export type AccountStatus = KnownAccountStatus | 'unknown'

export const knownAccessScopes = ['normal', 'limited', 'support_only'] as const
export type KnownAccessScope = (typeof knownAccessScopes)[number]
export type AccessScope = KnownAccessScope | 'unknown'

export type AccountProfile = {
  prenom: string | null
  nom: string | null
  nom_plume: string | null
  pays: string | null
  ville: string | null
}

export const knownSessionStatuses = ['active', 'expired', 'revoked'] as const
export type KnownSessionStatus = (typeof knownSessionStatuses)[number]
export type SessionStatus = KnownSessionStatus | 'unknown'

export type AccountSession = {
  id: string
  portee: string
  expire_absolue_a?: string | null
  expire_inactivite_a?: string | null
}

export type OwnAuthenticationSession = AccountSession & {
  courante: boolean
  statut: string
  ouverte_a?: string | null
  derniere_activite_a?: string | null
  contexte?: string | null
}

export type PendingLegalText = {
  id: string
  type: string
  version: string
  langue: string
  requis_pour_activation: boolean
}

export type AccountConsentNature = 'acceptation' | 'prise_connaissance' | 'consentement_optionnel'
export type AccountConsentAction = 'acceptation' | 'retrait'

export type AccountConsent = {
  id: string
  texte_version_id: string
  type?: string | null
  version?: string | null
  langue?: string | null
  nature: string
  accepte_a?: string | null
  retire_a?: string | null
}

export type ChangePasswordRequest = {
  password_actuel: string
  password: string
}

export type ChangePasswordResponse = {
  prochaine_action: 'connexion' | string
}

export const knownAuthorPathStates = ['non_active', 'active', 'revendication_en_examen'] as const
export type KnownAuthorPathState = (typeof knownAuthorPathStates)[number]
export type AuthorPathState = KnownAuthorPathState | 'unknown'

export type AuthorFiche = {
  id: string
  nom_affiche: string
  statut_publication: string
}

export type AuthorPathDemand = {
  reference: string
}

export type AuthorPath = {
  etat: string
  fiche: AuthorFiche | null
  demande: AuthorPathDemand | null
}

export type AccountOrganizationMembership = {
  id: string
  principal: boolean
  statut: string
  fonction: string | null
  permissions: string[]
}

export type AccountOrganizationType = {
  id?: string
  code: string
  libelle: string
}

export type AccountOrganization = {
  id: string
  raison_sociale: string
  statut_operationnel: string
  pays: string
  ville: string | null
  type: AccountOrganizationType | null
  adhesion: AccountOrganizationMembership
  demande?: { reference: string } | null
  exigences?: import('./registration').OrganizationRequirementDto[]
}

export type EnableAuthorPathPayload = {
  auteur_id?: string
}

export type EnableAuthorPathResponse = {
  statut: 'active' | 'examen' | string
  prochaine_action: 'compte' | 'attente_examen' | string
  fiche: AuthorFiche | null
  demande: AuthorPathDemand | null
}

export type AccountVerificationDossier = {
  id: string
  numero: string
  version: number
  niveau: string
  statut: string
  sujet: {
    type: string
    organisation_id: string | null
    raison_sociale: string | null
  }
  habilitation?: {
    id: string
    niveau: string
    etat: string
    obtenue_a: string | null
    valide_jusqua: string | null
    motif_restriction: string | null
  } | null
}

export type RecordConsentRequest = {
  texte_version_id: string
  action?: AccountConsentAction
  nature?: AccountConsentNature
}

export type RecordConsentResponse = {
  consentement: AccountConsent
  consentements: AccountConsent[]
  textes_en_attente: PendingLegalText[]
}

export type CurrentAccount = {
  compte: {
    id: string
    statut: string
    email_masque: string
    locale: string
  }
  profil: AccountProfile | null
  roles: string[]
  session: AccountSession | null
  consentements: AccountConsent[]
  textes_en_attente: PendingLegalText[]
  parcours_auteur: AuthorPath
  parcours_inscription?: 'lecteur' | 'auteur' | 'representant' | string | null
  organisations: AccountOrganization[]
  dossiers_verification: AccountVerificationDossier[]
  permissions_internes: string[]
  recours?: {
    reference: string
    type: string
    identite_etat: string
  } | null
}

export function isKnownAccountStatus(value: string): value is KnownAccountStatus {
  return (knownAccountStatuses as readonly string[]).includes(value)
}

export function isKnownAccessScope(value: string): value is KnownAccessScope {
  return (knownAccessScopes as readonly string[]).includes(value)
}

export function normalizeAccountStatus(value: string | null | undefined): AccountStatus {
  if (value && isKnownAccountStatus(value)) {
    return value
  }
  return 'unknown'
}

export function normalizeAccessScope(value: string | null | undefined): AccessScope {
  if (value && isKnownAccessScope(value)) {
    return value
  }
  return 'unknown'
}

export function isKnownAuthorPathState(value: string): value is KnownAuthorPathState {
  return (knownAuthorPathStates as readonly string[]).includes(value)
}

export function normalizeAuthorPathState(value: string | null | undefined): AuthorPathState {
  if (value && isKnownAuthorPathState(value)) {
    return value
  }
  return 'unknown'
}
