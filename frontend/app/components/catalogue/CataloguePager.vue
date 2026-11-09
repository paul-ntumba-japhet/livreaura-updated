<script setup lang="ts">
const props = defineProps<{
  page: number
  size: number
  total: number
}>()

const route = useRoute()
const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.size)))

function target(page: number) {
  const query = { ...route.query }
  if (page <= 1) {
    delete query.page
  } else {
    query.page = String(page)
  }
  return { path: route.path, query }
}
</script>

<template>
  <nav
    v-if="total > size"
    class="flex flex-wrap items-center gap-gutter"
    aria-label="Pagination"
  >
    <UiButton
      v-if="page > 1"
      variant="secondary"
      :to="target(page - 1)"
    >
      Page précédente
    </UiButton>
    <p class="font-sans text-sm text-text-muted tabular-nums">
      Page {{ page }} sur {{ lastPage }}
    </p>
    <UiButton
      v-if="page < lastPage"
      variant="secondary"
      :to="target(page + 1)"
    >
      Page suivante
    </UiButton>
  </nav>
</template>
