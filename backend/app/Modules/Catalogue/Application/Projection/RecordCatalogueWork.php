<?php

namespace App\Modules\Catalogue\Application\Projection;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Support\Facades\DB;

final class RecordCatalogueWork
{
    public const CONSUMER = 'a2-catalogue';

    /**
     * @param  array<string, mixed>  $target
     */
    public function forTarget(string $eventId, string $correlation, string $fingerprint, array $target): string
    {
        $existing = MessageReception::query()
            ->where('producteur', 'A2')
            ->where('evenement_externe_id', $eventId)
            ->where('consommateur', self::CONSUMER)
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        $reception = new MessageReception([
            'id' => IdentityUlid::make(),
            'evenement_externe_id' => $eventId,
            'producteur' => 'A2',
            'consommateur' => self::CONSUMER,
            'correlation' => $correlation,
            'empreinte_intention' => $fingerprint,
            'schema_message_version' => 1,
            'donnees_minimales' => $target,
            'niveau_acquittement' => null,
            'reference_resultat' => null,
        ]);
        $reception->recu_a = now();
        $reception->save();

        $receptionId = $reception->id;
        DB::afterCommit(function () use ($receptionId): void {
            try {
                app(SynchronizeCatalogueProjection::class)->forReception($receptionId);
            } catch (\Throwable) {
                // La réception reste non traitée. a2:dispatch-catalogue la reprend.
            }
        });

        return $receptionId;
    }
}
