<script setup lang="ts">
import type { ErrorSummaryItem } from '../../ui/ErrorSummary.vue'
import type {
  KnownRegistrationPathCode,
  RegisterAccountPayload,
  RequiredLegalTextDto,
} from '../../../types/identity-access/registration'
import {
  consentLabelForLegalText,
  consultLabelForLegalText,
  isPublicLegalReference,
  labelForLegalTextType,
} from '../../../types/identity-access/registration'
import { passwordMeetsRequirements, passwordRequirementsMessage } from '../../../utils/ui/password'
import { isRegistrationCountry, registrationCountries } from '../../../utils/identity-access/countries'
import { isWritingLanguage, writingLanguages } from '../../../utils/identity-access/languages'

export type RegistrationFormPanel = 'informations' | 'securite'

const personalErrorKeys = ['prenom', 'nom', 'pays', 'ville', 'telephone', 'nom_plume', 'langue_ecriture'] as const

const registrationCountryOptions = registrationCountries.map(country => ({
  value: country.code,
  label: country.label,
}))

const writingLanguageOptions = writingLanguages.map(language => ({
  value: language.code,
  label: language.label,
}))

const props = withDefaults(
  defineProps<{
    path: KnownRegistrationPathCode
    texts: RequiredLegalTextDto[]
    disabled?: boolean
    submitting?: boolean
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    disabled: false,
    submitting: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: [payload: RegisterAccountPayload]
}>()

const panel = defineModel<RegistrationFormPanel>('panel', { default: 'informations' })

const prenom = ref('')
const nom = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')
const telephone = ref('')
const pays = ref('')
const ville = ref('')
const nomPlume = ref('')
const langueEcriture = ref('')
const accepted = ref<Record<string, boolean>>({})
const localErrors = ref<Record<string, string>>({})

watch(
  () => props.texts,
  (texts) => {
    const next: Record<string, boolean> = {}
    for (const text of texts) {
      next[text.id] = accepted.value[text.id] ?? false
    }
    accepted.value = next
  },
  { immediate: true },
)

watch(
  () => props.fieldErrors,
  (errors) => {
    const keys = Object.keys(errors)
    if (keys.length === 0) {
      return
    }
    if (keys.some(key => isPersonalErrorKey(key))) {
      panel.value = 'informations'
      return
    }
    if (keys.some(key => isSecurityErrorKey(key))) {
      panel.value = 'securite'
    }
  },
)

function isPersonalErrorKey(name: string): boolean {
  return (personalErrorKeys as readonly string[]).includes(name)
}

function isSecurityErrorKey(name: string): boolean {
  return name === 'email'
    || name === 'password'
    || name === 'passwordConfirm'
    || name === 'consentements'
    || name.startsWith('consent-')
}

function firstServerError(name: string): string | undefined {
  if (props.fieldErrors[name]?.[0]) {
    return props.fieldErrors[name][0]
  }
  if (name === 'consentements') {
    const key = Object.keys(props.fieldErrors).find(item => item.startsWith('consentements'))
    return key ? props.fieldErrors[key]?.[0] : undefined
  }
  return undefined
}

function errorOf(name: string): string | undefined {
  return localErrors.value[name] ?? firstServerError(name)
}

function clearError(name: string) {
  if (localErrors.value[name]) {
    const next = { ...localErrors.value }
    delete next[name]
    localErrors.value = next
  }
}

const locked = computed(() => props.disabled || props.submitting)

const summaryErrors = computed<ErrorSummaryItem[]>(() => {
  const items: ErrorSummaryItem[] = []
  const labels: Record<string, { id: string, label: string }> = {
    prenom: { id: 'field-prenom', label: 'Prénom' },
    nom: { id: 'field-nom', label: 'Nom' },
    email: { id: 'field-email', label: 'Adresse e-mail' },
    password: { id: 'field-password', label: 'Mot de passe' },
    passwordConfirm: { id: 'field-password-confirm', label: 'Confirmation du mot de passe' },
    telephone: { id: 'field-telephone', label: 'Téléphone' },
    pays: { id: 'field-pays', label: 'Pays' },
    ville: { id: 'field-ville', label: 'Ville' },
    nom_plume: { id: 'field-nom-plume', label: 'Nom de plume' },
    langue_ecriture: { id: 'field-langue-ecriture', label: 'Langue d’écriture' },
    consentements: { id: 'field-consents', label: 'Textes requis' },
  }

  for (const [key, meta] of Object.entries(labels)) {
    if (panel.value === 'informations' && !isPersonalErrorKey(key)) {
      continue
    }
    if (panel.value === 'securite' && !isSecurityErrorKey(key)) {
      continue
    }
    const message = errorOf(key)
    if (message) {
      items.push({ id: meta.id, message: `${meta.label} : ${message}` })
    }
  }

  if (panel.value === 'securite') {
    for (const text of props.texts) {
      const message = errorOf(`consent-${text.id}`)
      if (message) {
        items.push({ id: `field-consent-${text.id}`, message: `${labelForLegalTextType(text.type)} : ${message}` })
      }
    }
  }

  return items
})

const personalComplete = computed(() => {
  return prenom.value.trim() !== ''
    && nom.value.trim() !== ''
    && isRegistrationCountry(pays.value)
})

const canContinue = computed(() => personalComplete.value && !locked.value)

const continueDisabledReason = computed(() => {
  if (locked.value) {
    return undefined
  }
  if (!canContinue.value) {
    return 'Complétez les champs requis avant de passer à la sécurité du compte.'
  }
  return undefined
})

function collectPersonalErrors(): Record<string, string> {
  const next: Record<string, string> = {}
  if (!prenom.value.trim()) {
    next.prenom = 'Indiquez votre prénom.'
  }
  if (!nom.value.trim()) {
    next.nom = 'Indiquez votre nom.'
  }
  if (!isRegistrationCountry(pays.value)) {
    next.pays = 'Choisissez un pays dans la liste.'
  }
  const langue = langueEcriture.value.trim()
  if (props.path === 'auteur' && langue && !isWritingLanguage(langue)) {
    next.langue_ecriture = 'Choisissez une langue dans la liste.'
  }
  return next
}

function collectSecurityErrors(): Record<string, string> {
  const next: Record<string, string> = {}
  if (!email.value.trim()) {
    next.email = 'Indiquez votre adresse e-mail.'
  }
  if (!passwordMeetsRequirements(password.value)) {
    next.password = passwordRequirementsMessage
  }
  if (password.value !== passwordConfirm.value) {
    next.passwordConfirm = 'Les deux saisies doivent être identiques.'
  }
  for (const text of props.texts) {
    if (!accepted.value[text.id]) {
      next[`consent-${text.id}`] = 'Acceptez ce texte pour créer le compte.'
    }
  }
  return next
}

function goToSecurity() {
  const next = collectPersonalErrors()
  localErrors.value = next
  if (Object.keys(next).length > 0) {
    return
  }
  panel.value = 'securite'
  nextTick(() => {
    document.getElementById('field-email')?.focus()
  })
}

function goToPersonal() {
  panel.value = 'informations'
  nextTick(() => {
    document.getElementById('field-prenom')?.focus()
  })
}

function onSubmit() {
  if (locked.value) {
    return
  }
  if (panel.value === 'informations') {
    goToSecurity()
    return
  }

  const personal = collectPersonalErrors()
  if (Object.keys(personal).length > 0) {
    localErrors.value = personal
    goToPersonal()
    return
  }

  const security = collectSecurityErrors()
  localErrors.value = security
  if (Object.keys(security).length > 0) {
    const first = Object.keys(security)[0]
    const fieldId = first === 'passwordConfirm'
      ? 'field-password-confirm'
      : (first.startsWith('consent-') ? `field-${first}` : `field-${first}`)
    nextTick(() => {
      document.getElementById(fieldId)?.focus()
    })
    return
  }

  const payload: RegisterAccountPayload = {
    parcours: props.path,
    email: email.value.trim(),
    password: password.value,
    telephone: telephone.value.trim() || null,
    locale: 'fr',
    prenom: prenom.value.trim(),
    nom: nom.value.trim(),
    nom_plume: props.path === 'auteur' && nomPlume.value.trim() ? nomPlume.value.trim() : null,
    pays: pays.value.trim().toUpperCase(),
    ville: ville.value.trim() || null,
    langue_ecriture: props.path === 'auteur' && langueEcriture.value.trim() ? langueEcriture.value.trim().toLowerCase() : null,
    consentements: props.texts
      .filter(text => accepted.value[text.id])
      .map(text => ({
        texte_version_id: text.id,
        nature: 'acceptation',
      })),
  }

  emit('submit', payload)
}
</script>

<template>
  <form class="flex flex-col gap-section" @submit.prevent="onSubmit">
    <ErrorSummary :errors="summaryErrors" />

    <section
      v-if="panel === 'informations'"
      class="flex flex-col gap-stack"
      aria-labelledby="inscription-informations-title"
    >
      <h2 id="inscription-informations-title" class="sr-only">
        Informations personnelles
      </h2>
      <div class="registration-field-row grid gap-stack">
        <FormField
          input-id="field-prenom"
          label="Prénom"
          required
          :error="errorOf('prenom')"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              v-model="prenom"
              icon="user"
              autocomplete="given-name"
              placeholder="Votre prénom"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('prenom')"
            />
          </template>
        </FormField>
        <FormField
          input-id="field-nom"
          label="Nom"
          required
          :error="errorOf('nom')"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              v-model="nom"
              icon="user"
              autocomplete="family-name"
              placeholder="Votre nom"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('nom')"
            />
          </template>
        </FormField>
      </div>
      <div class="registration-field-row grid gap-stack">
        <FormField
          input-id="field-pays"
          label="Pays"
          required
          :error="errorOf('pays')"
        >
          <template #default="{ id, describedBy, invalid }">
            <SelectInput
              :id="id"
              v-model="pays"
              icon="globe"
              autocomplete="country"
              placeholder="Choisir un pays"
              :options="registrationCountryOptions"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('pays')"
            />
          </template>
        </FormField>
        <FormField
          input-id="field-ville"
          label="Ville"
          :error="errorOf('ville')"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              v-model="ville"
              icon="map-pin"
              autocomplete="address-level2"
              placeholder="Votre ville"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('ville')"
            />
          </template>
        </FormField>
      </div>
      <div v-if="path === 'auteur'" class="registration-field-row grid gap-stack">
        <FormField
          input-id="field-nom-plume"
          label="Nom de plume"
          :error="errorOf('nom_plume')"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              v-model="nomPlume"
              icon="pen-line"
              placeholder="Votre nom de plume"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('nom_plume')"
            />
          </template>
        </FormField>
        <FormField
          input-id="field-langue-ecriture"
          label="Langue d’écriture"
          :error="errorOf('langue_ecriture')"
        >
          <template #default="{ id, describedBy, invalid }">
            <SelectInput
              :id="id"
              v-model="langueEcriture"
              icon="languages"
              placeholder="Choisir une langue"
              :options="writingLanguageOptions"
              :disabled="locked"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @update:model-value="clearError('langue_ecriture')"
            />
          </template>
        </FormField>
      </div>
      <FormField
        input-id="field-telephone"
        label="Téléphone"
        :error="errorOf('telephone')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            v-model="telephone"
            icon="phone"
            type="tel"
            autocomplete="tel"
            placeholder="+33 6 00 00 00 00"
            :maxlength="64"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid || undefined"
            @update:model-value="clearError('telephone')"
          />
        </template>
      </FormField>
    </section>

    <section
      v-if="panel === 'securite'"
      class="flex flex-col gap-stack"
      aria-labelledby="inscription-securite-title"
    >
      <h2 id="inscription-securite-title" class="sr-only">
        Sécurité du compte
      </h2>
      <FormField
        input-id="field-email"
        label="Adresse e-mail"
        required
        :error="errorOf('email')"
      >
        <template #default="{ id, describedBy, invalid }">
          <EmailInput
            :id="id"
            v-model="email"
            placeholder="votre.email@exemple.com"
            :maxlength="320"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid || undefined"
            @update:model-value="clearError('email')"
          />
        </template>
      </FormField>
      <FormField
        input-id="field-password"
        label="Mot de passe"
        required
        :described-by-extra="password ? 'field-password-requirements' : undefined"
        :error="errorOf('password')"
      >
        <template #default="{ id, describedBy, invalid }">
          <PasswordInput
            :id="id"
            v-model="password"
            placeholder="Votre mot de passe"
            :disabled="locked"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :aria-invalid="invalid || undefined"
            @update:model-value="clearError('password')"
          />
        </template>
        <template #after>
          <PasswordRequirements
            id="field-password-requirements"
            :password="password"
          />
        </template>
      </FormField>
      <PasswordConfirmation
        input-id="field-password-confirm"
        v-model="passwordConfirm"
        :password="password"
        :disabled="locked"
        :error="errorOf('passwordConfirm')"
        @update:model-value="clearError('passwordConfirm')"
      />
      <div id="field-consents" class="flex flex-col gap-stack">
        <ConsentControl
          v-for="text in texts"
          :key="text.id"
          :input-id="`field-consent-${text.id}`"
          :model-value="accepted[text.id]"
          required
          :disabled="locked"
          :error="errorOf(`consent-${text.id}`) ?? errorOf('consentements')"
          :label="consentLabelForLegalText(text.type)"
          :consult-label="consultLabelForLegalText(text.type)"
          :consult-title="labelForLegalTextType(text.type)"
          :consult-version="text.version"
          :consult-content="text.contenu"
          :consult-href="isPublicLegalReference(text.reference_immuable) ? text.reference_immuable : undefined"
          @update:model-value="(value) => { accepted[text.id] = value; clearError(`consent-${text.id}`); clearError('consentements') }"
        />
      </div>
    </section>

    <FormActions class="auth-submit">
      <UiButton
        v-if="panel === 'informations'"
        type="submit"
        :disabled="!canContinue"
        :disabled-reason="continueDisabledReason"
      >
        Continuer vers la sécurité du compte
      </UiButton>
      <UiButton
        v-else
        type="submit"
        :loading="submitting"
        :disabled="locked"
      >
        Créer le compte
      </UiButton>
    </FormActions>
  </form>
</template>
