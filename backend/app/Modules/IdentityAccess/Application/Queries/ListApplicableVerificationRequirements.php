<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;
use Illuminate\Support\Collection;

class ListApplicableVerificationRequirements
{
    /**
     * @return list<VerificationRequirement>
     */
    public function __invoke(KycSubject $subject, string $niveau, string $country): array
    {
        $typeId = null;
        $subjectType = 'particulier';

        if ($subject->isOrganization()) {
            $subjectType = 'organisation';
            $organization = Organization::query()->where('id', $subject->organisation_id)->first();
            $typeId = $organization?->type_organisation_id;
        }

        return $this->matching($subjectType, $niveau, $country, $typeId);
    }

    /**
     * @return list<VerificationRequirement>
     */
    public function forOrganizationType(?string $typeId, string $country): array
    {
        return $this->matching('organisation', 'organisation_verifiee', $country, $typeId);
    }

    /**
     * @return list<VerificationRequirement>
     */
    public function forPersonalCommercial(string $country): array
    {
        return $this->matching('particulier', 'partenaire_commercial', $country, null);
    }

    /**
     * @param  list<VerificationRequirement>  $requirements
     * @return list<array<string, mixed>>
     */
    public function serializeMany(array $requirements): array
    {
        return array_map(fn (VerificationRequirement $requirement): array => $this->serialize($requirement), $requirements);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(VerificationRequirement $requirement): array
    {
        return [
            'id' => $requirement->id,
            'code' => $requirement->code,
            'version_regles' => $requirement->version_regles,
            'volet' => $requirement->volet,
            'nature_preuve' => $requirement->nature_preuve,
            'obligatoire' => (bool) $requirement->obligatoire,
            'autorise_non_applicable' => (bool) $requirement->autorise_non_applicable,
        ];
    }

    /**
     * @return list<VerificationRequirement>
     */
    private function matching(string $subjectType, string $niveau, string $country, ?string $typeId): array
    {
        $country = strtoupper($country);
        $rows = $this->queryRows($subjectType, $niveau, $country, $typeId);

        if ($rows->isEmpty() && $country !== 'CD') {
            $rows = $this->queryRows($subjectType, $niveau, 'CD', $typeId);
        }

        $selected = $rows
            ->groupBy('code')
            ->map(function (Collection $versions) use ($typeId): ?VerificationRequirement {
                $typed = is_string($typeId) && $typeId !== ''
                    ? $versions->firstWhere('type_organisation_id', $typeId)
                    : null;

                return $typed ?? $versions->firstWhere('type_organisation_id', null) ?? $versions->first();
            })
            ->filter()
            ->values();

        return $selected
            ->sortBy(fn (VerificationRequirement $requirement): string => $this->sortKey($requirement->code))
            ->values()
            ->all();
    }

    private function queryRows(string $subjectType, string $niveau, string $country, ?string $typeId): Collection
    {
        return VerificationRequirement::query()
            ->where('type_sujet', $subjectType)
            ->where('pays', $country)
            ->where('niveau', $niveau)
            ->where('date_effet', '<=', now())
            ->where(function ($query) use ($typeId): void {
                $query->whereNull('type_organisation_id');
                if (is_string($typeId) && $typeId !== '') {
                    $query->orWhere('type_organisation_id', $typeId);
                }
            })
            ->orderByDesc('date_effet')
            ->get();
    }

    private function sortKey(string $code): string
    {
        if (str_starts_with($code, 'existence_')) {
            return '1-'.$code;
        }
        if (str_starts_with($code, 'identite_')) {
            return '2-'.$code;
        }
        if (str_starts_with($code, 'mandat_')) {
            return '3-'.$code;
        }

        return '9-'.$code;
    }
}
