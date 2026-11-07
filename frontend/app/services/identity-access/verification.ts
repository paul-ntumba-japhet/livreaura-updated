import type {
  AttachVerificationEvidenceResponse,
  FiscalProfile,
  OpenVerificationDossierPayload,
  OpenVerificationDossierResponse,
  PayoutAccount,
  SaveVerificationDraftPayload,
  SaveVerificationDraftResponse,
  SubmitVerificationDossierResponse,
  VerificationDocument,
  VerificationDossier,
  VerificationDossierSummary,
  VerificationGap,
  VerificationRequirement,
  VerificationSubjectSummary,
} from '../../types/identity-access/verification'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

function asSubject(value: VerificationSubjectSummary | null | undefined): VerificationSubjectSummary {
  const type = value?.type === 'organisation' || value?.type === 'particulier' ? value.type : 'unknown'
  return {
    id: value?.id ?? null,
    type,
    organisation_id: value?.organisation_id ?? null,
    raison_sociale: value?.raison_sociale ?? null,
  }
}

function asRequirements(value: VerificationRequirement[] | undefined): VerificationRequirement[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item): item is VerificationRequirement => Boolean(item?.id && item.code && item.volet))
}

function asDocuments(value: VerificationDocument[] | undefined): VerificationDocument[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item): item is VerificationDocument => Boolean(item?.id && item.type_doc))
}

function asGaps(value: VerificationGap[] | undefined): VerificationGap[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item): item is VerificationGap => Boolean(item?.code && item.motif))
}

function asFiscal(value: FiscalProfile | null | undefined): FiscalProfile | null {
  if (!value?.pays_fiscal || !value.nom_legal) {
    return null
  }

  return {
    version: Number(value.version) || 0,
    identifiant_fiscal: value.identifiant_fiscal ?? null,
    pays_fiscal: value.pays_fiscal,
    nom_legal: value.nom_legal,
    statut_controle: value.statut_controle || 'pending',
  }
}

function asPayout(value: PayoutAccount | null | undefined): PayoutAccount | null {
  if (!value?.canal || !value.titulaire || !value.affichage_masque) {
    return null
  }

  return {
    version: Number(value.version) || 0,
    canal: value.canal,
    prestataire: value.prestataire ?? null,
    titulaire: value.titulaire,
    pays: value.pays,
    devise: value.devise,
    affichage_masque: value.affichage_masque,
    statut_controle: value.statut_controle || 'pending',
  }
}

export function asVerificationDossier(value: VerificationDossier | null | undefined): VerificationDossier | null {
  if (!value?.id || !value.statut) {
    return null
  }

  return {
    id: value.id,
    numero: value.numero || '',
    version: Number(value.version) || 0,
    niveau: value.niveau || '',
    statut: value.statut,
    soumis_a: value.soumis_a ?? null,
    sujet: asSubject(value.sujet),
    exigences: asRequirements(value.exigences),
    pieces: asDocuments(value.pieces),
    profil_fiscal: asFiscal(value.profil_fiscal),
    compte_reversement: asPayout(value.compte_reversement),
    manques: asGaps(value.manques),
    complet: value.complet === true,
    habilitation: asCapability(value.habilitation),
  }
}

function asCapability(value: VerificationDossier['habilitation']): VerificationDossier['habilitation'] {
  if (!value?.id) {
    return null
  }

  return {
    id: value.id,
    niveau: value.niveau || '',
    etat: value.etat || '',
    obtenue_a: value.obtenue_a ?? null,
    valide_jusqua: value.valide_jusqua ?? null,
    motif_restriction: value.motif_restriction ?? null,
  }
}

export function asVerificationDossierSummaries(value: VerificationDossierSummary[] | undefined): VerificationDossierSummary[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value
    .filter((item): item is VerificationDossierSummary => Boolean(item?.id && item.statut))
    .map(item => ({
      id: item.id,
      numero: item.numero || '',
      version: Number(item.version) || 0,
      niveau: item.niveau || '',
      statut: item.statut,
      sujet: asSubject(item.sujet),
      habilitation: asCapability(item.habilitation),
    }))
}

export async function fetchVerificationDossier(dossierId: string): Promise<VerificationDossier> {
  try {
    const response = await $fetch<ApiSuccess<VerificationDossier>>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers/${dossierId}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const dossier = asVerificationDossier(response?.data)
    if (!dossier) {
      throw { statusCode: 503 }
    }

    return dossier
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function openVerificationDossier(
  payload: OpenVerificationDossierPayload,
  idempotencyKey: string,
): Promise<OpenVerificationDossierResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<OpenVerificationDossierResponse>>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers`,
      {
        method: 'POST',
        body: {
          niveau: payload.niveau,
          organisation_id: payload.organisation_id || undefined,
        },
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const dossier = asVerificationDossier(response?.data?.dossier)
    if (!response?.data?.statut || !response.data.prochaine_action || !dossier) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action,
      dossier,
    }
  })
}

export async function saveVerificationDraft(
  dossierId: string,
  payload: SaveVerificationDraftPayload,
): Promise<SaveVerificationDraftResponse> {
  return withIdentityCsrf(async () => {
    const body: Record<string, unknown> = {}
    if (payload.profil_fiscal) {
      body.profil_fiscal = payload.profil_fiscal
    }
    if (payload.compte_reversement) {
      body.compte_reversement = {
        canal: payload.compte_reversement.canal,
        prestataire: payload.compte_reversement.prestataire.trim() || undefined,
        titulaire: payload.compte_reversement.titulaire,
        pays: payload.compte_reversement.pays,
        devise: payload.compte_reversement.devise,
        reference_prestataire: payload.compte_reversement.reference_prestataire,
        affichage_masque: payload.compte_reversement.affichage_masque,
      }
    }

    const response = await $fetch<ApiSuccess<SaveVerificationDraftResponse>>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers/${dossierId}`,
      {
        method: 'PUT',
        body,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    const dossier = asVerificationDossier(response?.data?.dossier)
    if (!response?.data?.statut || !dossier) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || 'completer_dossier',
      dossier,
    }
  })
}

export async function attachVerificationEvidence(
  dossierId: string,
  typeDoc: string,
  file: File,
): Promise<AttachVerificationEvidenceResponse> {
  return withIdentityCsrf(async () => {
    const form = new FormData()
    form.append('type_doc', typeDoc)
    form.append('fichier', file)

    const response = await $fetch<ApiSuccess<AttachVerificationEvidenceResponse>>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers/${dossierId}/pieces`,
      {
        method: 'POST',
        body: form,
        credentials: 'include',
        timeout: 30000,
        headers: identityMutationHeaders(),
      },
    )

    const dossier = asVerificationDossier(response?.data?.dossier)
    if (!response?.data?.piece?.id || !dossier) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut || 'draft',
      prochaine_action: response.data.prochaine_action || 'completer_dossier',
      piece: response.data.piece,
      dossier,
    }
  })
}

export async function submitVerificationDossier(
  dossierId: string,
  idempotencyKey: string,
): Promise<SubmitVerificationDossierResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<SubmitVerificationDossierResponse>>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers/${dossierId}/soumission`,
      {
        method: 'POST',
        body: {},
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const dossier = asVerificationDossier(response?.data?.dossier)
    if (!response?.data?.statut || !dossier) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || 'attente_examen',
      dossier,
      manques: asGaps(response.data.manques),
    }
  })
}

export async function downloadVerificationEvidence(dossierId: string, pieceId: string, filename: string): Promise<void> {
  try {
    const blob = await $fetch<Blob>(
      `${apiBase()}/api/v1/identity-access/kyc/dossiers/${dossierId}/pieces/${pieceId}`,
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
