export const knownVerificationLevels = ['organisation_verifiee', 'partenaire_commercial'] as const
export type KnownVerificationLevel = (typeof knownVerificationLevels)[number]
export type VerificationLevel = KnownVerificationLevel | 'unknown'

export const knownDossierStatuses = [
  'draft',
  'submitted',
  'under_review',
  'needs_fix',
  'rejected',
  'approved',
  'expired',
] as const
export type KnownDossierStatus = (typeof knownDossierStatuses)[number]
export type DossierStatus = KnownDossierStatus | 'unknown'

export const knownRequirementSections = ['identite', 'mandat', 'fiscal', 'reversement'] as const
export type KnownRequirementSection = (typeof knownRequirementSections)[number]
export type RequirementSection = KnownRequirementSection | 'unknown'

export type VerificationSubjectSummary = {
  id?: string | null
  type: 'particulier' | 'organisation' | 'unknown'
  organisation_id: string | null
  raison_sociale: string | null
}

export type VerificationCapabilitySummary = {
  id: string
  niveau: string
  etat: string
  obtenue_a: string | null
  valide_jusqua: string | null
  motif_restriction: string | null
}

export type VerificationDossierSummary = {
  id: string
  numero: string
  version: number
  niveau: string
  statut: string
  sujet: VerificationSubjectSummary
  habilitation?: VerificationCapabilitySummary | null
}

export type VerificationRequirement = {
  id: string
  code: string
  version_regles: string
  volet: string
  nature_preuve: string
  obligatoire: boolean
  autorise_non_applicable: boolean
}

export type VerificationDocument = {
  id: string
  type_doc: string
  statut_verification: string
  depose_a: string | null
  numero: string | null
  autorite: string | null
}

export type FiscalProfile = {
  version: number
  identifiant_fiscal: string | null
  pays_fiscal: string
  nom_legal: string
  statut_controle: string
}

export type PayoutAccount = {
  version: number
  canal: string
  prestataire: string | null
  titulaire: string
  pays: string
  devise: string
  affichage_masque: string
  statut_controle: string
}

export type VerificationGap = {
  code: string
  volet: string
  motif: string
}

export type VerificationDossier = VerificationDossierSummary & {
  soumis_a: string | null
  exigences: VerificationRequirement[]
  pieces: VerificationDocument[]
  profil_fiscal: FiscalProfile | null
  compte_reversement: PayoutAccount | null
  manques: VerificationGap[]
  complet: boolean
}

export type FiscalProfileDraft = {
  identifiant_fiscal: string
  pays_fiscal: string
  nom_legal: string
}

export type PayoutAccountDraft = {
  canal: string
  prestataire: string
  titulaire: string
  pays: string
  devise: string
  reference_prestataire: string
  affichage_masque: string
}

export type OpenVerificationDossierPayload = {
  niveau: KnownVerificationLevel
  organisation_id?: string
}

export type OpenVerificationDossierResponse = {
  statut: string
  prochaine_action: string
  dossier: VerificationDossier
}

export type SaveVerificationDraftPayload = {
  profil_fiscal?: FiscalProfileDraft | null
  compte_reversement?: PayoutAccountDraft | null
}

export type SaveVerificationDraftResponse = {
  statut: string
  prochaine_action: string
  dossier: VerificationDossier
}

export type AttachVerificationEvidenceResponse = {
  statut: string
  prochaine_action: string
  piece: VerificationDocument
  dossier: VerificationDossier
}

export type SubmitVerificationDossierResponse = {
  statut: string
  prochaine_action: string
  dossier: VerificationDossier
  manques: VerificationGap[]
}

export type VerificationSubjectOption = {
  id: string
  kind: 'particulier' | 'organisation'
  label: string
  description: string
  niveau: KnownVerificationLevel
  organisation_id?: string
  disabled?: boolean
  disabledReason?: string
}

export function isKnownVerificationLevel(value: string): value is KnownVerificationLevel {
  return (knownVerificationLevels as readonly string[]).includes(value)
}

export function normalizeVerificationLevel(value: string | null | undefined): VerificationLevel {
  if (value && isKnownVerificationLevel(value)) {
    return value
  }
  return 'unknown'
}

export function isKnownDossierStatus(value: string): value is KnownDossierStatus {
  return (knownDossierStatuses as readonly string[]).includes(value)
}

export function normalizeDossierStatus(value: string | null | undefined): DossierStatus {
  if (value && isKnownDossierStatus(value)) {
    return value
  }
  return 'unknown'
}

export function isKnownRequirementSection(value: string): value is KnownRequirementSection {
  return (knownRequirementSections as readonly string[]).includes(value)
}

export function normalizeRequirementSection(value: string | null | undefined): RequirementSection {
  if (value && isKnownRequirementSection(value)) {
    return value
  }
  return 'unknown'
}
