<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

class ResendVerificationChallenge
{
    public function __construct(private IssueVerificationChallenge $issue) {}

    public function __invoke(string $email): void
    {
        $this->issue->resendByEmail($email);
    }
}
