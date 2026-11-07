import type { RouteLocationRaw } from 'vue-router'
import type { UiIconName } from '../../components/ui/icons'
import type { CurrentAccount } from '../../types/identity-access/account'
import { accessScopeOf } from './account'

export type AccountNavItem = {
  id: string
  label: string
  icon: UiIconName
  to: RouteLocationRaw
  keywords: string[]
  children?: AccountNavItem[]
}

export function accountNavItems(account: CurrentAccount | null | undefined): AccountNavItem[] {
  const items: AccountNavItem[] = [
    {
      id: 'overview',
      label: 'Vue d’ensemble',
      icon: 'house',
      to: '/compte',
      keywords: ['accueil', 'tableau', 'compte'],
    },
    {
      id: 'profil',
      label: 'Profil',
      icon: 'user',
      to: '/compte/profil',
      keywords: ['identité', 'e-mail', 'prénom', 'nom'],
    },
    {
      id: 'organisations',
      label: 'Organisations',
      icon: 'building-2',
      to: '/compte/organisation',
      keywords: ['école', 'éditeur', 'représentant'],
      children: [
        {
          id: 'organisations-liste',
          label: 'Mes organisations',
          icon: 'folder',
          to: '/compte/organisation',
          keywords: ['liste', 'rattachement', 'école', 'éditeur'],
        },
        {
          id: 'organisations-declarer',
          label: 'Déclarer',
          icon: 'plus',
          to: '/compte/organisation/declarer',
          keywords: ['créer', 'déclaration', 'nouvelle'],
        },
      ],
    },
    {
      id: 'verification',
      label: 'Dossier',
      icon: 'shield-check',
      to: '/compte/verification',
      keywords: ['kyc', 'dossier', 'pièces', 'vérification'],
    },
    {
      id: 'securite',
      label: 'Sécurité',
      icon: 'lock',
      to: '/compte/mot-de-passe',
      keywords: ['mot de passe', 'secret'],
    },
    {
      id: 'sessions',
      label: 'Sessions',
      icon: 'layers',
      to: { path: '/compte', hash: '#sessions' },
      keywords: ['connexion', 'révocation'],
    },
    {
      id: 'consentements',
      label: 'Consentements',
      icon: 'file-check',
      to: { path: '/compte', hash: '#consentements' },
      keywords: ['cgu', 'textes', 'légal'],
    },
  ]

  const showRecours = Boolean(account?.recours?.reference)
    || (account ? accessScopeOf(account) === 'support_only' : false)
  if (account && showRecours) {
    items.push({
      id: 'recours',
      label: 'Recours',
      icon: 'mail',
      to: '/compte/recours',
      keywords: ['support', 'demande'],
    })
  }

  return items
}

function itemMatchesQuery(item: AccountNavItem, needle: string): boolean {
  const haystack = [item.label, ...item.keywords].join(' ').toLocaleLowerCase('fr')
  return haystack.includes(needle)
}

function isAccountNavPathCurrent(item: AccountNavItem, path: string, hash: string): boolean {
  const resolved = typeof item.to === 'string' ? { path: item.to, hash: '' } : item.to
  const itemPath = typeof resolved === 'object' && resolved && 'path' in resolved ? resolved.path || '' : ''
  const itemHash = typeof resolved === 'object' && resolved && 'hash' in resolved ? resolved.hash || '' : ''

  if (item.id === 'overview') {
    return path === '/compte' && (hash === '' || hash === '#')
  }

  if (itemHash) {
    return path === itemPath && hash === itemHash
  }

  if (itemPath === '/compte/verification') {
    return path.startsWith('/compte/verification')
  }

  if (itemPath === '/compte/organisation') {
    return path === '/compte/organisation'
  }

  if (itemPath === '/compte/organisation/declarer') {
    return path === '/compte/organisation/declarer'
  }

  return path === itemPath || (itemPath !== '/compte' && path.startsWith(`${itemPath}/`))
}

export function isAccountNavCurrent(item: AccountNavItem, path: string, hash: string): boolean {
  if (item.children && item.children.length > 0) {
    return item.children.some(child => isAccountNavCurrent(child, path, hash))
      || isAccountNavPathCurrent(item, path, hash)
  }

  return isAccountNavPathCurrent(item, path, hash)
}

export function filterAccountNavItems(items: AccountNavItem[], query: string): AccountNavItem[] {
  const needle = query.trim().toLocaleLowerCase('fr')
  if (needle === '') {
    return []
  }

  const results: AccountNavItem[] = []
  for (const item of items) {
    if (item.children && item.children.length > 0) {
      const children = itemMatchesQuery(item, needle)
        ? item.children
        : item.children.filter(child => itemMatchesQuery(child, needle))
      results.push(...children)
      continue
    }
    if (itemMatchesQuery(item, needle)) {
      results.push(item)
    }
  }

  return results
}
