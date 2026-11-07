import type {
  ChangePasswordRequest,
  ChangePasswordResponse,
  CurrentAccount,
  EnableAuthorPathPayload,
  EnableAuthorPathResponse,
  RecordConsentRequest,
  RecordConsentResponse,
} from '../../types/identity-access/account'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { asVerificationDossierSummaries } from './verification'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function fetchCurrentAccount(): Promise<CurrentAccount> {
  try {
    const response = await $fetch<ApiSuccess<CurrentAccount>>(
      `${apiBase()}/api/v1/identity-access/moi`,
      {
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    if (!response?.data?.compte?.statut) {
      throw { statusCode: 503 }
    }

    return {
      ...response.data,
      profil: response.data.profil ?? null,
      roles: Array.isArray(response.data.roles) ? response.data.roles : [],
      session: response.data.session ?? null,
      consentements: Array.isArray(response.data.consentements) ? response.data.consentements : [],
      textes_en_attente: Array.isArray(response.data.textes_en_attente) ? response.data.textes_en_attente : [],
      parcours_auteur: normalizeAuthorPath(response.data.parcours_auteur),
      parcours_inscription: response.data.parcours_inscription ?? null,
      organisations: Array.isArray(response.data.organisations)
        ? response.data.organisations.map(organization => ({
            ...organization,
            exigences: Array.isArray(organization.exigences) ? organization.exigences : [],
          }))
        : [],
      dossiers_verification: asVerificationDossierSummaries(response.data.dossiers_verification),
      permissions_internes: Array.isArray(response.data.permissions_internes) ? response.data.permissions_internes : [],
      recours: response.data.recours?.reference
        ? {
            reference: response.data.recours.reference,
            type: response.data.recours.type || '',
            identite_etat: response.data.recours.identite_etat || '',
          }
        : null,
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function changePassword(payload: ChangePasswordRequest): Promise<ChangePasswordResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<ChangePasswordResponse>>(
      `${apiBase()}/api/v1/identity-access/moi/mot-de-passe`,
      {
        method: 'PUT',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    if (!response?.data?.prochaine_action) {
      throw { statusCode: 503 }
    }

    return response.data
  })
}

export async function recordConsent(payload: RecordConsentRequest): Promise<RecordConsentResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<RecordConsentResponse>>(
      `${apiBase()}/api/v1/identity-access/moi/consentements`,
      {
        method: 'POST',
        body: payload,
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(),
      },
    )

    if (!response?.data?.consentement?.id) {
      throw { statusCode: 503 }
    }

    return {
      consentement: response.data.consentement,
      consentements: Array.isArray(response.data.consentements) ? response.data.consentements : [],
      textes_en_attente: Array.isArray(response.data.textes_en_attente) ? response.data.textes_en_attente : [],
    }
  })
}

export async function enableAuthorPath(
  payload: EnableAuthorPathPayload,
  idempotencyKey: string,
): Promise<EnableAuthorPathResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<EnableAuthorPathResponse>>(
      `${apiBase()}/api/v1/identity-access/moi/parcours-auteur`,
      {
        method: 'POST',
        body: payload.auteur_id ? { auteur_id: payload.auteur_id } : {},
        credentials: 'include',
        timeout: 20000,
        headers: identityMutationHeaders(idempotencyKey),
      },
    )

    if (!response?.data?.statut || !response.data.prochaine_action) {
      throw { statusCode: 503 }
    }

    return {
      statut: response.data.statut,
      prochaine_action: response.data.prochaine_action,
      fiche: response.data.fiche ?? null,
      demande: response.data.demande ?? null,
    }
  })
}

function normalizeAuthorPath(value: CurrentAccount['parcours_auteur'] | undefined): CurrentAccount['parcours_auteur'] {
  if (!value || typeof value.etat !== 'string' || value.etat === '') {
    return {
      etat: 'unknown',
      fiche: null,
      demande: null,
    }
  }

  return {
    etat: value.etat,
    fiche: value.fiche ?? null,
    demande: value.demande ?? null,
  }
}
