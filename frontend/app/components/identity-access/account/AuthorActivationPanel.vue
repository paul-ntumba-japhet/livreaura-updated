<script setup lang="ts">
import type { AuthorPath } from '../../../types/identity-access/account'
import type { OrganizationRequirementDto } from '../../../types/identity-access/registration'
import { normalizeAuthorPathState } from '../../../types/identity-access/account'
import {
  labelForAuthorPathState,
  labelForPublicationStatus,
} from '../../../utils/identity-access/account'

const props = withDefaults(
  defineProps<{
    path: AuthorPath
    canEnable?: boolean
    disabledReason?: string
    optional?: boolean
    submitting?: boolean
    fieldErrors?: Record<string, string[]>
    mutationState?: 'idle' | 'submitting' | 'succeeded' | 'failed' | 'uncertain'
    errorMessage?: string
    successMessage?: string
    requirements?: OrganizationRequirementDto[]
  }>(),
  {
    canEnable: false,
    optional: false,
    submitting: false,
    fieldErrors: () => ({}),
    mutationState: 'idle',
    errorMessage: '',
    successMessage: '',
    requirements: () => [],
  },
)

const emit = defineEmits<{
  enable: []
  claim: [auteurId: string]
}>()

const etat = computed(() => normalizeAuthorPathState(props.path.etat))
const showClaim = computed(() => etat.value === 'non_active' && props.canEnable)
const showEnable = computed(() => etat.value === 'non_active')
const badgeTone = computed(() => {
  switch (etat.value) {
    case 'active':
      return 'success' as const
    case 'revendication_en_examen':
      return 'info' as const
    case 'unknown':
      return 'warning' as const
    default:
      return 'neutral' as const
  }
})
</script>

<template>
  <SectionCard
    title="Parcours auteur"
    :description="optional
      ? 'Facultatif. Représenter une organisation n’active pas un profil auteur et n’est pas requis pour une école, une université ou une bibliothèque.'
      : 'L’activation prépare une fiche brouillon. Elle ne publie aucun ouvrage.'"
  >

    <OrganizationTypeRequirements
      v-if="requirements.length > 0"
      class="mt-stack"
      :requirements="requirements"
      title="Documents exigés pour vendre en propre"
      hint="Pièce d’identité, profil fiscal et compte de reversement. Le dépôt se fait dans le dossier partenaire commercial, pas à l’activation du parcours."
    />

    <div class="mt-stack">
      <StatusBadge :label="labelForAuthorPathState(etat)" :tone="badgeTone" />
    </div>

    <DescriptionList
      v-if="path.fiche"
      class="mt-stack"
      :items="[
        { term: 'Nom affiché', value: path.fiche.nom_affiche },
        { term: 'Publication', value: labelForPublicationStatus(path.fiche.statut_publication) },
      ]"
    />

    <InlineAlert
      v-if="path.demande?.reference"
      class="mt-stack"
      tone="info"
      title="Demande en examen"
    >
      Référence {{ path.demande.reference }}. La fiche n’est pas rattachée tant que l’examen n’est pas terminé.
    </InlineAlert>

    <InlineAlert
      v-else-if="etat === 'unknown'"
      class="mt-stack"
      tone="warning"
      title="Statut indisponible"
    >
      L’état du parcours auteur n’est pas reconnu. Les actions restent bloquées.
    </InlineAlert>

    <InlineAlert
      v-if="mutationState === 'uncertain'"
      class="mt-stack"
      tone="warning"
      title="Résultat incertain"
    >
      {{ errorMessage }}
    </InlineAlert>
    <InlineAlert
      v-else-if="mutationState === 'failed'"
      class="mt-stack"
      tone="error"
      title="Activation refusée"
    >
      {{ errorMessage }}
    </InlineAlert>
    <InlineAlert
      v-else-if="mutationState === 'succeeded' && successMessage"
      class="mt-stack"
      tone="success"
      title="Effet confirmé"
    >
      {{ successMessage }}
    </InlineAlert>

    <template v-if="showEnable">
      <p v-if="!canEnable" class="mt-stack font-sans text-sm text-text-muted">
        {{ disabledReason || 'Le parcours auteur n’est disponible qu’après activation du compte, avec un accès normal.' }}
      </p>
      <FormActions v-else class="mt-stack" align="start">
        <UiButton
          :loading="submitting"
          :disabled="submitting"
          @click="emit('enable')"
        >
          Activer le parcours auteur
        </UiButton>
      </FormActions>
    </template>

    <div v-if="showClaim" class="mt-section">
      <h3 class="font-serif text-lg font-semibold text-text">
        Fiche déjà existante
      </h3>
      <AuthorClaimForm
        class="mt-stack"
        :disabled="submitting"
        :submitting="submitting"
        :field-errors="fieldErrors"
        @submit="emit('claim', $event)"
      />
    </div>
  </SectionCard>
</template>
