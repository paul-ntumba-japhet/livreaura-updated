<script setup lang="ts">
import { radioGroupKey } from '../../utils/ui/radio-group'

const props = withDefaults(
  defineProps<{
    legend: string
    name?: string
    modelValue?: string
    disabled?: boolean
    error?: string
    variant?: 'stack' | 'cards'
    legendSrOnly?: boolean
  }>(),
  {
    disabled: false,
    variant: 'stack',
    legendSrOnly: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const generatedName = useId()
const errorId = useId()
const resolvedName = computed(() => props.name ?? generatedName)

provide(radioGroupKey, {
  name: resolvedName,
  selected: computed(() => props.modelValue ?? ''),
  disabled: computed(() => props.disabled),
  setValue: (value: string) => {
    if (!props.disabled) {
      emit('update:modelValue', value)
    }
  },
})
</script>

<template>
  <fieldset
    class="flex min-w-0 flex-col gap-stack border-0 p-0"
    :disabled="disabled"
    :aria-describedby="error ? errorId : undefined"
    :aria-invalid="error ? true : undefined"
  >
    <legend
      class="mb-stack font-sans text-sm font-medium text-text"
      :class="{ 'sr-only': legendSrOnly }"
    >
      {{ legend }}
    </legend>
    <div
      :class="variant === 'cards'
        ? 'grid gap-gutter sm:grid-cols-3'
        : 'flex flex-col gap-stack'"
    >
      <slot />
    </div>
    <p v-if="error" :id="errorId" class="text-sm text-danger">
      {{ error }}
    </p>
  </fieldset>
</template>
