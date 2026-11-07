<script setup lang="ts">
const props = defineProps<{
  open: boolean
  title: string
  panelClass?: string
}>()

const emit = defineEmits<{
  close: []
}>()

const panel = ref<HTMLElement | null>(null)
const previouslyFocused = ref<HTMLElement | null>(null)

watch(
  () => props.open,
  async (open) => {
    if (!import.meta.client) {
      return
    }
    if (open) {
      previouslyFocused.value = document.activeElement instanceof HTMLElement ? document.activeElement : null
      document.body.classList.add('overflow-hidden')
      await nextTick()
      const focusable = panel.value?.querySelector<HTMLElement>('a, button, input')
      focusable?.focus()
      return
    }
    document.body.classList.remove('overflow-hidden')
    previouslyFocused.value?.focus()
  },
)

onBeforeUnmount(() => {
  if (import.meta.client) {
    document.body.classList.remove('overflow-hidden')
  }
})

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-40 md:hidden"
      @keydown="onKeydown"
    >
      <button
        type="button"
        class="absolute inset-0 bg-text/40"
        aria-label="Fermer le menu"
        @click="emit('close')"
      />
      <aside
        ref="panel"
        class="absolute inset-y-0 left-0 flex w-72 max-w-[calc(100%-2rem)] flex-col bg-surface-raised shadow-md"
        :class="panelClass"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <div class="flex items-center justify-between border-b border-border px-gutter py-stack">
          <p class="font-sans text-sm font-medium text-text">
            {{ title }}
          </p>
          <UiButton
            variant="quiet"
            aria-label="Fermer le menu"
            @click="emit('close')"
          >
            <UiIcon name="close" label="Fermer" />
          </UiButton>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto">
          <slot />
        </div>
      </aside>
    </div>
  </Teleport>
</template>
