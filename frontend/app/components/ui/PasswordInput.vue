<script setup lang="ts">
import type { UiIconName } from './icons'

defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    modelValue?: string
    autocomplete?: string
    maxlength?: number
    disabled?: boolean
    invalid?: boolean
    icon?: UiIconName
  }>(),
  {
    autocomplete: 'new-password',
    disabled: false,
    invalid: false,
    icon: 'lock',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const visible = ref(false)
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
      :type="visible ? 'text' : 'password'"
      class="field-control w-full font-sans text-sm text-text disabled:cursor-not-allowed disabled:opacity-50"
      :class="{
        'pl-11 pr-12': icon,
        'px-gutter pr-12': !icon,
      }"
      :value="modelValue"
      :autocomplete="autocomplete"
      :maxlength="maxlength"
      :disabled="disabled"
      spellcheck="false"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
    <button
      type="button"
      class="absolute right-2 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-sm text-text-muted focus-ring"
      :disabled="disabled"
      :aria-pressed="visible"
      :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
      @click="visible = !visible"
    >
      <UiIcon :name="visible ? 'eye-off' : 'eye'" />
    </button>
  </div>
</template>
