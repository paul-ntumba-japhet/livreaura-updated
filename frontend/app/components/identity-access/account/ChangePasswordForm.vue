<script setup lang="ts">
import { passwordMeetsRequirements, passwordRequirementsMessage } from '../../../utils/ui/password'

const props = withDefaults(
  defineProps<{
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
  submit: [payload: { password_actuel: string, password: string }]
}>()

const currentPassword = ref('')
const password = ref('')
const passwordConfirm = ref('')
const localErrors = ref<Record<string, string>>({})

const locked = computed(() => props.disabled || props.submitting)

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
  if (!currentPassword.value) {
    next.password_actuel = 'Indiquez le mot de passe actuel.'
  }
  if (!password.value) {
    next.password = 'Indiquez un nouveau mot de passe.'
  }
  else if (!passwordMeetsRequirements(password.value)) {
    next.password = passwordRequirementsMessage
  }
  if (!passwordConfirm.value) {
    next.passwordConfirm = 'Confirmez le mot de passe.'
  }
  else if (passwordConfirm.value !== password.value) {
    next.passwordConfirm = 'Les deux saisies doivent être identiques.'
  }

  localErrors.value = next
  if (Object.keys(next).length > 0) {
    return
  }

  emit('submit', {
    password_actuel: currentPassword.value,
    password: password.value,
  })
}
</script>

<template>
  <form class="flex flex-col gap-stack" @submit.prevent="onSubmit">
    <p class="font-sans text-sm text-text-muted">
      Toutes les sessions ouvertes seront révoquées. Aucune connexion automatique n’a lieu. Vous devrez vous reconnecter avec le nouveau mot de passe.
    </p>
    <FormField
      input-id="field-current-password"
      label="Mot de passe actuel"
      required
      :error="errorOf('password_actuel')"
    >
      <template #default="{ id, describedBy, invalid }">
        <PasswordInput
          :id="id"
          v-model="currentPassword"
          autocomplete="current-password"
          placeholder="Mot de passe actuel"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="clearError('password_actuel')"
        />
      </template>
    </FormField>
    <FormField
      input-id="field-new-password"
      label="Nouveau mot de passe"
      required
      :described-by-extra="password ? 'field-new-password-requirements' : undefined"
      :error="errorOf('password')"
    >
      <template #default="{ id, describedBy, invalid }">
        <PasswordInput
          :id="id"
          v-model="password"
          autocomplete="new-password"
          placeholder="Nouveau mot de passe"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="clearError('password')"
        />
      </template>
      <template #after>
        <PasswordRequirements
          id="field-new-password-requirements"
          :password="password"
        />
      </template>
    </FormField>
    <PasswordConfirmation
      input-id="field-new-password-confirm"
      v-model="passwordConfirm"
      :password="password"
      :disabled="locked"
      :error="errorOf('passwordConfirm')"
      @update:model-value="clearError('passwordConfirm')"
    />
    <FormActions align="stretch">
      <UiButton
        type="submit"
        :loading="submitting"
        :disabled="locked"
        block
      >
        Enregistrer le nouveau mot de passe
      </UiButton>
    </FormActions>
  </form>
</template>
