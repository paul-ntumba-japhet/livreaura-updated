<script setup lang="ts">
defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    disabled?: boolean
    readonly?: boolean
    invalid?: boolean
    min?: string
    max?: string
  }>(),
  {
    disabled: false,
    readonly: false,
    invalid: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
</script>

<template>
  <div class="grid gap-1">
    <input
      v-bind="$attrs"
      type="datetime-local"
      class="field-control w-full font-sans text-sm text-text disabled:cursor-not-allowed disabled:opacity-50 px-gutter"
      :value="modelValue"
      :disabled="disabled"
      :readonly="readonly"
      :min="min"
      :max="max"
      :aria-invalid="invalid || undefined"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
    <p class="font-sans text-sm text-text-muted">
      Fuseau affiché : {{ timeZone }}
    </p>
  </div>
</template>
