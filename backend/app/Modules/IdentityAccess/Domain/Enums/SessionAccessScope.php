<?php

namespace App\Modules\IdentityAccess\Domain\Enums;

enum SessionAccessScope: string
{
    case Limited = 'limited';
    case Normal = 'normal';
    case SupportOnly = 'support_only';
}
