<script setup lang="ts">
import type { RetryableTransmission } from '../../../types/identity-access/backoffice'
import { labelForTransmissionState, toneForTransmissionState } from '../../../utils/identity-access/backoffice'
import { formatActivationExpiry } from '../../../utils/identity-access/email-activation'

const props = withDefaults(
  defineProps<{
    rows?: RetryableTransmission[]
    canRetry?: boolean
    retryingId?: string | null
  }>(),
  {
    rows: () => [],
    canRetry: false,
    retryingId: null,
  },
)

const emit = defineEmits<{
  retry: [id: string]
}>()
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full border-collapse text-left">
      <caption class="sr-only">
        Transmissions en échec ou en attente de nouvelle tentative
      </caption>
      <thead>
        <tr class="border-b border-border">
          <th scope="col" class="py-2 pr-gutter font-sans text-sm font-medium text-text">
            Canal
          </th>
          <th scope="col" class="py-2 pr-gutter font-sans text-sm font-medium text-text">
            Destination
          </th>
          <th scope="col" class="py-2 pr-gutter font-sans text-sm font-medium text-text">
            État
          </th>
          <th scope="col" class="py-2 pr-gutter font-sans text-sm font-medium text-text">
            Tentatives
          </th>
          <th scope="col" class="py-2 pr-gutter font-sans text-sm font-medium text-text">
            Prochaine tentative
          </th>
          <th scope="col" class="py-2 font-sans text-sm font-medium text-text">
            Action
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in rows"
          :key="row.id"
          class="border-b border-border"
        >
          <td class="py-stack pr-gutter font-sans text-sm text-text">
            {{ row.canal || 'Canal indisponible' }}
          </td>
          <td class="py-stack pr-gutter font-sans text-sm text-text">
            {{ row.destination_masquee || 'Destination masquée indisponible' }}
          </td>
          <td class="py-stack pr-gutter">
            <StatusBadge
              :label="labelForTransmissionState(row.etat)"
              :tone="toneForTransmissionState(row.etat)"
            />
          </td>
          <td class="py-stack pr-gutter font-sans text-sm text-text">
            {{ row.nombre_tentatives }}
          </td>
          <td class="py-stack pr-gutter font-sans text-sm text-text">
            {{ row.prochaine_tentative_a ? formatActivationExpiry(row.prochaine_tentative_a) : 'Dès que possible' }}
          </td>
          <td class="py-stack">
            <RetryTransmissionAction
              :can-retry="canRetry"
              :submitting="retryingId === row.id"
              @retry="emit('retry', row.id)"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
