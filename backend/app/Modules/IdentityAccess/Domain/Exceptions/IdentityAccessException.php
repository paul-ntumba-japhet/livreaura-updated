<?php

namespace App\Modules\IdentityAccess\Domain\Exceptions;

use RuntimeException;

class IdentityAccessException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $fields
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $fields = [],
    ) {
        parent::__construct($message);
    }
}
