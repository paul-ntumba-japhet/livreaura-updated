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
    size?: 'default' | '46'
  }>(),
  {
    variant: 'primary',
    type: 'button',
    loading: false,
    disabled: false,
    block: false,
    pill: false,
    size: 'default',
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
      class="inline-flex items-center justify-center gap-2 rounded-md px-gutter font-sans text-sm font-medium focus-ring"
      :class="[
        props.size === '46' ? 'h-[46px] min-h-[46px] py-0' : 'min-h-11 py-2',
        block ? 'w-full' : '',
        !isDisabled ? 'cursor-pointer' : '',
        variant === 'primary' ? 'bg-brand text-text-on-brand hover:bg-brand-hover' : '',
        variant === 'secondary' ? 'border border-border bg-surface-raised text-text' : '',
        variant === 'outline' ? 'border border-brand bg-transparent text-text hover:bg-surface-muted' : '',
        variant === 'quiet' ? 'bg-transparent text-text' : '',
        variant === 'destructive' ? 'bg-danger text-text-inverse' : '',
        isDisabled ? 'pointer-events-none cursor-not-allowed opacity-50' : '',
      ]"
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
