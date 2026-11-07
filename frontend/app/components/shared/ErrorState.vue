<script setup lang="ts">
defineProps<{
  title: string
  message: string
  retryLabel?: string
  retrying?: boolean
}>()

const emit = defineEmits<{
  retry: []
}>()
</script>

<template>
  <div class="ui-alert ui-alert-danger" role="alert">
    <UiIcon name="error" class="mt-1 shrink-0" />
    <div class="min-w-0">
      <h2 class="font-serif text-xl font-semibold">
        {{ title }}
      </h2>
      <p class="mt-2 max-w-xl font-sans text-sm opacity-90">
        {{ message }}
      </p>
      <div v-if="retryLabel" class="mt-stack">
        <UiButton
          variant="secondary"
          :loading="retrying"
          :disabled="retrying"
          @click="emit('retry')"
        >
          {{ retryLabel }}
        </UiButton>
      </div>
    </div>
  </div>
</template>
