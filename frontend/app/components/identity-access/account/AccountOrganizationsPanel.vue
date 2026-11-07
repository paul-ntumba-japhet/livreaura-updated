<script setup lang="ts">
import type { CurrentAccount } from '../../../types/identity-access/account'

defineProps<{
  account: CurrentAccount
}>()
</script>

<template>
  <SectionCard
    id="organisations"
    elevated
    icon="building-2"
    icon-size="md"
    title="Organisations"
  >
    <div
      v-if="account.organisations.length === 0"
      class="flex flex-1 flex-col justify-center"
    >
      <div class="flex items-start gap-stack rounded-md bg-surface-muted px-gutter py-gutter">
        <span
          class="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-surface-raised text-text-muted"
          aria-hidden="true"
        >
          <UiIcon name="building-2" size="md" :stroke-width="2" />
        </span>
        <div class="min-w-0">
          <p class="font-sans text-sm font-semibold text-text">
            Aucune organisation
          </p>
          <p class="mt-1 font-sans text-sm text-text-muted">
            Ce compte n’est encore rattaché à aucune organisation.
          </p>
          <UiButton
            class="mt-gutter"
            variant="outline"
            to="/compte/organisation"
          >
            Gérer les organisations
            <UiIcon name="arrow-right" />
          </UiButton>
        </div>
      </div>
    </div>
    <template v-else>
      <ul class="grid flex-1 gap-stack">
        <li v-for="item in account.organisations" :key="item.id">
          <OrganizationCard
            :organization="item"
            :principal="item.adhesion.principal"
            :membership-status="item.adhesion.statut"
            :permissions="item.adhesion.permissions"
          />
          <OrganizationTypeRequirements
            v-if="item.exigences && item.exigences.length > 0"
            class="mt-stack"
            :requirements="item.exigences"
            :title="`Pièces exigées pour ${item.type?.libelle || 'cette organisation'}`"
            hint="Déposez-les dans le dossier d’organisation vérifiée. Aucune pièce n’est publique."
          />
          <p class="mt-stack">
            <UiButton
              variant="secondary"
              :disabled="item.adhesion.statut !== 'active' || !item.adhesion.permissions.includes('deposer_kyc')"
              :disabled-reason="item.adhesion.statut !== 'active'
                ? 'L’adhésion n’est pas active.'
                : (!item.adhesion.permissions.includes('deposer_kyc')
                  ? 'Le dépôt d’identité n’est pas accordé pour cette organisation.'
                  : undefined)"
              @click="navigateTo({ path: '/compte/verification', query: { sujet: item.id } })"
            >
              Ouvrir un dossier de vérification
            </UiButton>
          </p>
        </li>
      </ul>
      <p class="mt-gutter">
        <UiButton variant="outline" pill to="/compte/organisation">
          Gérer les organisations
          <UiIcon name="arrow-right" />
        </UiButton>
      </p>
    </template>
  </SectionCard>
</template>
