<script setup lang="ts">
export type OverviewAlert = {
  id: string
  tone: 'danger' | 'warning' | 'info'
  title: string
  message: string
  to: string
  action: string
}

defineProps<{
  alerts: OverviewAlert[]
  loading?: boolean
}>()
</script>

<template>
  <SectionCard title="Alertes à suivre">
    <div v-if="loading" class="flex flex-col gap-stack" aria-busy="true">
      <Skeleton v-for="index in 3" :key="index" class="h-20 w-full" />
    </div>

    <EmptyState
      v-else-if="alerts.length === 0"
      title="Aucune alerte à suivre"
      message="Les transmissions en échec, les échéances dépassées et les conflits d’intérêt apparaissent ici lorsqu’ils existent."
    />

    <ul v-else class="divide-y divide-border">
      <li
        v-for="alert in alerts"
        :key="alert.id"
        class="py-gutter first:pt-0 last:pb-0"
      >
        <p class="flex items-start gap-stack">
          <UiIcon
            :name="alert.tone === 'danger' ? 'error' : (alert.tone === 'warning' ? 'warning' : 'info')"
            size="md"
            class="mt-0.5"
            :class="{
              'text-danger': alert.tone === 'danger',
              'text-warning': alert.tone === 'warning',
              'text-info': alert.tone === 'info',
            }"
          />
          <span class="min-w-0">
            <span class="block font-sans text-sm font-semibold text-text">
              {{ alert.title }}
            </span>
            <span class="mt-1 block font-sans text-sm text-text-muted">
              {{ alert.message }}
            </span>
            <UiLink
              plain
              :to="alert.to"
              class="mt-2"
            >
              {{ alert.action }}
              <UiIcon name="arrow-right" />
            </UiLink>
          </span>
        </p>
      </li>
    </ul>
  </SectionCard>
</template>
