import type { AppealAuthorizationResult, AppealDepositResult, AppealDocumentGrantResult, SupportDemand, SupportEvidence, SupportExchange } from '../../types/identity-access/support'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function openSuspensionAppeal(
  payload: { email: string, message: string },
  idempotencyKey: string,
): Promise<AppealDepositResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AppealDepositResult>>(
      `${apiBase()}/api/v1/identity-access/recours`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.reference || response.data.statut !== 'recu') {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || '',
      reference: response.data.reference,
    }
  })
}

export async function openAccountClosure(
  payload: { email: string, message: string },
  idempotencyKey: string,
): Promise<AppealDepositResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AppealDepositResult>>(
      `${apiBase()}/api/v1/identity-access/cloture`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.reference || response.data.statut !== 'recu') {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action || '',
      reference: response.data.reference,
    }
  })
}

export async function authorizeAppealSession(
  demandKey: string,
  payload: { compte_id: string, motif: string, methode_verification: string },
  idempotencyKey: string,
): Promise<AppealAuthorizationResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AppealAuthorizationResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/recours/${encodeURIComponent(demandKey)}/autorisation-session`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.demande?.reference || response.data.session?.portee !== 'support_only') {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut || '',
      prochaine_action: response.data.prochaine_action || '',
      demande: {
        id: response.data.demande.id,
        reference: response.data.demande.reference,
        identite_etat: response.data.demande.identite_etat,
      },
      compte: {
        id: response.data.compte.id,
        statut: response.data.compte.statut,
        email_masque: response.data.compte.email_masque ?? null,
      },
      session: {
        portee: response.data.session.portee,
        expire_absolue_a: response.data.session.expire_absolue_a ?? null,
      },
    }
  })
}

export async function authorizeAppealDocument(
  demandKey: string,
  payload: {
    compte_id: string
    facture_vente_id?: string | null
    facture_commission_id?: string | null
    avoir_id?: string | null
    recu_paiement_id?: string | null
    debut: string
    fin: string
    motif: string
  },
  idempotencyKey: string,
): Promise<AppealDocumentGrantResult> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<AppealDocumentGrantResult>>(
      `${apiBase()}/api/v1/backoffice/identity-access/recours/${encodeURIComponent(demandKey)}/acces-documents`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.acces?.id || !response.data.demande?.reference || !response.data.acces.document?.id) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut || '',
      prochaine_action: response.data.prochaine_action || '',
      demande: {
        id: response.data.demande.id,
        reference: response.data.demande.reference,
      },
      acces: {
        id: response.data.acces.id,
        debut: response.data.acces.debut,
        fin: response.data.acces.fin,
        document: {
          type: response.data.acces.document.type,
          id: response.data.acces.document.id,
        },
      },
    }
  })
}

function asExchange(value: SupportExchange | undefined): SupportExchange | null {
  if (!value?.id || !value.origine || !value.visibilite) {
    return null
  }

  return {
    id: value.id,
    origine: value.origine,
    visibilite: value.visibilite,
    contenu: value.contenu || '',
    date: value.date ?? null,
    canal: value.canal || '',
  }
}

function asEvidence(value: SupportEvidence | undefined): SupportEvidence | null {
  if (!value?.id || !value.type || !value.acces) {
    return null
  }

  return {
    id: value.id,
    type: value.type,
    acces: value.acces,
    echange_id: value.echange_id ?? null,
    depose_a: value.depose_a ?? null,
  }
}

function asDemand(value: SupportDemand | null | undefined): SupportDemand | null {
  if (!value?.demande?.id || !value.demande.reference) {
    return null
  }

  return {
    demande: {
      id: value.demande.id,
      reference: value.demande.reference,
      type: value.demande.type || '',
      identite_etat: value.demande.identite_etat || '',
      clos_a: value.demande.clos_a ?? null,
    },
    echanges: Array.isArray(value.echanges)
      ? value.echanges.map(asExchange).filter((item): item is SupportExchange => item !== null)
      : [],
    pieces: Array.isArray(value.pieces)
      ? value.pieces.map(asEvidence).filter((item): item is SupportEvidence => item !== null)
      : [],
    capacites: {
      repondre: value.capacites?.repondre === true,
      deposer: value.capacites?.deposer === true,
      notes_internes: value.capacites?.notes_internes === true,
      consulter_internes: value.capacites?.consulter_internes === true,
    },
  }
}

export async function fetchSupportDemand(demandKey: string): Promise<SupportDemand> {
  try {
    const response = await $fetch<ApiSuccess<SupportDemand>>(
      `${apiBase()}/api/v1/identity-access/support/${encodeURIComponent(demandKey)}`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    const demand = asDemand(response?.data)
    if (!demand) {
      throw { statusCode: 503 }
    }

    return demand
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function replyToSupport(
  demandKey: string,
  payload: { contenu: string, visibilite?: 'demandeur' | 'interne' },
  idempotencyKey: string,
): Promise<SupportDemand> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<SupportDemand>>(
      `${apiBase()}/api/v1/identity-access/support/${encodeURIComponent(demandKey)}/echanges`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    const demand = asDemand(response?.data)
    if (!demand) {
      throw { statusCode: 503 }
    }

    return demand
  })
}

export async function attachSupportEvidence(
  demandKey: string,
  payload: { type: string, acces?: 'demandeur' | 'interne', file: File },
): Promise<SupportDemand> {
  return withIdentityCsrf(async () => {
    const form = new FormData()
    form.append('type', payload.type)
    form.append('fichier', payload.file)
    if (payload.acces) {
      form.append('acces', payload.acces)
    }

    const response = await $fetch<ApiSuccess<SupportDemand>>(
      `${apiBase()}/api/v1/identity-access/support/${encodeURIComponent(demandKey)}/pieces`,
      {
        method: 'POST',
        body: form,
        credentials: 'include',
        timeout: 30000,
        headers: identityMutationHeaders(),
      },
    )

    const demand = asDemand(response?.data)
    if (!demand) {
      throw { statusCode: 503 }
    }

    return demand
  })
}

export async function downloadSupportEvidence(demandKey: string, pieceId: string, filename: string): Promise<void> {
  try {
    const blob = await $fetch<Blob>(
      `${apiBase()}/api/v1/identity-access/support/${encodeURIComponent(demandKey)}/pieces/${pieceId}`,
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
