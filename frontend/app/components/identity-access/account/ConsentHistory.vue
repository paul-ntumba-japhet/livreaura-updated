<script setup lang="ts">
import type { AccountConsent } from '../../../types/identity-access/account'
import {
  consentTitle,
  isOptionalConsent,
  isWithdrawnConsent,
  labelForConsentNature,
  toneForConsentNature,
} from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    consents: AccountConsent[]
    submittingId?: string | null
    disabled?: boolean
  }>(),
  {
    submittingId: null,
    disabled: false,
  },
)

const emit = defineEmits<{
  withdraw: [texteVersionId: string]
  reaccept: [texteVersionId: string]
}>()

const confirmWithdrawId = ref<string | null>(null)

const locked = computed(() => props.disabled || Boolean(props.submittingId))

function isBusy(texteVersionId: string): boolean {
  return locked.value || (Boolean(props.submittingId) && props.submittingId !== texteVersionId)
}

function requestWithdraw(texteVersionId: string) {
  confirmWithdrawId.value = texteVersionId
}

function cancelWithdraw() {
  confirmWithdrawId.value = null
}

function confirmWithdraw(texteVersionId: string) {
  if (isBusy(texteVersionId)) {
    return
  }
  emit('withdraw', texteVersionId)
  confirmWithdrawId.value = null
}

function reaccept(texteVersionId: string) {
  if (isBusy(texteVersionId)) {
    return
  }
  emit('reaccept', texteVersionId)
}
</script>

<template>
  <SectionCard
    id="consentements"
    icon="file-check"
    title="Consentements"
  >
    <ul v-if="consents.length > 0" class="flex flex-col">
      <li
        v-for="consent in consents"
        :key="consent.id"
        class="flex flex-col gap-2 border-b border-border py-3 first:pt-0 last:border-b-0 last:pb-0"
      >
        <div class="flex flex-wrap items-center justify-between gap-stack">
          <p class="min-w-0 font-sans text-sm text-text">
            {{ consentTitle(consent) }}
          </p>
          <StatusBadge
            :label="labelForConsentNature(consent.nature, isWithdrawnConsent(consent))"
            :tone="toneForConsentNature(consent)"
          />
        </div>
        <FormActions v-if="isOptionalConsent(consent)" align="start">
          <template v-if="isWithdrawnConsent(consent)">
            <UiButton
              variant="secondary"
              :loading="submittingId === consent.texte_version_id"
              :disabled="isBusy(consent.texte_version_id)"
              @click="reaccept(consent.texte_version_id)"
            >
              Accepter à nouveau
            </UiButton>
          </template>
          <template v-else-if="confirmWithdrawId === consent.texte_version_id">
            <UiButton
              variant="secondary"
              :loading="submittingId === consent.texte_version_id"
              :disabled="isBusy(consent.texte_version_id)"
              @click="confirmWithdraw(consent.texte_version_id)"
            >
              Confirmer le retrait
            </UiButton>
            <UiButton
              variant="quiet"
              :disabled="isBusy(consent.texte_version_id)"
              @click="cancelWithdraw"
            >
              Annuler
            </UiButton>
          </template>
          <UiButton
            v-else
            variant="quiet"
            :disabled="isBusy(consent.texte_version_id)"
            @click="requestWithdraw(consent.texte_version_id)"
          >
            Retirer ce consentement
          </UiButton>
        </FormActions>
      </li>
    </ul>
    <p v-else class="font-sans text-sm text-text-muted">
      Aucune réponse de consentement n’est affichable pour le moment.
    </p>
  </SectionCard>
</template>
