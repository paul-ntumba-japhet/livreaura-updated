<script setup lang="ts">
import type { InternalPermissionOption } from '../../../types/identity-access/backoffice'
import { labelForInternalPermission } from '../../../utils/identity-access/backoffice'

const compteId = defineModel<string>('compteId', { default: '' })
const permission = defineModel<string>('permission', { default: '' })
const motif = defineModel<string>('motif', { default: '' })
const expireAt = defineModel<string>('expireAt', { default: '' })

const props = withDefaults(
  defineProps<{
    options?: InternalPermissionOption[]
    canGrant?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    options: () => [],
    canGrant: false,
    submitting: false,
    isSelf: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const permissionOptions = computed(() => props.options.map(option => ({
  value: option.code,
  label: labelForInternalPermission(option.code, option.libelle),
})))

const blockedReason = computed(() => {
  if (!props.canGrant) {
    return props.disabledReason || 'L’attribution exige gerer_habilitations_internes, pas seulement le rôle administration.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas s’attribuer une permission.'
  }
  return ''
})

const canSubmit = computed(() => !blockedReason.value && compteId.value.trim().length === 26 && permission.value.trim() !== '' && motif.value.trim().length >= 8)

function firstError(field: string): string | undefined {
  return props.fieldErrors[field]?.[0]
}

function submit() {
  if (!canSubmit.value || props.submitting) {
    return
  }
  emit('submit')
}
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Attribuer une permission
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Une période n’en remplace pas une autre. Révoquez d’abord l’attribution courante. L’amorçage initial reste hors de ce formulaire.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Attribution indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Compte"
        required
        hint="Référence du compte destinataire, pas une recherche par nom ou e-mail."
        :error="firstError('compte_id')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="compteId"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
            autocomplete="off"
          />
        </template>
      </FormField>
      <FormField
        label="Permission"
        required
        :error="firstError('permission')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="permission"
            :options="permissionOptions"
            placeholder="Choisir une permission"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Échéance"
        hint="Facultatif. Après cette date, le droit n’est plus courant. L’échéance ne révoque pas la période."
        :error="firstError('expire_a')"
      >
        <template #default="{ id, describedBy, invalid }">
          <DateTimeInput
            :id="id"
            v-model="expireAt"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormField
        label="Motif"
        required
        hint="Le motif est conservé avec l’événement. Il documente la décision humaine."
        :error="firstError('motif')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextArea
            :id="id"
            v-model="motif"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormActions align="start">
        <UiButton
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (compteId.trim().length !== 26 ? 'Indiquez la référence du compte.' : (permission.trim() === '' ? 'Choisissez une permission.' : (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined)))"
          :loading="submitting"
          @click="submit"
        >
          Attribuer cette permission
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
