<script setup lang="ts">
export type StepStatus = 'upcoming' | 'current' | 'complete' | 'error' | 'blocked'

export type StepItem = {
  id: string
  label: string
  status: StepStatus
  hint?: string
}

const props = withDefaults(
  defineProps<{
    steps: StepItem[]
    label?: string
    layout?: 'stacked' | 'inline'
    interactive?: boolean
    compact?: boolean
  }>(),
  {
    layout: 'stacked',
    interactive: false,
    compact: false,
  },
)

const currentIndex = computed(() => {
  const index = props.steps.findIndex(step => step.status === 'current' || step.status === 'error')
  return index === -1 ? 0 : index
})

const summary = computed(() => {
  const step = props.steps[currentIndex.value]
  if (!step) {
    return ''
  }
  return `Étape ${currentIndex.value + 1} sur ${props.steps.length} · ${step.label}`
})

function markClass(status: StepStatus): string {
  if (status === 'error') {
    return 'is-error'
  }
  if (status === 'complete') {
    return 'is-complete'
  }
  if (status === 'current') {
    return 'is-current'
  }
  return 'is-pending'
}

const emit = defineEmits<{
  select: [id: string]
}>()

function isPending(status: StepStatus): boolean {
  return status === 'upcoming' || status === 'blocked'
}

function onSelect(id: string) {
  emit('select', id)
}
</script>

<template>
  <nav
    :aria-label="label ?? 'Progression'"
    :class="[compact ? 'stepper-compact overflow-visible' : 'overflow-x-auto']"
  >
    <ol
      class="flex"
      :class="layout === 'inline' ? 'flex-wrap items-center gap-y-2' : 'items-start'"
    >
      <li
        v-for="(step, index) in steps"
        :key="step.id"
        class="flex min-w-0"
        :class="layout === 'inline' ? 'items-center' : 'flex-1 flex-col items-center'"
      >
        <div class="flex items-center" :class="layout === 'stacked' ? 'w-full' : ''">
          <span
            v-if="layout === 'stacked'"
            class="h-px flex-1"
            :class="compact
              ? ['stepper-line', { 'is-spacer': index === 0 }]
              : (index === 0
                ? 'bg-transparent'
                : isPending(step.status) ? 'bg-border' : 'bg-brand')"
            aria-hidden="true"
          />
          <button
            v-if="interactive"
            type="button"
            class="flex size-8 shrink-0 items-center justify-center rounded-full font-sans text-sm font-medium"
            :class="compact
              ? ['stepper-mark', markClass(step.status)]
              : {
                'bg-brand text-text-on-brand': step.status === 'current' || step.status === 'complete',
                'border border-border bg-surface-raised text-text-muted': isPending(step.status),
                'border border-danger text-danger': step.status === 'error',
              }"
            :aria-current="step.status === 'current' ? 'step' : undefined"
            :aria-label="step.label"
            @click="onSelect(step.id)"
          >
            <UiIcon v-if="step.status === 'complete'" name="check" />
            <span v-else>{{ index + 1 }}</span>
          </button>
          <span
            v-else
            class="flex size-8 shrink-0 items-center justify-center rounded-full font-sans text-sm font-medium"
            :class="compact
              ? ['stepper-mark', markClass(step.status)]
              : {
                'bg-brand text-text-on-brand': step.status === 'current' || step.status === 'complete',
                'border border-border bg-surface-raised text-text-muted': isPending(step.status),
                'border border-danger text-danger': step.status === 'error',
              }"
            :aria-current="step.status === 'current' ? 'step' : undefined"
            :aria-label="step.label"
          >
            <UiIcon v-if="step.status === 'complete'" name="check" />
            <span v-else>{{ index + 1 }}</span>
          </span>
          <span
            v-if="layout === 'stacked'"
            class="h-px flex-1"
            :class="compact
              ? ['stepper-line', { 'is-spacer': index === steps.length - 1 }]
              : (index === steps.length - 1
                ? 'bg-transparent'
                : isPending(steps[index + 1]?.status ?? 'upcoming') ? 'bg-border' : 'bg-brand')"
            aria-hidden="true"
          />
        </div>
        <button
          v-if="interactive"
          type="button"
          class="font-sans text-xs"
          :class="compact
            ? 'sr-only'
            : [
              layout === 'inline' ? 'ml-2 whitespace-nowrap' : 'mt-2 px-1 text-center',
              step.status === 'current' ? 'font-medium text-text' : 'text-text-muted',
            ]"
          @click="onSelect(step.id)"
        >
          <span class="block">{{ step.label }}</span>
          <span v-if="step.hint" class="mt-0.5 block text-[0.7rem] font-normal text-text-muted">{{ step.hint }}</span>
        </button>
        <span
          v-else
          class="font-sans text-xs"
          :class="compact
            ? 'sr-only'
            : [
              layout === 'inline' ? 'ml-2 whitespace-nowrap' : 'mt-2 px-1 text-center',
              step.status === 'current' ? 'font-medium text-text' : 'text-text-muted',
            ]"
        >
          <span class="block">{{ step.label }}</span>
          <span v-if="step.hint" class="mt-0.5 block text-[0.7rem] font-normal text-text-muted">{{ step.hint }}</span>
        </span>
        <span
          v-if="layout === 'inline' && index < steps.length - 1"
          class="mx-3 h-px min-w-8 flex-1"
          :class="isPending(steps[index + 1]?.status ?? 'upcoming') ? 'bg-border' : 'bg-brand'"
          aria-hidden="true"
        />
      </li>
    </ol>
    <p v-if="compact && summary" class="stepper-summary">
      {{ summary }}
    </p>
  </nav>
</template>
