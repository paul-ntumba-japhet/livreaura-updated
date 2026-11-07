<script setup lang="ts">
import type { VerificationDossierSummary } from '../../../types/identity-access/verification'
import { normalizeDossierStatus } from '../../../types/identity-access/verification'
import {
  labelForDossierStatus,
  labelForDossierSubject,
  labelForVerificationLevel,
  toneForDossierStatus,
} from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    dossier: VerificationDossierSummary
    selected?: boolean
    selectable?: boolean
    disabled?: boolean
  }>(),
  {
    selected: false,
    selectable: false,
    disabled: false,
  },
)

const emit = defineEmits<{
  select: [id: string]
}>()

const status = computed(() => normalizeDossierStatus(props.dossier.statut))
const subjectLabel = computed(() => labelForDossierSubject(props.dossier.sujet))

function onSelect() {
  if (!props.selectable || props.disabled) {
    return
  }
  emit('select', props.dossier.id)
}
</script>

<template>
  <article
    class="rounded-lg border-2 bg-surface-raised px-gutter py-stack"
    :class="{
      'border-brand shadow-sm': selected && !disabled,
      'border-border': !selected || disabled,
      'opacity-50': disabled,
    }"
  >
    <div class="flex items-start justify-between gap-gutter">
      <div class="min-w-0">
        <h3 class="font-serif text-lg font-semibold text-text">
          {{ dossier.numero || 'Dossier sans numéro' }}
        </h3>
        <p class="mt-1 font-sans text-sm text-text-muted">
          {{ labelForVerificationLevel(dossier.niveau) }}
          · {{ subjectLabel }}
          · version {{ dossier.version }}
        </p>
      </div>
      <StatusBadge :label="labelForDossierStatus(status)" :tone="toneForDossierStatus(status)" />
    </div>

    <p v-if="selectable" class="mt-stack">
      <UiButton
        :variant="selected || status !== 'draft' ? 'secondary' : 'primary'"
        :disabled="disabled || selected"
        :disabled-reason="selected ? 'Ce dossier est déjà ouvert ici.' : undefined"
        @click="onSelect"
      >
        {{ selected ? 'Dossier ouvert' : (status === 'draft' ? 'Continuer' : 'Consulter ce dossier') }}
      </UiButton>
    </p>
  </article>
</template>
