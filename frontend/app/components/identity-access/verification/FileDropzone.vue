<script setup lang="ts">
import { fetchVerificationEvidenceBlob } from '../../../services/identity-access/verification'

const MAX_BYTES = 8_388_608
const ACCEPT = '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png'

const props = withDefaults(
  defineProps<{
    label: string
    hint?: string
    disabled?: boolean
    disabledReason?: string
    loading?: boolean
    progress?: number | null
    error?: string
    selectedName?: string
    dossierId?: string
    pieceId?: string
  }>(),
  {
    disabled: false,
    loading: false,
    progress: null,
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
const previewUrl = ref<string | null>(null)
const previewKind = ref<'image' | 'pdf' | 'file' | null>(null)
const previewName = ref('')
const storedPreviewFailed = ref(false)
let previewToken = 0

const blocked = computed(() => props.disabled || props.loading)
const measuredProgress = computed(() => {
  if (!props.loading || props.progress == null || Number.isNaN(props.progress)) {
    return null
  }
  return Math.min(1, Math.max(0, props.progress))
})
const progressLabel = computed(() => {
  if (measuredProgress.value === null) {
    return 'Téléversement en cours.'
  }
  return `Téléversement : ${Math.round(measuredProgress.value * 100)} %.`
})
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

function kindFor(name: string, type: string): 'image' | 'pdf' | 'file' {
  if (type.startsWith('image/') || /\.jpe?g$|\.png$/i.test(name)) {
    return 'image'
  }
  if (type === 'application/pdf' || /\.pdf$/i.test(name)) {
    return 'pdf'
  }
  return 'file'
}

async function sniffKind(source: Blob, name: string, type: string): Promise<'image' | 'pdf' | 'file'> {
  const known = kindFor(name, type)
  if (known !== 'file') {
    return known
  }
  const bytes = new Uint8Array(await source.slice(0, 8).arrayBuffer())
  if (bytes[0] === 0xff && bytes[1] === 0xd8) {
    return 'image'
  }
  if (bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4e && bytes[3] === 0x47) {
    return 'image'
  }
  if (bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46) {
    return 'pdf'
  }
  return 'file'
}

async function replacePreview(source: Blob, name: string, type: string) {
  const kind = await sniffKind(source, name, type)
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = URL.createObjectURL(source)
  previewKind.value = kind
  previewName.value = name
}

async function onFiles(files: FileList | null) {
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
  storedPreviewFailed.value = false
  await replacePreview(file, file.name, file.type)
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

watch(
  () => [props.dossierId, props.pieceId] as const,
  async ([dossierId, pieceId]) => {
    const token = ++previewToken
    if (previewName.value || !dossierId || !pieceId) {
      return
    }
    storedPreviewFailed.value = false
    try {
      const blob = await fetchVerificationEvidenceBlob(dossierId, pieceId)
      if (token !== previewToken || previewName.value) {
        return
      }
      await replacePreview(blob, props.label, blob.type)
    }
    catch {
      if (token === previewToken) {
        storedPreviewFailed.value = true
      }
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
})
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
        'opacity-50': disabled && !loading,
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
        <template v-if="loading">{{ progressLabel }}</template>
        <template v-else-if="previewName">Aperçu de {{ previewName }}.</template>
        <template v-else-if="selectedName">{{ selectedName }}</template>
        <template v-else>Déposez un PDF, JPEG ou PNG, ou choisissez un fichier.</template>
      </p>
      <div
        v-if="loading"
        class="mt-3"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuenow="measuredProgress === null ? undefined : Math.round(measuredProgress * 100)"
        :aria-valuetext="progressLabel"
      >
        <div class="h-1 overflow-hidden rounded-full bg-surface-muted">
          <div
            class="h-full rounded-full bg-brand"
            :class="measuredProgress === null ? 'evidence-progress-indeterminate' : ''"
            :style="measuredProgress === null ? undefined : { width: `${Math.round(measuredProgress * 100)}%` }"
          />
        </div>
      </div>
      <img
        v-if="previewUrl && previewKind === 'image'"
        :src="previewUrl"
        :alt="`Aperçu : ${label}`"
        class="mt-3 max-h-40 w-full rounded-md border border-border bg-white object-contain"
      >
      <iframe
        v-else-if="previewUrl && previewKind === 'pdf'"
        :src="previewUrl"
        :title="`Aperçu : ${label}`"
        class="mt-3 h-40 w-full rounded-md border border-border bg-white"
      />
      <p v-else-if="storedPreviewFailed" class="mt-3 font-sans text-sm text-text-muted">
        L’aperçu de cette pièce n’est pas disponible.
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
