<script setup lang="ts">
import type { UiIconName } from '../ui/icons'

withDefaults(
  defineProps<{
    title: string
    value?: string
    badgeLabel?: string
    badgeTone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
    hint?: string
    icon: UiIconName
    valueTone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
  }>(),
  {
    value: '',
    badgeLabel: '',
    hint: '',
    badgeTone: 'neutral',
    valueTone: 'neutral',
  },
)
</script>

<template>
  <article class="account-stat flex flex-col rounded-xl border border-border bg-white">
    <h2 class="account-stat-kicker flex items-center gap-2 font-sans">
      <UiIcon
        :name="icon"
        class="account-stat-icon shrink-0"
      />
      <span class="min-w-0">{{ title }}</span>
    </h2>
    <p
      v-if="value"
      class="account-stat-value font-sans"
      :class="valueTone !== 'neutral' ? `is-${valueTone}` : undefined"
    >
      {{ value }}
    </p>
    <p v-else-if="badgeLabel" class="account-stat-badge">
      <StatusBadge class="account-badge" :label="badgeLabel" :tone="badgeTone" />
    </p>
    <p v-if="hint" class="account-stat-hint font-sans">
      {{ hint }}
    </p>
  </article>
</template>
