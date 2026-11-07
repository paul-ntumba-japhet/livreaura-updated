<script setup lang="ts">
import type { UiIconName } from './icons'

export type SelectOption = {
  value: string
  label: string
  disabled?: boolean
}

defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    options: SelectOption[]
    placeholder?: string
    autocomplete?: string
    disabled?: boolean
    invalid?: boolean
    icon?: UiIconName
  }>(),
  {
    disabled: false,
    invalid: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div class="relative">
    <span
      v-if="icon"
      class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 text-text-muted"
      aria-hidden="true"
    >
      <UiIcon :name="icon" />
    </span>
    <select
      v-bind="$attrs"
      class="field-control w-full appearance-none font-sans text-sm disabled:cursor-not-allowed disabled:opacity-50"
      :class="{
        'px-gutter pr-11': !icon,
        'pl-11 pr-11': icon,
        'text-text-muted': !modelValue,
        'text-text': modelValue,
      }"
      :value="modelValue"
      :autocomplete="autocomplete"
      :disabled="disabled"
      :aria-invalid="invalid || undefined"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-if="placeholder" value="" disabled>
        {{ placeholder }}
      </option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
    <span
      class="pointer-events-none absolute right-3 top-1/2 flex -translate-y-1/2 text-text-muted"
      aria-hidden="true"
    >
      <UiIcon name="chevron-down" />
    </span>
  </div>
</template>
