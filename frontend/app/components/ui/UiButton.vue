<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

defineOptions({ inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'quiet' | 'destructive' | 'outline'
    type?: 'button' | 'submit' | 'reset'
    loading?: boolean
    disabled?: boolean
    disabledReason?: string
    block?: boolean
    to?: RouteLocationRaw
    pill?: boolean
  }>(),
  {
    variant: 'primary',
    type: 'button',
    loading: false,
    disabled: false,
    block: false,
    pill: false,
  },
)

const reasonId = useId()
const isDisabled = computed(() => props.disabled || props.loading)
const isLink = computed(() => Boolean(props.to) && !isDisabled.value)
const NuxtLink = resolveComponent('NuxtLink')
const controlTag = computed(() => (isLink.value ? NuxtLink : 'button'))
</script>

<template>
  <span
    class="flex-col items-stretch gap-1"
    :class="block ? 'flex w-full' : 'inline-flex'"
  >
    <component
      :is="controlTag"
      v-bind="$attrs"
      :to="isLink ? to : undefined"
      :type="isLink ? undefined : type"
      class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-gutter py-2 font-sans text-sm font-medium focus-ring"
      :class="{
        'w-full': block,
        'cursor-pointer': !isDisabled,
        'bg-brand text-text-on-brand hover:bg-brand-hover': variant === 'primary',
        'border border-border bg-surface-raised text-text': variant === 'secondary',
        'border border-brand bg-transparent text-text hover:bg-surface-muted': variant === 'outline',
        'bg-transparent text-text': variant === 'quiet',
        'bg-danger text-text-inverse': variant === 'destructive',
        'pointer-events-none cursor-not-allowed opacity-50': isDisabled,
      }"
      :disabled="isLink ? undefined : isDisabled"
      :aria-busy="loading || undefined"
      :aria-describedby="disabled && disabledReason ? reasonId : undefined"
    >
      <span
        v-if="loading"
        class="inline-flex"
        aria-hidden="true"
      >
        <UiIcon name="loader" class="animate-spin" />
      </span>
      <span class="inline-flex items-center gap-2">
        <slot />
      </span>
      <span v-if="loading" class="sr-only">Traitement en cours</span>
    </component>
    <span
      v-if="disabled && disabledReason"
      :id="reasonId"
      class="text-sm text-text-muted"
    >
      {{ disabledReason }}
    </span>
  </span>
</template>
