<script setup lang="ts">
import type { SupportEvidence } from '../../../types/identity-access/support'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'
import { labelForSupportEvidenceType } from '../../../utils/identity-access/support'
import { normalizeSupportVisibility } from '../../../types/identity-access/support'

const evidenceType = defineModel<string>('evidenceType', { default: 'identite' })
const internal = defineModel<boolean>('internal', { default: false })

const props = withDefaults(
  defineProps<{
    pieces?: SupportEvidence[]
    canDeposit?: boolean
    canInternal?: boolean
    submitting?: boolean
    downloadingId?: string | null
    selectedName?: string
    fieldError?: string
    disabledReason?: string
  }>(),
  {
    pieces: () => [],
    canDeposit: false,
    canInternal: false,
    submitting: false,
    downloadingId: null,
  },
)

const emit = defineEmits<{
  select: [file: File]
  deposit: []
  download: [id: string]
}>()

const typeOptions = [
  { value: 'identite', label: 'Pièce d’identité' },
  { value: 'mandat', label: 'Mandat' },
  { value: 'complement', label: 'Complément' },
]

const blockedReason = computed(() => {
  if (!props.canDeposit) {
    return props.disabledReason || 'Le dépôt d’une pièce n’est pas disponible pour cette session.'
  }
  return ''
})
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Pièces
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Les fichiers restent privés. Le téléchargement est contrôlé à chaque usage. Une pièce interne n’est pas visible du demandeur.
    </p>

    <ul
      v-if="pieces.length > 0"
      class="mt-stack grid gap-stack"
    >
      <li
        v-for="piece in pieces"
        :key="piece.id"
        class="flex flex-wrap items-center justify-between gap-stack rounded-md border border-border px-gutter py-stack"
      >
        <div class="min-w-0">
          <p class="font-sans text-sm font-medium text-text">
            {{ labelForSupportEvidenceType(piece.type) }}
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Déposée {{ formatActivationExpiry(piece.depose_a) || 'à une date non communiquée' }}
            <template v-if="normalizeSupportVisibility(piece.acces) === 'interne'">
              · note interne
            </template>
          </p>
        </div>
        <UiButton
          variant="secondary"
          :loading="downloadingId === piece.id"
          :disabled="downloadingId !== null"
          @click="emit('download', piece.id)"
        >
          Télécharger cette pièce
        </UiButton>
      </li>
    </ul>
    <EmptyState
      v-else
      class="mt-stack"
      title="Aucune pièce"
      message="Aucune pièce n’est encore visible pour cette demande."
    />

    <div
      v-if="canDeposit"
      class="mt-stack grid gap-stack"
    >
      <FormField label="Type de pièce" required>
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="evidenceType"
            :options="typeOptions"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="submitting || Boolean(blockedReason)"
          />
        </template>
      </FormField>
      <label
        v-if="canInternal"
        class="flex items-center gap-gutter font-sans text-sm text-text"
      >
        <Checkbox
          v-model="internal"
          :disabled="submitting"
        />
        Conserver cette pièce en note interne
      </label>
      <FileDropzone
        label="Fichier"
        hint="PDF, JPEG ou PNG, 8 Mo au plus."
        :disabled="submitting || Boolean(blockedReason)"
        :disabled-reason="blockedReason"
        :loading="submitting"
        :error="fieldError"
        :selected-name="selectedName"
        @select="emit('select', $event)"
      />
      <FormActions align="start">
        <UiButton
          :disabled="!selectedName || submitting || Boolean(blockedReason)"
          :disabled-reason="blockedReason || (!selectedName ? 'Choisissez un fichier.' : undefined)"
          :loading="submitting"
          @click="emit('deposit')"
        >
          Déposer cette pièce
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
