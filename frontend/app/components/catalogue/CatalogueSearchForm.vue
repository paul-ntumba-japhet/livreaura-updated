<script setup lang="ts">
import type { SelectOption } from '../ui/SelectInput'
import {
  listCatalogueAuthors,
  listCatalogueLanguages,
  listCatalogueLevels,
  listCataloguePublishers,
} from '../../services/catalogue/consultation'
import { catalogueFailure, cataloguePublicLabel } from '../../utils/catalogue/presentation'

const props = withDefaults(
  defineProps<{
    variant?: 'aside' | 'panel'
    searching?: boolean
    lockSort?: boolean
    lockAvailability?: boolean
  }>(),
  {
    variant: 'panel',
    searching: false,
    lockSort: false,
    lockAvailability: false,
  },
)

const route = useRoute()
const submitting = ref(false)
const badgeError = ref('')
const priceFloor = 0
const priceCeiling = 500

const formatOptions: SelectOption[] = [
  { value: '', label: 'Tous les formats' },
  { value: 'epub', label: 'EPUB' },
  { value: 'pdf', label: 'PDF' },
  { value: 'audio', label: 'Audio' },
  { value: 'paper', label: 'Papier' },
]

const conditionOptions: SelectOption[] = [
  { value: '', label: 'Tous les états' },
  { value: 'none', label: 'Sans état' },
  { value: 'new', label: 'Neuf' },
  { value: 'used', label: 'Occasion' },
]

const availabilityOptions: SelectOption[] = [
  { value: '', label: 'Toutes les disponibilités' },
  { value: 'available', label: 'Disponible' },
  { value: 'soon', label: 'À paraître' },
]

const currencyOptions: SelectOption[] = [
  { value: 'USD', label: 'USD' },
  { value: 'CDF', label: 'CDF' },
]

const freeOptions: SelectOption[] = [
  { value: '', label: 'Gratuites et payantes' },
  { value: '1', label: 'Gratuites' },
  { value: '0', label: 'Payantes' },
]

const africanOptions: SelectOption[] = [
  { value: '', label: 'Tous les ouvrages' },
  { value: '1', label: 'Ouvrages africains' },
  { value: '0', label: 'Autres ouvrages' },
]

const closedLabels: Record<string, Record<string, string>> = {
  tri: {
    recent: 'Plus récent',
    oldest: 'Plus anciens',
  },
  format: {
    epub: 'Format EPUB',
    pdf: 'Format PDF',
    audio: 'Format audio',
    paper: 'Format papier',
  },
  etat: {
    none: 'Sans état',
    new: 'Neuf',
    used: 'Occasion',
  },
  disponibilite: {
    available: 'Disponible',
    soon: 'À paraître',
  },
  devise: {
    USD: 'Devise USD',
    CDF: 'Devise CDF',
  },
  gratuite: {
    '1': 'Gratuites',
    '0': 'Payantes',
  },
  africain: {
    '1': 'Ouvrages africains',
    '0': 'Autres ouvrages',
  },
}

type Draft = {
  texte: string
  tri: string
  format: string
  etat: string
  disponibilite: string
  langue: string
  niveau: string
  auteur: string
  editeur: string
  badge: string
  prix_min: string
  prix_max: string
  devise: string
  gratuite: string
  africain: string
}

const draft = reactive<Draft>(emptyDraft())

type ReferenceLists = {
  langues: SelectOption[]
  niveaux: SelectOption[]
  editeurs: SelectOption[]
  auteurs: SelectOption[]
  erreurs: Partial<Record<'langues' | 'niveaux' | 'editeurs' | 'auteurs', string>>
  auteursIncomplets: boolean
}

const references = await useAsyncData('catalogue-filter-references', loadReferences)

const languageOptions = computed(() => withCurrent(referenceOptions('langues', 'Toutes les langues'), draft.langue))
const levelOptions = computed(() => withCurrent(referenceOptions('niveaux', 'Tous les niveaux'), draft.niveau))
const publisherOptions = computed(() => withCurrent(referenceOptions('editeurs', 'Tous les éditeurs'), draft.editeur))
const authorOptions = computed(() => withCurrent(referenceOptions('auteurs', 'Tous les auteurs'), draft.auteur))

const applied = computed(() => appliedDraft())

const chips = computed(() => {
  const items: { key: keyof Draft, label: string }[] = []
  const current = appliedDraft()
  if (current.texte) {
    items.push({ key: 'texte', label: `Texte « ${current.texte} »` })
  }
  for (const key of ['tri', 'format', 'etat', 'disponibilite', 'devise', 'gratuite', 'africain'] as const) {
    if (props.lockSort && key === 'tri') {
      continue
    }
    if (props.lockAvailability && key === 'disponibilite') {
      continue
    }
    const value = queryText(key)
    const label = value ? closedLabels[key]?.[value] : undefined
    if (label) {
      items.push({ key, label })
    }
  }
  pushNamed(items, 'langue', 'Langue', current.langue, languageOptions.value)
  pushNamed(items, 'niveau', 'Niveau', current.niveau, levelOptions.value)
  pushNamed(items, 'auteur', 'Auteur', current.auteur, authorOptions.value)
  pushNamed(items, 'editeur', 'Éditeur', current.editeur, publisherOptions.value)
  if (current.prix_min) {
    items.push({ key: 'prix_min', label: `Prix minimum ${current.prix_min} $` })
  }
  if (current.prix_max) {
    items.push({ key: 'prix_max', label: `Prix maximum ${current.prix_max} $` })
  }
  return items
})

watch(() => route.fullPath, syncDraft, { immediate: true })

function emptyDraft(): Draft {
  return {
    texte: '',
    tri: 'recent',
    format: '',
    etat: '',
    disponibilite: '',
    langue: '',
    niveau: '',
    auteur: '',
    editeur: '',
    badge: '',
    prix_min: '',
    prix_max: '',
    devise: 'USD',
    gratuite: '',
    africain: '',
  }
}

function queryText(key: string): string {
  const value = route.query[key]
  return typeof value === 'string' ? value : ''
}

function appliedDraft(): Draft {
  return {
    texte: queryText('texte'),
    tri: queryText('tri') || 'recent',
    format: queryText('format'),
    etat: queryText('etat'),
    disponibilite: queryText('disponibilite'),
    langue: queryText('langue'),
    niveau: queryText('niveau'),
    auteur: queryText('auteur'),
    editeur: queryText('editeur'),
    badge: queryText('badge'),
    prix_min: queryText('prix_min'),
    prix_max: queryText('prix_max'),
    devise: queryText('devise') || 'USD',
    gratuite: queryText('gratuite'),
    africain: queryText('africain'),
  }
}

function syncDraft() {
  Object.assign(draft, appliedDraft())
  badgeError.value = ''
}

function clampPrice(value: string, fallback: number): number {
  if (!/^\d{1,16}(\.\d{1,4})?$/.test(value)) {
    return fallback
  }
  const amount = Math.round(Number(value))
  if (!Number.isFinite(amount)) {
    return fallback
  }
  return Math.min(priceCeiling, Math.max(priceFloor, amount))
}

const priceBounds = computed(() => {
  const minimum = applied.value.prix_min === '' ? priceFloor : clampPrice(applied.value.prix_min, priceFloor)
  const maximum = applied.value.prix_max === '' ? priceCeiling : clampPrice(applied.value.prix_max, priceCeiling)
  return {
    minimum: Math.min(minimum, maximum),
    maximum,
  }
})

function referenceOptions(key: 'langues' | 'niveaux' | 'editeurs' | 'auteurs', allLabel: string): SelectOption[] {
  return [
    { value: '', label: allLabel },
    ...(references.data.value?.[key] ?? []),
  ]
}

function withCurrent(options: SelectOption[], value: string): SelectOption[] {
  if (value === '' || options.some(option => option.value === value)) {
    return options
  }
  return [...options, { value, label: value }]
}

function pushNamed(
  items: { key: keyof Draft, label: string }[],
  key: keyof Draft,
  prefix: string,
  value: string,
  options: SelectOption[],
) {
  if (value === '') {
    return
  }
  const match = options.find(option => option.value === value)
  items.push({ key, label: `${prefix} ${match?.label && match.value !== '' ? match.label : value}` })
}

function validate(): boolean {
  badgeError.value = ''
  if (draft.badge.trim().length > 80) {
    badgeError.value = 'Le badge dépasse 80 caractères.'
  }
  return badgeError.value === ''
}

async function writeQuery(values: Draft) {
  const query: Record<string, string | string[]> = {}
  for (const [key, value] of Object.entries(route.query)) {
    if (typeof value === 'string' && value !== '') {
      query[key] = value
    } else if (Array.isArray(value)) {
      const items = value.filter((item): item is string => typeof item === 'string' && item !== '')
      if (items.length > 0) {
        query[key] = items
      }
    }
  }
  delete query.page

  const assigned: Record<string, string> = {
    texte: values.texte.trim(),
    tri: values.tri === 'recent' ? '' : values.tri,
    format: values.format,
    etat: values.etat,
    disponibilite: values.disponibilite,
    langue: values.langue,
    niveau: values.niveau,
    auteur: values.auteur,
    editeur: values.editeur,
    badge: values.badge.trim(),
    prix_min: values.prix_min.trim(),
    prix_max: values.prix_max.trim(),
    devise: values.devise === 'USD' ? '' : values.devise,
    gratuite: values.gratuite,
    africain: values.africain,
  }
  for (const [key, value] of Object.entries(assigned)) {
    if (value === '') {
      delete query[key]
    } else {
      query[key] = value
    }
  }
  if (props.lockSort) {
    delete query.tri
  }
  if (props.lockAvailability) {
    delete query.disponibilite
  }

  submitting.value = true
  try {
    await navigateTo({ path: route.path, query })
  } finally {
    submitting.value = false
  }
}

async function applyPrice(value: { minimum: number, maximum: number }) {
  if (submitting.value || props.searching) {
    return
  }
  const next = appliedDraft()
  next.prix_min = value.minimum <= priceFloor ? '' : String(value.minimum)
  next.prix_max = value.maximum >= priceCeiling ? '' : String(value.maximum)
  if (next.prix_min === applied.value.prix_min && next.prix_max === applied.value.prix_max) {
    return
  }
  await writeQuery(next)
}

async function chooseCriterion(key: keyof Draft, value: string) {
  if (submitting.value || props.searching) {
    return
  }
  const next = appliedDraft()
  next[key] = value
  await writeQuery(next)
}

async function submit() {
  if (submitting.value || props.searching || !validate()) {
    return
  }
  await writeQuery({ ...draft })
}

async function removeCriterion(key: keyof Draft) {
  const next = appliedDraft()
  next[key] = key === 'tri' ? 'recent' : key === 'devise' ? 'USD' : ''
  await writeQuery(next)
}

async function clearCriteria() {
  await writeQuery(emptyDraft())
}

async function loadReferences(): Promise<ReferenceLists> {
  const [languages, levels, publishers, authors] = await Promise.allSettled([
    listCatalogueLanguages(),
    listCatalogueLevels(),
    listCataloguePublishers(),
    listAuthors(),
  ])
  const erreurs: ReferenceLists['erreurs'] = {}
  if (languages.status === 'rejected') {
    erreurs.langues = catalogueFailure(languages.reason).message
  }
  if (levels.status === 'rejected') {
    erreurs.niveaux = catalogueFailure(levels.reason).message
  }
  if (publishers.status === 'rejected') {
    erreurs.editeurs = catalogueFailure(publishers.reason).message
  }
  if (authors.status === 'rejected') {
    erreurs.auteurs = catalogueFailure(authors.reason).message
  }

  return {
    langues: languages.status === 'fulfilled'
      ? languages.value.langues.map(language => ({ value: language.code, label: language.nom }))
      : [],
    niveaux: levels.status === 'fulfilled'
      ? levels.value.niveaux.map(level => ({ value: level.slug, label: level.nom }))
      : [],
    editeurs: publishers.status === 'fulfilled'
      ? publishers.value.editeurs.map(publisher => ({ value: publisher.slug, label: publisher.nom }))
      : [],
    auteurs: authors.status === 'fulfilled' ? authors.value.auteurs : [],
    erreurs,
    auteursIncomplets: authors.status === 'fulfilled' && authors.value.incomplete,
  }
}

async function listAuthors(): Promise<{ auteurs: SelectOption[], incomplete: boolean }> {
  const auteurs: SelectOption[] = []
  let page = 1
  let total = 0
  do {
    const result = await listCatalogueAuthors({ taille: '50', page: String(page) })
    total = result.total
    for (const author of result.auteurs) {
      auteurs.push({ value: author.slug, label: cataloguePublicLabel(author.nom) })
    }
    if (result.auteurs.length === 0) {
      break
    }
    page += 1
  } while (auteurs.length < total && page <= 20)

  return {
    auteurs,
    incomplete: auteurs.length < total,
  }
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="submit">
    <div v-if="chips.length" class="space-y-2">
      <p id="criteres-actifs" class="font-sans text-sm font-medium text-text">
        Critères actifs
      </p>
      <ul class="flex flex-wrap gap-2" aria-labelledby="criteres-actifs">
        <li v-for="chip in chips" :key="chip.key">
          <button
            type="button"
            class="focus-ring inline-flex min-h-11 items-center rounded-md border border-border bg-surface-raised px-3 font-sans text-sm text-text"
            :aria-label="`Retirer le critère ${chip.label}`"
            :disabled="submitting"
            @click="removeCriterion(chip.key)"
          >
            {{ chip.label }}
          </button>
        </li>
      </ul>
      <UiButton type="button" variant="quiet" :disabled="submitting" @click="clearCriteria">
        Effacer les critères
      </UiButton>
    </div>

    <p v-if="lockSort" class="font-sans text-sm text-text">
      Cette sélection conserve son ordre.
    </p>

    <div class="w-full">
      <CatalogueChoiceList
        legend="Format"
        :options="formatOptions"
        :model-value="applied.format"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('format', $event)"
      />

      <CatalogueChoiceList
        legend="Gratuité"
        :options="freeOptions"
        :model-value="applied.gratuite"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('gratuite', $event)"
      />

      <CataloguePriceRange
        :minimum="priceBounds.minimum"
        :maximum="priceBounds.maximum"
        :disabled="submitting || searching"
        @change="applyPrice"
      />

      <CatalogueChoiceList
        legend="Langue"
        :options="languageOptions"
        :model-value="applied.langue"
        :disabled="submitting || searching || references.status.value === 'pending' || Boolean(references.data.value?.erreurs.langues)"
        :hint="references.status.value === 'pending' ? 'Chargement des langues.' : undefined"
        :error="references.data.value?.erreurs.langues"
        @update:model-value="chooseCriterion('langue', $event)"
      />
      <CatalogueChoiceList
        legend="Niveau"
        :options="levelOptions"
        :model-value="applied.niveau"
        :disabled="submitting || searching || references.status.value === 'pending' || Boolean(references.data.value?.erreurs.niveaux)"
        :hint="references.status.value === 'pending' ? 'Chargement des niveaux.' : undefined"
        :error="references.data.value?.erreurs.niveaux"
        @update:model-value="chooseCriterion('niveau', $event)"
      />

      <p v-if="lockAvailability" class="font-sans text-sm text-text">
        <span class="font-medium">Disponibilité. </span>Cette sélection ne montre que les œuvres à paraître.
      </p>
      <CatalogueChoiceList
        v-else
        legend="Disponibilité"
        :options="availabilityOptions"
        :model-value="applied.disponibilite"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('disponibilite', $event)"
      />

      <CatalogueChoiceList
        legend="État de l’offre"
        :options="conditionOptions"
        :model-value="applied.etat"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('etat', $event)"
      />

      <CatalogueChoiceList
        legend="Auteur"
        :options="authorOptions"
        :model-value="applied.auteur"
        :disabled="submitting || searching || references.status.value === 'pending' || Boolean(references.data.value?.erreurs.auteurs)"
        :hint="references.data.value?.auteursIncomplets
          ? 'La liste des auteurs est incomplète.'
          : references.status.value === 'pending' ? 'Chargement des auteurs.' : undefined"
        :error="references.data.value?.erreurs.auteurs"
        @update:model-value="chooseCriterion('auteur', $event)"
      />

      <CatalogueChoiceList
        legend="Éditeur"
        :options="publisherOptions"
        :model-value="applied.editeur"
        :disabled="submitting || searching || references.status.value === 'pending' || Boolean(references.data.value?.erreurs.editeurs)"
        :hint="references.status.value === 'pending' ? 'Chargement des éditeurs.' : undefined"
        :error="references.data.value?.erreurs.editeurs"
        @update:model-value="chooseCriterion('editeur', $event)"
      />

      <CatalogueChoiceList
        legend="Origine"
        :options="africanOptions"
        :model-value="applied.africain"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('africain', $event)"
      />

      <CatalogueChoiceList
        legend="Devise"
        :options="currencyOptions"
        :model-value="applied.devise"
        :disabled="submitting || searching"
        @update:model-value="chooseCriterion('devise', $event)"
      />
    </div>
  </form>
</template>
