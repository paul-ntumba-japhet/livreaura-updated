import type { UiIconName } from '../../components/ui/icons'
import type { OrganizationRequirementDto, OrganizationTypeDto } from '../../types/identity-access/registration'

export type OrganizationDocumentHighlight = {
  id: string
  label: string
  icon: UiIconName
  count: number
}

export function iconForOrganizationTypeCode(code: string): UiIconName {
  switch (code) {
    case 'ecole':
      return 'building-2'
    case 'universite':
      return 'graduation-cap'
    case 'editeur':
      return 'landmark'
    case 'bibliotheque':
    case 'librairie':
      return 'book-open'
    case 'association':
      return 'users'
    default:
      return 'building-2'
  }
}

export function highlightLabelForOrganizationType(code: string, libelle: string): string {
  if (code === 'editeur') {
    return 'Maison d’édition'
  }
  return libelle
}

export function pieceCountLabel(count: number): string {
  return count === 1 ? '1 pièce' : `${count} pièces`
}

export function documentHighlightsFromCatalog(
  types: OrganizationTypeDto[],
  authorRequirements: OrganizationRequirementDto[],
): OrganizationDocumentHighlight[] {
  const items: OrganizationDocumentHighlight[] = [{
    id: 'auteur',
    label: 'Personnel',
    icon: 'user',
    count: authorRequirements.length,
  }]

  for (const code of ['ecole', 'editeur']) {
    const type = types.find(item => item.code === code)
    if (!type) {
      continue
    }
    items.push({
      id: type.id,
      label: highlightLabelForOrganizationType(type.code, type.libelle),
      icon: iconForOrganizationTypeCode(type.code),
      count: type.exigences?.length ?? 0,
    })
  }

  if (items.length < 3) {
    for (const type of types) {
      if (items.some(item => item.id === type.id)) {
        continue
      }
      items.push({
        id: type.id,
        label: highlightLabelForOrganizationType(type.code, type.libelle),
        icon: iconForOrganizationTypeCode(type.code),
        count: type.exigences?.length ?? 0,
      })
      if (items.length >= 3) {
        break
      }
    }
  }

  return items.slice(0, 3)
}
