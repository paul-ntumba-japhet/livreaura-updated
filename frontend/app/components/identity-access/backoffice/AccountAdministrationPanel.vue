<script setup lang="ts">
const motif = defineModel<string>('motif', { default: '' })
const finPrevue = defineModel<string>('finPrevue', { default: '' })

const props = withDefaults(
  defineProps<{
    canSuspend?: boolean
    submitting?: boolean
    isSelf?: boolean
    disabledReason?: string
  }>(),
  {
    canSuspend: false,
    submitting: false,
    isSelf: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const blockedReason = computed(() => {
  if (!props.canSuspend) {
    return props.disabledReason || 'La suspension exige une attribution interne distincte, pas seulement le rôle administration.'
  }
  if (props.isSelf) {
    return 'Cette session ne peut pas suspendre ce compte.'
  }
  return ''
})

const canSubmit = computed(() => !blockedReason.value && motif.value.trim().length >= 8)

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
      Suspension du compte
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      La suspension coupe les accès d’authentification et de lecture. Les droits de propriété et les documents sont conservés. La levée suit un réexamen distinct.
    </p>
    <InlineAlert
      v-if="blockedReason"
      class="mt-stack"
      tone="warning"
      title="Suspension indisponible"
    >
      {{ blockedReason }}
    </InlineAlert>
    <div class="mt-stack grid gap-stack">
      <FormField
        label="Motif"
        required
        hint="Le motif est conservé avec l’événement. Il documente la décision humaine."
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
      <FormField
        label="Fin prévue"
        hint="Facultatif. L’échéance déclenche un réexamen, pas une réactivation automatique."
      >
        <template #default="{ id, describedBy, invalid }">
          <DateTimeInput
            :id="id"
            v-model="finPrevue"
            :invalid="invalid"
            :aria-describedby="describedBy"
            :disabled="Boolean(blockedReason) || submitting"
          />
        </template>
      </FormField>
      <FormActions align="start">
        <UiButton
          variant="destructive"
          :disabled="!canSubmit || submitting"
          :disabled-reason="blockedReason || (motif.trim().length < 8 ? 'Indiquez un motif d’au moins 8 caractères.' : undefined)"
          :loading="submitting"
          @click="submit"
        >
          Suspendre ce compte
        </UiButton>
      </FormActions>
    </div>
  </section>
</template>
