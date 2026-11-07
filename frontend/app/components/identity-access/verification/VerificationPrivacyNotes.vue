<script setup lang="ts">
withDefaults(
  defineProps<{
    compact?: boolean
  }>(),
  {
    compact: false,
  },
)
</script>

<template>
  <div>
    <h3
      class="font-sans font-semibold text-text"
      :class="compact ? 'mb-gutter text-sm' : 'mb-section text-base'"
    >
      Vos données sont protégées
    </h3>
    <ul class="flex flex-col" :class="compact ? 'gap-gutter' : 'gap-section'">
      <li class="flex items-start gap-stack">
        <span
          class="inline-flex shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-muted"
          :class="compact ? 'size-9' : 'size-11'"
          aria-hidden="true"
        >
          <UiIcon name="shield-check" :size="compact ? 'sm' : 'md'" />
        </span>
        <div class="min-w-0">
          <p class="font-sans text-sm font-semibold text-text">
            Pièces privées
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Toutes les pièces que vous déposez sont strictement privées et non publiques.
          </p>
        </div>
      </li>
      <li class="flex items-start gap-stack">
        <span
          class="inline-flex shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-muted"
          :class="compact ? 'size-9' : 'size-11'"
          aria-hidden="true"
        >
          <UiIcon name="file-text" :size="compact ? 'sm' : 'md'" />
        </span>
        <div class="min-w-0">
          <p class="font-sans text-sm font-semibold text-text">
            Brouillon modifiable
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Vous pouvez compléter votre dossier en plusieurs fois. Un dossier déjà en cours peut être rouvert et modifié.
          </p>
        </div>
      </li>
      <li class="flex items-start gap-stack">
        <span
          class="inline-flex shrink-0 items-center justify-center rounded-full bg-surface-muted text-text-muted"
          :class="compact ? 'size-9' : 'size-11'"
          aria-hidden="true"
        >
          <UiIcon name="clock" :size="compact ? 'sm' : 'md'" />
        </span>
        <div class="min-w-0">
          <p class="font-sans text-sm font-semibold text-text">
            Une décision précédente n’est jamais modifiée
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Les décisions prises sur un dossier antérieur demeurent inchangées.
          </p>
        </div>
      </li>
    </ul>
  </div>
</template>
