<script setup lang="ts">
import type { VerificationSubjectOption } from '../../../types/identity-access/verification'
import type { StepItem } from '../../shared/Stepper.vue'

withDefaults(
  defineProps<{
    steps: StepItem[]
    options: VerificationSubjectOption[]
    modelValue?: string
    canDeposit?: boolean
    opening?: boolean
    mutationBusy?: boolean
    selectedDisabledReason?: string
    canSubmitOpen?: boolean
    openLabel?: string
  }>(),
  {
    canDeposit: false,
    opening: false,
    mutationBusy: false,
    canSubmitOpen: false,
    openLabel: 'Ouvrir un dossier',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  open: []
}>()
</script>

<template>
  <div class="grid lg:grid-cols-12">
    <section class="dossier-create lg:col-span-8">
      <header>
        <h2>Créer un dossier de vérification</h2>
        <p>
          Choisissez le sujet, préparez vos pièces, puis soumettez votre dossier.
          Un dossier déjà en cours pour le même sujet et le même niveau est rouvert.
        </p>
      </header>

      <Stepper
        compact
        layout="inline"
        label="Progression du dossier"
        :steps="steps"
      />

      <VerificationSubjectSelector
        :model-value="modelValue"
        :options="options"
        :disabled="opening || !canDeposit"
        @update:model-value="emit('update:modelValue', $event)"
      />

      <p v-if="!canDeposit" class="font-sans text-sm text-text-muted">
        Cette action n’est disponible qu’après activation du compte, avec un accès normal.
      </p>

      <slot name="notices" />

      <UiButton
        v-if="canDeposit"
        class="dossier-create-submit"
        size="46"
        block
        :loading="opening"
        :disabled="!canSubmitOpen || mutationBusy"
        :disabled-reason="selectedDisabledReason || (!modelValue ? 'Choisissez un sujet.' : undefined)"
        @click="emit('open')"
      >
        {{ openLabel }}
        <UiIcon name="arrow-right" />
      </UiButton>
    </section>
  </div>
</template>
