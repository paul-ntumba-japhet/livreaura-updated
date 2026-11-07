<script setup lang="ts">
import type { BackofficeControl } from '../../../types/identity-access/backoffice'
import { normalizeControlResult } from '../../../types/identity-access/backoffice'
import {
  labelForControlResult,
  toneForControlResult,
} from '../../../utils/identity-access/backoffice'
import { labelForRequirementCode, labelForRequirementSection } from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    controls: BackofficeControl[]
    canRecord: boolean
    recordingId?: string | null
    disabledReason?: string
  }>(),
  {
    recordingId: null,
  },
)

const emit = defineEmits<{
  record: [payload: {
    controle_id: string
    resultat: 'passed' | 'failed' | 'not_applicable'
    observation?: string
    justification_non_applicable?: string
  }]
}>()

const drafts = ref<Record<string, { resultat: string, observation: string, justification: string }>>({})

function draftFor(control: BackofficeControl) {
  const current = drafts.value[control.id]
  if (current) {
    return current
  }
  return {
    resultat: control.resultat === 'pending' ? '' : control.resultat,
    observation: control.observation || '',
    justification: control.justification_non_applicable || '',
  }
}

function updateDraft(id: string, patch: Partial<{ resultat: string, observation: string, justification: string }>) {
  const control = props.controls.find(item => item.id === id)
  if (!control) {
    return
  }
  drafts.value = {
    ...drafts.value,
    [id]: { ...draftFor(control), ...patch },
  }
}

function submit(control: BackofficeControl) {
  const draft = draftFor(control)
  if (draft.resultat !== 'passed' && draft.resultat !== 'failed' && draft.resultat !== 'not_applicable') {
    return
  }
  emit('record', {
    controle_id: control.id,
    resultat: draft.resultat,
    observation: draft.observation.trim() || undefined,
    justification_non_applicable: draft.justification.trim() || undefined,
  })
}

const resultOptions = [
  { value: 'passed', label: 'Satisfait' },
  { value: 'failed', label: 'Non satisfait' },
  { value: 'not_applicable', label: 'Non applicable' },
]
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-xl font-semibold text-text">
      Contrôles
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Le téléversement, la recevabilité technique, le contrôle humain et l’habilitation restent distincts.
    </p>
    <ul class="mt-section flex flex-col gap-stack">
      <li
        v-for="control in controls"
        :key="control.id"
        class="rounded-md border border-border px-gutter py-stack"
      >
        <div class="flex flex-wrap items-start justify-between gap-gutter">
          <div>
            <h3 class="font-sans text-sm font-medium text-text">
              {{ control.exigence ? labelForRequirementCode(control.exigence.code) : 'Exigence indisponible' }}
            </h3>
            <p class="mt-1 font-sans text-sm text-text-muted">
              {{ control.exigence ? labelForRequirementSection(control.exigence.volet) : 'Volet indisponible' }}
              <template v-if="control.exigence?.obligatoire"> · obligatoire</template>
            </p>
          </div>
          <StatusBadge
            :label="labelForControlResult(control.resultat)"
            :tone="toneForControlResult(control.resultat)"
          />
        </div>
        <p v-if="normalizeControlResult(control.resultat) === 'unknown'" class="mt-stack font-sans text-sm text-text-muted">
          Résultat indisponible. La décision dépendante reste bloquée.
        </p>
        <div v-if="canRecord" class="mt-stack grid gap-stack">
          <FormField :label="`Résultat pour ${control.exigence ? labelForRequirementCode(control.exigence.code) : 'ce contrôle'}`" required>
            <template #default="{ id, describedBy, invalid }">
              <SelectInput
                :id="id"
                :model-value="draftFor(control).resultat"
                :options="resultOptions.map(option => ({
                  ...option,
                  disabled: option.value === 'not_applicable' && !control.exigence?.autorise_non_applicable,
                }))"
                placeholder="Consigner un résultat"
                :invalid="invalid"
                :aria-describedby="describedBy"
                :disabled="recordingId === control.id"
                @update:model-value="updateDraft(control.id, { resultat: $event })"
              />
            </template>
          </FormField>
          <FormField
            v-if="draftFor(control).resultat === 'failed' || draftFor(control).resultat === 'passed'"
            label="Observation"
            :required="draftFor(control).resultat === 'failed'"
          >
            <template #default="{ id, describedBy, invalid }">
              <TextArea
                :id="id"
                :model-value="draftFor(control).observation"
                :invalid="invalid"
                :aria-describedby="describedBy"
                :disabled="recordingId === control.id"
                @update:model-value="updateDraft(control.id, { observation: $event })"
              />
            </template>
          </FormField>
          <FormField
            v-if="draftFor(control).resultat === 'not_applicable'"
            label="Justification de non-applicabilité"
            required
          >
            <template #default="{ id, describedBy, invalid }">
              <TextArea
                :id="id"
                :model-value="draftFor(control).justification"
                :invalid="invalid"
                :aria-describedby="describedBy"
                :disabled="recordingId === control.id"
                @update:model-value="updateDraft(control.id, { justification: $event })"
              />
            </template>
          </FormField>
          <FormActions align="start">
            <UiButton
              variant="secondary"
              :loading="recordingId === control.id"
              :disabled="recordingId === control.id || !draftFor(control).resultat"
              :disabled-reason="disabledReason"
              @click="submit(control)"
            >
              Enregistrer ce contrôle
            </UiButton>
          </FormActions>
        </div>
      </li>
    </ul>
  </section>
</template>
