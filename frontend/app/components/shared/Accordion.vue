<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

const props = withDefaults(
  defineProps<{
    title: string
    icon?: UiIconName
    open?: boolean
  }>(),
  {
    open: false,
  },
)

const emit = defineEmits<{
  'update:open': [open: boolean]
}>()

const root = ref<HTMLDetailsElement | null>(null)

watch(
  () => props.open,
  (value) => {
    if (root.value && root.value.open !== value) {
      root.value.open = value
    }
  },
)

function onToggle(event: Event) {
  const target = event.currentTarget as HTMLDetailsElement
  emit('update:open', target.open)
}
</script>

<template>
  <details
    ref="root"
    class="group rounded-md border border-border bg-surface-raised open:border-brand open:bg-surface-muted"
    :open="open"
    @toggle="onToggle"
  >
    <summary
      class="flex cursor-pointer list-none items-center gap-2 px-gutter py-stack font-sans text-sm font-medium text-text focus-ring [&::-webkit-details-marker]:hidden [&::marker]:hidden"
    >
      <UiIcon
        v-if="icon"
        :name="icon"
        class="text-text-muted"
      />
      <span class="min-w-0 flex-1">{{ title }}</span>
      <UiIcon
        name="chevron-down"
        class="text-text-muted transition-transform group-open:rotate-180"
      />
    </summary>
    <div class="border-t border-border px-gutter py-stack">
      <slot />
    </div>
  </details>
</template>
