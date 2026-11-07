<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    open: boolean
    title: string
    confirmLabel?: string
    cancelLabel?: string
    submitting?: boolean
    disabled?: boolean
    disabledReason?: string
    destructive?: boolean
  }>(),
  {
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    submitting: false,
    disabled: false,
    destructive: false,
  },
)

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const dialog = ref<HTMLDialogElement | null>(null)
const previouslyFocused = ref<HTMLElement | null>(null)

watch(
  () => props.open,
  (open) => {
    const node = dialog.value
    if (!node) {
      return
    }
    if (open) {
      previouslyFocused.value = document.activeElement instanceof HTMLElement ? document.activeElement : null
      if (!node.open) {
        node.showModal()
      }
      return
    }
    if (node.open) {
      node.close()
      previouslyFocused.value?.focus()
    }
  },
)

function onCancel() {
  emit('cancel')
}

function onNativeClose() {
  if (props.open) {
    emit('cancel')
  }
}

function onConfirm() {
  if (props.submitting || props.disabled) {
    return
  }
  emit('confirm')
}
</script>

<template>
  <dialog
    ref="dialog"
    class="w-[min(32rem,calc(100%-2rem))] rounded-xl border border-border bg-surface-raised p-page text-text shadow-md"
    @cancel.prevent="onCancel"
    @close="onNativeClose"
  >
    <h2 class="font-serif text-xl font-semibold">
      {{ title }}
    </h2>
    <div class="mt-stack font-sans text-sm text-text">
      <slot />
    </div>
    <div class="mt-section flex flex-wrap gap-gutter">
      <UiButton
        :variant="destructive ? 'destructive' : 'primary'"
        :loading="submitting"
        :disabled="disabled || submitting"
        :disabled-reason="disabledReason"
        @click="onConfirm"
      >
        {{ confirmLabel }}
      </UiButton>
      <UiButton
        variant="secondary"
        :disabled="submitting"
        @click="onCancel"
      >
        {{ cancelLabel }}
      </UiButton>
    </div>
  </dialog>
</template>
