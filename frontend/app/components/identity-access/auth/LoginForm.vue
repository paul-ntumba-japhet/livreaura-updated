<script setup lang="ts">
import type { LoginPayload } from '../../../types/identity-access/session'

const props = withDefaults(
  defineProps<{
    initialEmail?: string
    disabled?: boolean
    submitting?: boolean
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    disabled: false,
    submitting: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: [payload: LoginPayload]
}>()

const email = ref(props.initialEmail ?? '')
const password = ref('')
const localErrors = ref<Record<string, string>>({})

watch(
  () => props.initialEmail,
  (value) => {
    if (value && email.value === '') {
      email.value = value
    }
  },
)

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
  const next: Record<string, string> = {}
  const trimmedEmail = email.value.trim()

  if (!trimmedEmail) {
    next.email = 'Indiquez votre adresse e-mail.'
  }
  if (!password.value) {
    next.password = 'Indiquez votre mot de passe.'
  }

  localErrors.value = next
  if (Object.keys(next).length > 0) {
    return
  }

  emit('submit', {
    email: trimmedEmail,
    password: password.value,
  })
}
</script>

<template>
  <form class="flex flex-col gap-stack" @submit.prevent="onSubmit">
    <FormField
      input-id="field-login-email"
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
          :disabled="disabled || submitting"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="clearError('email')"
        />
      </template>
    </FormField>
    <FormField
      input-id="field-login-password"
      label="Mot de passe"
      required
      :error="errorOf('password')"
    >
      <template #labelAction>
        <UiLink class="login-primary-link shrink-0" to="/recuperation">
          Mot de passe oublié ?
        </UiLink>
      </template>
      <template #default="{ id, describedBy, invalid }">
        <PasswordInput
          :id="id"
          v-model="password"
          autocomplete="current-password"
          placeholder="Votre mot de passe"
          :disabled="disabled || submitting"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="clearError('password')"
        />
      </template>
    </FormField>
    <FormActions align="stretch">
      <UiButton
        type="submit"
        class="min-h-control"
        :loading="submitting"
        :disabled="disabled || submitting"
        block
      >
        Se connecter
      </UiButton>
    </FormActions>
  </form>
</template>
