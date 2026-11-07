<?php

namespace App\Modules\IdentityAccess\Infrastructure\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerifyEmailMail extends Mailable
{
    public function __construct(
        public string $actionUrl,
        public ?string $expiresAtLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activez votre compte Livreaura',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.identity-access.verify-email',
            text: 'mail.identity-access.verify-email-text',
        );
    }
}
