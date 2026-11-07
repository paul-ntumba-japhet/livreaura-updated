<script setup lang="ts">
import type { RegistrationPathDto } from '../../../types/identity-access/registration'
import { isKnownRegistrationPathCode } from '../../../types/identity-access/registration'
import type { UiIconName } from '../../ui/icons'

const iconsByPath = {
  lecteur: 'book-open',
  auteur: 'pen-line',
  representant: 'building-2',
} as const satisfies Record<string, UiIconName>

const copyByPath = {
  lecteur: {
    subtitle: 'Compte personnel',
    description: 'Lire et suivre vos ouvrages.',
    benefits: ['Lire vos ouvrages', 'Suivre vos lectures'],
  },
  auteur: {
    subtitle: 'Compte personnel',
    description: 'Créer votre profil.',
    benefits: ['Préparer votre profil', 'Aucun rôle privilégié'],
  },
  representant: {
    subtitle: 'Organisation',
    description: 'École, université, bibliothèque ou édition.',
    benefits: ['Compte personnel d’abord', 'Organisation après l’activation'],
  },
} as const

defineProps<{
  paths: RegistrationPathDto[]
  modelValue: string
  disabled?: boolean
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function isUnavailable(path: RegistrationPathDto): boolean {
  return !isKnownRegistrationPathCode(path.code)
}

function iconFor(code: string): UiIconName | undefined {
  return isKnownRegistrationPathCode(code) ? iconsByPath[code] : undefined
}

function copyFor(code: string): { subtitle?: string, description?: string, benefits?: readonly string[] } {
  return isKnownRegistrationPathCode(code) ? copyByPath[code] : {}
}
</script>

<template>
  <RadioGroup
    :model-value="modelValue"
    legend="Parcours"
    name="parcours"
    variant="cards"
    legend-sr-only
    :disabled="disabled"
    :error="error"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <ChoiceCard
      v-for="path in paths"
      :key="path.code"
      variant="tile"
      :value="path.code"
      :title="path.libelle"
      :subtitle="copyFor(path.code).subtitle"
      :description="copyFor(path.code).description"
      :benefits="[...(copyFor(path.code).benefits ?? [])]"
      :disabled="disabled || isUnavailable(path)"
      :disabled-reason="isUnavailable(path) ? 'Ce parcours n’est pas proposé pour le moment.' : undefined"
    >
      <template #icon>
        <UiIcon v-if="iconFor(path.code)" :name="iconFor(path.code) ?? 'empty'" size="md" />
      </template>
    </ChoiceCard>
  </RadioGroup>
</template>
