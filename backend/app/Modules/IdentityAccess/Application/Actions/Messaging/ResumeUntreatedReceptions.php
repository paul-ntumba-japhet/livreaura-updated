<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;

class ResumeUntreatedReceptions
{
    public function __construct(private ReceiveAccountReviewInstruction $receive) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $ids = MessageReception::query()
            ->whereNull('traite_a')
            ->where('consommateur', ReceiveAccountReviewInstruction::CONSUMER)
            ->orderBy('recu_a')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $processed = [];

        foreach ($ids as $id) {
            $reception = MessageReception::query()->where('id', $id)->first();

            if ($reception === null || $reception->traite_a !== null) {
                continue;
            }

            $instruction = $this->instructionFrom($reception);

            if ($instruction === null) {
                continue;
            }

            try {
                $this->receive->__invoke($instruction, $reception->correlation);
            } catch (IdentityAccessException $exception) {
                if ($exception->errorCode === 'compte_inconnu') {
                    continue;
                }

                throw $exception;
            }

            $fresh = $reception->fresh();

            if ($fresh !== null && $fresh->traite_a !== null) {
                $processed[] = (string) $id;
            }
        }

        return $processed;
    }

    /**
     * @return array{evenement_externe_id: string, producteur: string, correlation: string, schema_message_version: int, compte_id: string, instruction_id: string, type: string, cause: ?string, motif: string}|null
     */
    private function instructionFrom(MessageReception $reception): ?array
    {
        $data = is_array($reception->donnees_minimales) ? $reception->donnees_minimales : [];
        $accountId = strtoupper(trim((string) ($data['compte_id'] ?? '')));
        $instructionId = trim((string) ($data['instruction_id'] ?? ''));
        $motif = trim((string) ($data['motif'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'demander_examen_compte'));

        if ($accountId === '' || $instructionId === '' || $motif === '') {
            return null;
        }

        $cause = isset($data['cause']) && trim((string) $data['cause']) !== ''
            ? trim((string) $data['cause'])
            : null;

        return [
            'evenement_externe_id' => (string) $reception->evenement_externe_id,
            'producteur' => (string) $reception->producteur,
            'correlation' => (string) $reception->correlation,
            'schema_message_version' => (int) $reception->schema_message_version,
            'compte_id' => $accountId,
            'instruction_id' => $instructionId,
            'type' => $type,
            'cause' => $cause,
            'motif' => $motif,
        ];
    }
}
