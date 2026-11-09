<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Review;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use Illuminate\Database\QueryException;

final class SubmitReview
{
    public function __construct(
        private CoordinationGuard $guard,
        private ReviewWorkGate $works,
        private ReviewIdempotency $idempotency,
        private CurrentServiceConfiguration $configurations,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function submit(Account $actor, ?string $sessionId, string $slug, array $input, ?string $clientKey, string $correlation): array
    {
        return $this->guard->withinIdentityThenCatalog(function () use ($actor, $sessionId, $slug, $input, $clientKey, $correlation): array {
            $this->assertSession($actor, $sessionId);
            $work = $this->works->visible($slug);
            $this->works->acceptsReview($work);
            $note = $this->note($input['note'] ?? null);
            $body = $this->body($input['corps'] ?? null);
            $key = $this->idempotency->key('avis:depot', $actor->id, $work->oeuvre_id, $clientKey);
            $fingerprint = $this->idempotency->fingerprint([
                'oeuvre_id' => $work->oeuvre_id,
                'note' => $note,
                'corps' => $body,
            ]);
            $replay = $this->idempotency->replay($key, $fingerprint);

            if ($replay !== null) {
                return $replay;
            }

            $reviewId = IdentityUlid::make();
            $submittedAt = now();

            try {
                Review::query()->create([
                    'avis_id' => $reviewId,
                    'compte_id' => $actor->id,
                    'oeuvre_id' => $work->oeuvre_id,
                    'note' => $note,
                    'corps' => $body,
                    'statut' => 'pending',
                    'soumis_a' => $submittedAt,
                    'moderateur_compte_id' => null,
                    'modere_a' => null,
                    'motif_rejet' => null,
                ]);
            } catch (QueryException $exception) {
                if ($this->isExistingReview($exception)) {
                    throw CatalogueConsultationException::conflict('Un avis est déjà en cours pour cette œuvre.');
                }

                throw $exception;
            }

            $payload = [
                'avis' => [
                    'id' => $reviewId,
                    'oeuvre_id' => $work->oeuvre_id,
                    'note' => $note,
                    'corps' => $body,
                    'statut' => 'pending',
                    'soumis_a' => $submittedAt->toIso8601String(),
                ],
            ];
            $this->idempotency->record($key, 'avis_depose', $fingerprint, $correlation, $actor->id, $payload);

            return $payload;
        });
    }

    private function assertSession(Account $actor, ?string $sessionId): void
    {
        $account = Account::query()->where('id', $actor->id)->lockForUpdate()->first();
        $session = $sessionId === null
            ? null
            : AuthenticationSession::query()->where('id', $sessionId)->lockForUpdate()->first();

        if ($account === null || $session === null || $session->compte_id !== $account->id) {
            throw CatalogueConsultationException::unauthenticated();
        }

        if ($session->statut !== SessionStatus::Active || $session->revoque_a !== null) {
            throw CatalogueConsultationException::unauthenticated();
        }

        $now = now();

        if ($session->expire_absolue_a <= $now || $session->expire_inactivite_a <= $now) {
            throw CatalogueConsultationException::unauthenticated();
        }

        if ($account->statut !== AccountStatus::Active || $session->portee !== SessionAccessScope::Normal) {
            throw CatalogueConsultationException::forbidden(
                'session_restreinte',
                'Cette session ne permet pas de déposer un avis.',
            );
        }
    }

    private function note(mixed $value): int
    {
        if (! is_int($value) || $value < 1 || $value > 5) {
            throw CatalogueConsultationException::invalidInput('note', 'La note doit être un entier de 1 à 5.');
        }

        return $value;
    }

    private function body(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            $this->bodyCeiling();

            return null;
        }

        if (! is_string($value)) {
            throw CatalogueConsultationException::invalidInput('corps', 'Le texte de l’avis n’est pas recevable.');
        }

        $ceiling = $this->bodyCeiling();

        if (mb_strlen($value) > $ceiling) {
            throw CatalogueConsultationException::invalidInput('corps', 'Le texte de l’avis dépasse la longueur autorisée.');
        }

        return $value;
    }

    private function bodyCeiling(): int
    {
        $configuration = $this->configurations->find('A2_consultation');
        $ceiling = $configuration instanceof ServiceConfiguration
            ? ($configuration->valeurs['longueur_corps'] ?? null)
            : null;

        if (! is_int($ceiling) || $ceiling < 1) {
            throw CatalogueConsultationException::unavailable('La longueur autorisée des avis n’est pas configurée.');
        }

        return $ceiling;
    }

    private function isExistingReview(QueryException $exception): bool
    {
        $state = $exception->errorInfo[0] ?? null;

        return $state === '23000' && str_contains($exception->getMessage(), 'uq_avis_45b94da625');
    }
}
