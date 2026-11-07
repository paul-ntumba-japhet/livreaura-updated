<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Resources;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class SessionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(AuthenticationSession $session, ?string $currentId): array
    {
        $portee = $session->portee instanceof SessionAccessScope
            ? $session->portee->value
            : (string) $session->portee;
        $statut = $session->statut instanceof SessionStatus
            ? $session->statut->value
            : (string) $session->statut;
        $contexte = is_string($session->contexte_client) ? trim($session->contexte_client) : '';

        return [
            'id' => $session->id,
            'courante' => is_string($currentId) && $currentId !== '' && hash_equals($session->id, $currentId),
            'portee' => $portee,
            'statut' => $statut,
            'ouverte_a' => $session->ouverte_a?->toIso8601String(),
            'derniere_activite_a' => $session->derniere_activite_a?->toIso8601String(),
            'expire_absolue_a' => $session->expire_absolue_a?->toIso8601String(),
            'expire_inactivite_a' => $session->expire_inactivite_a?->toIso8601String(),
            'contexte' => $contexte !== '' ? $contexte : null,
        ];
    }
}
