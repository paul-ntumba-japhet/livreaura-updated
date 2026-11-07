<script setup lang="ts">
import type { ServiceConfigurationGroup } from '../../../types/identity-access/backoffice'
import { labelForConfigurationCode } from '../../../utils/identity-access/backoffice'
import type { CalendarDraft } from './ServiceCalendarEditor.vue'

const code = defineModel<string>('code', { default: 'A1_delais' })
const effectAt = defineModel<string>('effectAt', { default: '' })
const access = defineModel<Record<string, string>>('access', {
  default: () => ({
    inactivite_secondes_normal: '3600',
    absolue_secondes_normal: '43200',
    inactivite_secondes_limited: '900',
    absolue_secondes_limited: '3600',
  }),
})
const delays = defineModel<Record<string, string>>('delays', {
  default: () => ({
    activation_heures: '24',
    reset_minutes: '15',
    prise_heures_ouvre: '8',
    reponse_heures_ouvre: '40',
  }),
})
const calendar = defineModel<CalendarDraft>('calendar', {
  default: () => ({
    timezone: 'Africa/Kinshasa',
    debut: '09:00',
    fin: '17:00',
    jours: [1, 2, 3, 4, 5],
    jours_non_travailles: '',
  }),
})
const rules = defineModel<string[]>('rules', {
  default: () => ['securite', 'conformite', 'decision_humaine'],
})

const props = withDefaults(
  defineProps<{
    groups?: ServiceConfigurationGroup[]
    canApprove?: boolean
    submitting?: boolean
    disabledReason?: string
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    groups: () => [],
    canApprove: false,
    submitting: false,
    fieldErrors: () => ({}),
  },
)

const emit = defineEmits<{
  submit: []
}>()

const codeOptions = [
  { value: 'A1_acces', label: labelForConfigurationCode('A1_acces') },
  { value: 'A1_delais', label: labelForConfigurationCode('A1_delais') },
  { value: 'A1_calendrier', label: labelForConfigurationCode('A1_calendrier') },
  { value: 'A1_regles_suspension', label: labelForConfigurationCode('A1_regles_suspension') },
]

const currentGroup = computed(() => props.groups.find(group => group.code === code.value) ?? null)
const currentItems = computed(() => {
  const values = currentGroup.value?.courante?.valeurs
  if (!values) {
    return [{ term: 'Version courante', value: 'Aucune version en vigueur', missing: true }]
  }
  return Object.entries(values).map(([term, value]) => ({
    term,
    value: Array.isArray(value) ? JSON.stringify(value) : String(value),
  }))
})

const blockedReason = computed(() => {
  if (!props.canApprove) {
    return props.disabledReason || 'L’approbation exige configurer_service, pas seulement le rôle administration.'
  }
  return ''
})

function firstError(field: string): string | undefined {
  return props.fieldErrors[field]?.[0] || props.fieldErrors[`valeurs.${field}`]?.[0]
}

function toggleRule(rule: string, checked: boolean) {
  rules.value = checked
    ? Array.from(new Set([...rules.value, rule]))
    : rules.value.filter(item => item !== rule)
}

function submit() {
  if (blockedReason.value || props.submitting) {
    return
  }
  emit('submit')
}
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Approuver une configuration
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Une version approuvée est immuable. L’effet à une date future ne remplace pas immédiatement la version courante.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Approbation indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Code"
        required
        :error="firstError('code')"
      >
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="code"
            :options="codeOptions"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <div v-if="currentGroup" class="rounded-md border border-border px-gutter py-stack">
        <h3 class="font-serif text-base font-semibold text-text">
          Version en vigueur
        </h3>
        <DescriptionList class="mt-stack" :items="currentItems" />
      </div>
      <template v-if="code === 'A1_acces'">
        <FormField
          v-for="field in [
            ['inactivite_secondes_normal', 'Inactivité, portée normale (s)'],
            ['absolue_secondes_normal', 'Durée absolue, portée normale (s)'],
            ['inactivite_secondes_limited', 'Inactivité, portée limitée (s)'],
            ['absolue_secondes_limited', 'Durée absolue, portée limitée (s)'],
          ]"
          :key="field[0]"
          :label="field[1]"
          required
          :error="firstError(field[0])"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              :model-value="access[field[0]]"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :disabled="Boolean(blockedReason) || submitting"
              inputmode="numeric"
              autocomplete="off"
              @update:model-value="access = { ...access, [field[0]]: $event }"
            />
          </template>
        </FormField>
      </template>
      <template v-else-if="code === 'A1_delais'">
        <FormField
          v-for="field in [
            ['activation_heures', 'Activation (heures)'],
            ['reset_minutes', 'Réinitialisation (minutes)'],
            ['prise_heures_ouvre', 'Prise en charge (heures ouvrées)'],
            ['reponse_heures_ouvre', 'Première réponse (heures ouvrées)'],
          ]"
          :key="field[0]"
          :label="field[1]"
          required
          :error="firstError(field[0])"
        >
          <template #default="{ id, describedBy, invalid }">
            <TextInput
              :id="id"
              :model-value="delays[field[0]]"
              :invalid="invalid"
              :aria-describedby="describedBy"
              :disabled="Boolean(blockedReason) || submitting"
              inputmode="numeric"
              autocomplete="off"
              @update:model-value="delays = { ...delays, [field[0]]: $event }"
            />
          </template>
        </FormField>
      </template>
      <ServiceCalendarEditor
        v-else-if="code === 'A1_calendrier'"
        v-model:draft="calendar"
        :disabled="Boolean(blockedReason) || submitting"
        :field-errors="fieldErrors"
      />
      <fieldset v-else class="grid gap-2">
        <legend class="font-sans text-sm font-medium text-text">
          Règles autorisées
        </legend>
        <label
          v-for="rule in [
            ['securite', 'Sécurité'],
            ['conformite', 'Conformité'],
            ['decision_humaine', 'Décision humaine'],
          ]"
          :key="rule[0]"
          class="flex items-center gap-2 font-sans text-sm text-text"
        >
          <Checkbox
            :model-value="rules.includes(rule[0])"
            :disabled="Boolean(blockedReason) || submitting"
            @update:model-value="toggleRule(rule[0], $event)"
          />
          {{ rule[1] }}
        </label>
      </fieldset>
      <FormField
        label="Effet à"
        required
        hint="L’effet futur n’est lu qu’après cette échéance. Le fuseau du navigateur est utilisé pour la saisie."
        :error="firstError('effet_a')"
      >
        <template #default="{ id, describedBy, invalid }">
          <DateTimeInput
            :id="id"
            v-model="effectAt"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormActions align="start">
        <UiButton
          :disabled="Boolean(blockedReason) || submitting"
          :disabled-reason="blockedReason || undefined"
          :loading="submitting"
          @click="submit"
        >
          Approuver cette version
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
