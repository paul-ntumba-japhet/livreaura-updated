<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Actions\Messaging\RetryTransmission;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionAttempt;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListBackofficeSupervision
{
    private const CANALS = ['email', 'sms', 'interne'];

    private const STATES = ['queued', 'processing', 'retry_wait', 'sent', 'acked', 'failed', 'cancelled'];

    private const TABS = ['supervision', 'journal', 'reprises'];

    public function __construct(
        private HasInternalPermission $hasPermission,
        private RetryTransmission $retry,
    ) {}

    /**
     * @param  array{q: ?string, onglet: string, canal: ?string, etat: ?string, jour: string, page: int, per_page: int}  $filters
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, array $filters): array
    {
        if (! ($this->hasPermission)($actor, 'reprendre_transmissions')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $tab = in_array($filters['onglet'], self::TABS, true) ? $filters['onglet'] : 'supervision';
        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        $payload = [
            'onglet' => $tab,
            'totaux' => $this->totals(),
            'journal_recent' => $this->recentJournal(),
            'capacites' => [
                'reprendre' => true,
            ],
            'transmissions' => [],
            'evenements' => [],
            'reprises' => [],
        ];

        if ($tab === 'journal') {
            [$rows, $pagination] = $this->paginate($this->journalEntries($filters['q']), $page, $perPage);
            $payload['evenements'] = $rows;
            $payload['pagination'] = $pagination;

            return $payload;
        }

        if ($tab === 'reprises') {
            [$rows, $pagination] = $this->paginate($this->retryEntries($filters['q']), $page, $perPage);
            $payload['reprises'] = $rows;
            $payload['pagination'] = $pagination;

            return $payload;
        }

        $query = MessageTransmission::query()
            ->with([
                'message',
                'attempts' => function ($attempts): void {
                    $attempts->orderByDesc('numero');
                },
            ]);
        $this->applyTransmissionFilters($query, $filters);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $rows = $query
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (MessageTransmission $transmission): array => $this->serializeTransmission($transmission))
            ->values()
            ->all();

        $payload['transmissions'] = $rows;
        $payload['pagination'] = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $filteredTotal,
            'last_page' => $lastPage,
        ];

        return $payload;
    }

    /**
     * @return array<string, int>
     */
    private function totals(): array
    {
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        return [
            'echecs' => MessageTransmission::query()->where('etat', 'failed')->count(),
            'attente_accuse' => MessageTransmission::query()->where('etat', 'sent')->count(),
            'messages_en_attente' => MessageTransmission::query()
                ->whereIn('etat', ['queued', 'processing', 'retry_wait'])
                ->count(),
            'evenements_aujourdhui' => AccountEvent::query()->whereBetween('date', [$start, $end])->count()
                + VerificationEvent::query()->whereBetween('date', [$start, $end])->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentJournal(): array
    {
        return $this->journalEntries(null)->take(5)->values()->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function journalEntries(?string $search): Collection
    {
        $limit = 100;
        $accountEvents = AccountEvent::query()
            ->with(['subjectAccount.profile', 'author.profile'])
            ->when($this->searchNeedle($search) !== null, function (Builder $query) use ($search): void {
                $this->applyAccountEventSearch($query, (string) $search);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AccountEvent $event): array => $this->serializeAccountEvent($event));

        $kycEvents = VerificationEvent::query()
            ->with(['dossier', 'author.profile'])
            ->when($this->searchNeedle($search) !== null, function (Builder $query) use ($search): void {
                $this->applyVerificationEventSearch($query, (string) $search);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (VerificationEvent $event): array => $this->serializeVerificationEvent($event));

        return $accountEvents
            ->concat($kycEvents)
            ->sortByDesc(fn (array $row): string => ($row['date'] ?? '').($row['id'] ?? ''))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function retryEntries(?string $search): Collection
    {
        $query = OutgoingMessage::query()
            ->where('type', 'reprise_transmission')
            ->orderByDesc('cree_a')
            ->orderByDesc('id')
            ->limit(100);

        $needle = $this->searchNeedle($search);
        if ($needle !== null) {
            $query->where(function (Builder $builder) use ($search, $needle): void {
                $builder
                    ->where('id', strtoupper((string) $search))
                    ->orWhereRaw('LOWER(correlation) like ?', [$needle])
                    ->orWhereRaw('LOWER(type) like ?', [$needle]);
            });
        }

        return $query
            ->get()
            ->map(fn (OutgoingMessage $message): array => $this->serializeRetry($message))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{0: list<array<string, mixed>>, 1: array{page: int, per_page: int, total: int, last_page: int}}
     */
    private function paginate(Collection $rows, int $page, int $perPage): array
    {
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        return [
            $rows->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @param  array{q: ?string, canal: ?string, etat: ?string, jour: string}  $filters
     */
    private function applyTransmissionFilters(Builder $query, array $filters): void
    {
        $canal = $filters['canal'];
        if (is_string($canal) && in_array($canal, self::CANALS, true)) {
            $query->where('canal', $canal);
        }

        $etat = $filters['etat'];
        if (is_string($etat) && in_array($etat, self::STATES, true)) {
            $query->where('etat', $etat);
        }

        if (($filters['jour'] ?? 'aujourdhui') !== 'tous') {
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->endOfDay();
            $query->where(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->whereBetween('envoye_a', [$start, $end])
                    ->orWhereBetween('prochaine_tentative_a', [$start, $end])
                    ->orWhereHas('message', fn ($message) => $message->whereBetween('cree_a', [$start, $end]));
            });
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search === '') {
            return;
        }

        $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
        $suffix = $this->trackingSuffix($search);

        $query->where(function (Builder $builder) use ($search, $needle, $suffix): void {
            $builder
                ->where('id', strtoupper($search))
                ->orWhereRaw('LOWER(destination_masquee) like ?', [$needle])
                ->orWhereHas('message', function ($message) use ($needle): void {
                    $message
                        ->whereRaw('LOWER(type) like ?', [$needle])
                        ->orWhereRaw('LOWER(operation) like ?', [$needle]);
                });

            if ($suffix !== null) {
                $builder->orWhere('id', 'like', '%'.$suffix);
            }
        });
    }

    private function applyAccountEventSearch(Builder $query, string $search): void
    {
        $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
        $suffix = $this->trackingSuffix($search);
        $fullNameSql = $this->fullNameSql();

        $query->where(function (Builder $builder) use ($search, $needle, $suffix, $fullNameSql): void {
            $builder
                ->where('id', strtoupper($search))
                ->orWhereRaw('LOWER(type) like ?', [$needle])
                ->orWhereHas('subjectAccount', function ($account) use ($needle, $fullNameSql): void {
                    $account->whereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                        $profile
                            ->whereRaw('LOWER(prenom) like ?', [$needle])
                            ->orWhereRaw('LOWER(nom) like ?', [$needle])
                            ->orWhereRaw($fullNameSql, [$needle]);
                    });
                })
                ->orWhereHas('author', function ($account) use ($needle, $fullNameSql): void {
                    $account->whereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                        $profile
                            ->whereRaw('LOWER(prenom) like ?', [$needle])
                            ->orWhereRaw('LOWER(nom) like ?', [$needle])
                            ->orWhereRaw($fullNameSql, [$needle]);
                    });
                });

            if ($suffix !== null) {
                $builder->orWhere('compte_sujet_id', 'like', '%'.$suffix);
            }
        });
    }

    private function applyVerificationEventSearch(Builder $query, string $search): void
    {
        $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
        $fullNameSql = $this->fullNameSql();

        $query->where(function (Builder $builder) use ($search, $needle, $fullNameSql): void {
            $builder
                ->where('id', strtoupper($search))
                ->orWhereRaw('LOWER(type) like ?', [$needle])
                ->orWhereHas('dossier', function ($dossier) use ($needle): void {
                    $dossier->whereRaw('LOWER(numero) like ?', [$needle]);
                })
                ->orWhereHas('author', function ($account) use ($needle, $fullNameSql): void {
                    $account->whereHas('profile', function ($profile) use ($needle, $fullNameSql): void {
                        $profile
                            ->whereRaw('LOWER(prenom) like ?', [$needle])
                            ->orWhereRaw('LOWER(nom) like ?', [$needle])
                            ->orWhereRaw($fullNameSql, [$needle]);
                    });
                });
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransmission(MessageTransmission $transmission): array
    {
        $row = $this->retry->serialize($transmission);
        $last = $transmission->attempts
            ->sortByDesc(fn (TransmissionAttempt $attempt): int => (int) $attempt->numero)
            ->first();

        $row['reference'] = $this->tracking('TX', $transmission->id);
        $row['derniere_tentative_a'] = $last?->termine_a?->toIso8601String()
            ?? $last?->commence_a?->toIso8601String()
            ?? $transmission->envoye_a?->toIso8601String();
        $row['eligible_reprise'] = in_array($transmission->etat, ['failed', 'retry_wait'], true);
        $row['tentatives'] = $transmission->attempts
            ->sortByDesc(fn (TransmissionAttempt $attempt): int => (int) $attempt->numero)
            ->take(5)
            ->values()
            ->map(fn (TransmissionAttempt $attempt): array => [
                'numero' => (int) $attempt->numero,
                'resultat' => $attempt->resultat,
                'commence_a' => $attempt->commence_a?->toIso8601String(),
                'termine_a' => $attempt->termine_a?->toIso8601String(),
                'code_erreur_masque' => $attempt->code_erreur_masque,
            ])
            ->all();

        unset($row['destination_chiffree'], $row['contenu_chiffre_temporaire']);

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAccountEvent(AccountEvent $event): array
    {
        return [
            'id' => $event->id,
            'source' => 'compte',
            'type' => $event->type,
            'date' => $event->date?->toIso8601String(),
            'auteur' => $this->person($event->author),
            'systeme' => $event->auteur_compte_id === null,
            'reference' => $this->tracking('CPT', (string) $event->compte_sujet_id),
            'sujet' => $this->person($event->subjectAccount),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVerificationEvent(VerificationEvent $event): array
    {
        $numero = $event->dossier?->numero;

        return [
            'id' => $event->id,
            'source' => 'kyc',
            'type' => $event->type,
            'date' => $event->date?->toIso8601String(),
            'auteur' => $this->person($event->author),
            'systeme' => $event->auteur_compte_id === null,
            'reference' => is_string($numero) && $numero !== '' ? $numero : $this->tracking('KYC', (string) $event->dossier_id),
            'sujet' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRetry(OutgoingMessage $message): array
    {
        $payload = is_array($message->donnees_minimales) ? $message->donnees_minimales : [];
        $transmission = is_array($payload['transmission'] ?? null) ? $payload['transmission'] : [];
        $transmissionId = is_string($transmission['id'] ?? null) ? $transmission['id'] : null;

        return [
            'id' => $message->id,
            'type' => $message->type,
            'date' => $message->cree_a?->toIso8601String(),
            'transmission_id' => $transmissionId,
            'etat' => is_string($transmission['etat'] ?? null) ? $transmission['etat'] : null,
            'canal' => is_string($transmission['canal'] ?? null) ? $transmission['canal'] : null,
            'destination_masquee' => is_string($transmission['destination_masquee'] ?? null) ? $transmission['destination_masquee'] : null,
            'reference' => $transmissionId ? $this->tracking('TX', $transmissionId) : $this->tracking('TX', $message->id),
        ];
    }

    /**
     * @return array{id: string, prenom: ?string, nom: ?string}|null
     */
    private function person(?Account $account): ?array
    {
        if (! $account instanceof Account) {
            return null;
        }

        return [
            'id' => $account->id,
            'prenom' => $account->profile?->prenom,
            'nom' => $account->profile?->nom,
        ];
    }

    private function tracking(string $prefix, string $id): string
    {
        return $prefix.'-'.strtoupper(substr($id, -4));
    }

    private function trackingSuffix(string $search): ?string
    {
        if (preg_match('/^(?:TX|CPT|KYC)-([A-Za-z0-9]{4})$/', strtoupper(trim($search)), $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9]{4}$/', trim($search)) === 1) {
            return strtoupper(trim($search));
        }

        return null;
    }

    private function searchNeedle(?string $search): ?string
    {
        $trimmed = trim((string) $search);
        if ($trimmed === '') {
            return null;
        }

        return '%'.addcslashes(mb_strtolower($trimmed), '%_').'%';
    }

    private function fullNameSql(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(prenom || ' ' || nom) like ?"
            : "LOWER(CONCAT(prenom, ' ', nom)) like ?";
    }
}
