<script setup lang="ts">
import type { OrganizationFormDraft } from '../../../types/identity-access/organization'
import type { OrganizationTypeDto } from '../../../types/identity-access/registration'
import { isRegistrationCountry, registrationCountries } from '../../../utils/identity-access/countries'
import { labelForRequirementCode } from '../../../utils/identity-access/verification'

const props = withDefaults(
  defineProps<{
    types: OrganizationTypeDto[]
    draft: OrganizationFormDraft
    fieldErrors?: Record<string, string[]>
    submitting?: boolean
    disabled?: boolean
    disabledReason?: string
    confirmNew?: boolean
    showRequirements?: boolean
    contextualRequirements?: boolean
    similarFound?: boolean
    submitLabel?: string
    submitIcon?: boolean
  }>(),
  {
    fieldErrors: () => ({}),
    submitting: false,
    disabled: false,
    confirmNew: false,
    showRequirements: true,
    contextualRequirements: false,
    similarFound: false,
    submitLabel: 'Déclarer cette organisation',
    submitIcon: true,
  },
)

const emit = defineEmits<{
  'update:draft': [draft: OrganizationFormDraft]
  submit: []
  confirm: []
}>()

const locked = computed(() => props.disabled || props.submitting)
const typeOptions = computed(() => props.types.map(type => ({
  value: type.id,
  label: type.libelle,
})))
const selectedType = computed(() => props.types.find(type => type.id === props.draft.type_organisation_id) ?? null)
const selectedRequirements = computed(() => selectedType.value?.exigences ?? [])
const countryOptions = registrationCountries.map(country => ({
  value: country.code,
  label: country.label,
}))

const localErrors = reactive<Record<string, string>>({})

function errorOf(field: string): string | undefined {
  return localErrors[field] || props.fieldErrors[field]?.[0]
}

function patch(partial: Partial<OrganizationFormDraft>) {
  emit('update:draft', { ...props.draft, ...partial })
}

function validate(): boolean {
  localErrors.type_organisation_id = props.draft.type_organisation_id ? '' : 'Choisissez un type d’organisation.'
  localErrors.raison_sociale = props.draft.raison_sociale.trim() ? '' : 'Indiquez le nom officiel.'
  localErrors.pays = isRegistrationCountry(props.draft.pays) ? '' : 'Choisissez un pays.'
  if (props.draft.email_pro.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(props.draft.email_pro.trim())) {
    localErrors.email_pro = 'Indiquez une adresse e-mail professionnelle valide.'
  }
  else {
    localErrors.email_pro = ''
  }

  return !Object.values(localErrors).some(Boolean)
}

function onSubmit() {
  if (!validate()) {
    return
  }
  emit('submit')
}
</script>

<template>
  <form class="declare-form flex flex-col" @submit.prevent="onSubmit">
    <FormField
      input-id="field-type-organisation"
      label="Type d’organisation"
      required
      :error="errorOf('type_organisation_id')"
    >
      <template #default="{ id, describedBy, invalid }">
        <SelectInput
          :id="id"
          :model-value="draft.type_organisation_id"
          icon="building-2"
          placeholder="Choisir un type"
          :options="typeOptions"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid ? true : undefined"
          @update:model-value="patch({ type_organisation_id: $event })"
        />
      </template>
    </FormField>

    <OrganizationTypeRequirements
      v-if="showRequirements && selectedType"
      :requirements="selectedRequirements"
      :title="`Pièces exigées pour ${selectedType.libelle}`"
    />

    <FormField
      input-id="field-raison-sociale"
      label="Nom officiel"
      required
      :error="errorOf('raison_sociale')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.raison_sociale"
          placeholder="Nom officiel de l’organisation"
          autocomplete="organization"
          :maxlength="512"
          :disabled="locked"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :aria-invalid="invalid ? true : undefined"
          @update:model-value="patch({ raison_sociale: $event })"
        />
      </template>
    </FormField>

    <p v-if="similarFound" class="declare-similar" role="status">
      Des organisations au nom proche existent déjà. Une correspondance de nom n’accorde aucun accès.
    </p>

    <div class="declare-pair">
      <FormField
        input-id="field-org-pays"
        label="Pays"
        required
        :error="errorOf('pays')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            :model-value="draft.pays"
            icon="globe"
            autocomplete="country"
            placeholder="Choisir un pays"
            :options="countryOptions"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? true : undefined"
            @update:model-value="patch({ pays: $event })"
          />
        </template>
      </FormField>
      <FormField
        input-id="field-org-ville"
        label="Ville"
        :error="errorOf('ville')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.ville"
            icon="map-pin"
            placeholder="Entrez la ville"
            autocomplete="address-level2"
            :maxlength="512"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? true : undefined"
            @update:model-value="patch({ ville: $event })"
          />
        </template>
      </FormField>
    </div>

    <div class="declare-pair">
      <FormField
        input-id="field-email-pro"
        label="E-mail professionnel"
        :error="errorOf('email_pro')"
      >
        <template #default="{ id, describedBy, invalid }">
          <EmailInput
            :id="id"
            :model-value="draft.email_pro"
            placeholder="exemple@domaine.org"
            autocomplete="email"
            :maxlength="320"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? true : undefined"
            @update:model-value="patch({ email_pro: $event })"
          />
        </template>
      </FormField>

      <FormField
        input-id="field-site"
        label="Site web"
        :error="errorOf('site')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.site"
            icon="link"
            placeholder="https://www.exemple.org"
            autocomplete="url"
            :maxlength="512"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid ? true : undefined"
            @update:model-value="patch({ site: $event })"
          />
        </template>
      </FormField>
    </div>

    <div v-if="contextualRequirements && selectedType" class="declare-requirements">
      <p>Des justificatifs pourront être demandés à l’étape suivante.</p>
      <details v-if="selectedRequirements.length > 0">
        <summary class="focus-ring">Voir les documents requis</summary>
        <ul>
          <li v-for="requirement in selectedRequirements" :key="requirement.code">
            {{ labelForRequirementCode(requirement.code) }}
            <template v-if="requirement.obligatoire"> · obligatoire</template>
          </li>
        </ul>
      </details>
    </div>

    <FormActions align="start">
      <UiButton
        type="submit"
        :loading="submitting && !confirmNew"
        :disabled="disabled"
        :disabled-reason="disabledReason"
      >
        {{ submitLabel }}
        <UiIcon v-if="submitIcon" name="arrow-right" />
      </UiButton>
      <UiButton
        v-if="confirmNew"
        type="button"
        variant="secondary"
        :loading="submitting"
        :disabled="disabled"
        @click="emit('confirm')"
      >
        Confirmer une nouvelle organisation distincte
      </UiButton>
    </FormActions>
  </form>
</template>
