<script setup lang="ts">
withDefaults(
  defineProps<{
    displayLabel: string
    initials?: string
    accountCurrent?: boolean
    backofficeCurrent?: boolean
    canAccessBackoffice?: boolean
    loggingOut?: boolean
  }>(),
  {
    initials: '',
    accountCurrent: false,
    backofficeCurrent: false,
    canAccessBackoffice: false,
    loggingOut: false,
  },
)

const emit = defineEmits<{
  logout: []
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

function onDocumentClick(event: MouseEvent) {
  if (!root.value?.contains(event.target as Node)) {
    close()
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    close()
  }
}

watch(open, (isOpen) => {
  if (!import.meta.client) {
    return
  }
  if (isOpen) {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onKeydown)
    return
  }
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  if (!import.meta.client) {
    return
  }
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="relative flex items-center gap-2">
    <UiLink
      v-if="canAccessBackoffice"
      to="/administration"
      :current="backofficeCurrent"
      class="hidden lg:inline"
    >
      Administration
    </UiLink>
    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-full py-1 pr-2 pl-1 focus-ring"
      :aria-expanded="open"
      aria-haspopup="menu"
      @click.stop="toggle"
    >
      <span
        v-if="initials"
        class="account-avatar inline-flex size-9 items-center justify-center rounded-full bg-surface-muted font-sans text-sm font-medium text-text"
        aria-hidden="true"
      >
        {{ initials }}
      </span>
      <span class="account-label hidden max-w-40 truncate font-sans text-sm text-text sm:inline">
        {{ displayLabel }}
      </span>
      <UiIcon name="chevron-down" class="text-text-muted" />
    </button>
    <div
      v-if="open"
      class="absolute right-0 top-full z-30 mt-2 min-w-52 rounded-xl border border-border bg-surface-raised py-1 shadow-md"
      role="menu"
    >
      <NuxtLink
        to="/compte"
        class="flex min-h-11 items-center px-gutter font-sans text-sm text-text hover:bg-surface-muted focus-ring"
        role="menuitem"
        :aria-current="accountCurrent ? 'page' : undefined"
        @click="close"
      >
        Mon compte
      </NuxtLink>
      <NuxtLink
        v-if="canAccessBackoffice"
        to="/administration"
        class="flex min-h-11 items-center px-gutter font-sans text-sm text-text hover:bg-surface-muted focus-ring lg:hidden"
        role="menuitem"
        :aria-current="backofficeCurrent ? 'page' : undefined"
        @click="close"
      >
        Administration
      </NuxtLink>
      <button
        type="button"
        class="flex min-h-11 w-full items-center gap-2 px-gutter text-left font-sans text-sm text-text hover:bg-surface-muted focus-ring"
        role="menuitem"
        :disabled="loggingOut"
        @click="emit('logout')"
      >
        <UiIcon v-if="loggingOut" name="loader" class="animate-spin" />
        Se déconnecter
      </button>
    </div>
  </div>
</template>
