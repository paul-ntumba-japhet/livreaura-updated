<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;

class TouchAuthenticationSession
{
    public function __invoke(AuthenticationSession $session): void
    {
        $now = now();
        $seconds = $this->inactivitySeconds($session);
        $next = $now->copy()->addSeconds($seconds);

        if ($session->expire_absolue_a !== null && $next->gt($session->expire_absolue_a)) {
            $next = $session->expire_absolue_a->copy();
        }

        $session->derniere_activite_a = $now;
        $session->expire_inactivite_a = $next;
        $session->save();
    }

    private function inactivitySeconds(AuthenticationSession $session): int
    {
        $configuration = ServiceConfiguration::query()->find($session->config_acces_id);
        $values = is_array($configuration?->valeurs) ? $configuration->valeurs : [];
        $limited = $session->portee === SessionAccessScope::Limited
            || $session->portee === SessionAccessScope::SupportOnly;
        $configured = $limited
            ? (int) ($values['inactivite_secondes_limited'] ?? 900)
            : (int) ($values['inactivite_secondes_normal'] ?? 3600);

        return max($configured, 60);
    }
}
