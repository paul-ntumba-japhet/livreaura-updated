<?php

namespace App\Modules\Catalogue\Application\Actions\Projection;

use App\Modules\Catalogue\Application\Actions\Reviews\RecalculateReviewAggregates;
use App\Modules\Catalogue\Application\Projection\ApplyCatalogueRestriction;
use App\Modules\Catalogue\Application\Projection\RecordCatalogueWork;
use App\Modules\Catalogue\Application\Projection\SynchronizeCatalogueProjection;
use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Application\Actions\Messaging\RetryTransmission;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Support\Facades\DB;

final class ResumeCatalogueWork
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
        private RecalculateReviewAggregates $aggregates,
        private SynchronizeCatalogueProjection $projection,
        private ApplyCatalogueRestriction $restrictions,
        private RetryTransmission $transmissions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function resume(Account $account, ?string $sessionId, array $input, ?string $clientKey, string $correlation): array
    {
        $workId = $input['oeuvre_id'] ?? null;
        $receptionId = $input['reception_id'] ?? null;
        $transmissionId = $input['transmission_id'] ?? null;
        $present = array_filter([
            is_string($workId) && $workId !== '',
            is_string($receptionId) && $receptionId !== '',
            is_string($transmissionId) && $transmissionId !== '',
        ]);

        if (count($present) !== 1) {
            throw CatalogueConsultationException::invalidInput('cible', 'Indiquez une œuvre, une réception ou une transmission.');
        }

        if (is_string($transmissionId) && $transmissionId !== '') {
            return $this->retryTransmission($account, $transmissionId, $clientKey, $correlation);
        }

        if (is_string($workId) && $workId !== '') {
            $this->guard->withinIdentity(function () use ($account, $sessionId): void {
                $this->actor->assert($account, $sessionId, 'reprendre_traitements_catalogue', ['support', 'admin']);
            });

            if (! IdentityUlid::isValid($workId) || ! DB::table('oeuvre')->where('oeuvre_id', $workId)->exists()) {
                throw CatalogueConsultationException::absent();
            }

            return [
                'niveau_acquittement' => 'engage_local',
                'agregats' => $this->aggregates->forWork($workId),
            ];
        }

        $this->guard->withinIdentity(function () use ($account, $sessionId): void {
            $this->actor->assert($account, $sessionId, 'reprendre_traitements_catalogue', ['support', 'admin']);
        });
        $reception = MessageReception::query()->where('id', $receptionId)->first();

        if ($reception === null || $reception->consommateur !== RecordCatalogueWork::CONSUMER) {
            throw CatalogueConsultationException::absent();
        }

        if (in_array($reception->producteur, ['A5', 'A6'], true)) {
            return $this->restrictions->forReception($reception->id);
        }

        if ($reception->producteur === 'A2') {
            return $this->projection->forReception($reception->id);
        }

        throw CatalogueConsultationException::absent();
    }

    /**
     * @return array<string, mixed>
     */
    private function retryTransmission(Account $account, string $transmissionId, ?string $clientKey, string $correlation): array
    {
        $transmissionId = strtoupper($transmissionId);

        if (! IdentityUlid::isValid($transmissionId) || ! is_string($clientKey) || strlen($clientKey) < 8 || strlen($clientKey) > 128) {
            throw CatalogueConsultationException::invalidInput('transmission_id', 'Cette transmission n’est pas recevable.');
        }

        return $this->transmissions->__invoke(
            $account,
            $transmissionId,
            'transmissions_messages:retry:idk:'.$account->id.':'.$transmissionId.':'.$clientKey,
            SecretHasher::hmac(json_encode([
                'action' => 'reprise',
                'transmission_id' => $transmissionId,
            ], JSON_THROW_ON_ERROR)),
            $correlation,
        );
    }
}
