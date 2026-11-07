<?php

namespace App\Modules\IdentityAccess\Domain\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
