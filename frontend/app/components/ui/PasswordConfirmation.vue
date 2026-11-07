<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    password: string
    modelValue?: string
    disabled?: boolean
    error?: string
    inputId?: string
  }>(),
  {
    disabled: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const mismatch = computed(() => {
  return Boolean(props.modelValue) && props.modelValue !== props.password
})
const shownError = computed(() => {
  if (props.error) {
    return props.error
  }
  return mismatch.value ? 'Les deux saisies doivent être identiques.' : undefined
})
</script>

<template>
  <FormField
    label="Confirmation du mot de passe"
    required
    :error="shownError"
    :input-id="inputId"
  >
    <template #default="{ id, describedBy, invalid }">
      <PasswordInput
        :id="id"
        :model-value="modelValue"
        autocomplete="new-password"
        placeholder="Confirmez le mot de passe"
        :disabled="disabled"
        :invalid="invalid"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || undefined"
        @update:model-value="emit('update:modelValue', $event)"
      />
    </template>
  </FormField>
</template>
