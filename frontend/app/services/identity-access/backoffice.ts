import type {
  AccountClosureResult,
  AccountReactivationResult,
  ClosureIdentityResult,
  AccountSuspensionResult,
  BackofficeAccountFilters,
  BackofficeAccountList,
  BackofficeAccountPagination,
  BackofficeAccountRow,
  BackofficeAccountTotals,
  BackofficeOrganizationDemand,
  BackofficeOrganizationFilters,
  BackofficeOrganizationList,
  BackofficeOrganizationPerson,
  BackofficeOrganizationRow,
  BackofficeOrganizationTab,
  BackofficeCapabilityFilters,
  BackofficeCapabilityList,
  BackofficeCapabilityRenewal,
  BackofficeCapabilityRow,
  BackofficeCapabilityTab,
  BackofficeCapabilityTotals,
  BackofficeSupportFilters,
  BackofficeSupportList,
  BackofficeSupportRequester,
  BackofficeSupportRow,
  BackofficeSupportTab,
  BackofficeSupportTotals,
  BackofficeTeamAgent,
  BackofficeTeamCapabilities,
  BackofficeTeamConfiguration,
  BackofficeTeamFilters,
  BackofficeTeamList,
  BackofficeTeamSubstitute,
  BackofficeTeamTotals,
  BackofficeKycFilters,
  BackofficeKycList,
  BackofficeKycRow,
  BackofficeKycSubject,
  BackofficeKycTab,
  BackofficeKycTotals,
  BackofficeOrganizationTotals,
  BackofficeOrganizationType,
  BackofficeControl,
  BackofficeReview,
  BackofficeReviewCapabilities,
  BackofficeTask,
  BackofficeTaskList,
  BackofficeTaskRow,
  InternalAttribution,
  InternalPermissionCatalog,
  InternalPermissionMutationResult,
  InternalPermissionOption,
  RestrictedOrganization,
  RetryableTransmission,
  RetryableTransmissionCatalog,
  RetryTransmissionResult,
  BackofficeSupervisionFilters,
  BackofficeSupervisionList,
  BackofficeSupervisionTab,
  SupervisionAttempt,
  SupervisionJournalEntry,
  SupervisionRetryEntry,
  SupervisionTotals,
  SupervisionTransmission,
  ServiceConfigurationCatalog,
  ServiceConfigurationGroup,
  ServiceConfigurationMutationResult,
  ServiceConfigurationVersion,
  ServiceSubstitute,
  ServiceSubstituteCatalog,
  ServiceSubstituteMutationResult,
} from '../../types/identity-access/backoffice'
import type { RepresentativeTransferResult } from '../../types/identity-access/organization'
import type { VerificationDossier } from '../../types/identity-access/verification'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'
import { asVerificationDossier } from './verification'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

function asTask(value: BackofficeTask | null | undefined): BackofficeTask | null {
  if (!value?.id || !value.etat_travail) {
    return null
  }

  return {
    id: value.id,
    etat_travail: value.etat_travail,
    priorite: value.priorite || 'normale',
    motif_priorite: value.motif_priorite ?? null,
    responsable_id: value.responsable_id ?? null,
    cree_a: value.cree_a ?? null,
    affecte_a: value.affecte_a ?? null,
    premiere_prise_a: value.premiere_prise_a ?? null,
    echeance_prise_a: value.echeance_prise_a ?? null,
    echeance_reponse_a: value.echeance_reponse_a ?? null,
    clos_a: value.clos_a ?? null,
  }
}

function asControls(value: BackofficeControl[] | undefined): BackofficeControl[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item): item is BackofficeControl => Boolean(item?.id && item.resultat))
}

function asCapabilities(value: BackofficeReviewCapabilities | undefined): BackofficeReviewCapabilities {
  return {
    affecter: value?.affecter === true,
    prendre_en_charge: value?.prendre_en_charge === true,
    controler: value?.controler === true,
    decider: value?.decider === true,
    restreindre: value?.restreindre === true,
    consulter_pieces: value?.consulter_pieces === true,
  }
}

function asReview(value: BackofficeReview | null | undefined): BackofficeReview | null {
  const dossier = asVerificationDossier(value?.dossier)
  if (!dossier) {
    return null
  }

  return {
    dossier,
    controles: asControls(value?.controles),
    tache: asTask(value?.tache),
    decision: value?.decision?.id
      ? {
          id: value.decision.id,
          resultat: value.decision.resultat,
          motif: value.decision.motif,
          decide_a: value.decision.decide_a ?? null,
        }
      : null,
    habilitation: value?.habilitation?.id
      ? {
          id: value.habilitation.id,
          niveau: value.habilitation.niveau,
          etat: value.habilitation.etat,
          obtenue_a: value.habilitation.obtenue_a ?? null,
          valide_jusqua: value.habilitation.valide_jusqua ?? null,
          motif_restriction: value.habilitation.motif_restriction ?? null,
        }
      : null,
    conflit_interet: value?.conflit_interet === true,
    niveau_decision: value?.niveau_decision || 'hors_perimetre',
    capacites: asCapabilities(value?.capacites),
    statut: value?.statut,
    prochaine_action: value?.prochaine_action,
  }
}

function asTaskRow(value: BackofficeTaskRow | undefined): BackofficeTaskRow | null {
  const tache = asTask(value?.tache)
  const dossier = asVerificationDossier(value?.dossier as VerificationDossier | null)
  if (!tache || !dossier) {
    return null
  }

  return {
    tache,
    dossier,
    conflit_interet: value?.conflit_interet === true,
    capacites: {
      consulter: value?.capacites?.consulter !== false,
      affecter: value?.capacites?.affecter === true,
      prendre_en_charge: value?.capacites?.prendre_en_charge === true,
    },
  }
}

export async function fetchBackofficeTasks(): Promise<BackofficeTaskList> {
  try {
    const response = await $fetch<ApiSuccess<BackofficeTaskList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/taches`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const rows = Array.isArray(response?.data?.taches)
      ? response.data.taches.map(asTaskRow).filter((item): item is BackofficeTaskRow => item !== null)
      : []

    return {
      taches: rows,
      capacites: {
        affecter: response?.data?.capacites?.affecter === true,
        controler: response?.data?.capacites?.controler === true,
        decider: response?.data?.capacites?.decider === true,
      },
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asCount(value: unknown, fallback = 0): number {
  return typeof value === 'number' && Number.isFinite(value) && value >= 0 ? value : fallback
}

function asAccountRow(value: BackofficeAccountRow | undefined): BackofficeAccountRow | null {
  if (!value?.id || !value.statut) {
    return null
  }

  return {
    id: value.id,
    statut: value.statut,
    email_masque: value.email_masque || '',
    email_verifie: value.email_verifie === true,
    prenom: value.prenom ?? null,
    nom: value.nom ?? null,
    roles: Array.isArray(value.roles)
      ? value.roles.filter((code): code is string => typeof code === 'string' && code !== '')
      : [],
    organisation: value.organisation ?? null,
    derniere_connexion_a: value.derniere_connexion_a ?? null,
  }
}

function asAccountTotals(value: BackofficeAccountTotals | undefined): BackofficeAccountTotals {
  return {
    total: asCount(value?.total),
    active: asCount(value?.active),
    pending: asCount(value?.pending),
    suspended: asCount(value?.suspended),
    closed: asCount(value?.closed),
  }
}

function asAccountPagination(value: BackofficeAccountPagination | undefined): BackofficeAccountPagination {
  const perPage = Math.min(50, Math.max(1, asCount(value?.per_page, 6)))
  const total = asCount(value?.total)
  const lastPage = Math.max(1, asCount(value?.last_page, 1))
  const page = Math.min(lastPage, Math.max(1, asCount(value?.page, 1)))

  return {
    page,
    per_page: perPage,
    total,
    last_page: lastPage,
  }
}

export async function fetchBackofficeAccounts(filters: BackofficeAccountFilters = {}): Promise<BackofficeAccountList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.statut) {
      params.set('statut', filters.statut)
    }
    if (filters.role) {
      params.set('role', filters.role)
    }
    if (filters.email_verifie) {
      params.set('email_verifie', filters.email_verifie)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    if (filters.per_page) {
      params.set('per_page', String(filters.per_page))
    }
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeAccountList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/comptes${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const rows = Array.isArray(response?.data?.comptes)
      ? response.data.comptes.map(asAccountRow).filter((item): item is BackofficeAccountRow => item !== null)
      : []

    return {
      totaux: asAccountTotals(response?.data?.totaux),
      pagination: asAccountPagination(response?.data?.pagination),
      comptes: rows,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asOrganizationType(value: BackofficeOrganizationType | null | undefined): BackofficeOrganizationType | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    code: value.code || '',
    libelle: value.libelle || '',
  }
}

function asPerson(value: BackofficeOrganizationPerson | null | undefined): BackofficeOrganizationPerson | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    prenom: value.prenom ?? null,
    nom: value.nom ?? null,
    email_masque: value.email_masque ?? null,
  }
}

function asOrganizationRow(value: BackofficeOrganizationRow | undefined): BackofficeOrganizationRow | null {
  if (!value?.id || !value.raison_sociale) {
    return null
  }
  return {
    id: value.id,
    raison_sociale: value.raison_sociale,
    ville: value.ville ?? null,
    pays: value.pays ?? null,
    statut_operationnel: value.statut_operationnel || '',
    type: asOrganizationType(value.type),
    representant: asPerson(value.representant),
    representation: value.representation ?? null,
    verification: value.verification || 'non_verifiee',
  }
}

function asOrganizationDemand(value: BackofficeOrganizationDemand | undefined): BackofficeOrganizationDemand | null {
  if (!value?.id || !value.reference) {
    return null
  }
  return {
    id: value.id,
    reference: value.reference,
    type: value.type || '',
    cree_a: value.cree_a ?? null,
    organisation: value.organisation?.id
      ? {
          id: value.organisation.id,
          raison_sociale: value.organisation.raison_sociale || '',
          ville: value.organisation.ville ?? null,
          pays: value.organisation.pays ?? null,
          type: asOrganizationType(value.organisation.type),
        }
      : null,
    demandeur: asPerson(value.demandeur),
  }
}

function asOrganizationTotals(value: BackofficeOrganizationTotals | undefined): BackofficeOrganizationTotals {
  return {
    organisations: asCount(value?.organisations),
    verifiees: asCount(value?.verifiees),
    rattachements: asCount(value?.rattachements),
    transferts: asCount(value?.transferts),
  }
}

function asOrganizationTab(value: string | undefined): BackofficeOrganizationTab {
  if (value === 'representation' || value === 'transfert') {
    return value
  }
  return 'organisations'
}

export async function fetchBackofficeOrganizations(filters: BackofficeOrganizationFilters = {}): Promise<BackofficeOrganizationList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.onglet && filters.onglet !== 'organisations') {
      params.set('onglet', filters.onglet)
    }
    if (filters.type_organisation_id) {
      params.set('type_organisation_id', filters.type_organisation_id)
    }
    if (filters.verification) {
      params.set('verification', filters.verification)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 5))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeOrganizationList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/organisations${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const organisations = Array.isArray(response?.data?.organisations)
      ? response.data.organisations.map(asOrganizationRow).filter((item): item is BackofficeOrganizationRow => item !== null)
      : []
    const demandes = Array.isArray(response?.data?.demandes)
      ? response.data.demandes.map(asOrganizationDemand).filter((item): item is BackofficeOrganizationDemand => item !== null)
      : []
    const types = Array.isArray(response?.data?.types)
      ? response.data.types.map(asOrganizationType).filter((item): item is BackofficeOrganizationType => item !== null)
      : []

    return {
      onglet: asOrganizationTab(response?.data?.onglet),
      totaux: asOrganizationTotals(response?.data?.totaux),
      types,
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 5),
      }),
      organisations,
      demandes,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asKycTab(value: string | undefined): BackofficeKycTab {
  if (value === 'mes_dossiers' || value === 'a_corriger' || value === 'historique') {
    return value
  }
  return 'a_traiter'
}

function asKycTotals(value: BackofficeKycTotals | undefined): BackofficeKycTotals {
  return {
    a_traiter: asCount(value?.a_traiter),
    non_affectes: asCount(value?.non_affectes),
    en_retard: asCount(value?.en_retard),
    a_corriger: asCount(value?.a_corriger),
    mes_dossiers: asCount(value?.mes_dossiers),
    historique: asCount(value?.historique),
  }
}

function asKycSubject(value: BackofficeKycSubject | undefined): BackofficeKycSubject {
  const type = value?.type
  return {
    type: type === 'organisation' || type === 'particulier' ? type : 'unknown',
    libelle: value?.libelle || 'Dossier',
  }
}

function asKycRow(value: BackofficeKycRow | undefined): BackofficeKycRow | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    numero: value.numero || '',
    version: typeof value.version === 'number' ? value.version : 1,
    niveau: value.niveau || '',
    statut: value.statut || '',
    sujet: asKycSubject(value.sujet),
    tache: asTask(value.tache),
    responsable: asPerson(value.responsable),
    echeance_a: value.echeance_a ?? null,
    clos_a: value.clos_a ?? null,
    conflit_interet: value.conflit_interet === true,
  }
}

export async function fetchBackofficeKycDossiers(filters: BackofficeKycFilters = {}): Promise<BackofficeKycList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.onglet && filters.onglet !== 'a_traiter') {
      params.set('onglet', filters.onglet)
    }
    if (filters.niveau) {
      params.set('niveau', filters.niveau)
    }
    if (filters.responsable_id) {
      params.set('responsable_id', filters.responsable_id)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 5))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeKycList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/kyc${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const dossiers = Array.isArray(response?.data?.dossiers)
      ? response.data.dossiers.map(asKycRow).filter((item): item is BackofficeKycRow => item !== null)
      : []
    const responsables = Array.isArray(response?.data?.responsables)
      ? response.data.responsables.map(asPerson).filter((item): item is BackofficeOrganizationPerson => item !== null)
      : []

    return {
      onglet: asKycTab(response?.data?.onglet),
      totaux: asKycTotals(response?.data?.totaux),
      responsables,
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 5),
      }),
      dossiers,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asCapabilityTab(value: string | undefined): BackofficeCapabilityTab {
  if (value === 'valides' || value === 'restreintes' || value === 'expirees' || value === 'revoquees') {
    return value
  }
  return 'toutes'
}

function asCapabilityTotals(value: BackofficeCapabilityTotals | undefined): BackofficeCapabilityTotals {
  return {
    toutes: asCount(value?.toutes),
    valides: asCount(value?.valides),
    expirent_30j: asCount(value?.expirent_30j),
    restreintes: asCount(value?.restreintes),
    expirees: asCount(value?.expirees),
    revoquees: asCount(value?.revoquees),
  }
}

function asCapabilityRenewal(value: BackofficeCapabilityRenewal | undefined): BackofficeCapabilityRenewal {
  const code = value?.code
  return {
    code: code === 'en_examen' || code === 'a_corriger' || code === 'a_anticiper' || code === 'non_demande' || code === 'aucun'
      ? code
      : 'unknown',
    jours_restants: typeof value?.jours_restants === 'number' ? value.jours_restants : null,
    dossier_id: value?.dossier_id || null,
  }
}

function asCapabilityRow(value: BackofficeCapabilityRow | undefined): BackofficeCapabilityRow | null {
  if (!value?.id) {
    return null
  }
  const type = value.beneficiaire?.type
  return {
    id: value.id,
    niveau: value.niveau || '',
    etat: value.etat || '',
    valide_jusqua: value.valide_jusqua ?? null,
    obtenue_a: value.obtenue_a ?? null,
    dossier_id: value.dossier_id ?? null,
    beneficiaire: {
      type: type === 'organisation' || type === 'particulier' ? type : 'unknown',
      libelle: value.beneficiaire?.libelle || 'Bénéficiaire',
    },
    renouvellement: asCapabilityRenewal(value.renouvellement),
  }
}

export async function fetchBackofficeCapabilities(filters: BackofficeCapabilityFilters = {}): Promise<BackofficeCapabilityList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.onglet && filters.onglet !== 'toutes') {
      params.set('onglet', filters.onglet)
    }
    if (filters.niveau) {
      params.set('niveau', filters.niveau)
    }
    if (filters.echeance) {
      params.set('echeance', filters.echeance)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 6))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeCapabilityList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/habilitations${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const habilitations = Array.isArray(response?.data?.habilitations)
      ? response.data.habilitations.map(asCapabilityRow).filter((item): item is BackofficeCapabilityRow => item !== null)
      : []

    return {
      onglet: asCapabilityTab(response?.data?.onglet),
      totaux: asCapabilityTotals(response?.data?.totaux),
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 6),
      }),
      habilitations,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asTeamTotals(value: BackofficeTeamTotals | undefined): BackofficeTeamTotals {
  return {
    agents_actifs: asCount(value?.agents_actifs),
    attributions_actives: asCount(value?.attributions_actives),
    suppleances_planifiees: asCount(value?.suppleances_planifiees),
    configurations_a_approuver: asCount(value?.configurations_a_approuver),
  }
}

function asTeamCapabilities(value: BackofficeTeamCapabilities | undefined): BackofficeTeamCapabilities {
  return {
    permissions: value?.permissions === true,
    suppleances: value?.suppleances === true,
    configurations: value?.configurations === true,
  }
}

function asTeamAgent(value: BackofficeTeamAgent | undefined): BackofficeTeamAgent | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    prenom: value.prenom ?? null,
    nom: value.nom ?? null,
    email_masque: value.email_masque ?? null,
    permissions: Array.isArray(value.permissions) ? value.permissions.filter(code => typeof code === 'string') : [],
    permissions_actives: asCount(value.permissions_actives),
    expire_a: value.expire_a ?? null,
  }
}

function asTeamSubstitute(value: BackofficeTeamSubstitute | undefined): BackofficeTeamSubstitute | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    fonction: value.fonction || '',
    debut_a: value.debut_a ?? null,
    fin_a: value.fin_a ?? null,
    titulaire: asPerson(value.titulaire),
    suppleant: asPerson(value.suppleant),
  }
}

function asTeamConfiguration(value: BackofficeTeamConfiguration | undefined): BackofficeTeamConfiguration | null {
  if (!value?.code) {
    return null
  }
  return {
    code: value.code,
    version: typeof value.version === 'number' ? value.version : null,
    courante: value.courante === true,
  }
}

export async function fetchBackofficeTeam(filters: BackofficeTeamFilters = {}): Promise<BackofficeTeamList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.fonction) {
      params.set('fonction', filters.fonction)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 4))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeTeamList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/equipe${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const agents = Array.isArray(response?.data?.agents)
      ? response.data.agents.map(asTeamAgent).filter((item): item is BackofficeTeamAgent => item !== null)
      : []
    const upcoming = Array.isArray(response?.data?.suppleances_a_venir)
      ? response.data.suppleances_a_venir.map(asTeamSubstitute).filter((item): item is BackofficeTeamSubstitute => item !== null)
      : []
    const configurations = Array.isArray(response?.data?.configurations)
      ? response.data.configurations.map(asTeamConfiguration).filter((item): item is BackofficeTeamConfiguration => item !== null)
      : []

    return {
      totaux: asTeamTotals(response?.data?.totaux),
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 4),
      }),
      agents,
      suppleances_a_venir: upcoming,
      configurations,
      capacites: asTeamCapabilities(response?.data?.capacites),
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

function asSupportTab(value: string | undefined): BackofficeSupportTab {
  if (value === 'mes_demandes' || value === 'attente_demandeur' || value === 'cloturees') {
    return value
  }
  return 'a_traiter'
}

function asSupportTotals(value: BackofficeSupportTotals | undefined): BackofficeSupportTotals {
  return {
    ouvertes: asCount(value?.ouvertes),
    a_traiter: asCount(value?.a_traiter),
    non_affectes: asCount(value?.non_affectes),
    priorite_haute: asCount(value?.priorite_haute),
    reponses_en_retard: asCount(value?.reponses_en_retard),
    mes_demandes: asCount(value?.mes_demandes),
    attente_demandeur: asCount(value?.attente_demandeur),
    cloturees: asCount(value?.cloturees),
  }
}

function asSupportRequester(value: BackofficeSupportRequester | undefined): BackofficeSupportRequester {
  return {
    id: value?.id || null,
    prenom: value?.prenom ?? null,
    nom: value?.nom ?? null,
    email_masque: value?.email_masque ?? null,
    externe: value?.externe === true,
  }
}

function asSupportRow(value: BackofficeSupportRow | undefined): BackofficeSupportRow | null {
  if (!value?.id || !value.reference) {
    return null
  }
  return {
    id: value.id,
    reference: value.reference,
    type: value.type || '',
    identite_etat: value.identite_etat || '',
    cree_a: value.cree_a ?? null,
    clos_a: value.clos_a ?? null,
    demandeur: asSupportRequester(value.demandeur),
    tache: asTask(value.tache),
    responsable: asPerson(value.responsable),
    echeance_a: value.echeance_a ?? null,
  }
}

export async function fetchBackofficeSupportDemands(filters: BackofficeSupportFilters = {}): Promise<BackofficeSupportList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.onglet && filters.onglet !== 'a_traiter') {
      params.set('onglet', filters.onglet)
    }
    if (filters.type) {
      params.set('type', filters.type)
    }
    if (filters.priorite) {
      params.set('priorite', filters.priorite)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 5))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeSupportList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/recours${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const demandes = Array.isArray(response?.data?.demandes)
      ? response.data.demandes.map(asSupportRow).filter((item): item is BackofficeSupportRow => item !== null)
      : []

    return {
      onglet: asSupportTab(response?.data?.onglet),
      totaux: asSupportTotals(response?.data?.totaux),
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 5),
      }),
      demandes,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function fetchBackofficeDossier(dossierId: string): Promise<BackofficeReview> {
  try {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}/api/v1/backoffice/identity-access/kyc/${dossierId}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function assignBackofficeTask(taskId: string): Promise<BackofficeReview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}/api/v1/backoffice/identity-access/taches/${taskId}/affectation`,
      {
        method: 'POST',
        body: {},
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  })
}

export async function takeChargeBackofficeDossier(dossierId: string): Promise<BackofficeReview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}/api/v1/backoffice/identity-access/kyc/${dossierId}/prise-en-charge`,
      {
        method: 'POST',
        body: {},
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  })
}

export async function recordBackofficeControl(
  dossierId: string,
  payload: {
    controle_id: string
    resultat: 'passed' | 'failed' | 'not_applicable'
    observation?: string
    justification_non_applicable?: string
  },
): Promise<BackofficeReview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}/api/v1/backoffice/identity-access/kyc/${dossierId}/controles`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  })
}

export async function decideBackofficeDossier(
  dossierId: string,
  payload: { resultat: 'approved' | 'needs_fix' | 'rejected', motif: string },
  idempotencyKey: string,
): Promise<BackofficeReview> {
  return postDecision(`/api/v1/backoffice/identity-access/kyc/${dossierId}/decision`, payload, idempotencyKey)
}

export async function decideCommercialBackofficeDossier(
  dossierId: string,
  payload: { resultat: 'approved' | 'needs_fix' | 'rejected', motif: string },
  idempotencyKey: string,
): Promise<BackofficeReview> {
  return postDecision(`/api/v1/backoffice/identity-access/kyc/${dossierId}/decision-commerciale`, payload, idempotencyKey)
}

async function postDecision(
  path: string,
  payload: { resultat: 'approved' | 'needs_fix' | 'rejected', motif: string },
  idempotencyKey: string,
): Promise<BackofficeReview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}${path}`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  })
}

export async function restrictBackofficeCapability(
  capabilityId: string,
  payload: { etat: 'a_renouveler' | 'restreint' | 'revoked', motif: string },
  idempotencyKey: string,
): Promise<BackofficeReview> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<BackofficeReview>>(
      `${apiBase()}/api/v1/backoffice/identity-access/habilitations/${capabilityId}/restriction`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const review = asReview(response?.data)
    if (!review) {
      throw { statusCode: 503 }
    }

    return review
  })
}

export async function downloadBackofficeEvidence(dossierId: string, pieceId: string, filename: string): Promise<void> {
  try {
    const blob = await $fetch<Blob>(
      `${apiBase()}/api/v1/backoffice/identity-access/kyc/${dossierId}/pieces/${pieceId}`,
      {
        credentials: 'include',
        timeout: 30000,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const objectUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = objectUrl
    link.download = filename
    link.rel = 'noreferrer'
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(objectUrl)
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function transferPrimaryRepresentative(
  organizationId: string,
  payload: { nouveau_compte_id: string, motif: string, depart: boolean, demande_id?: string | null },
  idempotencyKey: string,
): Promise<RepresentativeTransferResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RepresentativeTransferResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/organisations/${organizationId}/transfert-principal`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asTransferResult(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

function asTransferParty(value: RepresentativeTransferResult['nouveau'] | null | undefined): RepresentativeTransferResult['nouveau'] | null {
  if (!value?.compte_id || !value.adhesion) {
    return null
  }

  return {
    compte_id: value.compte_id,
    email_masque: value.email_masque ?? null,
    prenom: value.prenom ?? null,
    nom: value.nom ?? null,
    adhesion: {
      id: value.adhesion.id,
      principal: value.adhesion.principal === true,
      statut: value.adhesion.statut || '',
      fonction: value.adhesion.fonction ?? null,
      permissions: Array.isArray(value.adhesion.permissions) ? value.adhesion.permissions : [],
    },
    sessions_revoquees: Number(value.sessions_revoquees) || 0,
  }
}

function asTransferResult(value: RepresentativeTransferResult | null | undefined): RepresentativeTransferResult | null {
  const incoming = asTransferParty(value?.nouveau)
  if (!value?.organisation?.id || !incoming) {
    return null
  }

  return {
    statut: value.statut || '',
    prochaine_action: value.prochaine_action || '',
    organisation: {
      id: value.organisation.id,
      raison_sociale: value.organisation.raison_sociale || '',
      statut_operationnel: value.organisation.statut_operationnel || '',
    },
    ancien: asTransferParty(value.ancien),
    nouveau: incoming,
    demande: value.demande?.reference ? { reference: value.demande.reference } : null,
    depart: value.depart === true,
  }
}

export async function suspendAccount(
  accountId: string,
  payload: { motif: string, fin_prevue: string | null },
  idempotencyKey: string,
): Promise<AccountSuspensionResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AccountSuspensionResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/comptes/${accountId}/suspension`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asSuspensionResult(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

function asRestrictedOrganization(value: RestrictedOrganization | undefined): RestrictedOrganization | null {
  if (!value?.id) {
    return null
  }

  return {
    id: value.id,
    raison_sociale: value.raison_sociale || '',
    statut_operationnel: value.statut_operationnel || '',
  }
}

function asSuspensionResult(value: AccountSuspensionResult | null | undefined): AccountSuspensionResult | null {
  if (!value?.compte?.id || !value.compte.statut) {
    return null
  }

  const organisations = Array.isArray(value.organisations_restreintes)
    ? value.organisations_restreintes
      .map(asRestrictedOrganization)
      .filter((item): item is RestrictedOrganization => item !== null)
    : []

  return {
    statut: value.statut || '',
    prochaine_action: value.prochaine_action || '',
    propagation: value.propagation || '',
    compte: {
      id: value.compte.id,
      statut: value.compte.statut,
      email_masque: value.compte.email_masque ?? null,
      prenom: value.compte.prenom ?? null,
      nom: value.compte.nom ?? null,
    },
    sessions_revoquees: Number(value.sessions_revoquees) || 0,
    fin_prevue: value.fin_prevue ?? null,
    organisations_restreintes: organisations,
  }
}

export async function reactivateAccount(
  accountId: string,
  payload: { demande_id: string, motif: string, avis_securite: string | null },
  idempotencyKey: string,
): Promise<AccountReactivationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AccountReactivationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/comptes/${accountId}/reactivation`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asReactivationResult(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

function asReactivationResult(value: AccountReactivationResult | null | undefined): AccountReactivationResult | null {
  if (!value?.compte?.id || !value.compte.statut || !value.demande?.reference) {
    return null
  }

  return {
    statut: value.statut || '',
    prochaine_action: value.prochaine_action || '',
    propagation: value.propagation || '',
    compte: {
      id: value.compte.id,
      statut: value.compte.statut,
      email_masque: value.compte.email_masque ?? null,
      prenom: value.compte.prenom ?? null,
      nom: value.compte.nom ?? null,
    },
    demande: {
      id: value.demande.id,
      reference: value.demande.reference,
    },
    sessions_revoquees: Number(value.sessions_revoquees) || 0,
    conditions: {
      email_verifie: value.conditions?.email_verifie === true,
      profil_complet: value.conditions?.profil_complet === true,
      textes_requis: value.conditions?.textes_requis === true,
    },
    avis_securite_consigne: value.avis_securite_consigne === true,
  }
}

export async function closeAccount(
  accountId: string,
  payload: { demande_id: string, motif: string },
  idempotencyKey: string,
): Promise<AccountClosureResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AccountClosureResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/comptes/${accountId}/cloture`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asClosureResult(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

export async function verifyClosureIdentity(
  demandKey: string,
  payload: { compte_id: string, motif: string, methode_verification: string },
  idempotencyKey: string,
): Promise<ClosureIdentityResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ClosureIdentityResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/cloture/${encodeURIComponent(demandKey)}/identite`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asClosureIdentityResult(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

function asClosureIdentityResult(value: ClosureIdentityResult | null | undefined): ClosureIdentityResult | null {
  if (!value?.demande?.id || !value.demande.reference || !value.compte?.id) {
    return null
  }

  return {
    statut: value.statut || '',
    prochaine_action: value.prochaine_action || '',
    demande: {
      id: value.demande.id,
      reference: value.demande.reference,
      identite_etat: value.demande.identite_etat || '',
    },
    compte: {
      id: value.compte.id,
      statut: value.compte.statut || '',
      email_masque: value.compte.email_masque ?? null,
    },
  }
}

function asClosureResult(value: AccountClosureResult | null | undefined): AccountClosureResult | null {
  if (!value?.compte?.id || !value.compte.statut || !value.demande?.reference) {
    return null
  }

  return {
    statut: value.statut || '',
    prochaine_action: value.prochaine_action || '',
    propagation: value.propagation || '',
    compte: {
      id: value.compte.id,
      statut: value.compte.statut,
      ferme_a: value.compte.ferme_a ?? null,
      email_masque: value.compte.email_masque ?? null,
      prenom: value.compte.prenom ?? null,
      nom: value.compte.nom ?? null,
    },
    demande: {
      id: value.demande.id,
      reference: value.demande.reference,
    },
    sessions_revoquees: Number(value.sessions_revoquees) || 0,
    defis_revoques: Number(value.defis_revoques) || 0,
  }
}

function asPermissionOption(value: InternalPermissionOption | undefined): InternalPermissionOption | null {
  if (!value?.code) {
    return null
  }

  return {
    code: value.code,
    libelle: value.libelle || value.code,
  }
}

function asAttribution(value: InternalAttribution | undefined): InternalAttribution | null {
  if (!value?.id || !value.permission || !value.etat) {
    return null
  }

  return {
    id: value.id,
    permission: value.permission,
    libelle: value.libelle || value.permission,
    etat: value.etat,
    attribue_a: value.attribue_a ?? null,
    expire_a: value.expire_a ?? null,
    revoque_a: value.revoque_a ?? null,
    motif: value.motif || '',
  }
}

function asPermissionCatalog(value: InternalPermissionCatalog | null | undefined): InternalPermissionCatalog {
  const permissions = Array.isArray(value?.permissions)
    ? value.permissions.map(asPermissionOption).filter((item): item is InternalPermissionOption => item !== null)
    : []
  const attributions = Array.isArray(value?.attributions)
    ? value.attributions.map(asAttribution).filter((item): item is InternalAttribution => item !== null)
    : []

  return {
    permissions,
    compte: value?.compte?.id
      ? {
          id: value.compte.id,
          statut: value.compte.statut || '',
        }
      : null,
    attributions,
  }
}

function asPermissionMutation(value: InternalPermissionMutationResult | null | undefined): InternalPermissionMutationResult | null {
  const attribution = asAttribution(value?.attribution)
  if (!value?.statut || !value.compte_id || !attribution) {
    return null
  }

  return {
    statut: value.statut,
    prochaine_action: value.prochaine_action || '',
    compte_id: value.compte_id,
    attribution,
  }
}

export async function fetchInternalPermissionCatalog(compteId?: string | null): Promise<InternalPermissionCatalog> {
  try {
    const query = compteId ? `?compte_id=${encodeURIComponent(compteId)}` : ''
    const response = await $fetch<ApiSuccess<InternalPermissionCatalog>>(
      `${apiBase()}/api/v1/backoffice/identity-access/permissions${query}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return asPermissionCatalog(response?.data)
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function grantInternalPermission(
  payload: { compte_id: string, permission: string, motif: string, expire_a: string | null },
  idempotencyKey: string,
): Promise<InternalPermissionMutationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<InternalPermissionMutationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/permissions`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asPermissionMutation(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

export async function revokeInternalPermission(
  attributionId: string,
  payload: { motif: string },
  idempotencyKey: string,
): Promise<InternalPermissionMutationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<InternalPermissionMutationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/permissions/${attributionId}/revocation`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const result = asPermissionMutation(response?.data)
    if (!result) {
      throw { statusCode: 503 }
    }

    return result
  })
}

function asConfigurationVersion(value: ServiceConfigurationVersion | null | undefined): ServiceConfigurationVersion | null {
  if (!value?.id || !value.code) {
    return null
  }

  return {
    id: value.id,
    code: value.code,
    version: Number(value.version) || 0,
    schema_version: Number(value.schema_version) || 0,
    valeurs: value.valeurs && typeof value.valeurs === 'object' ? value.valeurs : {},
    empreinte: value.empreinte || '',
    effet_a: value.effet_a ?? null,
    approuve_a: value.approuve_a ?? null,
    courante: Boolean(value.courante),
  }
}

function asConfigurationCatalog(value: ServiceConfigurationCatalog | null | undefined): ServiceConfigurationCatalog {
  const groups = (value?.configurations ?? []).map((group): ServiceConfigurationGroup | null => {
    if (!group?.code) {
      return null
    }
    return {
      code: group.code,
      courante: asConfigurationVersion(group.courante),
      versions: (group.versions ?? []).map(asConfigurationVersion).filter((item): item is ServiceConfigurationVersion => item !== null),
    }
  }).filter((item): item is ServiceConfigurationGroup => item !== null)

  return { configurations: groups }
}

function asSubstitute(value: ServiceSubstitute | null | undefined): ServiceSubstitute | null {
  if (!value?.id || !value.titulaire_id || !value.suppleant_id || !value.fonction) {
    return null
  }

  return {
    id: value.id,
    titulaire_id: value.titulaire_id,
    suppleant_id: value.suppleant_id,
    fonction: value.fonction,
    debut_a: value.debut_a ?? null,
    fin_a: value.fin_a ?? null,
    etat: value.etat || '',
    motif: value.motif || '',
    permission_implicite: Boolean(value.permission_implicite),
  }
}

function asTransmission(value: RetryableTransmission | null | undefined): RetryableTransmission | null {
  if (!value?.id || !value.etat) {
    return null
  }

  return {
    id: value.id,
    etat: value.etat,
    canal: value.canal || '',
    type: value.type ?? null,
    operation: value.operation ?? null,
    destination_masquee: value.destination_masquee ?? null,
    nombre_tentatives: Number(value.nombre_tentatives) || 0,
    prochaine_tentative_a: value.prochaine_tentative_a ?? null,
    niveau_acquittement: value.niveau_acquittement ?? null,
    envoye_a: value.envoye_a ?? null,
  }
}

function asSupervisionTab(value: string | undefined): BackofficeSupervisionTab {
  if (value === 'journal' || value === 'reprises') {
    return value
  }
  return 'supervision'
}

function asSupervisionTotals(value: SupervisionTotals | undefined): SupervisionTotals {
  return {
    echecs: asCount(value?.echecs),
    attente_accuse: asCount(value?.attente_accuse),
    messages_en_attente: asCount(value?.messages_en_attente),
    evenements_aujourdhui: asCount(value?.evenements_aujourdhui),
  }
}

function asSupervisionAttempt(value: SupervisionAttempt | undefined): SupervisionAttempt | null {
  const numero = Number(value?.numero)
  if (!value || !Number.isFinite(numero)) {
    return null
  }
  return {
    numero,
    resultat: value.resultat || '',
    commence_a: value.commence_a ?? null,
    termine_a: value.termine_a ?? null,
    code_erreur_masque: value.code_erreur_masque ?? null,
  }
}

function asSupervisionTransmission(value: SupervisionTransmission | undefined): SupervisionTransmission | null {
  const base = asTransmission(value)
  if (!base) {
    return null
  }
  const tentatives = Array.isArray(value?.tentatives)
    ? value.tentatives.map(asSupervisionAttempt).filter((item): item is SupervisionAttempt => item !== null)
    : []
  return {
    ...base,
    reference: value?.reference || '',
    derniere_tentative_a: value?.derniere_tentative_a ?? null,
    eligible_reprise: value?.eligible_reprise === true,
    tentatives,
  }
}

function asJournalEntry(value: SupervisionJournalEntry | undefined): SupervisionJournalEntry | null {
  if (!value?.id || !value.type) {
    return null
  }
  return {
    id: value.id,
    source: value.source === 'kyc' ? 'kyc' : 'compte',
    type: value.type,
    date: value.date ?? null,
    auteur: asPerson(value.auteur),
    systeme: value.systeme === true,
    reference: value.reference || '',
    sujet: asPerson(value.sujet),
  }
}

function asRetryEntry(value: SupervisionRetryEntry | undefined): SupervisionRetryEntry | null {
  if (!value?.id) {
    return null
  }
  return {
    id: value.id,
    type: value.type || 'reprise_transmission',
    date: value.date ?? null,
    transmission_id: value.transmission_id ?? null,
    etat: value.etat ?? null,
    canal: value.canal ?? null,
    destination_masquee: value.destination_masquee ?? null,
    reference: value.reference || '',
  }
}

export async function fetchBackofficeSupervision(
  filters: BackofficeSupervisionFilters = {},
): Promise<BackofficeSupervisionList> {
  try {
    const params = new URLSearchParams()
    const search = filters.q?.trim()
    if (search) {
      params.set('q', search)
    }
    if (filters.onglet && filters.onglet !== 'supervision') {
      params.set('onglet', filters.onglet)
    }
    if (filters.canal) {
      params.set('canal', filters.canal)
    }
    if (filters.etat) {
      params.set('etat', filters.etat)
    }
    if (filters.jour && filters.jour !== 'aujourdhui') {
      params.set('jour', filters.jour)
    }
    if (filters.page && filters.page > 1) {
      params.set('page', String(filters.page))
    }
    params.set('per_page', String(filters.per_page || 5))
    const query = params.toString()
    const response = await $fetch<ApiSuccess<BackofficeSupervisionList>>(
      `${apiBase()}/api/v1/backoffice/identity-access/supervision${query ? `?${query}` : ''}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return {
      onglet: asSupervisionTab(response?.data?.onglet),
      totaux: asSupervisionTotals(response?.data?.totaux),
      pagination: asAccountPagination({
        ...response?.data?.pagination,
        per_page: asCount(response?.data?.pagination?.per_page, 5),
      }),
      journal_recent: Array.isArray(response?.data?.journal_recent)
        ? response.data.journal_recent.map(asJournalEntry).filter((item): item is SupervisionJournalEntry => item !== null)
        : [],
      capacites: {
        reprendre: response?.data?.capacites?.reprendre === true,
      },
      transmissions: Array.isArray(response?.data?.transmissions)
        ? response.data.transmissions.map(asSupervisionTransmission).filter((item): item is SupervisionTransmission => item !== null)
        : [],
      evenements: Array.isArray(response?.data?.evenements)
        ? response.data.evenements.map(asJournalEntry).filter((item): item is SupervisionJournalEntry => item !== null)
        : [],
      reprises: Array.isArray(response?.data?.reprises)
        ? response.data.reprises.map(asRetryEntry).filter((item): item is SupervisionRetryEntry => item !== null)
        : [],
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function fetchServiceConfigurations(code?: string | null): Promise<ServiceConfigurationCatalog> {
  try {
    const query = code ? `?code=${encodeURIComponent(code)}` : ''
    const response = await $fetch<ApiSuccess<ServiceConfigurationCatalog>>(
      `${apiBase()}/api/v1/backoffice/identity-access/configurations${query}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return asConfigurationCatalog(response?.data)
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function approveServiceConfiguration(
  payload: { code: string, valeurs: Record<string, unknown>, effet_a: string },
  idempotencyKey: string,
): Promise<ServiceConfigurationMutationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ServiceConfigurationMutationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/configurations`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const configuration = asConfigurationVersion(response?.data?.configuration)
    if (!response?.data?.statut || !configuration) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || '',
      configuration,
      courante: Boolean(response.data.courante),
    }
  })
}

export async function fetchServiceSubstitutes(): Promise<ServiceSubstituteCatalog> {
  try {
    const response = await $fetch<ApiSuccess<ServiceSubstituteCatalog>>(
      `${apiBase()}/api/v1/backoffice/identity-access/suppleances`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return {
      suppleances: (response?.data?.suppleances ?? []).map(asSubstitute).filter((item): item is ServiceSubstitute => item !== null),
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function assignServiceSubstitute(
  payload: {
    titulaire_id: string
    suppleant_id: string
    fonction: string
    debut_a: string
    fin_a: string
    motif: string
  },
  idempotencyKey: string,
): Promise<ServiceSubstituteMutationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ServiceSubstituteMutationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/suppleances`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const substitute = asSubstitute(response?.data?.suppleance)
    if (!response?.data?.statut || !substitute) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || '',
      suppleance: substitute,
    }
  })
}

export async function fetchRetryableTransmissions(): Promise<RetryableTransmissionCatalog> {
  try {
    const response = await $fetch<ApiSuccess<RetryableTransmissionCatalog>>(
      `${apiBase()}/api/v1/backoffice/identity-access/transmissions`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return {
      transmissions: (response?.data?.transmissions ?? []).map(asTransmission).filter((item): item is RetryableTransmission => item !== null),
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function retryTransmission(
  transmissionId: string,
  idempotencyKey: string,
): Promise<RetryTransmissionResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RetryTransmissionResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/transmissions/${transmissionId}/reprise`,
      {
        method: 'POST',
        body: {},
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const transmission = asTransmission(response?.data?.transmission)
    if (!response?.data?.statut || !transmission) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || '',
      transmission,
    }
  })
}
