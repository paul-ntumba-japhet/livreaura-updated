<script setup lang="ts">
import type { VerificationDocument } from '../../../types/identity-access/verification'
import { labelForRequirementCode } from '../../../utils/identity-access/verification'

withDefaults(
  defineProps<{
    piece: VerificationDocument
    downloading?: boolean
    disabled?: boolean
  }>(),
  {
    downloading: false,
    disabled: false,
  },
)

const emit = defineEmits<{
  download: [id: string]
}>()
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-stack rounded-md border border-border px-gutter py-stack">
    <div class="min-w-0">
      <p class="font-sans text-sm font-medium text-text">
        {{ labelForRequirementCode(piece.type_doc) }}
      </p>
      <p class="mt-1 font-sans text-sm text-text-muted">
        <template v-if="piece.depose_a">
          Déposée
          <template v-if="piece.statut_verification === 'pending'"> · en attente de contrôle</template>
        </template>
        <template v-else>
          Aucun fichier n’a été déposé.
        </template>
      </p>
    </div>
    <UiButton
      v-if="piece.depose_a"
      variant="secondary"
      :loading="downloading"
      :disabled="disabled || downloading"
      @click="emit('download', piece.id)"
    >
      Télécharger cette pièce
    </UiButton>
  </div>
</template>
