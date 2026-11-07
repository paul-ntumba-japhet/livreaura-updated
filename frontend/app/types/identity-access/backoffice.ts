import type { VerificationDossier } from './verification'

export const knownTaskStates = [
  'a_affecter',
  'en_cours',
  'attente_demandeur',
  'attente_tiers',
  'terminee',
] as const
export type KnownTaskState = (typeof knownTaskStates)[number]
export type TaskWorkState = KnownTaskState | 'unknown'

export const knownTaskPriorities = ['normale', 'urgente'] as const
export type KnownTaskPriority = (typeof knownTaskPriorities)[number]
export type TaskPriority = KnownTaskPriority | 'unknown'

export const knownControlResults = ['pending', 'passed', 'failed', 'not_applicable'] as const
export type KnownControlResult = (typeof knownControlResults)[number]
export type ControlResult = KnownControlResult | 'unknown'

export const knownDecisionResults = ['approved', 'needs_fix', 'rejected'] as const
export type KnownDecisionResult = (typeof knownDecisionResults)[number]
export type DecisionResult = KnownDecisionResult | 'unknown'

export const knownCapabilityStates = ['valid', 'a_renouveler', 'restreint', 'expired', 'revoked'] as const
export type KnownCapabilityState = (typeof knownCapabilityStates)[number]
export type CapabilityState = KnownCapabilityState | 'unknown'

export type BackofficeTask = {
  id: string
  etat_travail: string
  priorite: string
  motif_priorite: string | null
  responsable_id: string | null
  cree_a: string | null
  affecte_a: string | null
  premiere_prise_a: string | null
  echeance_prise_a: string | null
  echeance_reponse_a: string | null
  clos_a: string | null
}

export type BackofficeControl = {
  id: string
  resultat: string
  observation: string | null
  justification_non_applicable: string | null
  controle_a: string | null
  exigence: {
    id: string
    code: string
    volet: string
    obligatoire: boolean
    autorise_non_applicable: boolean
  } | null
}

export type BackofficeDecision = {
  id: string
  resultat: string
  motif: string
  decide_a: string | null
}

export type BackofficeCapability = {
  id: string
  niveau: string
  etat: string
  obtenue_a: string | null
  valide_jusqua: string | null
  motif_restriction?: string | null
}

export type BackofficeReviewCapabilities = {
  affecter: boolean
  prendre_en_charge: boolean
  controler: boolean
  decider: boolean
  restreindre: boolean
  consulter_pieces: boolean
}

export type BackofficeTaskRowCapabilities = {
  consulter: boolean
  affecter: boolean
  prendre_en_charge: boolean
}

export type BackofficeTaskRow = {
  tache: BackofficeTask
  dossier: VerificationDossier
  conflit_interet: boolean
  capacites: BackofficeTaskRowCapabilities
}

export type BackofficeTaskList = {
  taches: BackofficeTaskRow[]
  capacites: {
    affecter: boolean
    controler: boolean
    decider: boolean
  }
}

export type BackofficeReview = {
  dossier: VerificationDossier
  controles: BackofficeControl[]
  tache: BackofficeTask | null
  decision: BackofficeDecision | null
  habilitation: BackofficeCapability | null
  conflit_interet: boolean
  niveau_decision: 'organisation_verifiee' | 'partenaire_commercial' | 'hors_perimetre' | string
  capacites: BackofficeReviewCapabilities
  statut?: string
  prochaine_action?: string
}

export function isKnownTaskState(value: string): value is KnownTaskState {
  return (knownTaskStates as readonly string[]).includes(value)
}

export function normalizeTaskState(value: string | null | undefined): TaskWorkState {
  return value && isKnownTaskState(value) ? value : 'unknown'
}

export function isKnownTaskPriority(value: string): value is KnownTaskPriority {
  return (knownTaskPriorities as readonly string[]).includes(value)
}

export function normalizeTaskPriority(value: string | null | undefined): TaskPriority {
  return value && isKnownTaskPriority(value) ? value : 'unknown'
}

export function isKnownControlResult(value: string): value is KnownControlResult {
  return (knownControlResults as readonly string[]).includes(value)
}

export function normalizeControlResult(value: string | null | undefined): ControlResult {
  return value && isKnownControlResult(value) ? value : 'unknown'
}

export function isKnownDecisionResult(value: string): value is KnownDecisionResult {
  return (knownDecisionResults as readonly string[]).includes(value)
}

export function normalizeDecisionResult(value: string | null | undefined): DecisionResult {
  return value && isKnownDecisionResult(value) ? value : 'unknown'
}

export type RestrictedOrganization = {
  id: string
  raison_sociale: string
  statut_operationnel: string
}

export type AccountSuspensionResult = {
  statut: string
  prochaine_action: string
  propagation: string
  compte: {
    id: string
    statut: string
    email_masque: string | null
    prenom: string | null
    nom: string | null
  }
  sessions_revoquees: number
  fin_prevue: string | null
  organisations_restreintes: RestrictedOrganization[]
}

export type AccountReactivationConditions = {
  email_verifie: boolean
  profil_complet: boolean
  textes_requis: boolean
}

export type AccountReactivationResult = {
  statut: string
  prochaine_action: string
  propagation: string
  compte: {
    id: string
    statut: string
    email_masque: string | null
    prenom: string | null
    nom: string | null
  }
  demande: {
    id: string
    reference: string
  }
  sessions_revoquees: number
  conditions: AccountReactivationConditions
  avis_securite_consigne: boolean
}

export type AccountClosureResult = {
  statut: string
  prochaine_action: string
  propagation: string
  compte: {
    id: string
    statut: string
    ferme_a: string | null
    email_masque: string | null
    prenom: string | null
    nom: string | null
  }
  demande: {
    id: string
    reference: string
  }
  sessions_revoquees: number
  defis_revoques: number
}

export type ClosureIdentityResult = {
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
}

export const knownAttributionStates = ['a_venir', 'effective', 'expiree', 'revoquee'] as const
export type KnownAttributionState = (typeof knownAttributionStates)[number]
export type AttributionState = KnownAttributionState | 'unknown'

export type InternalPermissionOption = {
  code: string
  libelle: string
}

export type InternalAttribution = {
  id: string
  permission: string
  libelle: string
  etat: string
  attribue_a: string | null
  expire_a: string | null
  revoque_a: string | null
  motif: string
}

export type InternalPermissionCatalog = {
  permissions: InternalPermissionOption[]
  compte: { id: string, statut: string } | null
  attributions: InternalAttribution[]
}

export type InternalPermissionMutationResult = {
  statut: string
  prochaine_action: string
  compte_id: string
  attribution: InternalAttribution
}

export const knownConfigurationCodes = [
  'A1_acces',
  'A1_delais',
  'A1_calendrier',
  'A1_regles_suspension',
] as const
export type KnownConfigurationCode = (typeof knownConfigurationCodes)[number]

export type ServiceConfigurationVersion = {
  id: string
  code: string
  version: number
  schema_version: number
  valeurs: Record<string, unknown>
  empreinte: string
  effet_a: string | null
  approuve_a: string | null
  courante?: boolean
}

export type ServiceConfigurationGroup = {
  code: string
  courante: ServiceConfigurationVersion | null
  versions: ServiceConfigurationVersion[]
}

export type ServiceConfigurationCatalog = {
  configurations: ServiceConfigurationGroup[]
}

export type ServiceConfigurationMutationResult = {
  statut: string
  prochaine_action: string
  configuration: ServiceConfigurationVersion
  courante: boolean
}

export const knownSubstituteStates = ['a_venir', 'en_vigueur', 'terminee', 'annulee'] as const
export type KnownSubstituteState = (typeof knownSubstituteStates)[number]
export type SubstituteState = KnownSubstituteState | 'unknown'

export type ServiceSubstitute = {
  id: string
  titulaire_id: string
  suppleant_id: string
  fonction: string
  debut_a: string | null
  fin_a: string | null
  etat: string
  motif: string
  permission_implicite: boolean
}

export type ServiceSubstituteCatalog = {
  suppleances: ServiceSubstitute[]
}

export type ServiceSubstituteMutationResult = {
  statut: string
  prochaine_action: string
  suppleance: ServiceSubstitute
}

export const knownTransmissionStates = [
  'queued',
  'processing',
  'retry_wait',
  'sent',
  'acked',
  'failed',
  'cancelled',
] as const
export type KnownTransmissionState = (typeof knownTransmissionStates)[number]
export type TransmissionState = KnownTransmissionState | 'unknown'

export const knownAcknowledgementLevels = ['recu_durable', 'engage_local', 'effet_confirme'] as const
export type KnownAcknowledgementLevel = (typeof knownAcknowledgementLevels)[number]
export type AcknowledgementLevel = KnownAcknowledgementLevel | 'unknown'

export type RetryableTransmission = {
  id: string
  etat: string
  canal: string
  type: string | null
  operation: string | null
  destination_masquee: string | null
  nombre_tentatives: number
  prochaine_tentative_a: string | null
  niveau_acquittement: string | null
  envoye_a: string | null
}

export type RetryableTransmissionCatalog = {
  transmissions: RetryableTransmission[]
}

export type RetryTransmissionResult = {
  statut: string
  prochaine_action: string
  transmission: RetryableTransmission
}

export type BackofficeAccountRow = {
  id: string
  statut: string
  email_masque: string
  email_verifie: boolean
  prenom: string | null
  nom: string | null
  roles: string[]
  organisation: string | null
  derniere_connexion_a: string | null
}

export type BackofficeAccountTotals = {
  total: number
  active: number
  pending: number
  suspended: number
  closed: number
}

export type BackofficeAccountPagination = {
  page: number
  per_page: number
  total: number
  last_page: number
}

export type BackofficeAccountList = {
  totaux: BackofficeAccountTotals
  pagination: BackofficeAccountPagination
  comptes: BackofficeAccountRow[]
}

export type BackofficeAccountFilters = {
  q?: string
  statut?: string
  role?: string
  email_verifie?: string
  page?: number
  per_page?: number
}

export type BackofficeOrganizationTab = 'organisations' | 'representation' | 'transfert'

export type BackofficeOrganizationType = {
  id: string
  code: string
  libelle: string
}

export type BackofficeOrganizationPerson = {
  id: string
  prenom: string | null
  nom: string | null
  email_masque?: string | null
}

export type BackofficeOrganizationRow = {
  id: string
  raison_sociale: string
  ville: string | null
  pays: string | null
  statut_operationnel: string
  type: BackofficeOrganizationType | null
  representant: BackofficeOrganizationPerson | null
  representation: string | null
  verification: string
}

export type BackofficeOrganizationDemand = {
  id: string
  reference: string
  type: string
  cree_a: string | null
  organisation: {
    id: string
    raison_sociale: string
    ville: string | null
    pays: string | null
    type: BackofficeOrganizationType | null
  } | null
  demandeur: BackofficeOrganizationPerson | null
}

export type BackofficeOrganizationTotals = {
  organisations: number
  verifiees: number
  rattachements: number
  transferts: number
}

export type BackofficeOrganizationList = {
  onglet: BackofficeOrganizationTab
  totaux: BackofficeOrganizationTotals
  types: BackofficeOrganizationType[]
  pagination: BackofficeAccountPagination
  organisations: BackofficeOrganizationRow[]
  demandes: BackofficeOrganizationDemand[]
}

export type BackofficeOrganizationFilters = {
  q?: string
  onglet?: BackofficeOrganizationTab
  type_organisation_id?: string
  verification?: string
  page?: number
  per_page?: number
}

export type BackofficeKycTab = 'a_traiter' | 'mes_dossiers' | 'a_corriger' | 'historique'

export type BackofficeKycSubject = {
  type: 'organisation' | 'particulier' | 'unknown'
  libelle: string
}

export type BackofficeKycRow = {
  id: string
  numero: string
  version: number
  niveau: string
  statut: string
  sujet: BackofficeKycSubject
  tache: BackofficeTask | null
  responsable: BackofficeOrganizationPerson | null
  echeance_a: string | null
  clos_a: string | null
  conflit_interet: boolean
}

export type BackofficeKycTotals = {
  a_traiter: number
  non_affectes: number
  en_retard: number
  a_corriger: number
  mes_dossiers: number
  historique: number
}

export type BackofficeKycList = {
  onglet: BackofficeKycTab
  totaux: BackofficeKycTotals
  responsables: BackofficeOrganizationPerson[]
  pagination: BackofficeAccountPagination
  dossiers: BackofficeKycRow[]
}

export type BackofficeKycFilters = {
  q?: string
  onglet?: BackofficeKycTab
  niveau?: string
  responsable_id?: string
  page?: number
  per_page?: number
}

export type BackofficeCapabilityTab = 'toutes' | 'valides' | 'restreintes' | 'expirees' | 'revoquees'

export type BackofficeCapabilityBeneficiary = {
  type: 'organisation' | 'particulier' | 'unknown'
  libelle: string
}

export type BackofficeCapabilityRenewal = {
  code: 'en_examen' | 'a_corriger' | 'a_anticiper' | 'non_demande' | 'aucun' | 'unknown'
  jours_restants: number | null
  dossier_id: string | null
}

export type BackofficeCapabilityRow = {
  id: string
  niveau: string
  etat: string
  valide_jusqua: string | null
  obtenue_a: string | null
  dossier_id: string | null
  beneficiaire: BackofficeCapabilityBeneficiary
  renouvellement: BackofficeCapabilityRenewal
}

export type BackofficeCapabilityTotals = {
  toutes: number
  valides: number
  expirent_30j: number
  restreintes: number
  expirees: number
  revoquees: number
}

export type BackofficeCapabilityList = {
  onglet: BackofficeCapabilityTab
  totaux: BackofficeCapabilityTotals
  pagination: BackofficeAccountPagination
  habilitations: BackofficeCapabilityRow[]
}

export type BackofficeCapabilityFilters = {
  q?: string
  onglet?: BackofficeCapabilityTab
  niveau?: string
  echeance?: string
  page?: number
  per_page?: number
}

export type BackofficeSupportTab = 'a_traiter' | 'mes_demandes' | 'attente_demandeur' | 'cloturees'

export type BackofficeSupportRequester = {
  id: string | null
  prenom: string | null
  nom: string | null
  email_masque: string | null
  externe: boolean
}

export type BackofficeSupportRow = {
  id: string
  reference: string
  type: string
  identite_etat: string
  cree_a: string | null
  clos_a: string | null
  demandeur: BackofficeSupportRequester
  tache: BackofficeTask | null
  responsable: BackofficeOrganizationPerson | null
  echeance_a: string | null
}

export type BackofficeSupportTotals = {
  ouvertes: number
  a_traiter: number
  non_affectes: number
  priorite_haute: number
  reponses_en_retard: number
  mes_demandes: number
  attente_demandeur: number
  cloturees: number
}

export type BackofficeSupportList = {
  onglet: BackofficeSupportTab
  totaux: BackofficeSupportTotals
  pagination: BackofficeAccountPagination
  demandes: BackofficeSupportRow[]
}

export type BackofficeSupportFilters = {
  q?: string
  onglet?: BackofficeSupportTab
  type?: string
  priorite?: string
  page?: number
  per_page?: number
}

export type BackofficeTeamTab = 'equipe' | 'permissions' | 'suppleances' | 'parametres'

export type BackofficeTeamAgent = {
  id: string
  prenom: string | null
  nom: string | null
  email_masque: string | null
  permissions: string[]
  permissions_actives: number
  expire_a: string | null
}

export type BackofficeTeamSubstitute = {
  id: string
  fonction: string
  debut_a: string | null
  fin_a: string | null
  titulaire: BackofficeOrganizationPerson | null
  suppleant: BackofficeOrganizationPerson | null
}

export type BackofficeTeamConfiguration = {
  code: string
  version: number | null
  courante: boolean
}

export type BackofficeTeamTotals = {
  agents_actifs: number
  attributions_actives: number
  suppleances_planifiees: number
  configurations_a_approuver: number
}

export type BackofficeTeamCapabilities = {
  permissions: boolean
  suppleances: boolean
  configurations: boolean
}

export type BackofficeTeamList = {
  totaux: BackofficeTeamTotals
  pagination: BackofficeAccountPagination
  agents: BackofficeTeamAgent[]
  suppleances_a_venir: BackofficeTeamSubstitute[]
  configurations: BackofficeTeamConfiguration[]
  capacites: BackofficeTeamCapabilities
}

export type BackofficeTeamFilters = {
  q?: string
  fonction?: string
  page?: number
  per_page?: number
}

export type BackofficeSupervisionTab = 'supervision' | 'journal' | 'reprises'

export type SupervisionAttempt = {
  numero: number
  resultat: string
  commence_a: string | null
  termine_a: string | null
  code_erreur_masque: string | null
}

export type SupervisionTransmission = RetryableTransmission & {
  reference: string
  derniere_tentative_a: string | null
  eligible_reprise: boolean
  tentatives: SupervisionAttempt[]
}

export type SupervisionJournalEntry = {
  id: string
  source: 'compte' | 'kyc'
  type: string
  date: string | null
  auteur: BackofficeOrganizationPerson | null
  systeme: boolean
  reference: string
  sujet: BackofficeOrganizationPerson | null
}

export type SupervisionRetryEntry = {
  id: string
  type: string
  date: string | null
  transmission_id: string | null
  etat: string | null
  canal: string | null
  destination_masquee: string | null
  reference: string
}

export type SupervisionTotals = {
  echecs: number
  attente_accuse: number
  messages_en_attente: number
  evenements_aujourdhui: number
}

export type BackofficeSupervisionList = {
  onglet: BackofficeSupervisionTab
  totaux: SupervisionTotals
  pagination: BackofficeAccountPagination
  journal_recent: SupervisionJournalEntry[]
  capacites: { reprendre: boolean }
  transmissions: SupervisionTransmission[]
  evenements: SupervisionJournalEntry[]
  reprises: SupervisionRetryEntry[]
}

export type BackofficeSupervisionFilters = {
  q?: string
  onglet?: BackofficeSupervisionTab
  canal?: string
  etat?: string
  jour?: string
  page?: number
  per_page?: number
}

export function isKnownCapabilityState(value: string): value is KnownCapabilityState {
  return (knownCapabilityStates as readonly string[]).includes(value)
}

export function normalizeCapabilityState(value: string | null | undefined): CapabilityState {
  return value && isKnownCapabilityState(value) ? value : 'unknown'
}

export function isKnownAttributionState(value: string): value is KnownAttributionState {
  return (knownAttributionStates as readonly string[]).includes(value)
}

export function normalizeAttributionState(value: string | null | undefined): AttributionState {
  return value && isKnownAttributionState(value) ? value : 'unknown'
}
