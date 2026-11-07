<?php

namespace App\Modules\IdentityAccess\Infrastructure\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordChangedMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre mot de passe Livreaura a été modifié',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.identity-access.password-changed',
            text: 'mail.identity-access.password-changed-text',
        );
    }
}
