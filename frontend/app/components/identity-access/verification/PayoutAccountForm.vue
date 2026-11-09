<script setup lang="ts">
import type { PayoutAccountDraft } from '../../../types/identity-access/verification'
import { isRegistrationCountry, registrationCountries } from '../../../utils/identity-access/countries'

const props = withDefaults(
  defineProps<{
    draft: PayoutAccountDraft
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
  'update:draft': [draft: PayoutAccountDraft]
}>()

const locked = computed(() => props.disabled || props.readonly)
const localErrors = reactive<Record<string, string>>({})
const countryOptions = registrationCountries.map(country => ({
  value: country.code,
  label: country.label,
}))
const currencyOptions = [
  { value: 'USD', label: 'USD — dollar américain' },
  { value: 'CDF', label: 'CDF — franc congolais' },
]

function errorOf(field: string): string | undefined {
  return localErrors[field] || props.fieldErrors[field]?.[0]
}

function patch(partial: Partial<PayoutAccountDraft>) {
  emit('update:draft', { ...props.draft, ...partial })
}

function validate(): boolean {
  localErrors.canal = props.draft.canal.trim() ? '' : 'Indiquez le canal de reversement.'
  localErrors.titulaire = props.draft.titulaire.trim() ? '' : 'Indiquez le titulaire.'
  localErrors.pays = isRegistrationCountry(props.draft.pays) ? '' : 'Choisissez le pays.'
  localErrors.devise = currencyOptions.some(option => option.value === props.draft.devise.trim().toUpperCase())
    ? ''
    : 'Choisissez la devise.'
  localErrors.reference_prestataire = props.draft.reference_prestataire.trim() ? '' : 'Indiquez la référence du prestataire.'
  localErrors.affichage_masque = props.draft.affichage_masque.trim() ? '' : 'Indiquez un affichage masqué, sans secret complet.'
  return !Object.values(localErrors).some(Boolean)
}

defineExpose({ validate })
</script>

<template>
  <div class="payout-form">
    <div class="payout-pair">
      <FormField
        input-id="field-canal"
        label="Canal"
        required
        :error="errorOf('canal') || errorOf('compte_reversement.canal')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.canal"
            :disabled="locked"
            :readonly="readonly"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ canal: $event })"
          />
        </template>
      </FormField>
      <FormField
        input-id="field-titulaire"
        label="Titulaire"
        required
        :error="errorOf('titulaire') || errorOf('compte_reversement.titulaire')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.titulaire"
            :disabled="locked"
            :readonly="readonly"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ titulaire: $event })"
          />
        </template>
      </FormField>
    </div>
    <div class="payout-pair">
      <FormField
        input-id="field-pays-reversement"
        label="Pays"
        required
        :error="errorOf('pays') || errorOf('compte_reversement.pays')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            :model-value="draft.pays"
            icon="globe"
            placeholder="Choisir un pays"
            :options="countryOptions"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ pays: $event })"
          />
        </template>
      </FormField>
      <FormField
        input-id="field-devise"
        label="Devise"
        required
        :error="errorOf('devise') || errorOf('compte_reversement.devise')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            :model-value="draft.devise"
            placeholder="Choisir une devise"
            :options="currencyOptions"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ devise: $event })"
          />
        </template>
      </FormField>
    </div>
    <div class="payout-pair">
      <FormField
        input-id="field-prestataire"
        label="Prestataire"
        hint="Facultatif."
        :error="errorOf('prestataire') || errorOf('compte_reversement.prestataire')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.prestataire"
            :disabled="locked"
            :readonly="readonly"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ prestataire: $event })"
          />
        </template>
      </FormField>
      <FormField
        input-id="field-reference-prestataire"
        label="Référence prestataire"
        required
        hint="Référence interne, sans coordonnées secrètes."
        :error="errorOf('reference_prestataire') || errorOf('compte_reversement.reference_prestataire')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.reference_prestataire"
            :disabled="locked"
            :readonly="readonly"
            :invalid="invalid"
            :aria-describedby="describedBy"
            @update:model-value="patch({ reference_prestataire: $event })"
          />
        </template>
      </FormField>
    </div>
    <FormField
      input-id="field-affichage-masque"
      label="Affichage masqué"
      required
      hint="Exemple : compte se terminant par 001. N’indiquez pas l’intégralité d’un secret."
      :error="errorOf('affichage_masque') || errorOf('compte_reversement.affichage_masque')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.affichage_masque"
          :disabled="locked"
          :readonly="readonly"
          :invalid="invalid"
          :aria-describedby="describedBy"
          @update:model-value="patch({ affichage_masque: $event })"
        />
      </template>
    </FormField>
  </div>
</template>
