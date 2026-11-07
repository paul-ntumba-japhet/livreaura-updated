<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { UiIconName } from '../ui/icons'

export type SidebarItem = {
  id: string
  label: string
  to?: RouteLocationRaw
  current?: boolean
  disabled?: boolean
  disabledReason?: string
  icon?: UiIconName
  children?: SidebarItem[]
}

const props = withDefaults(
  defineProps<{
    label?: string
    items: SidebarItem[]
    compact?: boolean
  }>(),
  {
    label: 'Sections',
    compact: false,
  },
)

const emit = defineEmits<{
  navigate: []
}>()

const openGroups = ref<Record<string, boolean>>({})

function hasChildren(item: SidebarItem): boolean {
  return Boolean(item.children && item.children.length > 0)
}

function isGroupOpen(item: SidebarItem): boolean {
  if (openGroups.value[item.id] !== undefined) {
    return openGroups.value[item.id]
  }
  return Boolean(item.current || item.children?.some(child => child.current))
}

function toggleGroup(item: SidebarItem) {
  openGroups.value = {
    ...openGroups.value,
    [item.id]: !isGroupOpen(item),
  }
}

function itemClass(current?: boolean): string {
  if (props.compact) {
    return current ? 'account-nav-item is-current' : 'account-nav-item'
  }
  return current
    ? 'rounded-full bg-brand/20 font-semibold text-text'
    : 'rounded-full text-text-muted hover:bg-surface-muted hover:text-text'
}

function rowClass(): string {
  return props.compact
    ? 'min-h-10 gap-2 px-2.5 py-1'
    : 'min-h-11 gap-stack px-gutter py-2'
}
</script>

<template>
  <nav
    class="bg-surface-raised"
    :class="compact ? 'px-3 py-3' : 'border-b border-border px-page py-stack md:min-h-full md:border-b-0 md:border-r'"
    :aria-label="label"
  >
    <ul class="flex flex-col" :class="compact ? 'gap-0.5' : 'gap-1'">
      <li v-for="item in items" :key="item.id">
        <template v-if="hasChildren(item)">
          <button
            type="button"
            class="flex w-full cursor-pointer items-center text-left font-sans text-sm font-medium focus-ring"
            :class="[rowClass(), itemClass(item.current)]"
            :aria-expanded="isGroupOpen(item) ? 'true' : 'false'"
            :aria-controls="`sidebar-group-${item.id}`"
            @click="toggleGroup(item)"
          >
            <UiIcon
              v-if="item.icon"
              :name="item.icon"
              :size="compact ? 'sm' : 'md'"
              class="sidebar-icon"
              :class="item.current ? 'text-brand' : 'text-text-muted'"
            />
            <span class="min-w-0 flex-1">{{ item.label }}</span>
            <UiIcon
              name="chevron-down"
              size="sm"
              :stroke-width="2.25"
              class="shrink-0 text-text-muted transition-transform"
              :class="isGroupOpen(item) ? 'rotate-180' : ''"
            />
          </button>
          <ul
            v-show="isGroupOpen(item)"
            :id="`sidebar-group-${item.id}`"
            class="mt-0.5 flex flex-col gap-0.5"
          >
            <li v-for="child in item.children" :key="child.id">
              <NuxtLink
                v-if="child.to && !child.disabled"
                :to="child.to"
                class="flex items-center font-sans text-sm font-medium focus-ring"
                :class="[compact ? 'min-h-10 gap-2 py-1 pl-8 pr-2.5' : 'min-h-11 gap-stack py-2 pl-10 pr-gutter', itemClass(child.current)]"
                :aria-current="child.current ? 'page' : undefined"
                @click="emit('navigate')"
              >
                <UiIcon
                  v-if="child.icon"
                  :name="child.icon"
                  :size="compact ? 'sm' : 'md'"
                  :stroke-width="2.25"
                  :class="child.current ? 'text-brand' : 'text-text-muted'"
                />
                <span>{{ child.label }}</span>
              </NuxtLink>
            </li>
          </ul>
        </template>
        <NuxtLink
          v-else-if="item.to && !item.disabled"
          :to="item.to"
          class="flex items-center font-sans text-sm font-medium focus-ring"
          :class="[rowClass(), itemClass(item.current)]"
          :aria-current="item.current ? 'page' : undefined"
          @click="emit('navigate')"
        >
          <UiIcon
            v-if="item.icon"
            :name="item.icon"
            :size="compact ? 'sm' : 'md'"
            :stroke-width="2.25"
            :class="item.current ? 'text-brand' : 'text-text-muted'"
          />
          <span>{{ item.label }}</span>
        </NuxtLink>
        <div v-else class="flex flex-col gap-1">
          <span
            class="flex items-center font-sans text-sm font-medium text-text-muted"
            :class="[rowClass(), props.compact ? 'rounded-md' : 'rounded-full']"
            :aria-disabled="true"
          >
            <UiIcon
              v-if="item.icon"
              :name="item.icon"
              :size="compact ? 'sm' : 'md'"
              :stroke-width="2.25"
              class="text-text-muted"
            />
            <span>{{ item.label }}</span>
          </span>
          <p v-if="item.disabled && item.disabledReason" class="px-gutter pb-2 font-sans text-sm text-text-muted">
            {{ item.disabledReason }}
          </p>
        </div>
      </li>
    </ul>
  </nav>
</template>
