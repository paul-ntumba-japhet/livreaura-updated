<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    tone?: 'info' | 'success' | 'warning' | 'error'
    title?: string
  }>(),
  {
    tone: 'info',
  },
)

const liveRole = computed(() => (props.tone === 'error' || props.tone === 'warning' ? 'alert' : 'status'))
const iconName = computed(() => (props.tone === 'error' ? 'error' : props.tone))
const alertClass = computed(() => {
  switch (props.tone) {
    case 'success':
      return 'ui-alert-success'
    case 'warning':
      return 'ui-alert-warning'
    case 'error':
      return 'ui-alert-danger'
    default:
      return 'ui-alert-info'
  }
})
</script>

<template>
  <div
    :role="liveRole"
    class="ui-alert font-sans text-sm"
    :class="alertClass"
  >
    <UiIcon
      :name="iconName"
      class="mt-0.5 shrink-0"
    />
    <div class="min-w-0">
      <p v-if="title" class="font-medium">
        <span class="sr-only">
          <template v-if="tone === 'error'">Erreur : </template>
          <template v-else-if="tone === 'warning'">Avertissement : </template>
          <template v-else-if="tone === 'success'">Succès : </template>
          <template v-else>Information : </template>
        </span>
        {{ title }}
      </p>
      <p
        v-if="$slots.default"
        class="opacity-90"
        :class="{ 'mt-1': title }"
      >
        <slot />
      </p>
    </div>
  </div>
</template>
