<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    modelValue?: boolean
    required?: boolean
    disabled?: boolean
    error?: string
    label: string
    inputId?: string
    consultLabel?: string
    consultTitle?: string
    consultVersion?: string
    consultContent?: string | null
    consultHref?: string
  }>(),
  {
    disabled: false,
    required: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const generatedId = useId()
const errorId = useId()
const titleId = useId()
const controlId = computed(() => props.inputId ?? generatedId)
const dialog = useTemplateRef<HTMLDialogElement>('dialog')
const hasConsult = computed(() => Boolean(props.consultLabel))
const consultOpensExternally = computed(() => Boolean(props.consultHref))

function openConsult() {
  dialog.value?.showModal()
}

function closeConsult() {
  dialog.value?.close()
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <div class="flex items-start gap-gutter">
      <Checkbox
        :id="controlId"
        class="mt-0.5"
        :model-value="modelValue"
        :disabled="disabled"
        :aria-invalid="error ? true : undefined"
        :aria-describedby="error ? errorId : undefined"
        @update:model-value="emit('update:modelValue', $event)"
      />
      <div class="flex min-w-0 flex-col gap-1">
        <label :for="controlId" class="font-sans text-sm text-text">
          {{ label }}
          <span v-if="required" class="text-danger" aria-hidden="true"> *</span>
          <span v-if="required" class="sr-only"> (requis)</span>
        </label>
        <p v-if="hasConsult">
          <UiLink
            v-if="consultOpensExternally && consultHref"
            :to="consultHref"
            external
          >
            {{ consultLabel }}
          </UiLink>
          <button
            v-else
            type="button"
            class="rounded-sm font-sans text-sm text-text underline decoration-brand underline-offset-4 focus-ring"
            @click="openConsult"
          >
            {{ consultLabel }}
          </button>
        </p>
      </div>
    </div>
    <p v-if="error" :id="errorId" class="font-sans text-sm text-danger">
      {{ error }}
    </p>
    <dialog
      v-if="hasConsult && !consultOpensExternally"
      ref="dialog"
      class="legal-text-dialog"
      :aria-labelledby="titleId"
    >
      <div class="flex flex-col gap-stack">
        <VersionedLegalText
          :title="consultTitle ?? label"
          :title-id="titleId"
          :version="consultVersion"
          :content="consultContent"
        />
        <FormActions>
          <UiButton variant="secondary" @click="closeConsult">
            Fermer
          </UiButton>
        </FormActions>
      </div>
    </dialog>
  </div>
</template>

<style scoped>
.legal-text-dialog {
  max-width: min(40rem, calc(100vw - 2 * var(--spacing-page)));
  max-height: min(80vh, 40rem);
  width: 100%;
  margin: auto;
  overflow: auto;
  border: var(--border-width-thin) solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-raised);
  color: var(--color-text);
  padding: var(--spacing-gutter);
}

.legal-text-dialog::backdrop {
  background: color-mix(in srgb, var(--color-text) 45%, transparent);
}
</style>
