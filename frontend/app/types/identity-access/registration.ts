export type KnownRegistrationPathCode = 'lecteur' | 'auteur' | 'representant'

export type RegistrationPathDto = {
  code: string
  libelle: string
  role_a_la_creation: string
}

export type RequiredLegalTextDto = {
  id: string
  type: string
  version: string
  langue: string
  requis_pour_activation: boolean
  contenu?: string | null
  reference_immuable?: string | null
}

export type OrganizationRequirementDto = {
  id?: string
  code: string
  volet: string
  nature_preuve: string
  obligatoire: boolean
}

export type OrganizationTypeDto = {
  id: string
  code: string
  libelle: string
  exigences?: OrganizationRequirementDto[]
}

export type RegistrationPathsData = {
  parcours: RegistrationPathDto[]
  textes_requis: RequiredLegalTextDto[]
  types_organisation: OrganizationTypeDto[]
  exigences_auteur?: OrganizationRequirementDto[]
}

export type RegisterAccountPayload = {
  parcours: KnownRegistrationPathCode
  email: string
  password: string
  telephone?: string | null
  locale?: string
  prenom: string
  nom: string
  nom_plume?: string | null
  pays: string
  ville?: string | null
  langue_ecriture?: string | null
  consentements: Array<{
    texte_version_id: string
    nature: 'acceptation' | 'prise_connaissance' | 'consentement_optionnel'
  }>
}

export type RegisterAccountResult = {
  compte: {
    id: string
    statut: string
    email_masque: string
  }
  prochaine_action: string
}

export const knownRegistrationPathCodes: readonly KnownRegistrationPathCode[] = [
  'lecteur',
  'auteur',
  'representant',
]

export function isKnownRegistrationPathCode(code: string): code is KnownRegistrationPathCode {
  return (knownRegistrationPathCodes as readonly string[]).includes(code)
}

export function labelForLegalTextType(type: string): string {
  switch (type) {
    case 'cgu':
      return 'Conditions générales d’utilisation'
    case 'confidentialite':
      return 'Politique de confidentialité'
    case 'newsletter':
      return 'Informations par e-mail'
    default:
      return 'Texte juridique'
  }
}

export function consentLabelForLegalText(type: string): string {
  switch (type) {
    case 'cgu':
      return 'J’accepte les Conditions générales d’utilisation'
    case 'confidentialite':
      return 'J’ai lu et j’accepte la Politique de confidentialité'
    default:
      return `J’accepte ${labelForLegalTextType(type)}`
  }
}

export function consultLabelForLegalText(type: string): string {
  switch (type) {
    case 'cgu':
      return 'Consulter les CGU'
    case 'confidentialite':
      return 'Consulter la politique'
    default:
      return 'Consulter le texte'
  }
}

export function isPublicLegalReference(value?: string | null): value is string {
  return Boolean(value && /^https?:\/\//i.test(value))
}
