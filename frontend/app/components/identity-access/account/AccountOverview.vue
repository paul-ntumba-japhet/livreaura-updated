<script setup lang="ts">
import type { CurrentAccount } from '../../../types/identity-access/account'
import { identityItems, profileItems } from '../../../utils/identity-access/account'

defineProps<{
  account: CurrentAccount
}>()
</script>

<template>
  <SectionCard
    id="profil"
    elevated
    icon="user"
    icon-size="md"
    title="Profil et identité"
  >
    <template #action>
      <span class="inline-flex items-center">
        <UiButton
          variant="quiet"
          disabled
          aria-label="Modifier le profil. Cette action n’est pas proposée dans cette version."
        >
          <UiIcon name="pen-line" />
          Modifier
        </UiButton>
      </span>
    </template>
    <DescriptionList layout="rows" :items="identityItems(account)" />
    <div class="my-gutter border-t border-border" role="separator" />
    <DescriptionList layout="rows" :items="profileItems(account)" />
  </SectionCard>
</template>
