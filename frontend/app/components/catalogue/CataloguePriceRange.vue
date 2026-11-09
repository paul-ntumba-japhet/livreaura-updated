<script setup lang="ts">
const PRICE_FLOOR = 0
const PRICE_CEILING = 500

const props = withDefaults(
  defineProps<{
    minimum?: number
    maximum?: number
    disabled?: boolean
  }>(),
  {
    minimum: PRICE_FLOOR,
    maximum: PRICE_CEILING,
    disabled: false,
  },
)

const emit = defineEmits<{
  change: [value: { minimum: number, maximum: number }]
}>()

const low = ref(props.minimum)
const high = ref(props.maximum)
const active = ref<'minimum' | 'maximum'>('maximum')

watch(
  () => [props.minimum, props.maximum],
  ([minimum, maximum]) => {
    low.value = minimum ?? PRICE_FLOOR
    high.value = maximum ?? PRICE_CEILING
  },
)

const fillStyle = computed(() => ({
  left: `${(low.value / PRICE_CEILING) * 100}%`,
  right: `${100 - (high.value / PRICE_CEILING) * 100}%`,
}))

function read(event: Event): number {
  return Number((event.target as HTMLInputElement).value)
}

function setMinimum(event: Event) {
  low.value = Math.min(read(event), high.value)
}

function setMaximum(event: Event) {
  high.value = Math.max(read(event), low.value)
}

function commit() {
  if (props.disabled) {
    return
  }
  emit('change', { minimum: low.value, maximum: high.value })
}
</script>

<template>
  <div class="w-full min-w-0 border-t border-border py-3">
    <div class="flex items-baseline justify-between gap-3">
      <span class="font-sans text-sm font-medium text-text">Prix</span>
      <span class="font-sans text-sm text-text-muted tabular-nums">{{ low }} $ – {{ high }} $</span>
    </div>
    <div class="relative mt-2 h-11">
      <div class="pointer-events-none absolute inset-x-3 top-1/2 h-0.5 -translate-y-1/2 rounded-full bg-border-strong">
        <div class="absolute inset-y-0 rounded-full bg-brand" :style="fillStyle" />
      </div>
      <input
        class="price-range"
        type="range"
        :min="PRICE_FLOOR"
        :max="PRICE_CEILING"
        :step="1"
        :value="low"
        :disabled="disabled"
        :style="{ zIndex: active === 'minimum' ? 2 : 1 }"
        aria-label="Prix minimum"
        :aria-valuetext="`${low} dollars`"
        @pointerdown="active = 'minimum'"
        @input="setMinimum"
        @change="commit"
      >
      <input
        class="price-range"
        type="range"
        :min="PRICE_FLOOR"
        :max="PRICE_CEILING"
        :step="1"
        :value="high"
        :disabled="disabled"
        :style="{ zIndex: active === 'maximum' ? 2 : 1 }"
        aria-label="Prix maximum"
        :aria-valuetext="`${high} dollars`"
        @pointerdown="active = 'maximum'"
        @input="setMaximum"
        @change="commit"
      >
    </div>
  </div>
</template>

<style scoped>
.price-range {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  appearance: none;
  background: transparent;
  pointer-events: none;
}

.price-range:disabled {
  cursor: not-allowed;
}

.price-range::-webkit-slider-runnable-track {
  height: 44px;
  background: transparent;
  border: 0;
}

.price-range::-webkit-slider-thumb {
  width: 16px;
  height: 16px;
  margin-top: 14px;
  appearance: none;
  border: 1px solid var(--color-border-strong);
  border-radius: 999px;
  background: var(--livreaura-white);
  pointer-events: auto;
  cursor: grab;
}

.price-range:disabled::-webkit-slider-thumb {
  cursor: not-allowed;
}

.price-range:focus {
  outline: none;
}

.price-range:focus-visible::-webkit-slider-thumb {
  box-shadow:
    0 0 0 var(--ring-offset-width) var(--color-ring-offset),
    0 0 0 calc(var(--ring-offset-width) + var(--ring-width-focus)) var(--color-focus);
}

.price-range::-moz-range-track {
  height: 44px;
  background: transparent;
  border: 0;
}

.price-range::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border: 1px solid var(--color-border-strong);
  border-radius: 999px;
  background: var(--livreaura-white);
  pointer-events: auto;
  cursor: grab;
}

.price-range:focus-visible::-moz-range-thumb {
  box-shadow:
    0 0 0 var(--ring-offset-width) var(--color-ring-offset),
    0 0 0 calc(var(--ring-offset-width) + var(--ring-width-focus)) var(--color-focus);
}
</style>
