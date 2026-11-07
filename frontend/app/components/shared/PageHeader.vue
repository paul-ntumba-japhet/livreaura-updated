<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    description?: string
    context?: string
    loading?: boolean
    align?: 'start' | 'center'
  }>(),
  {
    align: 'start',
  },
)
</script>

<template>
  <header
    class="flex flex-col gap-stack"
    :class="align === 'center' ? 'items-center text-center' : 'sm:flex-row sm:items-center sm:justify-between'"
  >
    <div
      class="min-w-0 space-y-1"
      :class="align === 'center' ? 'w-full' : ''"
    >
      <p v-if="context" class="text-sm font-sans text-text-muted">
        {{ context }}
      </p>
      <div v-if="loading" class="space-y-2" aria-busy="true">
        <Skeleton class="h-8 w-48 max-w-full" :class="align === 'center' ? 'mx-auto' : ''" />
        <Skeleton
          v-if="description !== undefined"
          class="h-4 w-80 max-w-full"
          :class="align === 'center' ? 'mx-auto' : ''"
        />
        <span class="sr-only">Chargement du titre</span>
      </div>
      <template v-else>
        <h1 class="font-geist text-3xl font-semibold text-text">
          {{ title }}
        </h1>
        <p
          v-if="description"
          class="font-sans text-base text-text-muted"
          :class="align === 'center' ? 'mx-auto max-w-md' : 'max-w-2xl'"
        >
          {{ description }}
        </p>
      </template>
    </div>
    <div
      v-if="$slots.actions"
      class="shrink-0"
    >
      <slot name="actions" />
    </div>
  </header>
</template>
