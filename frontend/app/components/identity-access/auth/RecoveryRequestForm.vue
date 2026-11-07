<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    disabled?: boolean
    submitting?: boolean
    waitSeconds?: number
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    disabled: false,
    submitting: false,
    waitSeconds: 0,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: [email: string]
}>()

const email = ref('')
const localErrors = ref<Record<string, string>>({})

const waiting = computed(() => (props.waitSeconds ?? 0) > 0)
const locked = computed(() => props.disabled || props.submitting || waiting.value)

const disabledReason = computed(() => {
  if (waiting.value) {
    return `Nouvelle demande possible dans ${props.waitSeconds} s.`
  }
  return undefined
})

function clearError(name: string) {
  if (localErrors.value[name]) {
    const next = { ...localErrors.value }
    delete next[name]
    localErrors.value = next
  }
}

function errorOf(key: string): string | undefined {
  return localErrors.value[key] ?? props.fieldErrors[key]?.[0]
}

function onSubmit() {
  const trimmed = email.value.trim()
  const next: Record<string, string> = {}
  if (!trimmed) {
    next.email = 'Indiquez votre adresse e-mail.'
  }
  localErrors.value = next
  if (Object.keys(next).length > 0) {
    return
  }
  emit('submit', trimmed)
}
</script>

<template>
  <form class="flex flex-col gap-stack" @submit.prevent="onSubmit">
    <FormField
      input-id="field-recovery-email"
      label="Adresse e-mail"
      required
      :error="errorOf('email')"
    >
      <template #default="{ id, describedBy, invalid }">
        <EmailInput
          :id="id"
          v-model="email"
          autocomplete="username"
          placeholder="votre.email@exemple.com"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="clearError('email')"
        />
      </template>
    </FormField>
    <FormActions align="stretch">
      <UiButton
        type="submit"
        :loading="submitting"
        :disabled="locked"
        :disabled-reason="disabledReason"
        block
      >
        Demander la réinitialisation
      </UiButton>
    </FormActions>
  </form>
</template>
