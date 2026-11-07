<script setup lang="ts">
defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    maxlength?: number
    rows?: number
    disabled?: boolean
    readonly?: boolean
    invalid?: boolean
  }>(),
  {
    rows: 4,
    disabled: false,
    readonly: false,
    invalid: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <textarea
    v-bind="$attrs"
    class="field-control w-full resize-y font-sans text-sm text-text disabled:cursor-not-allowed disabled:opacity-50"
    :value="modelValue"
    :rows="rows"
    :maxlength="maxlength"
    :disabled="disabled"
    :readonly="readonly"
    :aria-invalid="invalid || undefined"
    @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
  />
</template>
