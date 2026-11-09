<?php

namespace App\Modules\Catalogue\Domain;

use RuntimeException;

final class CatalogueConsultationException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $fields
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly array $fields = [],
    ) {
        parent::__construct($message);
    }

    public static function absent(): self
    {
        return new self('ressource_absente', 'Cette ressource est absente.', 404);
    }

    public static function unavailable(string $message): self
    {
        return new self('service_indisponible', $message, 503);
    }

    public static function invalid(string $field, string $message): self
    {
        return new self(
            'critere_invalide',
            'Certains critères ne peuvent pas être appliqués.',
            422,
            [$field => [$message]],
        );
    }

    public static function unauthenticated(): self
    {
        return new self('session_invalide', 'La session n’est plus recevable.', 401);
    }

    public static function forbidden(string $code, string $message): self
    {
        return new self($code, $message, 403);
    }

    public static function conflict(string $message): self
    {
        return new self('conflit_etat', $message, 409);
    }

    public static function invalidInput(string $field, string $message): self
    {
        return new self(
            'saisie_invalide',
            'Certaines informations ne peuvent pas être enregistrées.',
            422,
            [$field => [$message]],
        );
    }
}
