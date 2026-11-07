<?php

namespace App\Modules\IdentityAccess\Application\Actions\Consent;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ConsentEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;

class RecordConsentResponse
{
    public const SOURCE = 'espace_compte';

    public const CANAL = 'web';

    public function __construct(private CoordinationGuard $coordination) {}

    public function __invoke(Account $account, string $textVersionId, string $action, ?string $nature = null): Consent
    {
        return $this->coordination->withinIdentity(function () use ($account, $textVersionId, $action, $nature): Consent {
            Account::query()->where('id', $account->id)->lockForUpdate()->first();

            $text = LegalTextVersion::query()
                ->where('id', $textVersionId)
                ->lockForUpdate()
                ->first();

            if ($text === null || $text->publie_a === null || $text->publie_a->isFuture()) {
                throw new IdentityAccessException(
                    'texte_inconnu',
                    'Ce texte legal n est pas applicable.',
                    422,
                    ['texte_version_id' => ['Texte legal inconnu ou non publie.']],
                );
            }

            return $action === 'retrait'
                ? $this->withdraw($account->id, $text)
                : $this->accept($account->id, $text, $nature);
        });
    }

    private function accept(string $accountId, LegalTextVersion $text, ?string $nature): Consent
    {
        $expectedNature = $text->requis_pour_activation
            ? ['acceptation', 'prise_connaissance']
            : ['consentement_optionnel'];

        if (! is_string($nature) || ! in_array($nature, $expectedNature, true)) {
            throw new IdentityAccessException(
                'nature_incompatible',
                'La nature de la reponse ne correspond pas a ce texte.',
                422,
                ['nature' => ['Nature incompatible avec ce texte.']],
            );
        }

        $existing = Consent::query()
            ->where('compte_id', $accountId)
            ->where('texte_version_id', $text->id)
            ->lockForUpdate()
            ->first();

        $now = now();

        if ($existing === null) {
            $consent = new Consent;
            $consent->id = IdentityUlid::make();
            $consent->compte_id = $accountId;
            $consent->texte_version_id = $text->id;
            $consent->nature = $nature;
            $consent->accepte_a = $now;
            $consent->source = self::SOURCE;
            $consent->canal = self::CANAL;
            $consent->save();

            $this->recordEvent($consent->id, 'acceptation', $now);

            return $consent;
        }

        if ($existing->nature !== $nature) {
            throw new IdentityAccessException(
                'nature_incompatible',
                'Une reponse existe deja pour cette version avec une autre nature.',
                409,
                ['nature' => ['Nature conflictuelle pour cette version.']],
            );
        }

        if ($existing->retire_a === null) {
            return $existing;
        }

        $existing->retire_a = null;
        $existing->save();
        $this->recordEvent($existing->id, 'reacceptation', $now);

        return $existing;
    }

    private function withdraw(string $accountId, LegalTextVersion $text): Consent
    {
        $existing = Consent::query()
            ->where('compte_id', $accountId)
            ->where('texte_version_id', $text->id)
            ->lockForUpdate()
            ->first();

        if ($existing === null) {
            throw new IdentityAccessException(
                'consentement_absent',
                'Aucune preuve n existe pour ce texte.',
                422,
                ['texte_version_id' => ['Consentement introuvable.']],
            );
        }

        if ($existing->nature !== 'consentement_optionnel' || $text->requis_pour_activation) {
            throw new IdentityAccessException(
                'retrait_non_autorise',
                'Le refus d un texte requis bloque seulement la fonction conditionnee ; le compte n est pas suspendu.',
                422,
                ['texte_version_id' => ['Retrait interdit pour un texte requis.']],
            );
        }

        if ($existing->retire_a !== null) {
            return $existing;
        }

        $now = now();
        $existing->retire_a = $now;
        $existing->save();
        $this->recordEvent($existing->id, 'retrait', $now);

        return $existing;
    }

    private function recordEvent(string $consentId, string $type, $at): void
    {
        $event = new ConsentEvent;
        $event->id = IdentityUlid::make();
        $event->consentement_id = $consentId;
        $event->type = $type;
        $event->date = $at;
        $event->source = self::SOURCE;
        $event->canal = self::CANAL;
        $event->save();
    }
}
