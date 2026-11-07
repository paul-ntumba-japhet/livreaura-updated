<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class ResolveSupportDemandAccess
{
    public function __construct(
        private SessionIdentityBridge $sessions,
        private HasInternalPermission $hasPermission,
    ) {}

    public function __invoke(Account $actor, string $demandKey, bool $lock = false, bool $forMutation = false): SupportDemandAccess
    {
        $demande = $this->findDemand($demandKey, $lock);

        if ($demande === null) {
            throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
        }

        $session = $this->sessions->current();

        if ($session === null || $session->compte_id !== $actor->id) {
            throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
        }

        $linked = $demande->demandeur_compte_id === $actor->id || $demande->compte_concerne_id === $actor->id;
        $authSupport = $session->portee === SessionAccessScope::SupportOnly
            && $session->demande_support_id === $demande->id;
        $open = $demande->clos_a === null;
        $normal = $session->portee === SessionAccessScope::Normal;
        $agent = $normal && ($this->hasPermission)($actor, 'traiter_support');

        if ($authSupport) {
            if ($forMutation && ! $open) {
                throw new IdentityAccessException('etat_incompatible', 'Cette demande n est plus ouverte.', 409);
            }

            return new SupportDemandAccess($demande, 'claimant', $open, false);
        }

        if ($linked && $normal) {
            if ($forMutation && ! $open) {
                throw new IdentityAccessException('etat_incompatible', 'Cette demande n est plus ouverte.', 409);
            }

            return new SupportDemandAccess($demande, 'claimant', $open, false);
        }

        if ($agent && ! $linked) {
            if ($forMutation && ! $open) {
                throw new IdentityAccessException('etat_incompatible', 'Cette demande n est plus ouverte.', 409);
            }

            return new SupportDemandAccess($demande, 'agent', $open, true);
        }

        throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
    }

    private function findDemand(string $demandKey, bool $lock): ?SupportRequest
    {
        $trimmed = trim($demandKey);
        $ulid = strtoupper($trimmed);

        if (IdentityUlid::isValid($ulid)) {
            $query = SupportRequest::query()->where('id', $ulid);
            if ($lock) {
                $query->lockForUpdate();
            }

            return $query->first();
        }

        $query = SupportRequest::query()->where('reference', $trimmed);
        if ($lock) {
            $query->lockForUpdate();
        }

        $found = $query->first();

        if ($found !== null) {
            return $found;
        }

        $fallback = SupportRequest::query()->where('reference', $ulid);
        if ($lock) {
            $fallback->lockForUpdate();
        }

        return $fallback->first();
    }
}
