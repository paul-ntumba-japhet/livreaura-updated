<script setup lang="ts">
export type CalendarDraft = {
  timezone: string
  debut: string
  fin: string
  jours: number[]
  jours_non_travailles: string
}

const draft = defineModel<CalendarDraft>('draft', {
  default: () => ({
    timezone: 'Africa/Kinshasa',
    debut: '09:00',
    fin: '17:00',
    jours: [1, 2, 3, 4, 5],
    jours_non_travailles: '',
  }),
})

const props = withDefaults(
  defineProps<{
    disabled?: boolean
    fieldErrors?: Record<string, string[]>
  }>(),
  {
    disabled: false,
    fieldErrors: () => ({}),
  },
)

const dayOptions = [
  { value: 1, label: 'Lundi' },
  { value: 2, label: 'Mardi' },
  { value: 3, label: 'Mercredi' },
  { value: 4, label: 'Jeudi' },
  { value: 5, label: 'Vendredi' },
  { value: 6, label: 'Samedi' },
  { value: 7, label: 'Dimanche' },
]

function firstError(field: string): string | undefined {
  return props.fieldErrors[field]?.[0]
}

function toggleDay(day: number, checked: boolean) {
  const current = draft.value.jours
  draft.value = {
    ...draft.value,
    jours: checked
      ? Array.from(new Set([...current, day])).sort((a, b) => a - b)
      : current.filter(item => item !== day),
  }
}
</script>

<template>
  <div class="grid gap-stack">
    <FormField
      label="Fuseau"
      required
      hint="Le calendrier de service utilise ce fuseau pour les plages et les jours non travaillés."
      :error="firstError('valeurs.timezone')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.timezone"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :disabled="disabled"
          autocomplete="off"
          @update:model-value="draft = { ...draft, timezone: $event }"
        />
      </template>
    </FormField>
    <fieldset class="grid gap-2">
      <legend class="font-sans text-sm font-medium text-text">
        Jours travaillés
      </legend>
      <p class="font-sans text-sm text-text-muted">
        Une plage unique s’applique aux jours cochés. Les jours non travaillés s’ajoutent aux week-ends non cochés.
      </p>
      <div class="flex flex-wrap gap-gutter">
        <label
          v-for="option in dayOptions"
          :key="option.value"
          class="flex items-center gap-2 font-sans text-sm text-text"
        >
          <Checkbox
            :model-value="draft.jours.includes(option.value)"
            :disabled="disabled"
            @update:model-value="toggleDay(option.value, $event)"
          />
          {{ option.label }}
        </label>
      </div>
    </fieldset>
    <div class="grid gap-stack sm:grid-cols-2">
      <FormField
        label="Début"
        required
        :error="firstError('valeurs.plages')"
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.debut"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="disabled"
            autocomplete="off"
            @update:model-value="draft = { ...draft, debut: $event }"
          />
        </template>
      </FormField>
      <FormField
        label="Fin"
        required
      >
        <template #default="{ id, describedBy, invalid }">
          <TextInput
            :id="id"
            :model-value="draft.fin"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="disabled"
            autocomplete="off"
            @update:model-value="draft = { ...draft, fin: $event }"
          />
        </template>
      </FormField>
    </div>
    <FormField
      label="Jours non travaillés"
      hint="Dates AAAA-MM-JJ, séparées par une virgule. Facultatif."
      :error="firstError('valeurs.jours_non_travailles')"
    >
      <template #default="{ id, describedBy, invalid }">
        <TextInput
          :id="id"
          :model-value="draft.jours_non_travailles"
          :invalid="invalid"
          :aria-describedby="describedBy"
          :disabled="disabled"
          autocomplete="off"
          @update:model-value="draft = { ...draft, jours_non_travailles: $event }"
        />
      </template>
    </FormField>
  </div>
</template>
