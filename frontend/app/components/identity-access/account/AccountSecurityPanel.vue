<script setup lang="ts">
defineProps<{
  canChangePassword?: boolean
}>()
</script>

<template>
  <SectionCard
    id="securite"
    elevated
    icon="lock"
    icon-size="md"
    title="Sécurité"
    description="Un changement de mot de passe révoque toutes les sessions ouvertes. Une nouvelle connexion sera demandée."
  >
    <UiButton
      variant="outline"
      pill
      :disabled="canChangePassword === false"
      :disabled-reason="canChangePassword === false ? 'Le changement de mot de passe n’est pas disponible dans cet état.' : undefined"
      to="/compte/mot-de-passe"
    >
      <UiIcon name="lock" />
      Changer le mot de passe
    </UiButton>
  </SectionCard>
</template>
