import type { RegistrationPathsData } from '../../types/identity-access/registration'
import { normalizeIdentityFetchError } from '../../utils/identity-access/errors'
import { apiBase } from './http'

type ApiSuccess<T> = {
  data: T
  correlation_id: string
}

export async function fetchRegistrationPaths(langue = 'fr'): Promise<RegistrationPathsData> {
  try {
    const response = await $fetch<ApiSuccess<RegistrationPathsData>>(
      `${apiBase()}/api/v1/identity-access/parcours`,
      {
        query: { langue },
        credentials: 'include',
        timeout: 8000,
      },
    )

    if (!response?.data?.parcours || !Array.isArray(response.data.parcours)) {
      throw { statusCode: 503 }
    }

    return {
      ...response.data,
      textes_requis: Array.isArray(response.data.textes_requis) ? response.data.textes_requis : [],
      types_organisation: Array.isArray(response.data.types_organisation)
        ? response.data.types_organisation.map(type => ({
            ...type,
            exigences: Array.isArray(type.exigences) ? type.exigences : [],
          }))
        : [],
      exigences_auteur: Array.isArray(response.data.exigences_auteur) ? response.data.exigences_auteur : [],
    }
  }
  catch (error) {
    throw normalizeIdentityFetchError(error)
  }
}
