<script setup lang="ts">
import type { OrganizationRequirementDto } from '../../../types/identity-access/registration'
import {
  labelForRequirementCode,
  labelForRequirementSection,
  requirementKindLabel,
} from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    requirements: OrganizationRequirementDto[]
    title?: string
    hint?: string
    variant?: 'default' | 'compact'
  }>(),
  {
    title: 'Pièces exigées',
    hint: 'Le dépôt se fait dans le dossier de vérification. Aucune pièce n’est publique.',
    variant: 'default',
  },
)

const count = computed(() => props.requirements.length)
const fileCount = computed(() => props.requirements.filter(item => requirementKindLabel(item.volet) === 'fichier').length)
const compact = computed(() => props.variant === 'compact')
</script>

<template>
  <div class="flex flex-col gap-stack">
    <div v-if="!compact">
      <h3 class="font-serif text-lg font-semibold text-text">
        {{ title }}
      </h3>
      <p class="mt-1 font-sans text-sm text-text-muted">
        <template v-if="count > 0">
          {{ fileCount }} fichier{{ fileCount > 1 ? 's' : '' }}
          <template v-if="count > fileCount"> et {{ count - fileCount }} formulaire{{ count - fileCount > 1 ? 's' : '' }}</template>
          obligatoire{{ count > 1 ? 's' : '' }}. {{ hint }}
        </template>
        <template v-else>Aucune pièce n’est encore renseignée pour ce parcours.</template>
      </p>
    </div>
    <p
      v-else-if="count === 0"
      class="font-sans text-sm text-text-muted"
    >
      Aucune pièce n’est encore renseignée pour ce parcours.
    </p>
    <ol v-if="count > 0" class="grid" :class="compact ? 'gap-gutter' : 'gap-stack'">
      <li
        v-for="(requirement, index) in requirements"
        :key="requirement.code"
        :class="compact
          ? 'flex items-start gap-stack'
          : 'rounded-md border border-border px-gutter py-stack'"
      >
        <span
          v-if="compact"
          class="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-brand font-sans text-xs font-medium text-text-on-brand"
          aria-hidden="true"
        >
          {{ index + 1 }}
        </span>
        <div class="min-w-0 flex-1">
          <p class="font-sans text-sm font-medium text-text">
            <template v-if="!compact">{{ index + 1 }}. </template>
            {{ labelForRequirementCode(requirement.code) }}
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            {{ requirement.nature_preuve || labelForRequirementSection(requirement.volet) }}
            <template v-if="!compact">
              · {{ requirementKindLabel(requirement.volet) }}
              <template v-if="requirement.obligatoire"> · obligatoire</template>
            </template>
          </p>
        </div>
        <span
          v-if="compact"
          class="inline-flex shrink-0 items-center gap-1 rounded-md border border-border bg-surface-raised px-2 py-1 font-sans text-xs text-text-muted"
        >
          <UiIcon name="file-text" />
          {{ requirementKindLabel(requirement.volet) === 'fichier' ? 'Fichier' : 'Formulaire' }}
        </span>
      </li>
    </ol>
  </div>
</template>
