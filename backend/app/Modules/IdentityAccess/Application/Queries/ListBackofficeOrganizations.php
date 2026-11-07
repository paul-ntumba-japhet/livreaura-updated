<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessOrganizationBackoffice;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListBackofficeOrganizations
{
    public function __construct(
        private CanAccessOrganizationBackoffice $canAccess,
    ) {}

    /**
     * @param  array{q: ?string, onglet: string, type_organisation_id: ?string, verification: ?string, page: int, per_page: int}  $filters
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, array $filters): array
    {
        if (! ($this->canAccess)($actor)) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $onglet = in_array($filters['onglet'], ['organisations', 'representation', 'transfert'], true)
            ? $filters['onglet']
            : 'organisations';
        $page = max(1, $filters['page']);
        $perPage = min(50, max(1, $filters['per_page']));

        if ($onglet === 'organisations') {
            [$items, $filteredTotal, $page, $lastPage] = $this->paginateOrganizations($filters, $page, $perPage);
        } else {
            [$items, $filteredTotal, $page, $lastPage] = $this->paginateDemands(
                $onglet === 'transfert' ? 'transfert_principal' : 'representation',
                $filters,
                $page,
                $perPage,
            );
        }

        return [
            'onglet' => $onglet,
            'totaux' => [
                'organisations' => Organization::query()->count(),
                'verifiees' => $this->verifiedCount(),
                'rattachements' => $this->openDemandCount('representation'),
                'transferts' => $this->openDemandCount('transfert_principal'),
            ],
            'types' => OrganizationType::query()
                ->orderBy('libelle')
                ->get()
                ->map(fn (OrganizationType $type): array => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'libelle' => $type->libelle,
                ])
                ->values()
                ->all(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filteredTotal,
                'last_page' => $lastPage,
            ],
            'organisations' => $onglet === 'organisations' ? $items : [],
            'demandes' => $onglet === 'organisations' ? [] : $items,
        ];
    }

    /**
     * @param  array{q: ?string, onglet: string, type_organisation_id: ?string, verification: ?string, page: int, per_page: int}  $filters
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int}
     */
    private function paginateOrganizations(array $filters, int $page, int $perPage): array
    {
        $query = Organization::query()->with([
            'type',
            'memberships' => function ($memberships): void {
                $memberships
                    ->where('principal', 1)
                    ->where('statut', 'active')
                    ->with('account.profile');
            },
            'kycSubject.capabilities',
            'kycSubject.dossiers' => function ($dossiers): void {
                $dossiers
                    ->where('niveau', 'organisation_verifiee')
                    ->orderByDesc('version')
                    ->orderByDesc('cree_a');
            },
        ]);

        $this->applyOrganizationFilters($query, $filters);

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $organizations = $query
            ->orderByDesc('cree_a')
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $rows = $organizations
            ->map(fn (Organization $organization): array => $this->serializeOrganization($organization))
            ->values()
            ->all();

        return [$rows, $filteredTotal, $page, $lastPage];
    }

    /**
     * @param  array{q: ?string, onglet: string, type_organisation_id: ?string, verification: ?string, page: int, per_page: int}  $filters
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int}
     */
    private function paginateDemands(string $type, array $filters, int $page, int $perPage): array
    {
        $query = SupportRequest::query()
            ->with(['requester.profile', 'organization.type'])
            ->where('type', $type)
            ->whereNull('clos_a');

        $typeId = $filters['type_organisation_id'];
        if (is_string($typeId) && $typeId !== '') {
            $query->whereHas('organization', fn ($organization) => $organization->where('type_organisation_id', $typeId));
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
            $query->where(function (Builder $builder) use ($search, $needle): void {
                $builder
                    ->where('id', strtoupper($search))
                    ->orWhere('reference', 'like', $needle)
                    ->orWhereHas('organization', function ($organization) use ($needle): void {
                        $organization->whereRaw('LOWER(raison_sociale) like ?', [$needle]);
                    })
                    ->orWhereHas('requester', function ($requester) use ($needle): void {
                        $requester
                            ->where('email_normalise', 'like', $needle)
                            ->orWhereHas('profile', function ($profile) use ($needle): void {
                                $profile
                                    ->whereRaw('LOWER(prenom) like ?', [$needle])
                                    ->orWhereRaw('LOWER(nom) like ?', [$needle]);
                            });
                    });
            });
        }

        $filteredTotal = (clone $query)->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $demands = $query
            ->orderByDesc('cree_a')
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $rows = $demands
            ->map(function (SupportRequest $demande): array {
                $organization = $demande->organization;
                $requester = $demande->requester;

                return [
                    'id' => $demande->id,
                    'reference' => $demande->reference,
                    'type' => $demande->type,
                    'cree_a' => $demande->cree_a instanceof Carbon
                        ? $demande->cree_a->toIso8601String()
                        : null,
                    'organisation' => $organization instanceof Organization
                        ? [
                            'id' => $organization->id,
                            'raison_sociale' => $organization->raison_sociale,
                            'ville' => $organization->ville,
                            'pays' => $organization->pays,
                            'type' => $organization->type instanceof OrganizationType ? [
                                'id' => $organization->type->id,
                                'code' => $organization->type->code,
                                'libelle' => $organization->type->libelle,
                            ] : null,
                        ]
                        : null,
                    'demandeur' => $requester instanceof Account
                        ? [
                            'id' => $requester->id,
                            'prenom' => $requester->profile?->prenom,
                            'nom' => $requester->profile?->nom,
                            'email_masque' => DestinationMasker::email($requester->email_normalise),
                        ]
                        : null,
                ];
            })
            ->values()
            ->all();

        return [$rows, $filteredTotal, $page, $lastPage];
    }

    /**
     * @param  Builder<Organization>  $query
     * @param  array{q: ?string, onglet: string, type_organisation_id: ?string, verification: ?string, page: int, per_page: int}  $filters
     */
    private function applyOrganizationFilters(Builder $query, array $filters): void
    {
        $typeId = $filters['type_organisation_id'];
        if (is_string($typeId) && $typeId !== '') {
            $query->where('type_organisation_id', $typeId);
        }

        $this->applyVerificationFilter($query, $filters['verification']);

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search === '') {
            return;
        }

        $needle = '%'.addcslashes(mb_strtolower($search), '%_').'%';
        $query->where(function (Builder $builder) use ($search, $needle): void {
            $builder
                ->where('id', strtoupper($search))
                ->orWhereRaw('LOWER(raison_sociale) like ?', [$needle])
                ->orWhereRaw('LOWER(ville) like ?', [$needle])
                ->orWhereHas('memberships', function ($membership) use ($needle): void {
                    $membership
                        ->where('principal', 1)
                        ->where('statut', 'active')
                        ->whereHas('account', function ($account) use ($needle): void {
                            $account
                                ->where('email_normalise', 'like', $needle)
                                ->orWhereHas('profile', function ($profile) use ($needle): void {
                                    $profile
                                        ->whereRaw('LOWER(prenom) like ?', [$needle])
                                        ->orWhereRaw('LOWER(nom) like ?', [$needle]);
                                });
                        });
                });
        });
    }

    /**
     * @param  Builder<Organization>  $query
     */
    private function applyVerificationFilter(Builder $query, ?string $verification): void
    {
        match ($verification) {
            'verifiee' => $query->whereHas('kycSubject.capabilities', function ($capability): void {
                $capability->where('niveau', 'organisation_verifiee')->where('etat', 'valid');
            }),
            'expiree' => $query->whereHas('kycSubject.capabilities', function ($capability): void {
                $capability->where('niveau', 'organisation_verifiee')->where('etat', 'expired');
            }),
            'a_renouveler' => $query->whereHas('kycSubject.capabilities', function ($capability): void {
                $capability->where('niveau', 'organisation_verifiee')->where('etat', 'a_renouveler');
            }),
            'en_cours' => $query
                ->whereHas('kycSubject.dossiers', function ($dossier): void {
                    $dossier->where('niveau', 'organisation_verifiee')->whereIn('statut', ['submitted', 'under_review']);
                })
                ->whereDoesntHave('kycSubject.capabilities', function ($capability): void {
                    $capability->where('niveau', 'organisation_verifiee')->where('etat', 'valid');
                }),
            'non_verifiee' => $query
                ->whereDoesntHave('kycSubject.capabilities', function ($capability): void {
                    $capability
                        ->where('niveau', 'organisation_verifiee')
                        ->whereIn('etat', ['valid', 'expired', 'a_renouveler']);
                })
                ->whereDoesntHave('kycSubject.dossiers', function ($dossier): void {
                    $dossier->where('niveau', 'organisation_verifiee')->whereIn('statut', ['submitted', 'under_review']);
                }),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrganization(Organization $organization): array
    {
        $principal = $organization->memberships
            ->first(fn (OrganizationMembership $membership): bool => $membership->principal && $membership->statut === 'active');
        $account = $principal?->account;
        $subject = $organization->kycSubject;
        $capability = $subject instanceof KycSubject
            ? $subject->capabilities->first(fn (VerificationCapability $item): bool => $item->niveau === 'organisation_verifiee')
            : null;
        $dossier = $subject instanceof KycSubject
            ? $subject->dossiers->first()
            : null;
        $verification = $this->verificationCode($capability, $dossier);
        $confirmed = $verification === 'verifiee';

        return [
            'id' => $organization->id,
            'raison_sociale' => $organization->raison_sociale,
            'ville' => $organization->ville,
            'pays' => $organization->pays,
            'statut_operationnel' => $organization->statut_operationnel,
            'type' => $organization->type instanceof OrganizationType ? [
                'id' => $organization->type->id,
                'code' => $organization->type->code,
                'libelle' => $organization->type->libelle,
            ] : null,
            'representant' => $account instanceof Account
                ? [
                    'id' => $account->id,
                    'prenom' => $account->profile?->prenom,
                    'nom' => $account->profile?->nom,
                ]
                : null,
            'representation' => $account instanceof Account
                ? ($confirmed ? 'confirmee' : 'declaree')
                : null,
            'verification' => $verification,
        ];
    }

    private function verificationCode(?VerificationCapability $capability, ?VerificationDossier $dossier): string
    {
        $etat = $capability?->etat;
        if ($etat === 'valid') {
            return 'verifiee';
        }
        if ($etat === 'expired') {
            return 'expiree';
        }
        if ($etat === 'a_renouveler') {
            return 'a_renouveler';
        }
        if ($etat === 'restreint') {
            return 'restreint';
        }
        if ($etat === 'revoked') {
            return 'revoquee';
        }
        if (in_array($dossier?->statut, ['submitted', 'under_review'], true)) {
            return 'en_cours';
        }

        return 'non_verifiee';
    }

    private function verifiedCount(): int
    {
        return VerificationCapability::query()
            ->where('niveau', 'organisation_verifiee')
            ->where('etat', 'valid')
            ->whereHas('subject', fn ($subject) => $subject->whereNotNull('organisation_id'))
            ->count();
    }

    private function openDemandCount(string $type): int
    {
        return SupportRequest::query()
            ->where('type', $type)
            ->whereNull('clos_a')
            ->count();
    }
}
