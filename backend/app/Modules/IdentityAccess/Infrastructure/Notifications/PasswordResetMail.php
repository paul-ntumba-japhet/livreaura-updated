<?php

namespace App\Modules\IdentityAccess\Infrastructure\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetMail extends Mailable
{
    public function __construct(
        public string $actionUrl,
        public ?string $expiresAtLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation du mot de passe Livreaura',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.identity-access.password-reset',
            text: 'mail.identity-access.password-reset-text',
        );
    }
}
