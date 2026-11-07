<script setup lang="ts">
import type { RepresentativeTransferParty } from '../../../types/identity-access/organization'
import { labelForMembershipStatus, labelForOrganizationPermission } from '../../../utils/identity-access/account'

defineProps<{
  ancien?: RepresentativeTransferParty | null
  nouveau?: RepresentativeTransferParty | null
  depart?: boolean
}>()

function displayName(party: RepresentativeTransferParty): string {
  const name = [party.prenom, party.nom].filter(Boolean).join(' ').trim()
  return name || party.email_masque || 'Compte'
}
</script>

<template>
  <section class="rounded-lg border border-border bg-surface-raised px-gutter py-stack">
    <h2 class="font-serif text-lg font-semibold text-text">
      Comparaison
    </h2>
    <p class="mt-stack font-sans text-sm text-text-muted">
      Un seul principal actif est conservé. Les sessions du contexte concerné sont révoquées après confirmation serveur.
    </p>
    <div class="mt-stack grid gap-stack md:grid-cols-2">
      <article class="rounded-lg border border-border px-gutter py-stack">
        <h3 class="font-sans text-sm font-semibold text-text">
          Ancien principal
        </h3>
        <p v-if="!ancien" class="mt-stack font-sans text-sm text-text-muted">
          Aucun principal actif n’était enregistré.
        </p>
        <DescriptionList
          v-else
          class="mt-stack"
          :items="[
            { term: 'Titulaire', value: displayName(ancien) },
            { term: 'Adhésion', value: labelForMembershipStatus(ancien.adhesion.statut) },
            { term: 'Principal', value: ancien.adhesion.principal ? 'Oui' : 'Non' },
            { term: 'Permissions', value: ancien.adhesion.permissions.map(labelForOrganizationPermission).join(', ') || 'Aucune' },
            { term: 'Sessions révoquées', value: String(ancien.sessions_revoquees) },
            { term: 'Départ', value: depart ? 'Adhésion révoquée' : 'Reste membre' },
          ]"
        />
      </article>
      <article class="rounded-lg border border-border px-gutter py-stack">
        <h3 class="font-sans text-sm font-semibold text-text">
          Nouveau principal
        </h3>
        <p v-if="!nouveau" class="mt-stack font-sans text-sm text-text-muted">
          Le nouveau titulaire apparaîtra après confirmation du serveur.
        </p>
        <DescriptionList
          v-else
          class="mt-stack"
          :items="[
            { term: 'Titulaire', value: displayName(nouveau) },
            { term: 'Adhésion', value: labelForMembershipStatus(nouveau.adhesion.statut) },
            { term: 'Principal', value: nouveau.adhesion.principal ? 'Oui' : 'Non' },
            { term: 'Permissions', value: nouveau.adhesion.permissions.map(labelForOrganizationPermission).join(', ') || 'Aucune' },
            { term: 'Sessions révoquées', value: String(nouveau.sessions_revoquees) },
          ]"
        />
      </article>
    </div>
  </section>
</template>
