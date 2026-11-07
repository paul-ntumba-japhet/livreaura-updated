<script setup lang="ts">
import type { OrganizationRequirementDto, OrganizationTypeDto } from '../../../types/identity-access/registration'

defineProps<{
  types: OrganizationTypeDto[]
  authorRequirements?: OrganizationRequirementDto[]
  showAuthor?: boolean
}>()
</script>

<template>
  <div class="flex flex-col gap-section">
    <OrganizationTypeRequirements
      v-if="showAuthor"
      :requirements="authorRequirements ?? []"
      title="Auteur ou éditeur à titre personnel"
      hint="Pour vendre en propre. L’activation du parcours auteur ne dépose pas encore ces pièces."
    />
    <OrganizationTypeRequirements
      v-for="type in types"
      :key="type.id"
      :requirements="type.exigences ?? []"
      :title="type.libelle"
      hint="Pour l’organisation vérifiée. Le représentant dépose ces pièces après la déclaration."
    />
  </div>
</template>
