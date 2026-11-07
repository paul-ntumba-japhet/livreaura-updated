<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use Illuminate\Support\Facades\Auth;

class ForgetTechnicalSession
{
    public function __construct(private SessionIdentityBridge $sessions) {}

    public function __invoke(): void
    {
        $this->sessions->bind(null);
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();
    }
}
