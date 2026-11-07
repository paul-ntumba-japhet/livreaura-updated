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
  <section class="rounded-md border border-border bg-surface-raised">
    <div class="grid lg:grid-cols-12">
      <div class="px-gutter py-gutter lg:col-span-8">
        <p class="inline-flex items-center gap-1.5 font-sans text-sm font-medium text-brand">
          <UiIcon name="folder" />
          Nouveau dossier
        </p>
        <h2 class="mt-stack font-serif text-2xl font-semibold text-text">
          Créer un dossier de vérification
        </h2>
        <p class="mt-2 font-sans text-sm text-text-muted">
          Choisissez le sujet, préparez vos pièces, puis soumettez votre dossier.
          Un dossier déjà en cours pour le même sujet et le même niveau est rouvert.
        </p>

        <Stepper
          class="mt-gutter"
          layout="inline"
          label="Progression du dossier"
          :steps="steps"
        />

        <div class="mt-gutter">
          <VerificationSubjectSelector
            :model-value="modelValue"
            :options="options"
            :disabled="opening || !canDeposit"
            @update:model-value="emit('update:modelValue', $event)"
          />
        </div>

        <p v-if="!canDeposit" class="mt-stack font-sans text-sm text-text-muted">
          Cette action n’est disponible qu’après activation du compte, avec un accès normal.
        </p>

        <slot name="notices" />

        <p v-if="canDeposit" class="mt-gutter">
          <UiButton
            :loading="opening"
            :disabled="!canSubmitOpen || mutationBusy"
            :disabled-reason="selectedDisabledReason || (!modelValue ? 'Choisissez un sujet.' : undefined)"
            @click="emit('open')"
          >
            {{ openLabel }}
            <UiIcon name="arrow-right" />
          </UiButton>
        </p>
      </div>

      <aside class="border-t border-border px-gutter py-gutter lg:col-span-4 lg:border-l lg:border-t-0">
        <VerificationPrivacyNotes />
      </aside>
    </div>
  </section>
</template>
