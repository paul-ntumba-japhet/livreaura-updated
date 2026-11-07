<script setup lang="ts">
import VerificationDossierPictogram from '../verification/VerificationDossierPictogram.vue'

defineProps<{
  canOpen?: boolean
  disabledReason?: string
}>()
</script>

<template>
  <SectionCard
    id="verification"
    icon="shield-check"
    title="Vérification"
  >
    <div class="flex items-center justify-between gap-gutter">
      <div class="min-w-0">
        <p class="font-sans text-sm text-text-muted">
          Constituez un dossier, déposez des pièces privées, puis soumettez-le. La soumission fige les preuves.
        </p>
        <p class="mt-gutter">
          <UiButton
            pill
            :disabled="!canOpen"
            :disabled-reason="disabledReason"
            to="/compte/verification"
          >
            Ouvrir un dossier de vérification
            <UiIcon name="arrow-right" />
          </UiButton>
        </p>
      </div>
      <VerificationDossierPictogram />
    </div>
  </SectionCard>
</template>
