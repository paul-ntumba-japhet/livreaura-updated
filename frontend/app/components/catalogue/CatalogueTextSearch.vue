<script setup lang="ts">
import type { CatalogueCategoryNode } from '../../types/catalogue/consultation'
import { searchCatalogue } from '../../services/catalogue/consultation'
import { catalogueCategoryTarget, cataloguePublicLabel } from '../../utils/catalogue/presentation'

const props = withDefaults(
  defineProps<{
    categories?: CatalogueCategoryNode[]
  }>(),
  {
    categories: () => [],
  },
)

type Suggestion = {
  key: string
  kind: 'work' | 'author' | 'category' | 'all'
  label: string
  detail: string
  to: string | ReturnType<typeof catalogueCategoryTarget>
}

const route = useRoute()
const texte = ref('')
const error = ref('')
const submitting = ref(false)
const suggesting = ref(false)
const suggestionError = ref('')
const open = ref(false)
const activeIndex = ref(-1)
const suggestions = ref<Suggestion[]>([])
const listId = useId()
let requestId = 0
let timer: ReturnType<typeof setTimeout> | undefined

const activeId = computed(() => {
  const item = suggestions.value[activeIndex.value]
  return item ? `${listId}-${item.key}` : undefined
})

watch(() => route.fullPath, sync, { immediate: true })

onBeforeUnmount(() => {
  if (timer !== undefined) {
    clearTimeout(timer)
  }
  requestId += 1
})

function sync() {
  const value = route.query.texte
  texte.value = typeof value === 'string' ? value : ''
  error.value = ''
  closePanel()
}

function onType(value: string) {
  texte.value = value
  error.value = ''
  activeIndex.value = -1
  if (timer !== undefined) {
    clearTimeout(timer)
  }
  const term = value.trim().replace(/\s+/g, ' ')
  if (term.length < 2) {
    requestId += 1
    suggestions.value = []
    suggestionError.value = ''
    suggesting.value = false
    open.value = false
    return
  }
  open.value = true
  timer = setTimeout(() => {
    void loadSuggestions(term)
  }, 300)
}

async function loadSuggestions(term: string) {
  const current = ++requestId
  suggesting.value = true
  suggestionError.value = ''
  const query: Record<string, string> = { texte: term, taille: '6' }
  if (typeof route.query.categorie === 'string' && route.query.categorie !== '') {
    query.categorie = route.query.categorie
    query.descendance = typeof route.query.descendance === 'string' && route.query.descendance !== ''
      ? route.query.descendance
      : '1'
  }
  try {
    const page = await searchCatalogue(query)
    if (current !== requestId) {
      return
    }
    suggestions.value = buildSuggestions(term, page.oeuvres)
  } catch {
    if (current !== requestId) {
      return
    }
    suggestions.value = [allResults(term)]
    suggestionError.value = 'Les suggestions sont indisponibles.'
  } finally {
    if (current === requestId) {
      suggesting.value = false
    }
  }
}

function buildSuggestions(term: string, works: Awaited<ReturnType<typeof searchCatalogue>>['oeuvres']): Suggestion[] {
  const folded = fold(term)
  const workItems: Suggestion[] = []
  const authorItems: Suggestion[] = []
  const seenAuthors = new Set<string>()
  for (const work of works) {
    if (workItems.length < 4) {
      const authors = work.auteurs.map(author => cataloguePublicLabel(author.nom)).filter(name => name !== '')
      workItems.push({
        key: `work-${work.slug}`,
        kind: 'work',
        label: cataloguePublicLabel(work.titre),
        detail: authors.join(', '),
        to: `/catalogue/oeuvres/${work.slug}`,
      })
    }
    for (const author of work.auteurs) {
      const name = cataloguePublicLabel(author.nom)
      if (authorItems.length >= 2 || name === '' || seenAuthors.has(author.slug) || !fold(name).includes(folded)) {
        continue
      }
      seenAuthors.add(author.slug)
      authorItems.push({
        key: `author-${author.slug}`,
        kind: 'author',
        label: name,
        detail: 'Auteur',
        to: `/catalogue/auteurs/${author.slug}`,
      })
    }
  }
  const categoryItems: Suggestion[] = []
  for (const category of flatten(props.categories)) {
    if (categoryItems.length >= 2 || !fold(category.nom).includes(folded)) {
      continue
    }
    categoryItems.push({
      key: `category-${category.categorie_id}`,
      kind: 'category',
      label: category.nom,
      detail: 'Catégorie',
      to: catalogueCategoryTarget(route.query, category.chemin),
    })
  }
  return [...workItems, ...authorItems, ...categoryItems, allResults(term)]
}

function allResults(term: string): Suggestion {
  return {
    key: 'all',
    kind: 'all',
    label: `Voir tous les résultats pour « ${term} »`,
    detail: '',
    to: '',
  }
}

function flatten(nodes: CatalogueCategoryNode[], seen = new Set<string>()): CatalogueCategoryNode[] {
  const list: CatalogueCategoryNode[] = []
  for (const node of nodes) {
    if (seen.has(node.categorie_id)) {
      continue
    }
    seen.add(node.categorie_id)
    list.push(node)
    list.push(...flatten(node.enfants, seen))
  }
  return list
}

function fold(value: string): string {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('fr')
}

function parts(value: string, term: string): { text: string, match: boolean }[] {
  const foldedTerm = fold(term)
  if (foldedTerm === '') {
    return [{ text: value, match: false }]
  }
  const chars = [...value]
  let folded = ''
  const map: number[] = []
  chars.forEach((char, index) => {
    for (const piece of fold(char)) {
      folded += piece
      map.push(index)
    }
  })
  const at = folded.indexOf(foldedTerm)
  if (at < 0) {
    return [{ text: value, match: false }]
  }
  const start = map[at] ?? 0
  const end = (map[at + foldedTerm.length - 1] ?? start) + 1
  return [
    { text: chars.slice(0, start).join(''), match: false },
    { text: chars.slice(start, end).join(''), match: true },
    { text: chars.slice(end).join(''), match: false },
  ].filter(part => part.text !== '')
}

function closePanel() {
  open.value = false
  activeIndex.value = -1
  suggesting.value = false
  suggestionError.value = ''
}

function clearText() {
  texte.value = ''
  error.value = ''
  closePanel()
  if (typeof route.query.texte === 'string' && route.query.texte !== '') {
    void submit()
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    if (open.value) {
      event.preventDefault()
      closePanel()
    }
    return
  }
  if (!open.value || suggestions.value.length === 0) {
    return
  }
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = activeIndex.value >= suggestions.value.length - 1 ? 0 : activeIndex.value + 1
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = activeIndex.value <= 0 ? suggestions.value.length - 1 : activeIndex.value - 1
  } else if (event.key === 'Enter' && activeIndex.value >= 0) {
    event.preventDefault()
    activate(suggestions.value[activeIndex.value])
  }
}

function activate(item: Suggestion | undefined) {
  if (!item) {
    return
  }
  if (item.kind === 'all') {
    closePanel()
    void submit()
    return
  }
  closePanel()
  void navigateTo(item.to)
}

function onFocusOut(event: FocusEvent) {
  const next = event.relatedTarget
  if (next instanceof Node && event.currentTarget instanceof Node && event.currentTarget.contains(next)) {
    return
  }
  open.value = false
}

async function submit() {
  if (submitting.value) {
    return
  }
  const value = texte.value.trim().replace(/\s+/g, ' ')
  if (value.length > 200) {
    error.value = 'La recherche dépasse 200 caractères.'
    closePanel()
    return
  }
  error.value = ''
  closePanel()

  const query: Record<string, string | string[]> = {}
  for (const [key, current] of Object.entries(route.query)) {
    if (key === 'page' || key === 'texte') {
      continue
    }
    if (typeof current === 'string' && current !== '') {
      query[key] = current
    } else if (Array.isArray(current)) {
      const items = current.filter((entry): entry is string => typeof entry === 'string' && entry !== '')
      if (items.length > 0) {
        query[key] = items
      }
    }
  }
  if (value !== '') {
    query.texte = value
  }

  submitting.value = true
  try {
    await navigateTo({ path: route.path, query })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="catalogue-text-search relative w-full max-w-[720px]" @submit.prevent="submit" @focusout="onFocusOut">
    <div class="flex items-center gap-2">
      <div class="relative min-w-0 flex-1">
        <TextInput
          :model-value="texte"
          role="combobox"
          icon="search"
          type="text"
          autocomplete="off"
          :maxlength="200"
          placeholder="Rechercher un livre, un auteur ou un ISBN…"
          aria-label="Rechercher un livre, un auteur ou un ISBN"
          :aria-expanded="open"
          :aria-controls="open ? listId : undefined"
          aria-autocomplete="list"
          :aria-activedescendant="activeId"
          :aria-invalid="error ? 'true' : undefined"
          class="pr-12"
          :disabled="submitting"
          @update:model-value="onType"
          @keydown="onKeydown"
        />
        <button
          v-if="texte.trim() !== ''"
          type="button"
          class="focus-ring absolute right-1 top-1/2 inline-flex size-10 -translate-y-1/2 items-center justify-center text-text-muted"
          aria-label="Effacer la recherche"
          @click="clearText"
        >
          <UiIcon name="close" />
        </button>
      </div>
      <UiButton type="submit" variant="secondary" class="h-12 min-h-12" :loading="submitting" :disabled="submitting">
        Rechercher
      </UiButton>
    </div>
    <p v-if="error" class="mt-1 font-sans text-sm text-danger">
      {{ error }}
    </p>
    <div
      v-if="open"
      :id="listId"
      class="absolute left-0 right-0 z-30 mt-1 overflow-hidden rounded-md border border-border bg-surface-raised"
    >
      <p v-if="suggesting" class="px-3 py-2 font-sans text-sm text-text-muted">
        Recherche de suggestions.
      </p>
      <p v-else-if="suggestionError" class="px-3 py-2 font-sans text-sm text-danger">
        {{ suggestionError }}
      </p>
      <p v-else-if="suggestions.every(item => item.kind === 'all')" class="px-3 py-2 font-sans text-sm text-text-muted">
        Aucune suggestion.
      </p>
      <ul v-if="!suggesting && suggestions.length > 0" role="listbox" :aria-label="'Suggestions pour ' + texte.trim()" class="max-h-80 overflow-auto py-1">
        <li
          v-for="(item, index) in suggestions"
          :id="`${listId}-${item.key}`"
          :key="item.key"
          role="option"
          :aria-selected="index === activeIndex"
        >
          <NuxtLink
            v-if="item.kind !== 'all'"
            :to="item.to"
            class="focus-ring flex min-h-10 items-baseline gap-2 px-3 py-1 font-sans text-sm"
            :class="index === activeIndex ? 'bg-[var(--surface-subtle)]' : undefined"
            @mousedown.prevent
            @click="closePanel"
          >
            <span class="min-w-0 flex-1 truncate text-text">
              <template v-for="(part, partIndex) in parts(item.label, texte)" :key="partIndex">
                <span :class="part.match ? 'font-semibold' : undefined">{{ part.text }}</span>
              </template>
            </span>
            <span v-if="item.detail" class="shrink-0 text-text-muted">{{ item.detail }}</span>
          </NuxtLink>
          <button
            v-else
            type="button"
            class="focus-ring flex min-h-10 w-full items-center px-3 text-left font-sans text-sm text-text"
            :class="index === activeIndex ? 'bg-[var(--surface-subtle)]' : undefined"
            @mousedown.prevent
            @click="activate(item)"
          >
            {{ item.label }}
          </button>
        </li>
      </ul>
    </div>
  </form>
</template>

<style scoped>
.catalogue-text-search :deep(.field-control) {
  height: var(--control-height-default);
  min-height: var(--control-height-default);
  padding-right: 3rem;
}
</style>
