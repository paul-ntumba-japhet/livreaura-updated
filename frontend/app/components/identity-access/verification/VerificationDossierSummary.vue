<script setup lang="ts">
import type { VerificationDossier } from '../../../types/identity-access/verification'
import type { WorkspaceSection } from '../../../utils/identity-access/verification'
import {
  labelForDossierStatus,
  labelForDossierSubject,
  scrollToWorkspaceTarget,
  toneForDossierStatus,
} from '../../../utils/identity-access/verification'

defineProps<{
  dossier: VerificationDossier
  sections: WorkspaceSection[]
}>()

function onNavigate(event: Event, target: string) {
  event.preventDefault()
  scrollToWorkspaceTarget(target)
}
</script>

<template>
  <section class="rounded-md border border-border bg-surface-raised px-gutter py-gutter">
    <h2 class="font-sans text-sm font-semibold text-text">
      Résumé du dossier
    </h2>
    <p class="mt-1 font-sans text-sm text-text-muted">
      {{ dossier.numero }}
    </p>
    <div class="mt-stack flex flex-wrap items-center gap-2">
      <StatusBadge :label="labelForDossierStatus(dossier.statut)" :tone="toneForDossierStatus(dossier.statut)" />
      <StatusBadge :label="labelForDossierSubject(dossier.sujet)" tone="neutral" />
      <StatusBadge :label="`Version ${dossier.version}`" tone="neutral" />
    </div>

    <ol class="mt-gutter flex flex-col">
      <li
        v-for="(section, index) in sections"
        :key="section.id"
        class="border-t border-border first:border-t-0"
      >
        <a
          :href="`#${section.target}`"
          class="flex items-center gap-stack py-stack focus-ring rounded-sm"
          @click="onNavigate($event, section.target)"
        >
          <span
            class="flex size-7 shrink-0 items-center justify-center rounded-full font-sans text-xs font-medium"
            :class="section.complete
              ? 'bg-success text-text-inverse'
              : 'border border-border bg-surface-raised text-text-muted'"
            aria-hidden="true"
          >
            <UiIcon v-if="section.complete" name="check" size="sm" />
            <span v-else>{{ index + 1 }}</span>
          </span>
          <span class="min-w-0 flex-1 font-sans text-sm text-text">
            {{ section.label }}
          </span>
          <span class="font-sans text-sm text-text-muted">
            {{ section.done }}/{{ section.total }}
          </span>
          <UiIcon name="chevron-right" class="text-text-muted" />
        </a>
      </li>
    </ol>
  </section>
</template>
