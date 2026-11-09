<script setup lang="ts">
const route = useRoute()
const open = ref(false)
const root = useTemplateRef<HTMLElement>('root')

const options = [
  { value: 'recent', label: 'Plus récent' },
  { value: 'oldest', label: 'Plus anciens' },
]

const currentSort = computed(() => {
  const value = route.query.tri
  if (value === 'oldest') {
    return 'oldest'
  }
  if (typeof value !== 'string' || value === '' || value === 'recent') {
    return 'recent'
  }
  return value
})

const currentLabel = computed(() => options.find(option => option.value === currentSort.value)?.label ?? 'Plus récent')

function toggle() {
  open.value = !open.value
}

function onPointerDown(event: PointerEvent) {
  if (!open.value || !root.value || !(event.target instanceof Node)) {
    return
  }
  if (!root.value.contains(event.target)) {
    open.value = false
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}

onMounted(() => document.addEventListener('pointerdown', onPointerDown))
onBeforeUnmount(() => document.removeEventListener('pointerdown', onPointerDown))

async function applySort(value: string) {
  if (value === currentSort.value) {
    open.value = false
    return
  }
  const query: Record<string, string | string[]> = {}
  for (const [key, item] of Object.entries(route.query)) {
    if (typeof item === 'string' && item !== '') {
      query[key] = item
    } else if (Array.isArray(item)) {
      const values = item.filter((entry): entry is string => typeof entry === 'string' && entry !== '')
      if (values.length > 0) {
        query[key] = values
      }
    }
  }
  delete query.page
  if (value === '' || value === 'recent') {
    delete query.tri
  } else {
    query.tri = value
  }
  open.value = false
  await navigateTo({ path: route.path, query })
}
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="focus-ring inline-flex min-h-11 items-center gap-1 bg-transparent font-sans text-sm text-text"
      :aria-expanded="open"
      aria-haspopup="true"
      :aria-label="`Trier les œuvres, actuellement ${currentLabel}`"
      @click="toggle"
      @keydown="onKeydown"
    >
      <span>{{ currentLabel }}</span>
      <UiIcon
        name="chevron-down"
        class="transition-transform duration-150 motion-reduce:transition-none"
        :class="open ? 'rotate-180' : undefined"
      />
    </button>
    <div v-if="open" class="absolute right-0 top-full z-20 bg-white py-1" @keydown="onKeydown">
      <div class="flex flex-col gap-1">
        <label
          v-for="option in options"
          :key="option.value"
          class="focus-ring-within flex cursor-pointer items-center gap-2 whitespace-nowrap px-1 font-sans text-sm leading-5 text-text"
        >
          <input
            type="checkbox"
            class="size-4 shrink-0 accent-brand focus-ring"
            :checked="currentSort === option.value"
            @change="applySort(option.value)"
          >
          {{ option.label }}
        </label>
      </div>
    </div>
  </div>
</template>
