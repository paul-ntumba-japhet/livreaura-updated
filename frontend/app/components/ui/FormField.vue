<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    label: string
    required?: boolean
    hint?: string
    error?: string
    inputId?: string
    describedByExtra?: string
  }>(),
  {
    required: false,
  },
)

const generatedId = useId()
const hintId = useId()
const errorId = useId()
const controlId = computed(() => props.inputId ?? generatedId)
const describedBy = computed(() => {
  const ids = [
    props.hint ? hintId : null,
    props.error ? errorId : null,
    props.describedByExtra ?? null,
  ].filter(Boolean)
  return ids.length > 0 ? ids.join(' ') : undefined
})
</script>

<template>
  <div class="flex min-w-0 flex-col gap-1">
    <div class="flex items-center justify-between gap-3">
      <label :for="controlId" class="font-sans text-sm font-medium text-text">
        {{ label }}
        <span v-if="required" class="text-danger" aria-hidden="true"> *</span>
        <span v-if="required" class="sr-only"> (requis)</span>
      </label>
      <slot name="labelAction" />
    </div>
    <slot
      :id="controlId"
      :describedBy="describedBy"
      :invalid="Boolean(error)"
    />
    <slot name="after" />
    <p v-if="hint" :id="hintId" class="font-sans text-sm text-text-muted">
      {{ hint }}
    </p>
    <p v-if="error" :id="errorId" class="font-sans text-sm text-danger">
      {{ error }}
    </p>
  </div>
</template>
