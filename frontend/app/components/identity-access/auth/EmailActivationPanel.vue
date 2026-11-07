<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'inbox' | 'confirm'
    emailMasque?: string
    expireLabel?: string
    needsEmail?: boolean
    hideCopy?: boolean
    email?: string
    emailError?: string
    loading?: boolean
    submitting?: boolean
    waitSeconds?: number
    disabled?: boolean
    unavailable?: boolean
  }>(),
  {
    variant: 'inbox',
    needsEmail: false,
    hideCopy: false,
    loading: false,
    submitting: false,
    waitSeconds: 0,
    disabled: false,
    unavailable: false,
  },
)

const emit = defineEmits<{
  'update:email': [value: string]
  resend: []
  activate: []
}>()
</script>

<template>
  <div class="flex flex-col gap-stack">
    <p v-if="!hideCopy && emailMasque" class="font-sans text-sm text-text">
      Adresse concernée : {{ emailMasque }}
    </p>
    <p v-if="!hideCopy && variant === 'confirm' && expireLabel" class="font-sans text-sm text-text-muted">
      Valable jusqu’au {{ expireLabel }}.
    </p>
    <p v-if="!hideCopy" class="font-sans text-sm text-text-muted">
      <template v-if="variant === 'confirm'">
        L’affichage de cette page n’active pas le compte. Confirmez pour vérifier l’adresse e-mail.
      </template>
      <template v-else>
        Consultez votre boîte de réception, y compris les courriers indésirables.
        Ouvrez le lien reçu : le compte s’active, puis vous pourrez vous connecter.
      </template>
    </p>

    <FormField
      v-if="variant === 'inbox' && needsEmail"
      input-id="field-resend-email"
      label="Adresse e-mail"
      required
      :error="emailError"
    >
      <template #default="{ id, describedBy, invalid }">
        <EmailInput
          :id="id"
          :model-value="email"
          placeholder="votre.email@exemple.com"
          :invalid="invalid"
          :disabled="disabled || loading"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          @update:model-value="emit('update:email', $event)"
        />
      </template>
    </FormField>

    <FormActions v-if="variant === 'confirm'" align="start">
      <UiButton
        type="button"
        :loading="submitting"
        :disabled="disabled || submitting || unavailable"
        :disabled-reason="unavailable ? 'Ce lien n’est plus utilisable.' : undefined"
        @click="emit('activate')"
      >
        Activer le compte
      </UiButton>
    </FormActions>

    <ResendChallengeAction
      v-if="variant === 'inbox'"
      :loading="loading"
      :disabled="disabled"
      :wait-seconds="waitSeconds"
      @resend="emit('resend')"
    />
  </div>
</template>
