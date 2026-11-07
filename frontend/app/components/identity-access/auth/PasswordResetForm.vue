<script setup lang="ts">
import { passwordMeetsRequirements, passwordRequirementsMessage } from '../../../utils/ui/password'

const props = withDefaults(
  defineProps<{
    emailMasque?: string
    expireLabel?: string
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
  submit: [password: string]
}>()

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

  emit('submit', password.value)
}
</script>

<template>
  <form class="flex flex-col gap-stack" @submit.prevent="onSubmit">
    <p v-if="emailMasque" class="font-sans text-sm text-text">
      Adresse concernée : {{ emailMasque }}
    </p>
    <p v-if="expireLabel" class="font-sans text-sm text-text-muted">
      Valable jusqu’au {{ expireLabel }}.
    </p>
    <p class="font-sans text-sm text-text-muted">
      L’affichage de cette page ne change pas le mot de passe. Confirmez pour l’enregistrer.
      Toutes les sessions ouvertes seront révoquées. Aucune connexion automatique n’a lieu.
    </p>
    <FormField
      input-id="field-reset-password"
      label="Nouveau mot de passe"
      required
      :described-by-extra="password ? 'field-reset-password-requirements' : undefined"
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
          id="field-reset-password-requirements"
          :password="password"
        />
      </template>
    </FormField>
    <PasswordConfirmation
      input-id="field-reset-password-confirm"
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
