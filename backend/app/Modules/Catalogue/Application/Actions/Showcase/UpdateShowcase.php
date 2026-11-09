<?php

namespace App\Modules\Catalogue\Application\Actions\Showcase;

use App\Modules\Catalogue\Application\Projection\RecordCatalogueWork;
use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Application\Support\CatalogueIntention;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\DB;

final class UpdateShowcase
{
    private const COMING_SOON = ['availability' => 'soon'];

    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
        private CatalogueIntention $intentions,
        private CatalogueVisibility $visibility,
        private RecordCatalogueWork $works,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function selection(Account $account, ?string $sessionId, string $selectionId, array $input, ?string $clientKey, string $correlation): array
    {
        return $this->guard->withinIdentityThenCatalog(function () use ($account, $sessionId, $selectionId, $input, $clientKey, $correlation): array {
            $actor = $this->actor->assert($account, $sessionId, 'gerer_vitrine', ['moderator', 'admin']);
            $requested = $this->requestedSelection($input);
            $key = $this->intentions->key('vitrine:selection', $actor->id, $selectionId, $clientKey);
            $fingerprint = $this->intentions->fingerprint(['selection_id' => $selectionId, 'demande' => $requested]);
            $replay = $this->intentions->replay($key, $fingerprint);

            if ($replay !== null) {
                return $replay;
            }

            if (! IdentityUlid::isValid($selectionId)) {
                throw CatalogueConsultationException::absent();
            }

            $selection = DB::table('selection_editoriale')->where('selection_id', $selectionId)->lockForUpdate()->first();

            if ($selection === null) {
                throw CatalogueConsultationException::absent();
            }

            $lines = DB::table('selection_oeuvre')
                ->where('selection_id', $selectionId)
                ->orderBy('oeuvre_id')
                ->lockForUpdate()
                ->get();
            $current = $this->selectionState($selection, $lines->all());

            if (! hash_equals($current['empreinte'], $requested['empreinte'])) {
                throw CatalogueConsultationException::conflict('La sélection a changé depuis la lecture. Relisez-la avant d’enregistrer.');
            }

            $this->guardFixedSelection($selection->code, $requested);
            $this->replaceSelection($selection, $lines->all(), $requested);
            $freshLines = DB::table('selection_oeuvre')->where('selection_id', $selectionId)->orderBy('oeuvre_id')->get();
            $fresh = DB::table('selection_editoriale')->where('selection_id', $selectionId)->first();
            $payload = ['selection' => $this->selectionState($fresh, $freshLines->all())];
            $eventId = $this->intentions->record($key, 'vitrine_modifiee', 'vitrine', $fingerprint, $correlation, $actor->id, $payload);
            $this->works->forTarget($eventId, $correlation, $fingerprint, ['cible' => 'selection', 'id' => $selectionId]);

            return $payload;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function highlight(Account $account, ?string $sessionId, string $workId, array $input, ?string $clientKey, string $correlation): array
    {
        return $this->guard->withinIdentityThenCatalog(function () use ($account, $sessionId, $workId, $input, $clientKey, $correlation): array {
            $actor = $this->actor->assert($account, $sessionId, 'gerer_vitrine', ['moderator', 'admin']);
            $requested = $this->requestedHighlight($input);
            $key = $this->intentions->key('vitrine:oeuvre', $actor->id, $workId, $clientKey);
            $fingerprint = $this->intentions->fingerprint(['oeuvre_id' => $workId, 'demande' => $requested]);
            $replay = $this->intentions->replay($key, $fingerprint);

            if ($replay !== null) {
                return $replay;
            }

            if (! IdentityUlid::isValid($workId)) {
                throw CatalogueConsultationException::absent();
            }

            $work = DB::table('oeuvre')->where('oeuvre_id', $workId)->lockForUpdate()->first();

            if ($work === null) {
                throw CatalogueConsultationException::absent();
            }

            $current = $this->highlightState($work);

            if (! hash_equals($current['empreinte'], $requested['empreinte'])) {
                throw CatalogueConsultationException::conflict('L’œuvre a changé depuis la lecture. Relisez-la avant d’enregistrer.');
            }

            $this->guardBadge($requested['badge']);

            if ($requested['mis_en_avant'] && $this->visibility->isWithdrawn($workId)) {
                throw CatalogueConsultationException::invalidInput('mis_en_avant', 'Une œuvre retirée ne peut pas être mise en avant.');
            }

            DB::table('oeuvre')->where('oeuvre_id', $workId)->update([
                'badge' => $requested['badge'],
                'mis_en_avant' => $requested['mis_en_avant'] ? 1 : 0,
            ]);
            $fresh = DB::table('oeuvre')->where('oeuvre_id', $workId)->first();
            $payload = ['oeuvre' => $this->highlightState($fresh)];
            $eventId = $this->intentions->record($key, 'vitrine_modifiee', 'vitrine', $fingerprint, $correlation, $actor->id, $payload);
            $this->works->forTarget($eventId, $correlation, $fingerprint, ['cible' => 'oeuvre', 'id' => $workId]);

            return $payload;
        });
    }

    /**
     * @param  list<object>  $lines
     * @return array<string, mixed>
     */
    public function selectionState(object $selection, array $lines): array
    {
        $parameters = $this->storedParameters($selection->parametres);
        $works = [];

        foreach ($lines as $line) {
            $works[] = [
                'oeuvre_id' => $line->oeuvre_id,
                'sort_order' => (int) $line->sort_order,
            ];
        }

        usort($works, fn (array $left, array $right): int => $left['oeuvre_id'] <=> $right['oeuvre_id']);
        $state = [
            'id' => $selection->selection_id,
            'code' => $selection->code,
            'libelle' => $selection->libelle,
            'regle_ou_manuelle' => $selection->regle_ou_manuelle,
            'parametres' => $parameters,
            'actif' => (int) $selection->actif === 1,
            'oeuvres' => $works,
        ];
        $state['empreinte'] = $this->intentions->fingerprint([
            'regle_ou_manuelle' => $state['regle_ou_manuelle'],
            'parametres' => $state['parametres'],
            'actif' => $state['actif'],
            'oeuvres' => $state['oeuvres'],
        ]);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function highlightState(object $work): array
    {
        $state = [
            'id' => $work->oeuvre_id,
            'badge' => $work->badge,
            'mis_en_avant' => (int) $work->mis_en_avant === 1,
        ];
        $state['empreinte'] = $this->intentions->fingerprint([
            'badge' => $state['badge'],
            'mis_en_avant' => $state['mis_en_avant'],
        ]);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{empreinte: string, regle_ou_manuelle: string, parametres: array<string, mixed>|null, actif: bool, oeuvres: list<array{oeuvre_id: string, sort_order: int}>}
     */
    private function requestedSelection(array $input): array
    {
        $fingerprint = $input['empreinte'] ?? null;
        $mode = $input['regle_ou_manuelle'] ?? null;
        $active = $input['actif'] ?? null;

        if (! is_string($fingerprint) || ! preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
            throw CatalogueConsultationException::invalidInput('empreinte', 'L’empreinte de l’état lu est absente.');
        }

        if (! is_string($mode) || ! in_array($mode, ['filtre', 'manuelle'], true)) {
            throw CatalogueConsultationException::invalidInput('regle_ou_manuelle', 'Le mode doit être filtre ou manuelle.');
        }

        if (! is_bool($active)) {
            throw CatalogueConsultationException::invalidInput('actif', 'L’activation doit être vraie ou fausse.');
        }

        $parameters = $this->requestedParameters($mode, $input['parametres'] ?? null);
        $works = $this->requestedWorks($mode, $input['oeuvres'] ?? null);

        return [
            'empreinte' => $fingerprint,
            'regle_ou_manuelle' => $mode,
            'parametres' => $parameters,
            'actif' => $active,
            'oeuvres' => $works,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestedParameters(string $mode, mixed $value): ?array
    {
        if ($mode === 'manuelle') {
            if ($value !== null) {
                throw CatalogueConsultationException::invalidInput('parametres', 'Une sélection manuelle n’a pas de paramètres.');
            }

            return null;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw CatalogueConsultationException::invalidInput('parametres', 'Les paramètres du filtre sont absents.');
        }

        $allowed = [
            'texte', 'categorie', 'format', 'langue', 'niveau', 'prix_min', 'prix_max', 'devise',
            'modalite', 'gratuite', 'etat', 'disponibilite', 'badge', 'africain', 'auteur',
            'editeur', 'tri', 'page', 'taille', 'descendance', 'availability',
        ];

        foreach ($value as $key => $item) {
            if (! is_string($key) || ! in_array($key, $allowed, true) || is_array($item)) {
                throw CatalogueConsultationException::invalidInput('parametres', 'Ces paramètres de filtre ne sont pas recevables.');
            }
        }

        return $this->intentions->canonical($value);
    }

    /**
     * @return list<array{oeuvre_id: string, sort_order: int}>
     */
    private function requestedWorks(string $mode, mixed $value): array
    {
        if ($mode === 'filtre') {
            if ($value !== null && $value !== []) {
                throw CatalogueConsultationException::invalidInput('oeuvres', 'Un filtre ne conserve pas d’œuvres choisies.');
            }

            return [];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw CatalogueConsultationException::invalidInput('oeuvres', 'La liste des œuvres est absente.');
        }

        if (count($value) > 200) {
            throw CatalogueConsultationException::invalidInput('oeuvres', 'La sélection dépasse 200 œuvres.');
        }

        $works = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                throw CatalogueConsultationException::invalidInput('oeuvres', 'Une œuvre de la sélection n’est pas recevable.');
            }

            $id = $item['oeuvre_id'] ?? null;
            $order = $item['sort_order'] ?? null;

            if (! is_string($id) || ! IdentityUlid::isValid($id) || ! is_int($order) || $order < 0 || $order > 100000) {
                throw CatalogueConsultationException::invalidInput('oeuvres', 'Une œuvre de la sélection n’est pas recevable.');
            }

            if (isset($works[$id])) {
                throw CatalogueConsultationException::invalidInput('oeuvres', 'Une œuvre est répétée dans la sélection.');
            }

            $works[$id] = ['oeuvre_id' => $id, 'sort_order' => $order];
        }

        $ids = array_keys($works);

        if ($ids !== []) {
            $known = DB::table('oeuvre')->whereIn('oeuvre_id', $ids)->pluck('oeuvre_id')->all();

            if (count($known) !== count($ids)) {
                throw CatalogueConsultationException::invalidInput('oeuvres', 'Une œuvre de la sélection est absente.');
            }
        }

        return array_values($works);
    }

    /**
     * @param  array{empreinte: string, regle_ou_manuelle: string, parametres: array<string, mixed>|null, actif: bool, oeuvres: list<array{oeuvre_id: string, sort_order: int}>}  $requested
     */
    private function guardFixedSelection(string $code, array $requested): void
    {
        if ($code === 'tendances' && ($requested['regle_ou_manuelle'] !== 'manuelle' || $requested['parametres'] !== null)) {
            throw CatalogueConsultationException::invalidInput('regle_ou_manuelle', 'La sélection Tendances reste manuelle.');
        }

        if ($code === 'a_paraitre' && ($requested['regle_ou_manuelle'] !== 'filtre' || $requested['parametres'] !== self::COMING_SOON)) {
            throw CatalogueConsultationException::invalidInput('parametres', 'La sélection À paraître reste le filtre des œuvres à paraître.');
        }
    }

    /**
     * @param  list<object>  $currentLines
     * @param  array{empreinte: string, regle_ou_manuelle: string, parametres: array<string, mixed>|null, actif: bool, oeuvres: list<array{oeuvre_id: string, sort_order: int}>}  $requested
     */
    private function replaceSelection(object $selection, array $currentLines, array $requested): void
    {
        $parameters = $requested['regle_ou_manuelle'] === 'manuelle' ? null : $requested['parametres'];
        DB::table('selection_editoriale')->where('selection_id', $selection->selection_id)->update([
            'regle_ou_manuelle' => $requested['regle_ou_manuelle'],
            'parametres' => $parameters === null ? null : json_encode($parameters, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'actif' => $requested['actif'] ? 1 : 0,
        ]);

        if ($requested['regle_ou_manuelle'] === 'filtre') {
            DB::table('selection_oeuvre')->where('selection_id', $selection->selection_id)->delete();

            return;
        }

        $kept = [];

        foreach ($currentLines as $line) {
            $kept[$line->oeuvre_id] = $line->ajoute_a;
        }

        DB::table('selection_oeuvre')->where('selection_id', $selection->selection_id)->delete();
        $now = now();

        foreach ($requested['oeuvres'] as $work) {
            DB::table('selection_oeuvre')->insert([
                'selection_id' => $selection->selection_id,
                'oeuvre_id' => $work['oeuvre_id'],
                'sort_order' => $work['sort_order'],
                'ajoute_a' => $kept[$work['oeuvre_id']] ?? $now,
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storedParameters(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($value) || array_is_list($value)) {
            return null;
        }

        return $this->intentions->canonical($value);
    }

    private function guardBadge(mixed $badge): void
    {
        if ($badge === null) {
            return;
        }

        $folded = mb_strtolower(trim($badge));

        if (in_array($folded, ['gratuit', 'gratuite', 'gratuité', 'free'], true)) {
            throw CatalogueConsultationException::invalidInput('badge', 'Le badge ne porte pas la gratuité. Elle se lit sur le prix.');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{empreinte: string, badge: ?string, mis_en_avant: bool}
     */
    private function requestedHighlight(array $input): array
    {
        $fingerprint = $input['empreinte'] ?? null;
        $badge = $input['badge'] ?? null;
        $featured = $input['mis_en_avant'] ?? null;

        if (! is_string($fingerprint) || ! preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
            throw CatalogueConsultationException::invalidInput('empreinte', 'L’empreinte de l’état lu est absente.');
        }

        if ($badge !== null && (! is_string($badge) || trim($badge) === '' || mb_strlen($badge) > 80)) {
            throw CatalogueConsultationException::invalidInput('badge', 'Ce badge n’est pas recevable.');
        }

        if (! is_bool($featured)) {
            throw CatalogueConsultationException::invalidInput('mis_en_avant', 'La mise en avant doit être vraie ou fausse.');
        }

        return [
            'empreinte' => $fingerprint,
            'badge' => is_string($badge) ? trim($badge) : null,
            'mis_en_avant' => $featured,
        ];
    }
}
