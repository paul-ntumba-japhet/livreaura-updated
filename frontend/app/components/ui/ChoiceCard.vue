<script setup lang="ts">
import { radioGroupKey } from '../../utils/ui/radio-group'

const props = withDefaults(
  defineProps<{
    value: string
    name?: string
    modelValue?: string
    title: string
    subtitle?: string
    description?: string
    disabled?: boolean
    disabledReason?: string
    variant?: 'row' | 'tile'
    benefits?: string[]
  }>(),
  {
    disabled: false,
    variant: 'row',
    benefits: () => [],
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const group = inject(radioGroupKey, null)
const reasonId = useId()
const inputId = useId()

const inputName = computed(() => props.name ?? group?.name.value)
const isDisabled = computed(() => props.disabled || Boolean(group?.disabled.value))
const isSelected = computed(() => {
  const current = group?.selected.value ?? props.modelValue
  return current === props.value
})

function onChange() {
  if (isDisabled.value) {
    return
  }
  group?.setValue(props.value)
  emit('update:modelValue', props.value)
}
</script>

<template>
  <div class="flex flex-col gap-1" :class="{ 'h-full': variant === 'tile' }">
    <label
      :for="inputId"
      class="relative flex cursor-pointer rounded-md border-2"
      :class="{
        'min-h-11 items-start gap-gutter px-gutter py-stack': variant === 'row',
        'h-full min-h-48 flex-col items-center gap-stack px-gutter py-section text-center focus-ring-within': variant === 'tile',
        'border-border': !isSelected || isDisabled,
        'border-brand shadow-sm': isSelected && !isDisabled,
        'bg-surface': variant === 'row' && isSelected && !isDisabled,
        'bg-surface-raised': (variant === 'row' && (!isSelected || isDisabled)) || (variant === 'tile' && isSelected),
        'bg-surface-muted': variant === 'tile' && !isSelected,
        'cursor-not-allowed opacity-50': isDisabled,
      }"
    >
      <input
        :id="inputId"
        type="radio"
        class="size-4 shrink-0 accent-brand"
        :class="variant === 'tile' ? 'sr-only' : 'mt-1 focus-ring'"
        :name="inputName"
        :value="value"
        :checked="isSelected"
        :disabled="isDisabled"
        :aria-describedby="disabled && disabledReason ? reasonId : undefined"
        @change="onChange"
      >
      <span
        v-if="variant === 'tile' && isSelected"
        class="absolute right-3 top-3 flex size-6 items-center justify-center rounded-full bg-brand text-text-on-brand"
        aria-hidden="true"
      >
        <UiIcon name="check" />
      </span>
      <span
        v-if="$slots.icon"
        class="flex items-center justify-center"
        :class="variant === 'tile'
          ? 'choice-icon size-16 rounded-full border border-brand text-brand'
          : (isSelected ? 'text-brand' : 'text-text')"
        aria-hidden="true"
      >
        <slot name="icon" />
      </span>
      <span class="min-w-0" :class="variant === 'tile' ? 'choice-body w-full' : ''">
        <span
          class="choice-title block font-sans font-medium"
          :class="variant === 'tile' ? 'text-base text-text' : 'text-sm text-text'"
        >
          {{ title }}
        </span>
        <span v-if="subtitle" class="choice-subtitle mt-1 block font-sans text-sm text-brand">
          {{ subtitle }}
        </span>
        <span v-if="description" class="choice-description mt-2 block font-sans text-sm text-text-muted">
          {{ description }}
        </span>
        <ul v-if="benefits.length" class="choice-benefits">
          <li v-for="benefit in benefits" :key="benefit">
            {{ benefit }}
          </li>
        </ul>
      </span>
    </label>
    <p
      v-if="disabled && disabledReason"
      :id="reasonId"
      class="px-gutter text-sm text-text-muted"
    >
      {{ disabledReason }}
    </p>
  </div>
</template>
