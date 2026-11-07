<script setup lang="ts">
export type ErrorSummaryItem = {
  id: string
  message: string
}

defineProps<{
  errors: ErrorSummaryItem[]
}>()
</script>

<template>
  <div
    v-if="errors.length > 0"
    role="alert"
    class="ui-alert ui-alert-danger font-sans text-sm"
  >
    <UiIcon name="error" class="mt-0.5 shrink-0" />
    <div class="min-w-0">
      <p class="font-medium">
        Corrigez les erreurs suivantes avant de créer le compte.
      </p>
      <ul class="mt-2 list-disc space-y-1 pl-5">
        <li v-for="item in errors" :key="item.id">
          <a
            :href="`#${item.id}`"
            class="underline underline-offset-4 focus-ring rounded-sm"
          >
            {{ item.message }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>
