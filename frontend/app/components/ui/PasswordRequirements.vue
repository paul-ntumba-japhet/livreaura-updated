<script setup lang="ts">
import { evaluatePassword, passwordGuidance } from '../../utils/ui/password'

const props = defineProps<{
  password: string
}>()

const evaluation = computed(() => evaluatePassword(props.password))
const guidance = computed(() => passwordGuidance(evaluation.value))
const visible = computed(() => evaluation.value.level !== 'empty' && !evaluation.value.complete)
const statusLabel = computed(() => {
  if (evaluation.value.level === 'empty') {
    return ''
  }
  if (evaluation.value.complete) {
    return 'Le mot de passe respecte les exigences.'
  }
  return `Force du mot de passe : ${evaluation.value.levelLabel}. ${guidance.value}`
})

function barClass(index: number): string {
  const filled = index <= evaluation.value.score
  if (!filled) {
    return 'bg-surface-muted'
  }
  switch (evaluation.value.level) {
    case 'weak':
      return 'bg-danger'
    case 'medium':
      return 'bg-warning'
    case 'strong':
    case 'excellent':
      return 'bg-success'
    default:
      return 'bg-surface-muted'
  }
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <p class="sr-only" aria-live="polite">
      {{ statusLabel }}
    </p>
    <template v-if="visible">
      <div class="flex items-center gap-gutter">
        <div
          class="grid flex-1 grid-cols-5 gap-1"
          role="meter"
          aria-hidden="true"
          :aria-valuemin="0"
          :aria-valuemax="5"
          :aria-valuenow="evaluation.score"
        >
          <span
            v-for="index in 5"
            :key="index"
            class="h-0.5 rounded-sm"
            :class="barClass(index)"
          />
        </div>
        <p
          class="shrink-0 font-sans text-sm font-medium"
          :class="{
            'text-danger': evaluation.level === 'weak',
            'text-warning': evaluation.level === 'medium',
            'text-success': evaluation.level === 'strong' || evaluation.level === 'excellent',
          }"
        >
          {{ evaluation.levelLabel }}
        </p>
      </div>
      <p v-if="guidance" class="font-sans text-sm text-text-muted">
        {{ guidance }}
      </p>
    </template>
  </div>
</template>
