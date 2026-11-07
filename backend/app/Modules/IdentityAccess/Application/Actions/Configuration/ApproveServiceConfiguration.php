<?php

namespace App\Modules\IdentityAccess\Application\Actions\Configuration;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class ApproveServiceConfiguration
{
    /**
     * @var list<string>
     */
    public const CODES = ['A1_acces', 'A1_delais', 'A1_calendrier', 'A1_regles_suspension'];

    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
        private CurrentServiceConfiguration $current,
    ) {}

    /**
     * @param  array{code: string, valeurs: array<string, mixed>, effet_a: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'configurer_service')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $existingMessage = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

            if ($existingMessage !== null) {
                if ($existingMessage->empreinte_intention !== $intentionFingerprint) {
                    throw new IdentityAccessException(
                        'intention_conflictuelle',
                        'Cette intention entre en conflit avec une demande deja enregistree.',
                        409,
                    );
                }

                $payload = is_array($existingMessage->donnees_minimales) ? $existingMessage->donnees_minimales : [];
                unset($payload['http_status']);

                return $payload;
            }

            $code = $input['code'];
            $values = $this->validatedValues($code, $input['valeurs']);
            $effectAt = $this->parseEffectAt($input['effet_a']);
            $encoded = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $now = now();

            $nextVersion = (int) ServiceConfiguration::query()->where('code', $code)->max('version') + 1;

            $configuration = new ServiceConfiguration;
            $configuration->id = IdentityUlid::make();
            $configuration->code = $code;
            $configuration->version = $nextVersion;
            $configuration->schema_version = 1;
            $configuration->valeurs = $values;
            $configuration->empreinte = hash('sha256', $encoded);
            $configuration->cree_par = $actor->id;
            $configuration->cree_a = $now;
            $configuration->approuve_par = $actor->id;
            $configuration->approuve_a = $now;
            $configuration->effet_a = $effectAt;

            try {
                $configuration->save();
            } catch (QueryException $exception) {
                if ($this->isUniqueEffectViolation($exception)) {
                    throw new IdentityAccessException(
                        'etat_incompatible',
                        'Une version avec le meme effet existe deja pour ce code.',
                        409,
                    );
                }

                throw $exception;
            }

            $payload = [
                'http_status' => 201,
                'statut' => 'approuvee',
                'prochaine_action' => 'effet_planifie',
                'configuration' => $this->serialize($configuration),
                'courante' => $this->isCurrent($configuration),
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'configuration_approuvee',
                operation: 'configurations_service',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $actor->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ServiceConfiguration $configuration): array
    {
        return [
            'id' => $configuration->id,
            'code' => $configuration->code,
            'version' => $configuration->version,
            'schema_version' => $configuration->schema_version,
            'valeurs' => is_array($configuration->valeurs) ? $configuration->valeurs : [],
            'empreinte' => $configuration->empreinte,
            'effet_a' => $configuration->effet_a?->toIso8601String(),
            'approuve_a' => $configuration->approuve_a?->toIso8601String(),
        ];
    }

    private function isCurrent(ServiceConfiguration $configuration): bool
    {
        $current = $this->current->find((string) $configuration->code);

        return $current !== null && $current->id === $configuration->id;
    }

    private function parseEffectAt(string $value): Carbon
    {
        try {
            $effectAt = Carbon::parse($value);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La date d effet est invalide.',
                422,
                ['effet_a' => ['Date invalide.']],
            );
        }

        if ($effectAt->lt(now())) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La date d effet doit etre posterieure ou egale a maintenant.',
                422,
                ['effet_a' => ['Echeance passee.']],
            );
        }

        return $effectAt;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function validatedValues(string $code, array $values): array
    {
        if (! in_array($code, self::CODES, true)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Ce code de configuration n est pas reconnu.',
                422,
                ['code' => ['Code inconnu.']],
            );
        }

        foreach (array_keys($values) as $key) {
            $normalized = strtolower((string) $key);
            if (
                str_contains($normalized, 'password')
                || str_contains($normalized, 'secret')
                || str_contains($normalized, 'token')
                || str_contains($normalized, 'preuve')
            ) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs ne peuvent pas contenir de secret.',
                    422,
                    ['valeurs' => ['Secret interdit.']],
                );
            }
        }

        return match ($code) {
            'A1_acces' => $this->accessValues($values),
            'A1_delais' => $this->delayValues($values),
            'A1_calendrier' => $this->calendarValues($values),
            default => $this->suspensionValues($values),
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, int>
     */
    private function accessValues(array $values): array
    {
        $required = [
            'inactivite_secondes_normal',
            'absolue_secondes_normal',
            'inactivite_secondes_limited',
            'absolue_secondes_limited',
        ];
        $optional = [
            'inactivite_secondes_support_only',
            'absolue_secondes_support_only',
        ];

        $this->assertKnownKeys($values, [...$required, ...$optional]);
        $normalized = [];

        foreach ($required as $key) {
            $normalized[$key] = $this->positiveInt($values, $key);
        }

        foreach ($optional as $key) {
            if (array_key_exists($key, $values)) {
                $normalized[$key] = $this->positiveInt($values, $key);
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function delayValues(array $values): array
    {
        $required = ['activation_heures', 'reset_minutes'];
        $optionalInts = [
            'otp_minutes',
            'essais_defi',
            'renvoi_minimum_secondes',
            'renvoi_maximum_par_heure',
            'prise_heures_ouvre',
            'reponse_heures_ouvre',
        ];

        $this->assertKnownKeys($values, [...$required, ...$optionalInts, 'telephone_active']);
        $normalized = [
            'activation_heures' => $this->positiveInt($values, 'activation_heures'),
            'reset_minutes' => $this->positiveInt($values, 'reset_minutes'),
        ];

        foreach ($optionalInts as $key) {
            if (array_key_exists($key, $values)) {
                $normalized[$key] = $this->positiveInt($values, $key);
            }
        }

        if (array_key_exists('telephone_active', $values)) {
            if (! is_bool($values['telephone_active'])) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs.telephone_active' => ['Booleen attendu.']],
                );
            }

            $normalized['telephone_active'] = $values['telephone_active'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function calendarValues(array $values): array
    {
        $this->assertKnownKeys($values, ['timezone', 'plages', 'jours_non_travailles']);

        $timezone = trim((string) ($values['timezone'] ?? ''));

        try {
            new DateTimeZone($timezone);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Les valeurs de configuration sont invalides.',
                422,
                ['valeurs.timezone' => ['Fuseau invalide.']],
            );
        }

        $slots = $values['plages'] ?? null;

        if (! is_array($slots) || $slots === []) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Les valeurs de configuration sont invalides.',
                422,
                ['valeurs.plages' => ['Au moins une plage est requise.']],
            );
        }

        $normalizedSlots = [];

        foreach ($slots as $index => $slot) {
            if (! is_array($slot)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs.plages' => ['Plage invalide.']],
                );
            }

            $days = $slot['jours'] ?? null;
            $start = (string) ($slot['debut'] ?? '');
            $end = (string) ($slot['fin'] ?? '');

            if (! is_array($days) || $days === [] || ! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end) || $start >= $end) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs.plages.'.$index => ['Plage invalide.']],
                );
            }

            $normalizedDays = [];

            foreach ($days as $day) {
                $dayNumber = (int) $day;
                if ($dayNumber < 1 || $dayNumber > 7) {
                    throw new IdentityAccessException(
                        'saisie_invalide',
                        'Les valeurs de configuration sont invalides.',
                        422,
                        ['valeurs.plages.'.$index.'.jours' => ['Jour invalide.']],
                    );
                }

                $normalizedDays[] = $dayNumber;
            }

            $normalizedSlots[] = [
                'jours' => array_values(array_unique($normalizedDays)),
                'debut' => $start,
                'fin' => $end,
            ];
        }

        $holidays = $values['jours_non_travailles'] ?? [];

        if (! is_array($holidays)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Les valeurs de configuration sont invalides.',
                422,
                ['valeurs.jours_non_travailles' => ['Liste invalide.']],
            );
        }

        $normalizedHolidays = [];

        foreach ($holidays as $holiday) {
            $date = trim((string) $holiday);
            if ($date === '') {
                continue;
            }

            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs.jours_non_travailles' => ['Date invalide.']],
                );
            }

            $normalizedHolidays[] = $date;
        }

        return [
            'timezone' => $timezone,
            'plages' => $normalizedSlots,
            'jours_non_travailles' => $normalizedHolidays,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{regles_autorisees: list<string>}
     */
    private function suspensionValues(array $values): array
    {
        $this->assertKnownKeys($values, ['regles_autorisees']);
        $allowed = ['securite', 'conformite', 'decision_humaine'];
        $rules = $values['regles_autorisees'] ?? null;

        if (! is_array($rules) || $rules === []) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Les valeurs de configuration sont invalides.',
                422,
                ['valeurs.regles_autorisees' => ['Au moins une regle est requise.']],
            );
        }

        $normalized = [];

        foreach ($rules as $rule) {
            $code = trim((string) $rule);
            if (! in_array($code, $allowed, true)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs.regles_autorisees' => ['Regle inconnue.']],
                );
            }

            $normalized[] = $code;
        }

        return ['regles_autorisees' => array_values(array_unique($normalized))];
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $allowed
     */
    private function assertKnownKeys(array $values, array $allowed): void
    {
        foreach (array_keys($values) as $key) {
            if (! in_array((string) $key, $allowed, true)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Les valeurs de configuration sont invalides.',
                    422,
                    ['valeurs' => ['Cle inconnue : '.$key]],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function positiveInt(array $values, string $key): int
    {
        if (! array_key_exists($key, $values) || ! is_numeric($values[$key]) || (int) $values[$key] != $values[$key] || (int) $values[$key] < 1) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Les valeurs de configuration sont invalides.',
                422,
                ['valeurs.'.$key => ['Entier positif attendu.']],
            );
        }

        return (int) $values[$key];
    }

    private function isUniqueEffectViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000';
    }
}
