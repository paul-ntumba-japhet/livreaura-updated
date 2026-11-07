<script setup lang="ts">
const MAX_BYTES = 8_388_608
const ACCEPT = '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png'

const props = withDefaults(
  defineProps<{
    label: string
    hint?: string
    disabled?: boolean
    disabledReason?: string
    loading?: boolean
    error?: string
    selectedName?: string
  }>(),
  {
    disabled: false,
    loading: false,
  },
)

const emit = defineEmits<{
  select: [file: File]
}>()

const inputId = useId()
const reasonId = useId()
const errorId = useId()
const hovering = ref(false)
const localError = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

const blocked = computed(() => props.disabled || props.loading)
const describedBy = computed(() => {
  const ids = [
    props.disabled && props.disabledReason ? reasonId : null,
    localError.value || props.error ? errorId : null,
  ].filter(Boolean)
  return ids.length > 0 ? ids.join(' ') : undefined
})

function extensionOk(name: string): boolean {
  return /\.(pdf|jpe?g|png)$/i.test(name)
}

function onFiles(files: FileList | null) {
  localError.value = ''
  const file = files?.[0]
  if (!file) {
    return
  }
  if (!extensionOk(file.name) && !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) {
    localError.value = 'Utilisez un fichier PDF, JPEG ou PNG.'
    return
  }
  if (file.size > MAX_BYTES) {
    localError.value = 'Le fichier dépasse 8 Mo.'
    return
  }
  emit('select', file)
  if (inputRef.value) {
    inputRef.value.value = ''
  }
}

function onDrop(event: DragEvent) {
  hovering.value = false
  if (blocked.value) {
    return
  }
  event.preventDefault()
  onFiles(event.dataTransfer?.files ?? null)
}
</script>

<template>
  <div class="flex min-w-0 flex-col gap-1">
    <label :for="inputId" class="font-sans text-sm font-medium text-text">
      {{ label }}
    </label>
    <div
      class="rounded-md border border-dashed px-gutter py-section"
      :class="{
        'border-brand bg-surface-muted': hovering && !blocked,
        'border-border bg-surface-raised': !hovering || blocked,
        'opacity-50': blocked,
      }"
      @dragenter.prevent="hovering = !blocked"
      @dragover.prevent="hovering = !blocked"
      @dragleave.prevent="hovering = false"
      @drop="onDrop"
    >
      <input
        :id="inputId"
        ref="inputRef"
        type="file"
        class="sr-only"
        :accept="ACCEPT"
        :disabled="blocked"
        :aria-invalid="Boolean(localError || error) || undefined"
        :aria-describedby="describedBy"
        @change="onFiles(($event.target as HTMLInputElement).files)"
      >
      <p class="font-sans text-sm text-text">
        <template v-if="loading">Téléversement en cours.</template>
        <template v-else-if="selectedName">Fichier sélectionné : {{ selectedName }}</template>
        <template v-else>Déposez un PDF, JPEG ou PNG, ou choisissez un fichier.</template>
      </p>
      <p class="mt-2">
        <UiButton
          variant="secondary"
          :disabled="blocked"
          :loading="loading"
          :disabled-reason="disabled && disabledReason ? disabledReason : undefined"
          @click="inputRef?.click()"
        >
          Choisir un fichier
        </UiButton>
      </p>
    </div>
    <p v-if="hint && !localError && !error" class="font-sans text-sm text-text-muted">
      {{ hint }}
    </p>
    <p
      v-if="disabled && disabledReason"
      :id="reasonId"
      class="font-sans text-sm text-text-muted"
    >
      {{ disabledReason }}
    </p>
    <p v-if="localError || error" :id="errorId" class="font-sans text-sm text-danger">
      {{ localError || error }}
    </p>
  </div>
</template>
