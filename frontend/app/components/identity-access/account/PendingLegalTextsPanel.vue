<script setup lang="ts">
import {
  consentLabelForLegalText,
  consultLabelForLegalText,
  isPublicLegalReference,
  labelForLegalTextType,
} from '../../../types/identity-access/registration'
import type { PendingLegalTextView } from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    texts: PendingLegalTextView[]
    loading?: boolean
    submittingId?: string | null
    disabled?: boolean
  }>(),
  {
    loading: false,
    submittingId: null,
    disabled: false,
  },
)

const emit = defineEmits<{
  accept: [textId: string]
}>()

const accepted = ref<Record<string, boolean>>({})
const localErrors = ref<Record<string, string>>({})

watch(
  () => props.texts.map(text => text.id).join(','),
  () => {
    const next: Record<string, boolean> = {}
    for (const text of props.texts) {
      next[text.id] = false
    }
    accepted.value = next
    localErrors.value = {}
  },
  { immediate: true },
)

function canConsult(text: PendingLegalTextView): boolean {
  return Boolean(text.contenu) || isPublicLegalReference(text.reference_immuable)
}

function canAccept(text: PendingLegalTextView): boolean {
  return Boolean(text.contenu?.trim())
}

function isBusy(): boolean {
  return props.disabled || props.loading || Boolean(props.submittingId)
}

function submit(text: PendingLegalTextView) {
  if (isBusy()) {
    return
  }
  if (!canAccept(text)) {
    localErrors.value = {
      ...localErrors.value,
      [text.id]: 'Le texte n’est pas consultable. L’acceptation reste bloquée.',
    }
    return
  }
  if (!accepted.value[text.id]) {
    localErrors.value = {
      ...localErrors.value,
      [text.id]: 'Cochez la case après avoir lu le texte.',
    }
    return
  }

  const next = { ...localErrors.value }
  delete next[text.id]
  localErrors.value = next
  emit('accept', text.id)
}

function clearError(textId: string) {
  if (!localErrors.value[textId]) {
    return
  }
  const next = { ...localErrors.value }
  delete next[textId]
  localErrors.value = next
}
</script>

<template>
  <section class="rounded-md border border-border bg-surface-raised px-page py-section">
    <h2 class="font-serif text-xl font-semibold text-text">
      Textes à relire
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Une nouvelle version s’applique à ce compte. Consultez le texte, puis enregistrez votre acceptation. L’affichage ne constitue pas une réponse.
    </p>

    <div v-if="loading" class="mt-stack" aria-busy="true">
      <Skeleton variant="form" />
    </div>

    <ul v-else class="mt-section flex flex-col gap-section">
      <li
        v-for="text in texts"
        :key="text.id"
        class="flex flex-col gap-stack"
      >
        <p class="font-sans text-sm text-text">
          {{ labelForLegalTextType(text.type) }}
          <span class="text-text-muted"> · version {{ text.version }}</span>
        </p>
        <ConsentControl
          :model-value="Boolean(accepted[text.id])"
          required
          :disabled="isBusy() || !canAccept(text)"
          :label="consentLabelForLegalText(text.type)"
          :consult-label="canConsult(text) ? consultLabelForLegalText(text.type) : undefined"
          :consult-title="labelForLegalTextType(text.type)"
          :consult-version="text.version"
          :consult-content="text.contenu"
          :consult-href="!text.contenu && isPublicLegalReference(text.reference_immuable) ? text.reference_immuable : undefined"
          :error="localErrors[text.id]"
          @update:model-value="accepted[text.id] = $event; clearError(text.id)"
        />
        <FormActions align="start">
          <UiButton
            :loading="submittingId === text.id"
            :disabled="isBusy() || !canAccept(text)"
            :disabled-reason="!canAccept(text) ? 'Le contenu de ce texte n’est pas consultable. L’acceptation reste bloquée.' : undefined"
            @click="submit(text)"
          >
            Enregistrer cette acceptation
          </UiButton>
        </FormActions>
      </li>
    </ul>
  </section>
</template>
