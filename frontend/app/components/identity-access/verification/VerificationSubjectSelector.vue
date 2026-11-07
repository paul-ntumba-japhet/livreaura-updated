<script setup lang="ts">
import type { VerificationSubjectOption } from '../../../types/identity-access/verification'

withDefaults(
  defineProps<{
    options: VerificationSubjectOption[]
    modelValue?: string
    disabled?: boolean
    error?: string
  }>(),
  {
    disabled: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <RadioGroup
    :model-value="modelValue"
    legend="Sujet du dossier"
    name="sujet-verification"
    :disabled="disabled"
    :error="error"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <ChoiceCard
      v-for="option in options"
      :key="option.id"
      :value="option.id"
      :title="option.label"
      :description="option.description"
      :disabled="disabled || option.disabled"
      :disabled-reason="option.disabledReason"
    >
      <template #icon>
        <span
          class="inline-flex size-10 items-center justify-center rounded-full bg-surface-muted text-text-muted"
        >
          <UiIcon
            :name="option.kind === 'organisation' ? 'building-2' : 'user'"
            size="md"
          />
        </span>
      </template>
    </ChoiceCard>
  </RadioGroup>
</template>
