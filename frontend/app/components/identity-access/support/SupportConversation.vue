<script setup lang="ts">
import type { SupportExchange } from '../../../types/identity-access/support'

defineProps<{
  messages: SupportExchange[]
}>()
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Échanges
    </h2>
    <EmptyState
      v-if="messages.length === 0"
      class="mt-stack"
      title="Aucun échange"
      message="Aucun message n’est encore visible pour cette demande."
    />
    <ol
      v-else
      class="mt-stack grid gap-stack"
    >
      <li
        v-for="message in messages"
        :key="message.id"
      >
        <SupportMessage :message="message" />
      </li>
    </ol>
  </section>
</template>
