import type { UiIconName } from '../../components/ui/icons'
import type { BackofficeTask, BackofficeTaskRow } from '../../types/identity-access/backoffice'
import type { VerificationDossier } from '../../types/identity-access/verification'
import {
  normalizeAttributionState,
  normalizeCapabilityState,
  normalizeControlResult,
  normalizeDecisionResult,
  normalizeTaskPriority,
  normalizeTaskState,
} from '../../types/identity-access/backoffice'
import { formatActivationExpiry } from './email-activation'

export function labelForTaskState(etat: string): string {
  switch (normalizeTaskState(etat)) {
    case 'a_affecter':
      return 'À affecter'
    case 'en_cours':
      return 'En cours'
    case 'attente_demandeur':
      return 'Attente du demandeur'
    case 'attente_tiers':
      return 'Attente d’un tiers'
    case 'terminee':
      return 'Terminée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForTaskState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeTaskState(etat)) {
    case 'en_cours':
      return 'info'
    case 'a_affecter':
      return 'warning'
    case 'terminee':
      return 'success'
    case 'attente_demandeur':
    case 'attente_tiers':
      return 'warning'
    default:
      return 'neutral'
  }
}

export function labelForTaskPriority(priorite: string): string {
  switch (normalizeTaskPriority(priorite)) {
    case 'urgente':
      return 'Urgente'
    case 'normale':
      return 'Normale'
    default:
      return 'Priorité indisponible'
  }
}

export function labelForControlResult(resultat: string): string {
  switch (normalizeControlResult(resultat)) {
    case 'pending':
      return 'Non contrôlé'
    case 'passed':
      return 'Consigné : satisfait'
    case 'failed':
      return 'Consigné : non satisfait'
    case 'not_applicable':
      return 'Non applicable'
    default:
      return 'Résultat indisponible'
  }
}

export function toneForControlResult(resultat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeControlResult(resultat)) {
    case 'passed':
      return 'success'
    case 'failed':
      return 'danger'
    case 'not_applicable':
      return 'warning'
    case 'pending':
      return 'info'
    default:
      return 'neutral'
  }
}

export function labelForDecisionResult(resultat: string): string {
  switch (normalizeDecisionResult(resultat)) {
    case 'approved':
      return 'Approuvé'
    case 'needs_fix':
      return 'Correction demandée'
    case 'rejected':
      return 'Rejeté'
    default:
      return 'Décision indisponible'
  }
}

export function labelForCapabilityState(etat: string): string {
  switch (normalizeCapabilityState(etat)) {
    case 'valid':
      return 'Valide'
    case 'a_renouveler':
      return 'À renouveler'
    case 'restreint':
      return 'Restreinte'
    case 'expired':
      return 'Expirée'
    case 'revoked':
      return 'Révoquée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForCapabilityState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeCapabilityState(etat)) {
    case 'valid':
      return 'success'
    case 'a_renouveler':
    case 'restreint':
      return 'warning'
    case 'expired':
      return 'danger'
    case 'revoked':
      return 'neutral'
    default:
      return 'neutral'
  }
}

export function iconForCapabilityState(etat: string): UiIconName {
  switch (normalizeCapabilityState(etat)) {
    case 'valid':
      return 'success'
    case 'a_renouveler':
      return 'warning'
    case 'restreint':
      return 'lock'
    case 'expired':
      return 'error'
    case 'revoked':
      return 'minus-circle'
    default:
      return 'info'
  }
}

export function deadlineKind(iso?: string | null): 'none' | 'upcoming' | 'soon' | 'overdue' {
  if (!iso) {
    return 'none'
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return 'none'
  }
  const diff = date.getTime() - Date.now()
  if (diff < 0) {
    return 'overdue'
  }
  if (diff <= 24 * 60 * 60 * 1000) {
    return 'soon'
  }
  return 'upcoming'
}

export function labelForDeadline(iso?: string | null): string {
  const formatted = formatActivationExpiry(iso)
  if (!formatted) {
    return 'Aucune échéance communiquée'
  }
  switch (deadlineKind(iso)) {
    case 'overdue':
      return `Échéance dépassée · ${formatted}`
    case 'soon':
      return `Échéance proche · ${formatted}`
    default:
      return formatted
  }
}

function startOfLocalDay(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()
}

function formatFrenchHour(date: Date): string {
  const hours = date.getHours()
  const minutes = date.getMinutes()
  if (minutes === 0) {
    return `${hours} h`
  }
  return `${hours} h ${String(minutes).padStart(2, '0')}`
}

export function formatLastSeen(iso?: string | null): string {
  if (!iso) {
    return 'Jamais'
  }

  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return 'Jamais'
  }

  const dayDiff = Math.round((startOfLocalDay(date) - startOfLocalDay(new Date())) / 86_400_000)
  const time = formatFrenchHour(date)

  if (dayDiff === 0) {
    return `Aujourd’hui, ${time}`
  }
  if (dayDiff === -1) {
    return `Hier, ${time}`
  }
  if (dayDiff === 1) {
    return `Demain, ${time}`
  }

  const day = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
  }).format(date)

  return `${day}, ${time}`
}

export function formatTeamValidity(iso?: string | null): string {
  if (!iso) {
    return 'Sans échéance'
  }
  const formatted = formatFrenchDay(iso)
  if (formatted === '—') {
    return 'Sans échéance'
  }
  return `Jusqu’au ${formatted}`
}

export const teamFunctionOptions = [
  { value: '', label: 'Tous les rôles' },
  { value: 'kyc', label: 'Vérification KYC' },
  { value: 'commercial', label: 'Vérification commerciale' },
  { value: 'support', label: 'Support' },
  { value: 'exploitation', label: 'Exploitation technique' },
] as const

const teamFunctionPermissions: Record<string, string[]> = {
  kyc: ['controler_kyc', 'decider_kyc', 'restreindre_habilitation'],
  commercial: ['decider_kyc_commercial'],
  support: ['traiter_support', 'autoriser_recours'],
  exploitation: ['configurer_service', 'reprendre_transmissions', 'affecter_taches'],
}

export function labelForTeamFunction(permissions: string[]): string {
  const codes = new Set(permissions)
  const order = ['kyc', 'commercial', 'support', 'exploitation'] as const
  for (const key of order) {
    if (teamFunctionPermissions[key].some(code => codes.has(code))) {
      return teamFunctionOptions.find(option => option.value === key)?.label || 'Fonction interne'
    }
  }
  return permissions.length > 0 ? 'Fonction interne' : 'Aucune permission courante'
}

export function labelForTeamConfiguration(code: string): string {
  switch (code) {
    case 'A1_calendrier':
      return 'Calendrier et horaires'
    case 'A1_delais':
      return 'Délais de traitement'
    case 'A1_acces':
      return 'Sécurité des accès'
    case 'A1_regles_suspension':
      return 'Règles de suspension'
    default:
      return labelForConfigurationCode(code)
  }
}

export function formatFrenchDay(iso?: string | null): string {
  if (!iso) {
    return '—'
  }

  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

export function labelForHabilitationRenewal(code: string, joursRestants: number | null): string {
  switch (code) {
    case 'en_examen':
      return 'En examen'
    case 'a_corriger':
      return 'À corriger'
    case 'a_anticiper':
      if (joursRestants === 0) {
        return 'Aujourd’hui'
      }
      if (typeof joursRestants === 'number' && joursRestants > 0) {
        return `Dans ${joursRestants} jour${joursRestants === 1 ? '' : 's'}`
      }
      return 'À anticiper'
    case 'non_demande':
      return 'Non demandé'
    case 'aucun':
      return '—'
    default:
      return 'Statut indisponible'
  }
}

export function toneForHabilitationRenewal(code: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (code) {
    case 'en_examen':
      return 'info'
    case 'a_corriger':
    case 'a_anticiper':
      return 'warning'
    case 'non_demande':
      return 'neutral'
    default:
      return 'neutral'
  }
}

export function iconForHabilitationRenewal(code: string): UiIconName | undefined {
  switch (code) {
    case 'en_examen':
      return 'clock'
    case 'a_corriger':
      return 'warning'
    case 'a_anticiper':
      return 'calendar'
    default:
      return undefined
  }
}

export function formatRelativeDeadline(iso?: string | null): string {
  if (!iso) {
    return 'Aucune échéance communiquée'
  }

  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return 'Aucune échéance communiquée'
  }

  const dayDiff = Math.round((startOfLocalDay(date) - startOfLocalDay(new Date())) / 86_400_000)
  const time = formatFrenchHour(date)

  if (dayDiff === 0) {
    return `Aujourd’hui, ${time}`
  }
  if (dayDiff === -1) {
    return `Hier, ${time}`
  }
  if (dayDiff === 1) {
    return `Demain, ${time}`
  }

  const day = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
  }).format(date)

  return `${day}, ${time}`
}

export function labelForAttributionState(etat: string): string {
  switch (normalizeAttributionState(etat)) {
    case 'a_venir':
      return 'À venir'
    case 'effective':
      return 'Effective'
    case 'expiree':
      return 'Expirée'
    case 'revoquee':
      return 'Révoquée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForAttributionState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (normalizeAttributionState(etat)) {
    case 'effective':
      return 'success'
    case 'a_venir':
      return 'info'
    case 'expiree':
      return 'warning'
    case 'revoquee':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function labelForInternalPermission(code: string, libelle?: string | null): string {
  switch (code) {
    case 'configurer_service':
      return 'Configurer le service'
    case 'gerer_habilitations_internes':
      return 'Gérer les habilitations internes'
    case 'affecter_taches':
      return 'Affecter les tâches'
    case 'controler_kyc':
      return 'Contrôler un dossier'
    case 'decider_kyc':
      return 'Décider un dossier d’organisation'
    case 'decider_kyc_commercial':
      return 'Décider un dossier commercial'
    case 'restreindre_habilitation':
      return 'Restreindre une habilitation'
    case 'transferer_principal':
      return 'Transférer le représentant principal'
    case 'verifier_representation':
      return 'Vérifier la représentation'
    case 'suspendre_compte':
      return 'Suspendre un compte'
    case 'traiter_support':
      return 'Traiter le support'
    case 'autoriser_recours':
      return 'Autoriser un recours'
    case 'reactiver_compte':
      return 'Réactiver un compte'
    case 'cloturer_compte':
      return 'Clôturer un compte'
    case 'reprendre_transmissions':
      return 'Reprendre les transmissions'
    default:
      return (libelle && libelle.trim()) || code || 'Permission indisponible'
  }
}

export function labelForConfigurationCode(code: string): string {
  switch (code) {
    case 'A1_acces':
      return 'Durées de session'
    case 'A1_delais':
      return 'Délais et défis'
    case 'A1_calendrier':
      return 'Calendrier de service'
    case 'A1_regles_suspension':
      return 'Règles de suspension'
    default:
      return 'Configuration indisponible'
  }
}

export function labelForSubstituteState(etat: string): string {
  switch (etat) {
    case 'a_venir':
      return 'À venir'
    case 'en_vigueur':
      return 'En vigueur'
    case 'terminee':
      return 'Terminée'
    case 'annulee':
      return 'Annulée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForSubstituteState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (etat) {
    case 'en_vigueur':
      return 'success'
    case 'a_venir':
      return 'info'
    case 'terminee':
      return 'neutral'
    case 'annulee':
      return 'warning'
    default:
      return 'neutral'
  }
}

export function labelForTransmissionState(etat: string): string {
  switch (etat) {
    case 'queued':
      return 'En file'
    case 'processing':
      return 'En cours d’envoi'
    case 'retry_wait':
      return 'Attente de nouvelle tentative'
    case 'sent':
      return 'Acceptée par le canal'
    case 'acked':
      return 'Acquittée'
    case 'failed':
      return 'Échouée'
    case 'cancelled':
      return 'Annulée'
    default:
      return 'Statut indisponible'
  }
}

export function labelForSupervisionState(etat: string): string {
  switch (etat) {
    case 'queued':
      return 'En attente'
    case 'processing':
      return 'En cours d’envoi'
    case 'retry_wait':
      return 'Attente de nouvelle tentative'
    case 'sent':
      return 'En attente d’accusé'
    case 'acked':
      return 'Acquittée'
    case 'failed':
      return 'Échec'
    case 'cancelled':
      return 'Annulée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForSupervisionState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (etat) {
    case 'acked':
      return 'success'
    case 'queued':
    case 'processing':
      return 'info'
    case 'sent':
    case 'retry_wait':
      return 'warning'
    case 'failed':
      return 'danger'
    default:
      return 'neutral'
  }
}

export const supervisionCanalOptions = [
  { value: '', label: 'Tous les canaux' },
  { value: 'email', label: 'E-mail' },
  { value: 'sms', label: 'SMS' },
  { value: 'interne', label: 'Canal interne' },
] as const

export const supervisionStateOptions = [
  { value: '', label: 'Tous les états' },
  { value: 'failed', label: 'Échec' },
  { value: 'sent', label: 'En attente d’accusé' },
  { value: 'queued', label: 'En attente' },
  { value: 'processing', label: 'En cours d’envoi' },
  { value: 'retry_wait', label: 'Attente de nouvelle tentative' },
  { value: 'acked', label: 'Acquittée' },
  { value: 'cancelled', label: 'Annulée' },
] as const

export const supervisionDayOptions = [
  { value: 'aujourdhui', label: 'Aujourd’hui' },
  { value: 'tous', label: 'Tous les jours' },
] as const

export function labelForTransmissionCanal(canal: string | null | undefined): string {
  switch (canal) {
    case 'email':
      return 'E-mail'
    case 'sms':
      return 'SMS'
    case 'interne':
      return 'Canal interne'
    default:
      return 'Canal indisponible'
  }
}

export function labelForOutgoingMessageType(type: string | null | undefined): string {
  switch (type) {
    case 'email_activation':
    case 'defi_email_activation':
      return 'Activation du compte'
    case 'password_reset':
    case 'defi_password_reset':
      return 'Réinitialisation du mot de passe'
    case 'compte_cree':
      return 'Compte créé'
    case 'dossier_kyc_ouvert':
      return 'Dossier KYC ouvert'
    case 'dossier_kyc_soumis':
      return 'Dossier KYC soumis'
    case 'dossier_kyc_decide':
      return 'Notification de décision KYC'
    case 'organisation_creee':
      return 'Organisation créée'
    case 'reprise_transmission':
      return 'Reprise d’une transmission'
    case 'recours_ouvert':
      return 'Recours ouvert'
    case 'demande_cloture_ouverte':
      return 'Demande de clôture'
    default:
      return type ? 'Événement' : 'Événement indisponible'
  }
}

export function labelForSupervisionJournalType(source: string, type: string): string {
  if (source === 'kyc') {
    switch (type) {
      case 'ouverture':
        return 'Dossier KYC ouvert'
      case 'prise_en_charge':
        return 'Dossier KYC pris en charge'
      case 'piece_deposee':
        return 'Pièce déposée'
      case 'controle':
        return 'Contrôle KYC consigné'
      case 'decision':
        return 'Dossier KYC décidé'
      default:
        return 'Événement de vérification'
    }
  }

  switch (type) {
    case 'compte_cree':
      return 'Compte créé'
    case 'compte_acces_modifie':
      return 'Accès du compte modifié'
    case 'permission_interne_modifiee':
      return 'Permission interne modifiée'
    case 'session_acces_modifie':
      return 'Session d’accès modifiée'
    case 'adhesion_organisation_modifiee':
      return 'Adhésion d’organisation'
    case 'acceptation':
      return 'Consentement enregistré'
    case 'acces_document_modifie':
      return 'Accès documentaire modifié'
    case 'auteur_compte_lie':
      return 'Parcours auteur lié'
    default:
      return 'Intervention'
  }
}

export function labelForAttemptResult(resultat: string): string {
  switch (resultat) {
    case 'started':
      return 'Démarrée'
    case 'accepted':
      return 'Acceptée'
    case 'acked':
      return 'Acquittée'
    case 'failed':
      return 'Échouée'
    case 'cancelled':
      return 'Annulée'
    case 'unknown':
      return 'Résultat inconnu'
    default:
      return 'Résultat indisponible'
  }
}

export function formatClockTime(iso?: string | null): string {
  if (!iso) {
    return '—'
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return '—'
  }
  return formatFrenchHour(date)
}

export function actorLabel(
  systeme: boolean,
  auteur?: { prenom?: string | null, nom?: string | null } | null,
): string {
  if (auteur) {
    return displayPersonName(auteur.prenom, auteur.nom)
  }
  if (systeme) {
    return 'Système'
  }
  return 'Auteur non consigné'
}

export function toneForTransmissionState(etat: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (etat) {
    case 'sent':
    case 'acked':
      return 'success'
    case 'queued':
    case 'processing':
      return 'info'
    case 'retry_wait':
      return 'warning'
    case 'failed':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function labelForAcknowledgement(niveau: string | null | undefined): string {
  switch (niveau) {
    case 'recu_durable':
      return 'Réception durable'
    case 'engage_local':
      return 'Travail engagé localement'
    case 'effet_confirme':
      return 'Effet confirmé'
    default:
      return 'Acquittement indisponible'
  }
}

export function taskDeadlineIso(task: BackofficeTask): string | null {
  return task.echeance_prise_a || task.echeance_reponse_a
}

export function isTaskUnassigned(task: BackofficeTask): boolean {
  return !task.responsable_id || normalizeTaskState(task.etat_travail) === 'a_affecter'
}

export function isTaskOverdue(task: BackofficeTask): boolean {
  return deadlineKind(task.echeance_prise_a) === 'overdue'
    || deadlineKind(task.echeance_reponse_a) === 'overdue'
}

export function isTaskUrgent(task: BackofficeTask): boolean {
  return normalizeTaskPriority(task.priorite) === 'urgente'
}

export function dossierSubjectLabel(dossier: VerificationDossier): string {
  const name = dossier.sujet?.raison_sociale?.trim()
  return name || dossier.numero || 'Dossier'
}

export function assigneeLabel(task: BackofficeTask, currentAccountId?: string | null): string {
  if (!task.responsable_id) {
    return 'Non affecté'
  }
  if (currentAccountId && task.responsable_id === currentAccountId) {
    return 'Vous'
  }
  return 'Affecté'
}

export function sortPriorityTasks(rows: BackofficeTaskRow[]): BackofficeTaskRow[] {
  return [...rows].sort((left, right) => {
    const score = (row: BackofficeTaskRow): number => {
      let value = 0
      if (isTaskOverdue(row.tache)) {
        value += 100
      }
      if (isTaskUrgent(row.tache)) {
        value += 50
      }
      if (deadlineKind(taskDeadlineIso(row.tache)) === 'soon') {
        value += 20
      }
      if (isTaskUnassigned(row.tache)) {
        value += 10
      }
      return value
    }

    const diff = score(right) - score(left)
    if (diff !== 0) {
      return diff
    }

    const leftDeadline = taskDeadlineIso(left.tache)
    const rightDeadline = taskDeadlineIso(right.tache)
    if (leftDeadline && rightDeadline) {
      return leftDeadline.localeCompare(rightDeadline)
    }
    if (leftDeadline) {
      return -1
    }
    if (rightDeadline) {
      return 1
    }
    return left.tache.id.localeCompare(right.tache.id)
  })
}

export function organizationInitials(name: string | null | undefined): string {
  const parts = (name || '')
    .split(/\s+/)
    .map(part => part.trim())
    .filter(Boolean)
  const letters = parts.slice(0, 2).map(part => part.slice(0, 1).toUpperCase()).join('')
  return letters || '?'
}

export function displayPersonName(prenom?: string | null, nom?: string | null): string {
  const first = prenom?.trim()
  const last = nom?.trim()
  if (first && last) {
    return `${first} ${last}`
  }
  return first || last || 'Compte'
}

export function displayShortPersonName(prenom?: string | null, nom?: string | null): string {
  const first = prenom?.trim()
  const last = nom?.trim()
  if (first && last) {
    return `${first} ${last.slice(0, 1).toUpperCase()}.`
  }
  return first || last || 'Compte'
}

export function personInitials(prenom?: string | null, nom?: string | null): string {
  const first = prenom?.trim()
  const last = nom?.trim()
  const value = `${first ? first.slice(0, 1) : ''}${last ? last.slice(0, 1) : ''}`.toUpperCase()
  return value || '?'
}

export function labelForOrganizationVerification(code: string | null | undefined): string {
  switch (code) {
    case 'verifiee':
      return 'Vérifiée'
    case 'en_cours':
      return 'En cours'
    case 'non_verifiee':
      return 'Non vérifiée'
    case 'expiree':
      return 'Expirée'
    case 'a_renouveler':
      return 'À renouveler'
    case 'restreint':
      return 'Restreinte'
    case 'revoquee':
      return 'Révoquée'
    default:
      return 'Statut indisponible'
  }
}

export function iconForOrganizationVerification(code: string | null | undefined): UiIconName {
  switch (code) {
    case 'verifiee':
      return 'success'
    case 'en_cours':
      return 'clock'
    case 'expiree':
      return 'error'
    case 'a_renouveler':
      return 'warning'
    case 'restreint':
      return 'lock'
    case 'revoquee':
      return 'error'
    case 'non_verifiee':
      return 'minus-circle'
    default:
      return 'info'
  }
}

export function toneForOrganizationVerification(code: string | null | undefined): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  switch (code) {
    case 'verifiee':
      return 'success'
    case 'en_cours':
    case 'a_renouveler':
      return 'warning'
    case 'expiree':
    case 'restreint':
    case 'revoquee':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function labelForRepresentationState(code: string | null | undefined): string {
  switch (code) {
    case 'confirmee':
      return 'Représentation confirmée'
    case 'declaree':
      return 'Représentant déclaré'
    default:
      return 'Aucun représentant'
  }
}
