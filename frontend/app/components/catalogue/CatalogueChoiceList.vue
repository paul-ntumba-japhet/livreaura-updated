<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    legend: string
    options?: { value: string, label: string }[]
    modelValue?: string
    disabled?: boolean
    hint?: string
    error?: string
  }>(),
  {
    options: () => [],
    modelValue: '',
    disabled: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)
const panelId = useId()
const hintId = useId()
const errorId = useId()
const describedBy = computed(() => {
  const ids = [
    props.hint ? hintId : null,
    props.error ? errorId : null,
  ].filter(Boolean)
  return ids.length > 0 ? ids.join(' ') : undefined
})

function toggle() {
  open.value = !open.value
}

function choose(value: string, checked: boolean) {
  if (props.disabled || !checked || value === props.modelValue) {
    return
  }
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="w-full min-w-0 border-t border-border first:border-t-0">
    <button
      type="button"
      class="focus-ring flex min-h-11 w-full items-center justify-between bg-transparent font-sans text-sm font-medium text-text"
      :aria-expanded="open"
      :aria-controls="panelId"
      :aria-describedby="describedBy"
      @click="toggle"
    >
      <span class="truncate">{{ legend }}</span>
      <UiIcon
        name="chevron-down"
        class="shrink-0 transition-transform duration-150 motion-reduce:transition-none"
        :class="open ? 'rotate-180' : undefined"
      />
    </button>
    <p v-if="error" :id="errorId" class="mb-1 font-sans text-sm text-danger">
      {{ error }}
    </p>
    <p v-if="hint" :id="hintId" class="mb-1 font-sans text-sm text-text-muted">
      {{ hint }}
    </p>
    <div v-if="open" :id="panelId" class="pb-2">
      <slot>
        <div class="flex flex-col gap-1">
          <label
            v-for="option in options"
            :key="option.value === '' ? `${legend}-tous` : option.value"
            class="focus-ring-within flex cursor-pointer items-center gap-2 font-sans text-sm leading-5 text-text"
            :class="disabled ? 'cursor-not-allowed opacity-50' : undefined"
          >
            <input
              type="checkbox"
              class="size-4 shrink-0 accent-brand focus-ring"
              :checked="modelValue === option.value"
              :disabled="disabled"
              @change="choose(option.value, ($event.target as HTMLInputElement).checked)"
            >
            {{ option.label }}
          </label>
        </div>
      </slot>
    </div>
  </div>
</template>
