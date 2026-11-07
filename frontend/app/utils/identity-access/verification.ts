import type {
  FiscalProfileDraft,
  PayoutAccountDraft,
  RequirementSection,
  VerificationDossier,
  VerificationGap,
  VerificationRequirement,
  VerificationSubjectSummary,
} from '../../types/identity-access/verification'
import { normalizeDossierStatus, normalizeRequirementSection, normalizeVerificationLevel } from '../../types/identity-access/verification'
import { isRegistrationCountry } from './countries'

export function labelForVerificationLevel(niveau: string): string {
  switch (normalizeVerificationLevel(niveau)) {
    case 'partenaire_commercial':
      return 'Partenaire commercial'
    case 'organisation_verifiee':
      return 'Organisation vérifiée'
    default:
      return 'Niveau indisponible'
  }
}

export function labelForVerificationLevelShort(niveau: string): string {
  switch (normalizeVerificationLevel(niveau)) {
    case 'partenaire_commercial':
      return 'Commercial'
    case 'organisation_verifiee':
      return 'Organisation'
    default:
      return 'Niveau indisponible'
  }
}

export function iconForVerificationLevel(niveau: string): 'building-2' | 'user' | 'file-text' {
  switch (normalizeVerificationLevel(niveau)) {
    case 'partenaire_commercial':
      return 'user'
    case 'organisation_verifiee':
      return 'building-2'
    default:
      return 'file-text'
  }
}

export function hintForDossierStatus(statut: string | null | undefined): string {
  if (!statut) {
    return 'Constituez votre dossier.'
  }
  switch (normalizeDossierStatus(statut)) {
    case 'draft':
      return 'Poursuivez la constitution du dossier.'
    case 'submitted':
    case 'under_review':
      return 'Le dossier est entre les mains du service.'
    case 'needs_fix':
      return 'Une correction a été demandée.'
    case 'rejected':
      return 'Une nouvelle version peut être ouverte.'
    case 'approved':
      return 'L’habilitation de ce niveau a été accordée.'
    case 'expired':
      return 'Le renouvellement reprend l’éditeur du dossier.'
    default:
      return 'Le statut du dossier n’est pas reconnu. Les décisions restent bloquées.'
  }
}

export function labelForDossierStatus(statut: string): string {
  switch (normalizeDossierStatus(statut)) {
    case 'draft':
      return 'Brouillon'
    case 'submitted':
      return 'Soumis'
    case 'under_review':
      return 'En examen'
    case 'needs_fix':
      return 'Correction demandée'
    case 'rejected':
      return 'Rejeté'
    case 'approved':
      return 'Approuvé'
    case 'expired':
      return 'Expiré'
    default:
      return 'Statut indisponible'
  }
}

export function toneForDossierStatus(statut: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeDossierStatus(statut)) {
    case 'approved':
      return 'success'
    case 'submitted':
    case 'under_review':
      return 'info'
    case 'needs_fix':
    case 'expired':
      return 'warning'
    case 'rejected':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function toneForBackofficeDossierStatus(statut: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeDossierStatus(statut)) {
    case 'approved':
      return 'success'
    case 'submitted':
      return 'warning'
    case 'under_review':
      return 'info'
    case 'needs_fix':
    case 'expired':
      return 'warning'
    case 'rejected':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function iconForDossierStatus(statut: string): 'success' | 'clock' | 'info' | 'warning' | 'error' | 'minus-circle' {
  switch (normalizeDossierStatus(statut)) {
    case 'approved':
      return 'success'
    case 'submitted':
      return 'clock'
    case 'under_review':
      return 'info'
    case 'needs_fix':
      return 'warning'
    case 'rejected':
    case 'expired':
      return 'error'
    default:
      return 'minus-circle'
  }
}

function isOpenReviewStatus(statut: string): boolean {
  const normalized = normalizeDossierStatus(statut)
  return normalized === 'submitted' || normalized === 'under_review'
}

export function labelForKycQueueStatus(statut: string, overdue: boolean): string {
  if (overdue && isOpenReviewStatus(statut)) {
    return 'En retard'
  }
  return labelForDossierStatus(statut)
}

export function toneForKycQueueStatus(statut: string, overdue: boolean): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  if (overdue && isOpenReviewStatus(statut)) {
    return 'danger'
  }
  return toneForBackofficeDossierStatus(statut)
}

export function iconForKycQueueStatus(statut: string, overdue: boolean): 'success' | 'clock' | 'info' | 'warning' | 'error' | 'minus-circle' {
  if (overdue && isOpenReviewStatus(statut)) {
    return 'error'
  }
  return iconForDossierStatus(statut)
}

export function labelForRequirementSection(volet: string): string {
  switch (normalizeRequirementSection(volet)) {
    case 'identite':
      return 'Identité'
    case 'mandat':
      return 'Mandat'
    case 'fiscal':
      return 'Profil fiscal'
    case 'reversement':
      return 'Compte de reversement'
    default:
      return 'Volet indisponible'
  }
}

export function labelForRequirementCode(code: string): string {
  switch (code) {
    case 'identite_personne':
      return 'Pièce d’identité'
    case 'identite_representant':
      return 'Identité du représentant'
    case 'mandat_representant':
      return 'Mandat de représentation'
    case 'existence_ecole':
      return 'Existence de l’école'
    case 'existence_universite':
      return 'Existence de l’université'
    case 'existence_bibliotheque':
      return 'Existence de la bibliothèque'
    case 'existence_editeur':
      return 'Existence de la maison d’édition'
    case 'existence_librairie':
      return 'Existence de la librairie'
    case 'existence_association':
      return 'Existence de l’association'
    case 'profil_fiscal':
      return 'Profil fiscal'
    case 'compte_reversement':
      return 'Compte de reversement'
    default:
      return code.replaceAll('_', ' ')
  }
}

export function labelForGap(gap: VerificationGap): string {
  switch (gap.motif) {
    case 'piece_manquante':
      return `${labelForRequirementCode(gap.code)} : pièce manquante.`
    case 'profil_fiscal_manquant':
      return 'Le profil fiscal n’est pas encore enregistré.'
    case 'compte_reversement_manquant':
      return 'Le compte de reversement n’est pas encore enregistré.'
    case 'exigences_indisponibles':
      return 'Aucune exigence n’est applicable pour ce sujet, ce pays et ce niveau.'
    default:
      return `${labelForRequirementCode(gap.code)} : élément manquant.`
  }
}

export function requirementNeedsDocument(volet: string): boolean {
  const section: RequirementSection = normalizeRequirementSection(volet)
  return section === 'identite' || section === 'mandat'
}

export function documentRequirementsOf<T extends { volet: string }>(requirements: T[]): T[] {
  return requirements.filter(item => requirementNeedsDocument(item.volet))
}

export function requirementKindLabel(volet: string): string {
  return requirementNeedsDocument(volet) ? 'fichier' : 'formulaire'
}

export function requirementNeedsFiscal(volet: string): boolean {
  return normalizeRequirementSection(volet) === 'fiscal'
}

export function requirementNeedsPayout(volet: string): boolean {
  return normalizeRequirementSection(volet) === 'reversement'
}

export function isDraftDossier(statut: string): boolean {
  return normalizeDossierStatus(statut) === 'draft'
}

export function fiscalDraftIsReady(draft: FiscalProfileDraft): boolean {
  return draft.nom_legal.trim().length > 0 && isRegistrationCountry(draft.pays_fiscal)
}

export function payoutDraftIsReady(draft: PayoutAccountDraft): boolean {
  return draft.canal.trim().length > 0
    && draft.titulaire.trim().length > 0
    && isRegistrationCountry(draft.pays)
    && /^[A-Za-z]{3}$/.test(draft.devise.trim())
    && draft.reference_prestataire.trim().length > 0
    && draft.affichage_masque.trim().length > 0
}

export function fiscalDraftFingerprint(draft: FiscalProfileDraft): string {
  return JSON.stringify({
    identifiant_fiscal: draft.identifiant_fiscal.trim(),
    pays_fiscal: draft.pays_fiscal,
    nom_legal: draft.nom_legal.trim(),
  })
}

export function payoutDraftFingerprint(draft: PayoutAccountDraft): string {
  return JSON.stringify({
    canal: draft.canal.trim(),
    prestataire: draft.prestataire.trim(),
    titulaire: draft.titulaire.trim(),
    pays: draft.pays,
    devise: draft.devise.trim().toUpperCase(),
    reference_prestataire: draft.reference_prestataire.trim(),
    affichage_masque: draft.affichage_masque.trim(),
  })
}

export function labelForDossierSubject(sujet: VerificationSubjectSummary): string {
  if (sujet.type === 'organisation') {
    return sujet.raison_sociale?.trim() || 'Organisation'
  }
  if (sujet.type === 'particulier') {
    return 'Compte personnel'
  }
  return 'Sujet indisponible'
}

export function hintForDossierSubject(sujet: VerificationSubjectSummary, niveau: string): string {
  if (sujet.type === 'organisation') {
    return 'Dossier organisation vérifiée. Une correspondance de nom n’accorde aucun accès.'
  }
  if (normalizeVerificationLevel(niveau) === 'partenaire_commercial') {
    return 'Dossier partenaire commercial pour ce compte.'
  }
  return 'Dossier de vérification pour ce sujet.'
}

export function isFrozenDossier(statut: string): boolean {
  return !isDraftDossier(statut)
}

export function followUpStatusLabel(statut: string): string {
  switch (normalizeDossierStatus(statut)) {
    case 'submitted':
      return 'En attente d’examen'
    case 'under_review':
      return 'En cours d’examen'
    default:
      return labelForDossierStatus(statut)
  }
}

export function followUpStatusMessage(statut: string): string {
  switch (normalizeDossierStatus(statut)) {
    case 'submitted':
      return 'Votre dossier a été reçu. Aucune action n’est requise pour le moment.'
    case 'under_review':
      return 'Votre dossier est en cours d’examen.'
    case 'needs_fix':
      return 'Des informations supplémentaires sont nécessaires pour poursuivre la vérification.'
    case 'approved':
      return 'La vérification de votre dossier a été approuvée.'
    case 'rejected':
      return 'Votre demande de vérification n’a pas été approuvée.'
    case 'expired':
      return hintForDossierStatus(statut)
    default:
      return 'Le statut du dossier n’est pas reconnu. Les décisions restent bloquées.'
  }
}

export function canOpenSuccessorVersion(statut: string): boolean {
  const normalized = normalizeDossierStatus(statut)
  return normalized === 'needs_fix' || normalized === 'rejected' || normalized === 'expired'
}

export type WorkspaceSectionId = 'pieces' | 'fiscal' | 'reversement' | 'soumission'

export type WorkspaceSection = {
  id: WorkspaceSectionId
  label: string
  target: string
  done: number
  total: number
  complete: boolean
}

function hasGap(
  gaps: VerificationGap[],
  match: { code?: string, motif?: string, volet?: string },
): boolean {
  return gaps.some((gap) => {
    if (match.code && gap.code !== match.code) {
      return false
    }
    if (match.motif && gap.motif !== match.motif) {
      return false
    }
    if (match.volet && gap.volet !== match.volet) {
      return false
    }
    return true
  })
}

function requirementComplete(requirement: VerificationRequirement, gaps: VerificationGap[]): boolean {
  return !hasGap(gaps, { code: requirement.code })
}

export function workspaceSectionsOf(dossier: VerificationDossier): WorkspaceSection[] {
  const gaps = dossier.manques
  const sections: WorkspaceSection[] = []
  const requirements = dossier.exigences

  const documents = documentRequirementsOf(requirements)
  if (documents.length > 0) {
    const done = documents.filter(item => requirementComplete(item, gaps)).length
    sections.push({
      id: 'pieces',
      label: 'Chargement des fichiers',
      target: 'section-pieces',
      done,
      total: documents.length,
      complete: done === documents.length,
    })
  }

  if (requirements.some(item => requirementNeedsFiscal(item.volet) || item.code === 'profil_fiscal')
    || hasGap(gaps, { motif: 'profil_fiscal_manquant' })
    || hasGap(gaps, { code: 'profil_fiscal' })) {
    const complete = dossier.profil_fiscal !== null && !hasGap(gaps, { motif: 'profil_fiscal_manquant' })
    sections.push({
      id: 'fiscal',
      label: 'Profil fiscal',
      target: 'section-fiscal',
      done: complete ? 1 : 0,
      total: 1,
      complete,
    })
  }

  if (requirements.some(item => requirementNeedsPayout(item.volet) || item.code === 'compte_reversement')
    || hasGap(gaps, { motif: 'compte_reversement_manquant' })
    || hasGap(gaps, { code: 'compte_reversement' })) {
    const complete = dossier.compte_reversement !== null && !hasGap(gaps, { motif: 'compte_reversement_manquant' })
    sections.push({
      id: 'reversement',
      label: 'Compte de reversement',
      target: 'section-reversement',
      done: complete ? 1 : 0,
      total: 1,
      complete,
    })
  }

  const submitted = !isDraftDossier(dossier.statut) && normalizeDossierStatus(dossier.statut) !== 'unknown'
  sections.push({
    id: 'soumission',
    label: 'Soumettre le dossier',
    target: 'section-soumission',
    done: submitted ? 1 : 0,
    total: 1,
    complete: submitted,
  })

  return sections
}

export function workspacePreparationComplete(sections: WorkspaceSection[]): boolean {
  return sections
    .filter(section => section.id !== 'soumission')
    .every(section => section.complete)
}

export type JourneyStepStatus = 'upcoming' | 'current' | 'complete' | 'blocked'

export function journeyStepsForDossier(
  statut: string,
  preparationComplete: boolean,
): Array<{ id: string, label: string, status: JourneyStepStatus }> {
  const status = normalizeDossierStatus(statut)
  if (status === 'unknown') {
    return [
      { id: 'choix', label: 'Choix du dossier', status: 'complete' },
      { id: 'pieces', label: 'Préparation des pièces', status: 'blocked' },
      { id: 'soumettre', label: 'Soumission', status: 'blocked' },
      { id: 'traitement', label: 'Traitement', status: 'blocked' },
    ]
  }

  const draft = status === 'draft'
  const submitted = status === 'submitted' || status === 'under_review'
  const decided = status === 'approved' || status === 'needs_fix' || status === 'rejected' || status === 'expired'

  return [
    { id: 'choix', label: 'Choix du dossier', status: 'complete' },
    {
      id: 'pieces',
      label: 'Préparation des pièces',
      status: draft ? (preparationComplete ? 'complete' : 'current') : 'complete',
    },
    {
      id: 'soumettre',
      label: 'Soumission',
      status: draft ? (preparationComplete ? 'current' : 'upcoming') : 'complete',
    },
    {
      id: 'traitement',
      label: 'Traitement',
      status: draft ? 'upcoming' : (submitted ? 'current' : (decided ? 'complete' : 'upcoming')),
    },
  ]
}

export function workspaceStepperStatuses(
  sections: WorkspaceSection[],
  activeId?: string,
): Array<{ id: string, label: string, status: 'upcoming' | 'current' | 'complete' }> {
  const currentId = activeId ?? sections.find(section => !section.complete)?.id
  return sections.map((section) => {
    if (section.id === currentId) {
      return { id: section.id, label: section.label, status: 'current' }
    }
    if (section.complete) {
      return { id: section.id, label: section.label, status: 'complete' }
    }
    return { id: section.id, label: section.label, status: 'upcoming' }
  })
}

export function targetForRequirement(requirement: VerificationRequirement): string {
  if (requirementNeedsDocument(requirement.volet)) {
    return 'section-pieces'
  }
  if (requirementNeedsFiscal(requirement.volet)) {
    return 'section-fiscal'
  }
  if (requirementNeedsPayout(requirement.volet)) {
    return 'section-reversement'
  }
  return 'section-soumission'
}

export function scrollToWorkspaceTarget(target: string): void {
  document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
