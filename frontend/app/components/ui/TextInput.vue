<script setup lang="ts">
import type { UiIconName } from './icons'

defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    type?: 'text' | 'email' | 'tel'
    autocomplete?: string
    maxlength?: number
    disabled?: boolean
    readonly?: boolean
    invalid?: boolean
    icon?: UiIconName
  }>(),
  {
    type: 'text',
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
  <div class="relative">
    <span
      v-if="icon"
      class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 text-text-muted"
      aria-hidden="true"
    >
      <UiIcon :name="icon" />
    </span>
    <input
      v-bind="$attrs"
      :type="type"
      class="field-control w-full font-sans text-sm text-text disabled:cursor-not-allowed disabled:opacity-50"
      :class="{
        'px-gutter': !icon,
        'pl-11 pr-gutter': icon,
      }"
      :value="modelValue"
      :autocomplete="autocomplete"
      :maxlength="maxlength"
      :disabled="disabled"
      :readonly="readonly"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
  </div>
</template>
