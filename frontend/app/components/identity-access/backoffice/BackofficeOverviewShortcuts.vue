<script setup lang="ts">
import type { UiIconName } from '../../ui/icons'

export type OverviewShortcut = {
  id: string
  label: string
  to: string
  icon: UiIconName
}

defineProps<{
  items: OverviewShortcut[]
}>()
</script>

<template>
  <SectionCard title="Accès rapides">
    <EmptyState
      v-if="items.length === 0"
      title="Aucun accès disponible"
      message="Les raccourcis correspondent aux attributions internes de ce compte."
    />
    <ul v-else class="flex flex-col">
      <li
        v-for="item in items"
        :key="item.id"
        class="border-b border-border last:border-b-0"
      >
        <NuxtLink
          :to="item.to"
          class="flex min-h-11 items-center gap-stack py-stack font-sans text-sm font-medium text-text focus-ring"
        >
          <span
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-md bg-surface-muted text-brand"
            aria-hidden="true"
          >
            <UiIcon :name="item.icon" size="md" />
          </span>
          <span class="min-w-0 flex-1">{{ item.label }}</span>
          <UiIcon name="chevron-right" class="text-text-muted" />
        </NuxtLink>
      </li>
    </ul>
  </SectionCard>
</template>
