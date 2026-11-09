<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Review;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use Illuminate\Support\Facades\DB;

final class DecideReview
{
    public function __construct(
        private CoordinationGuard $guard,
        private HasInternalPermission $permissions,
        private ReviewIdempotency $idempotency,
        private RecalculateReviewAggregates $aggregates,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function decide(
        Account $actor,
        ?string $sessionId,
        string $reviewId,
        string $decision,
        array $input,
        ?string $clientKey,
        string $correlation,
    ): array {
        return $this->guard->withinIdentityThenCatalog(function () use ($actor, $sessionId, $reviewId, $decision, $input, $clientKey, $correlation): array {
            $this->assertModerator($actor, $sessionId);
            $expected = $this->expectedStatus($input['statut_attendu'] ?? null);
            $reason = $this->reason($decision, $input['motif'] ?? null);
            $key = $this->idempotency->key('avis:decision', $actor->id, $reviewId, $clientKey);
            $fingerprint = $this->idempotency->fingerprint([
                'avis_id' => $reviewId,
                'decision' => $decision,
                'statut_attendu' => $expected,
                'motif' => $reason,
            ]);
            $replay = $this->idempotency->replay($key, $fingerprint);

            if ($replay !== null) {
                return $replay;
            }

            $review = Review::query()->where('avis_id', $reviewId)->lockForUpdate()->first();

            if ($review === null) {
                throw CatalogueConsultationException::absent();
            }

            if ($review->statut !== $expected) {
                throw CatalogueConsultationException::conflict('L’avis a changé depuis la lecture. Relisez-le avant de décider.');
            }

            $next = $this->nextStatus($decision, $review->statut);
            $moderatedAt = now();

            if ($review->soumis_a !== null && $moderatedAt->lt($review->soumis_a)) {
                $moderatedAt = $review->soumis_a->copy();
            }

            $review->forceFill([
                'statut' => $next,
                'moderateur_compte_id' => $actor->id,
                'modere_a' => $moderatedAt,
                'motif_rejet' => $next === 'rejected' ? $reason : null,
            ])->save();

            $totals = $this->aggregates->apply($review->oeuvre_id);
            $payload = [
                'avis' => [
                    'id' => $review->avis_id,
                    'oeuvre_id' => $review->oeuvre_id,
                    'statut' => $next,
                    'modere_a' => $moderatedAt->toIso8601String(),
                ],
                'agregats' => $totals,
            ];
            $this->idempotency->record($key, 'avis_modere', $fingerprint, $correlation, $actor->id, $payload);

            return $payload;
        });
    }

    private function assertModerator(Account $actor, ?string $sessionId): void
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
                'Cette session ne permet pas de modérer un avis.',
            );
        }

        InternalAttribution::query()
            ->where('compte_id', $account->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if (! $this->hasModeratorRole($account->id) || ! ($this->permissions)($account, 'moderer_avis')) {
            throw CatalogueConsultationException::forbidden(
                'permission_insuffisante',
                'La modération de cet avis n’est pas autorisée.',
            );
        }
    }

    private function hasModeratorRole(string $accountId): bool
    {
        return DB::table('comptes_roles')
            ->join('roles', 'roles.id', '=', 'comptes_roles.role_id')
            ->where('comptes_roles.compte_id', $accountId)
            ->where('comptes_roles.statut', 'active')
            ->whereNull('comptes_roles.revoque_a')
            ->whereIn('roles.code', ['moderator', 'admin'])
            ->exists();
    }

    private function expectedStatus(mixed $value): string
    {
        if (! is_string($value) || ! in_array($value, ['pending', 'published'], true)) {
            throw CatalogueConsultationException::invalidInput(
                'statut_attendu',
                'L’état attendu doit être pending ou published.',
            );
        }

        return $value;
    }

    private function reason(string $decision, mixed $value): ?string
    {
        if (! in_array($decision, ['publication', 'rejet', 'retrait'], true)) {
            throw CatalogueConsultationException::invalidInput('decision', 'Cette décision n’est pas recevable.');
        }

        if ($decision === 'publication') {
            if ($value !== null && $value !== '') {
                throw CatalogueConsultationException::invalidInput('motif', 'La publication ne prend pas de motif.');
            }

            return null;
        }

        if (! is_string($value) || trim($value) === '') {
            throw CatalogueConsultationException::invalidInput('motif', 'Le motif est obligatoire.');
        }

        $reason = trim($value);

        if (mb_strlen($reason) > 2000) {
            throw CatalogueConsultationException::invalidInput('motif', 'Le motif dépasse la longueur autorisée.');
        }

        return $reason;
    }

    private function nextStatus(string $decision, string $current): string
    {
        $allowed = $decision === 'publication' ? 'pending' : ($decision === 'rejet' ? 'pending' : 'published');

        if ($current !== $allowed || $current === 'rejected') {
            throw CatalogueConsultationException::conflict('Cette décision ne correspond plus à l’état de l’avis.');
        }

        return $decision === 'publication' ? 'published' : 'rejected';
    }
}
