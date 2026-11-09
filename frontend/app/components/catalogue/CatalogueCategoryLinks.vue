<script setup lang="ts">
import type { InjectionKey } from 'vue'
import type { CatalogueCategoryNode } from '../../types/catalogue/consultation'
import { catalogueCategoryTarget } from '../../utils/catalogue/presentation'

type CategoryTree = {
  isOpen: (chemin: string) => boolean
  toggle: (chemin: string, siblings: string[]) => void
  isSelected: (chemin: string) => boolean
  location: (chemin: string) => ReturnType<typeof catalogueCategoryTarget>
}

const categoryTreeKey: InjectionKey<CategoryTree> = 'catalogue-category-tree'

const props = withDefaults(
  defineProps<{
    categories: CatalogueCategoryNode[]
    activePath?: string
    managed?: boolean
    seen?: string[]
  }>(),
  {
    activePath: '',
    managed: false,
    seen: () => [],
  },
)

const route = useRoute()
const opened = ref<string[]>([])

function resolvedChemin(path: string): string {
  if (path === '') {
    return ''
  }
  return findChemin(props.categories, path) ?? path
}

function expandActive(path: string) {
  const chemin = resolvedChemin(path)
  if (chemin === '' || chemin.includes('\\')) {
    opened.value = []
    return
  }
  const parts = chemin.split('/').filter(part => part !== '')
  const chain: string[] = []
  for (let index = 0; index < parts.length; index += 1) {
    chain.push(parts.slice(0, index + 1).join('/'))
  }
  opened.value = chain
}

const owned: CategoryTree = {
  isOpen(chemin) {
    return opened.value.includes(chemin)
  },
  toggle(chemin, siblings) {
    if (opened.value.includes(chemin)) {
      opened.value = opened.value.filter(item => item !== chemin && !item.startsWith(`${chemin}/`))
      return
    }
    opened.value = [
      ...opened.value.filter(item => !siblings.some(sibling => item === sibling || item.startsWith(`${sibling}/`))),
      chemin,
    ]
  },
  isSelected(chemin) {
    return resolvedChemin(props.activePath) === chemin
  },
  location(chemin) {
    return catalogueCategoryTarget(route.query, chemin)
  },
}

if (!props.managed) {
  provide(categoryTreeKey, owned)
  watch(() => [props.activePath, props.categories] as const, () => expandActive(props.activePath), { immediate: true })
}

const tree = props.managed ? inject(categoryTreeKey) : owned
if (!tree) {
  throw new Error('L’arborescence des catégories n’est pas initialisée.')
}
const categoryTree: CategoryTree = tree

const visibleCategories = computed(() => props.categories.filter(category => !props.seen.includes(category.categorie_id)))
const siblingPaths = computed(() => visibleCategories.value.map(category => category.chemin))

function findChemin(nodes: CatalogueCategoryNode[], active: string, seen = new Set<string>()): string | null {
  for (const node of nodes) {
    if (seen.has(node.categorie_id)) {
      continue
    }
    seen.add(node.categorie_id)
    if (node.chemin === active || node.categorie_id === active) {
      return node.chemin
    }
    const child = findChemin(node.enfants, active, seen)
    if (child !== null) {
      return child
    }
  }
  return null
}

function childSeen(category: CatalogueCategoryNode): string[] {
  return [...props.seen, category.categorie_id]
}
</script>

<template>
  <ul>
    <li v-for="category in visibleCategories" :key="category.categorie_id">
      <div class="flex items-center">
        <NuxtLink
          :to="categoryTree.location(category.chemin)"
          class="focus-ring flex min-h-10 min-w-0 flex-1 items-center rounded-md border-l-2 px-2 font-sans text-sm leading-5 hover:bg-[var(--surface-subtle)]"
          :class="categoryTree.isSelected(category.chemin)
            ? 'border-brand bg-[var(--surface-subtle)] font-semibold text-text'
            : managed
              ? 'border-transparent font-normal text-text-muted'
              : 'border-transparent text-text'"
          :aria-current="categoryTree.isSelected(category.chemin) ? 'true' : undefined"
        >
          <span class="truncate">{{ category.nom }}</span>
        </NuxtLink>
        <button
          v-if="category.enfants.length > 0"
          type="button"
          class="focus-ring inline-flex size-10 shrink-0 items-center justify-center text-text-muted"
          :aria-expanded="categoryTree.isOpen(category.chemin)"
          :aria-controls="categoryTree.isOpen(category.chemin) ? `categorie-${category.categorie_id}` : undefined"
          :aria-label="categoryTree.isOpen(category.chemin) ? `Réduire ${category.nom}` : `Développer ${category.nom}`"
          @click="categoryTree.toggle(category.chemin, siblingPaths)"
        >
          <UiIcon :name="categoryTree.isOpen(category.chemin) ? 'chevron-down' : 'chevron-right'" />
        </button>
      </div>
      <CatalogueCategoryLinks
        v-if="category.enfants.length > 0 && categoryTree.isOpen(category.chemin)"
        :id="`categorie-${category.categorie_id}`"
        class="ml-2 border-l border-border pl-3"
        managed
        :categories="category.enfants"
        :seen="childSeen(category)"
      />
    </li>
  </ul>
</template>
