<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    modelValue?: boolean
    disabled?: boolean
    indeterminate?: boolean
  }>(),
  {
    disabled: false,
    indeterminate: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const input = useTemplateRef<HTMLInputElement>('input')

watch(
  () => props.indeterminate,
  (value) => {
    if (input.value) {
      input.value.indeterminate = value
    }
  },
  { immediate: true },
)
</script>

<template>
  <input
    ref="input"
    type="checkbox"
    class="size-4 shrink-0 accent-brand focus-ring"
    :checked="modelValue"
    :disabled="disabled"
    @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
  >
</template>
