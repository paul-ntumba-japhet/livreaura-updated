<script setup lang="ts">
import type { FiscalProfileDraft } from '../../../types/identity-access/verification'
import { isRegistrationCountry, registrationCountries } from '../../../utils/identity-access/countries'

const props = withDefaults(
  defineProps<{
    draft: FiscalProfileDraft
    fieldErrors?: Record<string, string[]>
    disabled?: boolean
    readonly?: boolean
  }>(),
  {
    fieldErrors: () => ({}),
    disabled: false,
    readonly: false,
  },
)

const emit = defineEmits<{
  'update:draft': [draft: FiscalProfileDraft]
}>()

const locked = computed(() => props.disabled || props.readonly)
const localErrors = reactive<Record<string, string>>({})
const countryOptions = registrationCountries.map(country => ({
  value: country.code,
  label: country.label,
}))

function errorOf(field: string): string | undefined {
  return localErrors[field] || props.fieldErrors[field]?.[0]
}

function patch(partial: Partial<FiscalProfileDraft>) {
  emit('update:draft', { ...props.draft, ...partial })
}

function validate(): boolean {
  localErrors.nom_legal = props.draft.nom_legal.trim() ? '' : 'Indiquez le nom légal.'
  localErrors.pays_fiscal = isRegistrationCountry(props.draft.pays_fiscal) ? '' : 'Choisissez le pays fiscal.'
  return !Object.values(localErrors).some(Boolean)
}

defineExpose({ validate })
</script>

<template>
  <div class="grid gap-stack">
    <FormField
      input-id="field-nom-legal"
      label="Nom légal"
      required
      :error="errorOf('nom_legal') || errorOf('profil_fiscal.nom_legal')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.nom_legal"
          :disabled="locked"
          :readonly="readonly"
          :invalid="invalid"
          :aria-describedby="describedBy"
          @update:model-value="patch({ nom_legal: $event })"
        />
      </template>
    </FormField>
    <FormField
      input-id="field-pays-fiscal"
      label="Pays fiscal"
      required
      :error="errorOf('pays_fiscal') || errorOf('profil_fiscal.pays_fiscal')"
    >
      <template #default="{ id, describedBy, invalid }">
        <SelectInput
          :id="id"
          :model-value="draft.pays_fiscal"
          icon="globe"
          placeholder="Choisir un pays"
          :options="countryOptions"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          @update:model-value="patch({ pays_fiscal: $event })"
        />
      </template>
    </FormField>
    <FormField
      input-id="field-identifiant-fiscal"
      label="Identifiant fiscal"
      hint="Facultatif."
      :error="errorOf('identifiant_fiscal') || errorOf('profil_fiscal.identifiant_fiscal')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.identifiant_fiscal"
          :disabled="locked"
          :readonly="readonly"
          :invalid="invalid"
          :aria-describedby="describedBy"
          @update:model-value="patch({ identifiant_fiscal: $event })"
        />
      </template>
    </FormField>
  </div>
</template>
