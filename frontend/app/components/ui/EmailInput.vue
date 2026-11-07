<script setup lang="ts">
import type { UiIconName } from './icons'

defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    autocomplete?: string
    maxlength?: number
    disabled?: boolean
    readonly?: boolean
    invalid?: boolean
    icon?: UiIconName
  }>(),
  {
    autocomplete: 'email',
    disabled: false,
    readonly: false,
    invalid: false,
    icon: 'mail',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <TextInput
    v-bind="$attrs"
    type="email"
    :model-value="modelValue"
    :autocomplete="autocomplete"
    :maxlength="maxlength"
    :disabled="disabled"
    :readonly="readonly"
    :invalid="invalid"
    :icon="icon"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
