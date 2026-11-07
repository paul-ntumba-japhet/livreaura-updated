<script setup lang="ts">
import type { VerificationGap } from '../../../types/identity-access/verification'
import { labelForGap } from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    complete: boolean
    gaps: VerificationGap[]
    submitting?: boolean
    disabled?: boolean
    disabledReason?: string
    confirmed?: boolean
    hideHeading?: boolean
    embedded?: boolean
  }>(),
  {
    submitting: false,
    disabled: false,
    confirmed: false,
    hideHeading: false,
    embedded: false,
  },
)

const emit = defineEmits<{
  'update:confirmed': [value: boolean]
  submit: []
}>()

const canSubmit = computed(() => props.complete && props.confirmed && !props.disabled && !props.submitting)
const confirmId = useId()
</script>

<template>
  <section
    :class="embedded ? '' : 'rounded-xl border border-border bg-surface-raised px-page py-section'"
  >
    <h2
      v-if="!hideHeading"
      class="font-serif text-xl font-semibold text-text"
    >
      Soumettre le dossier
    </h2>
    <p class="font-sans text-sm text-text-muted" :class="hideHeading ? '' : 'mt-stack'">
      La soumission fige les pièces, les exigences et les versions fiscales ou de reversement. Aucune pièce n’est publique.
    </p>

    <InlineAlert
      v-if="!complete"
      class="mt-stack"
      tone="warning"
      title="Dossier incomplet"
    >
      Complétez les éléments manquants avant de soumettre.
    </InlineAlert>
    <ul v-if="gaps.length > 0" class="mt-stack list-disc space-y-1 pl-5 font-sans text-sm text-text">
      <li v-for="gap in gaps" :key="`${gap.code}-${gap.motif}`">
        {{ labelForGap(gap) }}
      </li>
    </ul>

    <div class="mt-stack flex items-start gap-gutter">
      <Checkbox
        :id="confirmId"
        class="mt-0.5"
        :model-value="confirmed"
        :disabled="disabled || submitting || !complete"
        @update:model-value="emit('update:confirmed', $event)"
      />
      <label :for="confirmId" class="font-sans text-sm text-text">
        Je confirme que les preuves seront figées après soumission.
      </label>
    </div>

    <FormActions class="mt-stack" align="start">
      <UiButton
        :loading="submitting"
        :disabled="!canSubmit"
        :disabled-reason="disabledReason || (!complete ? 'Le dossier n’est pas encore complet.' : (!confirmed ? 'Confirmez le gel des preuves avant de soumettre.' : undefined))"
        @click="emit('submit')"
      >
        {{ submitting ? 'Soumission en cours… Ne fermez pas cette page.' : 'Soumettre le dossier' }}
      </UiButton>
    </FormActions>
  </section>
</template>
