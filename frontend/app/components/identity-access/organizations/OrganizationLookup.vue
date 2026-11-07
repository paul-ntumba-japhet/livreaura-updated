<script setup lang="ts">
import type { OrganizationSummary } from '../../../types/identity-access/organization'
import { normalizeOrganizationStatus } from '../../../types/identity-access/organization'
import { countryLabel, labelForOrganizationStatus } from '../../../utils/identity-access/account'

withDefaults(
  defineProps<{
    matches: OrganizationSummary[]
    loadState?: 'idle' | 'loading' | 'ready' | 'empty' | 'error'
    errorMessage?: string
    selectedId?: string | null
    disabled?: boolean
    variant?: 'default' | 'compact'
  }>(),
  {
    loadState: 'idle',
    errorMessage: '',
    selectedId: null,
    disabled: false,
    variant: 'default',
  },
)

const emit = defineEmits<{
  select: [id: string]
  retry: []
}>()

function matchMeta(match: OrganizationSummary): string {
  const type = match.type?.libelle || 'Type indisponible'
  const country = countryLabel(match.pays) || match.pays
  const city = match.ville?.trim()
  const place = city ? `${city}, ${country}` : country
  return place ? `${type} · ${place}` : type
}

function matchStatus(match: OrganizationSummary): string {
  const status = normalizeOrganizationStatus(match.statut_operationnel)
  if (status === 'active') {
    return ''
  }
  return labelForOrganizationStatus(status)
}
</script>

<template>
  <section v-if="variant === 'compact'" class="declare-matches" aria-labelledby="declare-matches-title">
    <h2 id="declare-matches-title">Organisations similaires</h2>

    <p v-if="loadState === 'loading' || loadState === 'idle'" class="declare-match-status" role="status" aria-busy="true">
      Recherche d’organisations similaires…
    </p>

    <div v-else-if="loadState === 'error'" class="declare-match-error" role="alert">
      <p>La recherche n’a pas pu être effectuée.</p>
      <p v-if="errorMessage" class="declare-muted">{{ errorMessage }}</p>
      <UiButton variant="secondary" type="button" @click="emit('retry')">
        Réessayer
      </UiButton>
    </div>

    <p v-else-if="loadState === 'empty'" class="declare-match-status" role="status">
      <UiIcon name="check" />
      Aucune organisation similaire trouvée.
    </p>

    <template v-else-if="loadState === 'ready'">
      <p class="declare-match-note" role="status">
        Une organisation similaire existe peut-être. Cette correspondance ne donne aucun accès automatique.
      </p>
      <ul class="declare-match-list">
        <li v-for="match in matches" :key="match.id" class="declare-match">
          <div class="declare-match-identity">
            <p class="declare-match-name">{{ match.raison_sociale }}</p>
            <p class="declare-muted">
              {{ matchMeta(match) }}
              <template v-if="matchStatus(match)"> · {{ matchStatus(match) }}</template>
            </p>
          </div>
          <UiButton
            variant="secondary"
            type="button"
            :disabled="disabled || selectedId === match.id"
            :disabled-reason="selectedId === match.id ? 'Cette organisation est déjà sélectionnée.' : undefined"
            @click="emit('select', match.id)"
          >
            {{ selectedId === match.id ? 'Organisation sélectionnée' : 'Demander l’examen de cette organisation' }}
          </UiButton>
        </li>
      </ul>
    </template>
  </section>

  <section v-else class="flex flex-col gap-stack">
    <h3 class="font-serif text-lg font-semibold text-text">
      Correspondances possibles
    </h3>
    <p class="font-sans text-sm text-text-muted">
      Une correspondance de nom ne prouve ni une identité d’organisation ni un droit d’accès. Aucune adhésion n’est créée par cette recherche.
    </p>

    <div v-if="loadState === 'loading'" aria-busy="true">
      <Skeleton variant="card" />
    </div>

    <InlineAlert
      v-else-if="loadState === 'error'"
      tone="error"
      title="Recherche impossible"
    >
      {{ errorMessage }}
    </InlineAlert>

    <EmptyState
      v-else-if="loadState === 'empty'"
      title="Aucune correspondance"
      message="Aucune organisation active n’a le même type, le même pays et la même raison sociale. Vous pouvez déclarer une nouvelle organisation."
    />

    <ul v-else-if="loadState === 'ready'" class="grid gap-stack">
      <li v-for="match in matches" :key="match.id">
        <OrganizationCard
          :organization="match"
          selectable
          :selected="selectedId === match.id"
          :disabled="disabled"
          @select="emit('select', $event)"
        />
      </li>
    </ul>
  </section>
</template>
