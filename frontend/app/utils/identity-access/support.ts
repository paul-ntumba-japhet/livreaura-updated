import type { UiIconName } from '../../components/ui/icons'
import {
  normalizeIdentityState,
  normalizeSupportOrigin,
  normalizeSupportType,
  normalizeSupportVisibility,
} from '../../types/identity-access/support'
import { normalizeTaskPriority, normalizeTaskState } from '../../types/identity-access/backoffice'

export function labelForSupportOrigin(origine: string): string {
  switch (normalizeSupportOrigin(origine)) {
    case 'demandeur':
      return 'Demandeur'
    case 'agent':
      return 'Support'
    case 'systeme':
      return 'Système'
    default:
      return 'Auteur indisponible'
  }
}

export function labelForSupportVisibility(visibilite: string): string {
  switch (normalizeSupportVisibility(visibilite)) {
    case 'demandeur':
      return 'Visible du demandeur'
    case 'interne':
      return 'Note interne'
    default:
      return 'Visibilité indisponible'
  }
}

export function labelForSupportType(type: string): string {
  switch (normalizeSupportType(type)) {
    case 'recours_suspension':
      return 'Recours après suspension'
    case 'recuperation_canal':
      return 'Récupération de canal'
    case 'revendication_auteur':
      return 'Revendication auteur'
    case 'representation':
      return 'Représentation'
    case 'transfert_principal':
      return 'Transfert du principal'
    case 'reactivation':
      return 'Réactivation'
    case 'cloture':
      return 'Clôture de compte'
    case 'incident_technique':
      return 'Incident technique'
    case 'examen_compte_conformite':
      return 'Examen de conformité'
    default:
      return 'Motif indisponible'
  }
}

export function labelForIdentityState(etat: string, externe = false): string {
  if (externe && normalizeIdentityState(etat) !== 'verifiee') {
    return 'Compte non identifié'
  }
  switch (normalizeIdentityState(etat)) {
    case 'verifiee':
      return 'Identité vérifiée'
    case 'non_verifiee':
      return 'Identité à vérifier'
    case 'refusee':
      return 'Identité refusée'
    case 'non_requise':
      return 'Identité non requise'
    default:
      return 'Identité indisponible'
  }
}

export function toneForIdentityState(etat: string, externe = false): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  if (externe && normalizeIdentityState(etat) !== 'verifiee') {
    return 'neutral'
  }
  switch (normalizeIdentityState(etat)) {
    case 'verifiee':
      return 'success'
    case 'non_verifiee':
      return 'warning'
    case 'refusee':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function iconForIdentityState(etat: string, externe = false): UiIconName {
  if (externe && normalizeIdentityState(etat) !== 'verifiee') {
    return 'user'
  }
  switch (normalizeIdentityState(etat)) {
    case 'verifiee':
      return 'success'
    case 'non_verifiee':
      return 'warning'
    case 'refusee':
      return 'error'
    default:
      return 'user'
  }
}

export function labelForSupportPriority(priorite: string): string {
  switch (normalizeTaskPriority(priorite)) {
    case 'urgente':
      return 'Haute'
    case 'normale':
      return 'Normale'
    default:
      return 'Priorité indisponible'
  }
}

export function labelForSupportQueueStatus(etatTravail: string | null | undefined, closed: boolean): string {
  if (closed) {
    return 'Clôturée'
  }
  switch (normalizeTaskState(etatTravail)) {
    case 'a_affecter':
      return 'À prendre en charge'
    case 'en_cours':
      return 'En cours'
    case 'attente_demandeur':
      return 'En attente du demandeur'
    case 'attente_tiers':
      return 'Attente d’un tiers'
    case 'terminee':
      return 'Clôturée'
    default:
      return etatTravail ? 'Statut indisponible' : 'À prendre en charge'
  }
}

export function toneForSupportQueueStatus(etatTravail: string | null | undefined, closed: boolean): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  if (closed) {
    return 'neutral'
  }
  switch (normalizeTaskState(etatTravail)) {
    case 'a_affecter':
      return 'info'
    case 'en_cours':
      return 'info'
    case 'attente_demandeur':
      return 'warning'
    case 'attente_tiers':
      return 'warning'
    case 'terminee':
      return 'neutral'
    default:
      return 'neutral'
  }
}

export function iconForSupportQueueStatus(etatTravail: string | null | undefined, closed: boolean): UiIconName {
  if (closed) {
    return 'success'
  }
  switch (normalizeTaskState(etatTravail)) {
    case 'a_affecter':
      return 'inbox'
    case 'en_cours':
      return 'clock'
    case 'attente_demandeur':
      return 'user'
    case 'attente_tiers':
      return 'clock'
    case 'terminee':
      return 'success'
    default:
      return 'info'
  }
}

export function labelForSupportEvidenceType(type: string): string {
  switch (type) {
    case 'identite':
      return 'Pièce d’identité'
    case 'mandat':
      return 'Mandat'
    case 'complement':
      return 'Complément'
    default:
      return type || 'Pièce'
  }
}
