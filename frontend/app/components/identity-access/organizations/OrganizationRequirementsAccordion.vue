<script setup lang="ts">
import type { OrganizationRequirementDto, OrganizationTypeDto } from '../../../types/identity-access/registration'
import { iconForOrganizationTypeCode } from '../../../utils/identity-access/organization-display'

const props = defineProps<{
  types: OrganizationTypeDto[]
  authorRequirements?: OrganizationRequirementDto[]
  openId?: string
}>()

const emit = defineEmits<{
  'update:openId': [id: string]
}>()

const authorId = 'auteur'

function isOpen(id: string): boolean {
  return props.openId === id
}

function onToggle(id: string, open: boolean) {
  emit('update:openId', open ? id : (props.openId === id ? '' : (props.openId ?? '')))
}
</script>

<template>
  <div class="flex flex-col gap-stack">
    <Accordion
      v-if="authorRequirements"
      title="Auteur ou éditeur à titre personnel"
      icon="user"
      :open="isOpen(authorId)"
      @update:open="onToggle(authorId, $event)"
    >
      <OrganizationTypeRequirements
        variant="compact"
        :requirements="authorRequirements"
      />
    </Accordion>
    <Accordion
      v-for="type in types"
      :key="type.id"
      :title="type.libelle"
      :icon="iconForOrganizationTypeCode(type.code)"
      :open="isOpen(type.id)"
      @update:open="onToggle(type.id, $event)"
    >
      <OrganizationTypeRequirements
        variant="compact"
        :requirements="type.exigences ?? []"
      />
    </Accordion>
  </div>
</template>
