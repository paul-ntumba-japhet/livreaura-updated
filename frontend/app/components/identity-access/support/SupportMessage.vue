<script setup lang="ts">
import type { SupportExchange } from '../../../types/identity-access/support'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'
import { labelForSupportOrigin, labelForSupportVisibility } from '../../../utils/identity-access/support'
import { normalizeSupportVisibility } from '../../../types/identity-access/support'

defineProps<{
  message: SupportExchange
}>()
</script>

<template>
  <article class="rounded-md border border-border px-gutter py-stack">
    <p class="font-sans text-sm font-medium text-text">
      {{ labelForSupportOrigin(message.origine) }}
      <span class="font-normal text-text-muted">
        · {{ formatActivationExpiry(message.date) || 'Date indisponible' }}
      </span>
    </p>
    <p
      v-if="normalizeSupportVisibility(message.visibilite) === 'interne'"
      class="mt-1 font-sans text-sm text-text-muted"
    >
      {{ labelForSupportVisibility(message.visibilite) }}
    </p>
    <p class="mt-stack whitespace-pre-wrap font-sans text-sm text-text">
      {{ message.contenu }}
    </p>
  </article>
</template>
