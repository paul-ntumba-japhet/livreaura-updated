import type {
  CreateOrganizationPayload,
  CreateOrganizationResponse,
  OrganizationMatchesResponse,
  OrganizationSummary,
} from '../../types/identity-access/organization'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase, identityMutationHeaders, withIdentityCsrf } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

function asOrganization(value: OrganizationSummary | null | undefined): OrganizationSummary | null {
  if (!value?.id || !value.raison_sociale) {
    return null
  }

  return {
    id: value.id,
    raison_sociale: value.raison_sociale,
    pays: value.pays,
    ville: value.ville ?? null,
    statut_operationnel: value.statut_operationnel,
    type: value.type ?? null,
  }
}

function asOrganizations(value: OrganizationSummary[] | undefined): OrganizationSummary[] {
  if (!Array.isArray(value)) {
    return []
  }

  return value.map(item => asOrganization(item)).filter((item): item is OrganizationSummary => item !== null)
}

export async function fetchOrganizationMatches(query: {
  type_organisation_id: string
  raison_sociale: string
  pays: string
}): Promise<OrganizationSummary[]> {
  try {
    const response = await $fetch<ApiSuccess<OrganizationMatchesResponse>>(
      `${apiBase()}/api/v1/identity-access/organisations/correspondances`,
      {
        query,
        credentials: 'include',
        timeout: 15000,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    )

    return asOrganizations(response?.data?.correspondances)
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}

export async function createOrRecognizeOrganization(
  payload: CreateOrganizationPayload,
  idempotencyKey: string,
): Promise<CreateOrganizationResponse> {
  return withIdentityCsrf(async () => {
    const response = await $fetch<ApiSuccess<CreateOrganizationResponse>>(
      `${apiBase()}/api/v1/identity-access/organisations`,
      {
        method: 'POST',
        body: {
          type_organisation_id: payload.type_organisation_id,
          raison_sociale: payload.raison_sociale,
          pays: payload.pays,
          ville: payload.ville || undefined,
          email_pro: payload.email_pro || undefined,
          site: payload.site || undefined,
          organisation_id: payload.organisation_id || undefined,
          confirmer_nouvelle: payload.confirmer_nouvelle === true ? true : undefined,
        },
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
      organisation: asOrganization(response.data.organisation),
      adhesion: response.data.adhesion ?? null,
      demande: response.data.demande ?? null,
      correspondances: asOrganizations(response.data.correspondances),
    }
  })
}
