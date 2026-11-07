import type { UiIconName } from '../../components/ui/icons'
import {
  labelForLegalTextType,
  type RequiredLegalTextDto,
} from '../../types/identity-access/registration'
import {
  normalizeAccessScope,
  normalizeAccountStatus,
  type AccessScope,
  type AccountConsent,
  type AccountStatus,
  type AccountVerificationDossier,
  type CurrentAccount,
  type OwnAuthenticationSession,
  type PendingLegalText,
  type SessionStatus,
} from '../../types/identity-access/account'
import { registrationCountries } from './countries'
import { formatActivationExpiry } from './email-activation'

export { normalizeAccessScope, normalizeAccountStatus }

export type DescriptionItem = {
  term: string
  value: string
  missing?: boolean
}

export function displayNameForAccount(account: CurrentAccount): string {
  const prenom = account.profil?.prenom?.trim()
  if (prenom) {
    return prenom
  }
  return 'Compte'
}

export function displayFullNameForAccount(account: CurrentAccount): string {
  const prenom = account.profil?.prenom?.trim()
  const nom = account.profil?.nom?.trim()
  if (prenom && nom) {
    return `${prenom} ${nom}`
  }
  if (prenom) {
    return prenom
  }
  return 'Mon compte'
}

export function labelForAccountStatus(status: AccountStatus): string {
  switch (status) {
    case 'pending':
      return 'En attente d’activation'
    case 'active':
      return 'Actif'
    case 'suspended':
      return 'Suspendu'
    case 'closed':
      return 'Clôturé'
    default:
      return 'Statut indisponible'
  }
}

export function labelForAccessScope(scope: AccessScope): string {
  switch (scope) {
    case 'normal':
      return 'Accès normal'
    case 'limited':
      return 'Accès limité'
    case 'support_only':
      return 'Recours uniquement'
    default:
      return 'Portée indisponible'
  }
}

export function labelForRole(code: string): string {
  switch (code) {
    case 'reader':
      return 'Lecteur'
    case 'author':
      return 'Auteur'
    case 'admin':
      return 'Administration'
    case 'representant':
      return 'Représentant'
    default:
      return 'Rôle non reconnu'
  }
}

export function isRepresentantAccountView(account: CurrentAccount): boolean {
  if (account.parcours_inscription === 'representant') {
    return true
  }
  if (account.parcours_inscription === 'auteur') {
    return false
  }
  return (account.organisations?.length ?? 0) > 0
}

export function displayedAccountRoles(account: CurrentAccount): Array<{ code: string, label: string }> {
  const representantView = isRepresentantAccountView(account)
  const items = account.roles
    .filter(code => !(representantView && code === 'author'))
    .map(code => ({
      code,
      label: labelForRole(code),
    }))

  if (representantView && !items.some(item => item.code === 'representant')) {
    const readerIndex = items.findIndex(item => item.code === 'reader')
    const badge = { code: 'representant', label: labelForRole('representant') }
    if (readerIndex >= 0) {
      items.splice(readerIndex + 1, 0, badge)
    }
    else {
      items.push(badge)
    }
  }

  return items
}

export function labelForAuthorPathState(etat: string): string {
  switch (etat) {
    case 'non_active':
      return 'Non activé'
    case 'active':
      return 'Activé'
    case 'revendication_en_examen':
      return 'Revendication en examen'
    default:
      return 'Statut indisponible'
  }
}

export function labelForOrganizationStatus(status: string): string {
  switch (status) {
    case 'active':
      return 'Active'
    case 'restricted':
      return 'Restreinte'
    case 'closed':
      return 'Clôturée'
    default:
      return 'Statut indisponible'
  }
}

export function labelForMembershipStatus(status: string): string {
  switch (status) {
    case 'active':
      return 'Adhésion active'
    case 'revoked':
      return 'Adhésion révoquée'
    default:
      return 'Adhésion indisponible'
  }
}

export function labelForOrganizationPermission(code: string): string {
  switch (code) {
    case 'deposer_kyc':
      return 'Dépôt d’identité'
    default:
      return 'Permission non reconnue'
  }
}

export function labelForPublicationStatus(status: string): string {
  switch (status) {
    case 'draft':
      return 'Brouillon'
    case 'published':
      return 'Publiée'
    case 'archived':
      return 'Archivée'
    default:
      return 'Publication indisponible'
  }
}

export function labelForConsentNature(nature: string, withdrawn: boolean): string {
  if (withdrawn) {
    return 'Retiré'
  }
  switch (nature) {
    case 'acceptation':
      return 'Accepté'
    case 'prise_connaissance':
      return 'Prise de connaissance'
    case 'refus':
      return 'Refusé'
    case 'consentement_optionnel':
      return 'Accepté'
    default:
      return 'Réponse indisponible'
  }
}

export function countryLabel(code: string | null | undefined): string {
  if (!code) {
    return ''
  }
  return registrationCountries.find(country => country.code === code)?.label ?? code
}

export function presentValue(value: string | null | undefined): DescriptionItem['value'] {
  const trimmed = value?.trim() ?? ''
  return trimmed
}

function descriptionItemsFrom(
  entries: Array<[string, string | null | undefined]>,
  emptyLabel = 'Non renseigné',
): DescriptionItem[] {
  return entries.map(([term, value]) => {
    const displayed = presentValue(value)
    return {
      term,
      value: displayed || emptyLabel,
      missing: displayed === '',
    }
  })
}

export function profileItems(account: CurrentAccount): DescriptionItem[] {
  const profile = account.profil
  const hidePenName = isRepresentantAccountView(account)
  const entries: Array<[string, string | null | undefined]> = [
    ['Prénom', profile?.prenom],
    ['Nom', profile?.nom],
    ...(!hidePenName ? [['Nom de plume', profile?.nom_plume] as [string, string | null | undefined]] : []),
    ['Pays', countryLabel(profile?.pays) || profile?.pays],
    ['Ville', profile?.ville],
  ]

  return descriptionItemsFrom(entries)
}

export function personalInformationItems(account: CurrentAccount): DescriptionItem[] {
  const profile = account.profil
  const hidePenName = isRepresentantAccountView(account)
  const language = localeLabel(account.compte.locale)
  const entries: Array<[string, string | null | undefined]> = [
    ['Prénom', profile?.prenom],
    ['Nom', profile?.nom],
    ...(!hidePenName ? [['Nom de plume', profile?.nom_plume] as [string, string | null | undefined]] : []),
    ['Langue', language],
  ]

  return descriptionItemsFrom(entries, 'Non renseigné')
}

export function contactInformationItems(account: CurrentAccount): DescriptionItem[] {
  const profile = account.profil
  return [
    {
      term: 'E-mail',
      value: account.compte.email_masque || 'Non disponible',
      missing: !account.compte.email_masque,
    },
    ...descriptionItemsFrom([
      ['Ville', profile?.ville],
      ['Pays', countryLabel(profile?.pays) || profile?.pays],
    ]),
  ]
}

export function profileLocationLabel(account: CurrentAccount): string {
  const country = countryLabel(account.profil?.pays) || account.profil?.pays?.trim() || ''
  const city = account.profil?.ville?.trim() || ''
  if (city && country) {
    return `${city}, ${country}`
  }
  return city || country
}

export function needsEmailConfirmation(status: AccountStatus, scope: AccessScope): boolean {
  return status === 'pending' || scope === 'limited'
}

export const profileEditUnavailableReason = 'La modification du profil n’est pas proposée dans cette version.'

export function localeLabel(code: string | null | undefined): string {
  const trimmed = code?.trim()
  if (!trimmed) {
    return ''
  }

  try {
    const name = new Intl.DisplayNames(['fr'], { type: 'language' }).of(trimmed)
    if (!name) {
      return trimmed
    }
    return name.charAt(0).toUpperCase() + name.slice(1)
  }
  catch {
    return trimmed
  }
}

export function identityItems(account: CurrentAccount): DescriptionItem[] {
  const language = localeLabel(account.compte.locale)
  return [
    {
      term: 'Adresse e-mail',
      value: account.compte.email_masque || 'Non disponible',
      missing: !account.compte.email_masque,
    },
    {
      term: 'Langue',
      value: language || 'Non renseignée',
      missing: language === '',
    },
  ]
}

export function sessionExpiryLabel(iso?: string | null): string {
  return formatActivationExpiry(iso) || 'Non communiquée'
}

export function normalizeSessionStatus(value: string | null | undefined): SessionStatus {
  if (value === 'active' || value === 'expired' || value === 'revoked') {
    return value
  }
  return 'unknown'
}

export function labelForSessionStatus(status: SessionStatus): string {
  switch (status) {
    case 'active':
      return 'Active'
    case 'expired':
      return 'Expirée'
    case 'revoked':
      return 'Révoquée'
    default:
      return 'Statut indisponible'
  }
}

export function toneForSessionStatus(status: SessionStatus): 'neutral' | 'success' | 'warning' | 'danger' {
  switch (status) {
    case 'active':
      return 'success'
    case 'expired':
      return 'warning'
    case 'revoked':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function asOwnAuthenticationSessions(value: unknown): OwnAuthenticationSession[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.flatMap((row) => {
    if (!row || typeof row !== 'object' || !('id' in row) || typeof row.id !== 'string' || row.id === '') {
      return []
    }
    const session = row as Record<string, unknown>
    return [{
      id: session.id,
      courante: session.courante === true,
      portee: typeof session.portee === 'string' ? session.portee : '',
      statut: typeof session.statut === 'string' ? session.statut : '',
      ouverte_a: typeof session.ouverte_a === 'string' ? session.ouverte_a : null,
      derniere_activite_a: typeof session.derniere_activite_a === 'string' ? session.derniere_activite_a : null,
      expire_absolue_a: typeof session.expire_absolue_a === 'string' ? session.expire_absolue_a : null,
      expire_inactivite_a: typeof session.expire_inactivite_a === 'string' ? session.expire_inactivite_a : null,
      contexte: typeof session.contexte === 'string' && session.contexte !== '' ? session.contexte : null,
    }]
  })
}

export function accountStatusOf(account: CurrentAccount): AccountStatus {
  return normalizeAccountStatus(account.compte.statut)
}

export function accessScopeOf(account: CurrentAccount): AccessScope {
  return normalizeAccessScope(account.session?.portee)
}

export function canAccessBackoffice(account: CurrentAccount | null | undefined): boolean {
  if (!account) {
    return false
  }

  return accountStatusOf(account) === 'active'
    && accessScopeOf(account) === 'normal'
    && Array.isArray(account.roles)
    && account.roles.includes('admin')
}

export function hasInternalPermission(account: CurrentAccount | null | undefined, code: string): boolean {
  return Boolean(account?.permissions_internes?.includes(code))
}

export function canAccessKycBackoffice(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && (
      hasInternalPermission(account, 'controler_kyc')
      || hasInternalPermission(account, 'decider_kyc')
      || hasInternalPermission(account, 'decider_kyc_commercial')
      || hasInternalPermission(account, 'affecter_taches')
      || hasInternalPermission(account, 'restreindre_habilitation')
    )
}

export function canTransferPrincipal(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'transferer_principal')
    && hasInternalPermission(account, 'verifier_representation')
}

export function canAccessOrganizationBackoffice(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && (
      hasInternalPermission(account, 'transferer_principal')
      || hasInternalPermission(account, 'verifier_representation')
      || hasInternalPermission(account, 'traiter_support')
    )
}

export function canSuspendAccount(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'suspendre_compte')
}

export function canReactivateAccount(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'reactiver_compte')
}

export function canCloseAccount(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'cloturer_compte')
}

export function canAuthorizeAppeal(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'traiter_support')
    && hasInternalPermission(account, 'autoriser_recours')
}

export function canHandleSupport(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'traiter_support')
}

export function canManageInternalPermissions(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'gerer_habilitations_internes')
}

export function canConfigureService(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'configurer_service')
}

export function canAssignServiceSubstitutes(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'affecter_taches')
}

export function canRetryTransmissions(account: CurrentAccount | null | undefined): boolean {
  return canAccessBackoffice(account)
    && hasInternalPermission(account, 'reprendre_transmissions')
}

export function canAccessExploitation(account: CurrentAccount | null | undefined): boolean {
  return canConfigureService(account)
    || canAssignServiceSubstitutes(account)
    || canRetryTransmissions(account)
}

export function canAccessTeamBackoffice(account: CurrentAccount | null | undefined): boolean {
  return canManageInternalPermissions(account)
    || canAssignServiceSubstitutes(account)
    || canConfigureService(account)
}

export function consentSummary(consent: AccountConsent): string {
  const title = consent.type ? labelForLegalTextType(consent.type) : 'Texte juridique'
  const version = consent.version ? ` · version ${consent.version}` : ''
  return `${title}${version}`
}

export function pendingTextSummary(type: string, version: string): string {
  return `${labelForLegalTextType(type)} · version ${version}`
}

export type PendingLegalTextView = PendingLegalText & {
  contenu?: string | null
  reference_immuable?: string | null
}

export function mergePendingLegalContent(
  pending: PendingLegalText[],
  texts: RequiredLegalTextDto[],
): PendingLegalTextView[] {
  const byId = new Map(texts.map(text => [text.id, text]))

  return pending.map((item) => {
    const match = byId.get(item.id)
    return {
      ...item,
      contenu: match?.contenu ?? null,
      reference_immuable: match?.reference_immuable ?? null,
    }
  })
}

export function isOptionalConsent(consent: AccountConsent): boolean {
  return consent.nature === 'consentement_optionnel'
}

export function isWithdrawnConsent(consent: AccountConsent): boolean {
  return Boolean(consent.retire_a)
}

export function toneForAccountStatus(status: AccountStatus): 'neutral' | 'success' | 'warning' | 'danger' {
  switch (status) {
    case 'active':
      return 'success'
    case 'pending':
      return 'warning'
    case 'suspended':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function iconForAccountStatus(status: AccountStatus): UiIconName {
  switch (status) {
    case 'active':
      return 'success'
    case 'pending':
      return 'clock'
    case 'suspended':
      return 'error'
    case 'closed':
      return 'minus-circle'
    default:
      return 'info'
  }
}

export function iconForEmailVerification(verified: boolean): UiIconName {
  return verified ? 'success' : 'warning'
}

export function hintForAccountStatus(status: AccountStatus): string {
  switch (status) {
    case 'active':
      return 'Votre compte est en règle.'
    case 'pending':
      return 'Confirmez l’adresse e-mail pour activer le compte.'
    case 'suspended':
      return 'Seuls les recours explicitement autorisés restent accessibles.'
    case 'closed':
      return 'Ce compte n’est plus utilisable.'
    default:
      return 'Le statut n’est pas reconnu. Les actions qui en dépendent restent bloquées.'
  }
}

export function toneForAccessScope(scope: AccessScope): 'neutral' | 'info' | 'warning' {
  switch (scope) {
    case 'normal':
      return 'info'
    case 'limited':
    case 'support_only':
      return 'warning'
    default:
      return 'neutral'
  }
}

export function hintForAccessScope(scope: AccessScope): string {
  switch (scope) {
    case 'normal':
      return 'Accès aux fonctionnalités standard.'
    case 'limited':
      return 'L’accès reste limité jusqu’à confirmation.'
    case 'support_only':
      return 'Seuls les recours et documents autorisés restent accessibles.'
    default:
      return 'La portée n’est pas reconnue. Aucun accès supplémentaire n’est présumé.'
  }
}

export function accountInitials(account: CurrentAccount): string {
  const prenom = account.profil?.prenom?.trim().charAt(0)
  const nom = account.profil?.nom?.trim().charAt(0)
  if (prenom && nom) {
    return `${prenom}${nom}`.toUpperCase()
  }
  if (prenom) {
    return prenom.toUpperCase()
  }
  return 'C'
}

export function primaryDisplayedRole(account: CurrentAccount): { code: string, label: string } | null {
  return displayedAccountRoles(account)[0] ?? null
}

export function identityAndProfileItems(account: CurrentAccount): DescriptionItem[] {
  return [...identityItems(account), ...profileItems(account)]
}

export function latestVerificationDossier(account: CurrentAccount): AccountVerificationDossier | null {
  return account.dossiers_verification?.[0] ?? null
}

export function consentTitle(consent: AccountConsent): string {
  return consent.type ? labelForLegalTextType(consent.type) : 'Texte juridique'
}

export function toneForConsentNature(consent: AccountConsent): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  if (isWithdrawnConsent(consent)) {
    return 'warning'
  }
  switch (consent.nature) {
    case 'acceptation':
    case 'consentement_optionnel':
      return 'success'
    case 'prise_connaissance':
      return 'info'
    case 'refus':
      return 'danger'
    default:
      return 'neutral'
  }
}
