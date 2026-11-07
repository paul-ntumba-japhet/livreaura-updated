<?php

namespace App\Modules\IdentityAccess\Domain\Enums;

enum RegistrationPath: string
{
    case Reader = 'lecteur';
    case Author = 'auteur';
    case Representative = 'representant';
}
