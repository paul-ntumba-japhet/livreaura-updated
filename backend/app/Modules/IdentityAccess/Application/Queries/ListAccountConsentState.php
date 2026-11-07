<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;

class ListAccountConsentState
{
    public function __construct(private ListRegistrationPaths $paths) {}

    /**
     * @return array{consentements: list<array<string, mixed>>, textes_en_attente: list<array<string, mixed>>}
     */
    public function __invoke(Account $account): array
    {
        $consents = Consent::query()
            ->where('compte_id', $account->id)
            ->with('legalText')
            ->orderBy('accepte_a')
            ->get();

        $acceptedIds = $consents
            ->filter(fn (Consent $consent): bool => $consent->retire_a === null
                && in_array($consent->nature, ['acceptation', 'prise_connaissance'], true))
            ->pluck('texte_version_id');

        $pending = $this->paths->applicableRequired($account->locale)
            ->reject(fn (LegalTextVersion $text): bool => $acceptedIds->contains($text->id))
            ->map(fn (LegalTextVersion $text): array => [
                'id' => $text->id,
                'type' => $text->type,
                'version' => $text->version,
                'langue' => $text->langue,
                'requis_pour_activation' => true,
            ])
            ->values()
            ->all();

        return [
            'consentements' => $consents
                ->map(fn (Consent $consent): array => $this->serialize($consent))
                ->values()
                ->all(),
            'textes_en_attente' => $pending,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Consent $consent): array
    {
        $consent->loadMissing('legalText');
        $text = $consent->legalText;

        return [
            'id' => $consent->id,
            'texte_version_id' => $consent->texte_version_id,
            'type' => $text?->type,
            'version' => $text?->version,
            'langue' => $text?->langue,
            'nature' => $consent->nature,
            'accepte_a' => $consent->accepte_a?->toIso8601String(),
            'retire_a' => $consent->retire_a?->toIso8601String(),
        ];
    }
}
