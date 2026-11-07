<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    value: string
    label?: string
    compact?: boolean
  }>(),
  {
    label: 'Copier la référence',
    compact: false,
  },
)

const state = ref<'idle' | 'copied' | 'failed'>('idle')
let resetTimer: ReturnType<typeof setTimeout> | null = null

const visibleLabel = computed(() => {
  if (state.value === 'copied') {
    return 'Copiée'
  }
  if (state.value === 'failed') {
    return 'Copie impossible'
  }
  return 'Copier'
})

async function copy() {
  if (!props.value) {
    return
  }
  try {
    await navigator.clipboard.writeText(props.value)
    state.value = 'copied'
  }
  catch {
    state.value = 'failed'
  }
  if (resetTimer) {
    clearTimeout(resetTimer)
  }
  resetTimer = setTimeout(() => {
    state.value = 'idle'
    resetTimer = null
  }, 2000)
}

onBeforeUnmount(() => {
  if (resetTimer) {
    clearTimeout(resetTimer)
  }
})
</script>

<template>
  <span
    v-if="value"
    class="inline-flex min-w-0 items-center gap-2"
  >
    <code class="truncate font-sans text-sm font-medium text-text">{{ value }}</code>
    <button
      v-if="compact"
      type="button"
      class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-muted focus-ring"
      :aria-label="label"
      @click="copy"
    >
      <UiIcon name="copy" />
      <span class="sr-only">{{ visibleLabel }}</span>
    </button>
    <UiButton
      v-else
      variant="quiet"
      :aria-label="label"
      @click="copy"
    >
      {{ visibleLabel }}
    </UiButton>
    <span class="sr-only" aria-live="polite">{{ state === 'idle' ? '' : visibleLabel }}</span>
  </span>
</template>
