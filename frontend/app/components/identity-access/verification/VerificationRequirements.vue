<script setup lang="ts">
import type { VerificationDocument, VerificationGap, VerificationRequirement } from '../../../types/identity-access/verification'
import {
  labelForGap,
  labelForRequirementCode,
  labelForRequirementSection,
  requirementNeedsDocument,
  targetForRequirement,
} from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    requirements: VerificationRequirement[]
    pieces: VerificationDocument[]
    gaps: VerificationGap[]
    navigable?: boolean
    showGapList?: boolean
  }>(),
  {
    navigable: false,
    showGapList: true,
  },
)

const emit = defineEmits<{
  navigate: [target: string]
}>()

function pieceFor(code: string): VerificationDocument | undefined {
  return props.pieces.find(piece => piece.type_doc === code)
}

function gapFor(code: string): VerificationGap | undefined {
  return props.gaps.find(gap => gap.code === code)
}

function onNavigate(event: Event, requirement: VerificationRequirement) {
  event.preventDefault()
  emit('navigate', targetForRequirement(requirement))
}
</script>

<template>
  <div class="flex flex-col gap-stack">
    <p v-if="requirements.length === 0" class="font-sans text-sm text-text-muted">
      Aucune exigence n’est affichable pour ce dossier.
    </p>
    <ul v-else class="grid gap-stack">
      <li
        v-for="requirement in requirements"
        :key="requirement.id"
      >
        <component
          :is="navigable ? 'a' : 'div'"
          :href="navigable ? `#${targetForRequirement(requirement)}` : undefined"
          class="rounded-md border border-border px-gutter py-stack"
          :class="navigable ? 'block focus-ring hover:bg-surface-muted' : ''"
          @click="navigable ? onNavigate($event, requirement) : undefined"
        >
          <div class="flex flex-wrap items-start justify-between gap-stack">
            <div class="min-w-0">
              <p class="font-sans text-sm font-medium text-text">
                {{ labelForRequirementCode(requirement.code) }}
              </p>
              <p class="mt-1 font-sans text-sm text-text-muted">
                {{ requirement.nature_preuve || labelForRequirementSection(requirement.volet) }}
                <template v-if="requirement.obligatoire"> · obligatoire</template>
                <template v-else> · facultatif</template>
              </p>
            </div>
            <span class="inline-flex items-center gap-2">
              <StatusBadge
                v-if="gapFor(requirement.code)"
                label="Manquante"
                tone="warning"
              />
              <StatusBadge
                v-else-if="requirementNeedsDocument(requirement.volet) && pieceFor(requirement.code)"
                label="Pièce déposée"
                tone="info"
              />
              <StatusBadge
                v-else-if="!gapFor(requirement.code)"
                label="Fournie"
                tone="info"
              />
              <UiIcon
                v-if="navigable"
                name="chevron-right"
                class="text-text-muted"
              />
            </span>
          </div>
        </component>
      </li>
    </ul>

    <ul v-if="showGapList && gaps.length > 0" class="list-disc space-y-1 pl-5 font-sans text-sm text-text">
      <li v-for="gap in gaps" :key="`${gap.code}-${gap.motif}`">
        {{ labelForGap(gap) }}
      </li>
    </ul>
  </div>
</template>
