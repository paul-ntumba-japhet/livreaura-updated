<?php

namespace App\Modules\IdentityAccess\Domain\Enums;

enum ChallengePurpose: string
{
    case EmailActivation = 'email_activation';
    case PasswordReset = 'password_reset';
    case PhoneVerification = 'phone_verification';
}
