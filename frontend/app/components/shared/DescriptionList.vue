<script setup lang="ts">
withDefaults(
  defineProps<{
    items: Array<{
      term: string
      value: string
      missing?: boolean
      badge?: {
        label: string
        tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
      }
    }>
    layout?: 'grid' | 'rows' | 'lined'
  }>(),
  {
    layout: 'grid',
  },
)
</script>

<template>
  <dl
    :class="{
      'grid gap-stack sm:grid-cols-2': layout === 'grid',
      'flex flex-col gap-3': layout === 'rows',
      'divide-y divide-border': layout === 'lined',
    }"
  >
    <div
      v-for="item in items"
      :key="item.term"
      class="min-w-0"
      :class="{
        'grid grid-cols-[8.5rem_minmax(0,1fr)] items-baseline gap-gutter': layout === 'rows',
        'grid grid-cols-[10rem_minmax(0,1fr)] items-center gap-gutter py-3 first:pt-0 last:pb-0': layout === 'lined',
      }"
    >
      <dt class="font-sans text-sm text-text-muted">
        {{ item.term }}
      </dt>
      <dd
        class="flex min-w-0 flex-wrap items-center gap-2 font-sans text-sm"
        :class="{
          'text-text-muted': item.missing,
          'text-text': !item.missing,
          'mt-1': layout === 'grid',
        }"
      >
        <span v-if="item.value">{{ item.value }}</span>
        <StatusBadge
          v-if="item.badge"
          :label="item.badge.label"
          :tone="item.badge.tone || 'neutral'"
        />
      </dd>
    </div>
  </dl>
</template>
