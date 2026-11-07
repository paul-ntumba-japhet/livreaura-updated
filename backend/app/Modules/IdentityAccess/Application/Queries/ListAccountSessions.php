<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Presentation\Http\Resources\SessionResource;

class ListAccountSessions
{
    public function __construct(private SessionIdentityBridge $sessions) {}

    /**
     * @return array{sessions: list<array<string, mixed>>}
     */
    public function __invoke(Account $account): array
    {
        $currentId = $this->sessions->currentId();
        $rows = AuthenticationSession::query()
            ->where('compte_id', $account->id)
            ->orderByDesc('ouverte_a')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (AuthenticationSession $session): array => SessionResource::toArray($session, $currentId))
            ->values()
            ->all();

        usort($rows, function (array $left, array $right): int {
            if (($left['courante'] ?? false) !== ($right['courante'] ?? false)) {
                return ($left['courante'] ?? false) ? -1 : 1;
            }

            return strcmp((string) ($right['ouverte_a'] ?? ''), (string) ($left['ouverte_a'] ?? ''));
        });

        return ['sessions' => $rows];
    }
}
