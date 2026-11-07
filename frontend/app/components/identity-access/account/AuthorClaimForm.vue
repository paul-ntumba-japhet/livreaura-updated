<script setup lang="ts">
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
  submit: [auteurId: string]
}>()

const auteurId = ref('')
const localError = ref('')

const locked = computed(() => props.disabled || props.submitting)

function errorMessage(): string | undefined {
  return localError.value || props.fieldErrors.auteur_id?.[0]
}

function onSubmit() {
  const value = auteurId.value.trim().toUpperCase()
  if (!value) {
    localError.value = 'Indiquez l’identifiant de la fiche catalogue.'
    return
  }
  if (!/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/.test(value)) {
    localError.value = 'L’identifiant doit comporter 26 caractères valides.'
    return
  }

  localError.value = ''
  emit('submit', value)
}
</script>

<template>
  <form class="flex flex-col gap-stack" @submit.prevent="onSubmit">
    <p class="font-sans text-sm text-text-muted">
      Une correspondance de nom ne rattache pas une fiche. Seule une preuve examinée par le support peut le faire.
    </p>
    <FormField
      input-id="field-auteur-id"
      label="Identifiant de fiche catalogue"
      hint="26 caractères. Aucun fichier n’est déposé sur cette étape."
      required
      :error="errorMessage()"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          v-model="auteurId"
          autocomplete="off"
          :maxlength="26"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid ? true : undefined"
        />
      </template>
    </FormField>
    <FormActions align="start">
      <UiButton
        type="submit"
        variant="secondary"
        :loading="submitting"
        :disabled="disabled"
        :disabled-reason="disabled ? 'Cette revendication n’est pas disponible pour le moment.' : undefined"
      >
        Demander l’examen d’une fiche existante
      </UiButton>
    </FormActions>
  </form>
</template>
