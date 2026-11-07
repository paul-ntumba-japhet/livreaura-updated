<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class GetAuthenticatedAccount
{
    public function __construct(
        private SessionIdentityBridge $sessions,
        private ListAccountConsentState $consents,
        private ListAuthorPathState $authorPath,
        private ListAccountOrganizations $organizations,
        private ListAccountVerificationDossiers $verifications,
        private ListInternalPermissions $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account): array
    {
        $account->load(['profile', 'accountRoles.role']);
        $session = $this->sessions->current();

        return [
            'compte' => [
                'id' => $account->id,
                'statut' => $account->statut->value,
                'email_masque' => DestinationMasker::email($account->email_normalise),
                'locale' => $account->locale,
            ],
            'profil' => $account->profile === null ? null : [
                'prenom' => $account->profile->prenom,
                'nom' => $account->profile->nom,
                'nom_plume' => $account->profile->nom_plume,
                'pays' => $account->profile->pays,
                'ville' => $account->profile->ville,
            ],
            'roles' => $account->accountRoles
                ->where('statut', 'active')
                ->map(fn ($assignment): ?string => $assignment->role instanceof Role ? $assignment->role->code : null)
                ->filter()
                ->values()
                ->all(),
            'parcours_inscription' => $this->registrationPathOf($account),
            'session' => $session === null ? null : [
                'id' => $session->id,
                'portee' => $session->portee->value,
                'expire_absolue_a' => $session->expire_absolue_a?->toIso8601String(),
                'expire_inactivite_a' => $session->expire_inactivite_a?->toIso8601String(),
            ],
            ...($this->consents)($account),
            ...($this->authorPath)($account),
            ...($this->organizations)($account),
            ...($this->verifications)($account),
            'permissions_internes' => ($this->permissions)($account),
            'recours' => $this->appealOf($session),
        ];
    }

    private function registrationPathOf(Account $account): ?string
    {
        $event = AccountEvent::query()
            ->where('compte_sujet_id', $account->id)
            ->where('type', 'compte_cree')
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        if ($event === null || ! is_string($event->nouvel_etat) || $event->nouvel_etat === '') {
            return null;
        }

        $decoded = json_decode($event->nouvel_etat, true);
        $path = is_array($decoded) ? ($decoded['parcours'] ?? null) : null;

        return in_array($path, ['lecteur', 'auteur', 'representant'], true) ? $path : null;
    }

    /**
     * @param  AuthenticationSession|null  $session
     * @return array{reference: string, type: string, identite_etat: string}|null
     */
    private function appealOf(?AuthenticationSession $session): ?array
    {
        if ($session === null || $session->demande_support_id === null) {
            return null;
        }

        $demande = SupportRequest::query()->where('id', $session->demande_support_id)->first();

        if ($demande === null) {
            return null;
        }

        return [
            'reference' => $demande->reference,
            'type' => $demande->type,
            'identite_etat' => $demande->identite_etat,
        ];
    }
}
