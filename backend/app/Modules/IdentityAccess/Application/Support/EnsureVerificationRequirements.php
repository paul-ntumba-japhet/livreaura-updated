<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;

class EnsureVerificationRequirements
{
    public function __invoke(): void
    {
        $now = now()->subDay();
        $country = 'CD';
        $types = OrganizationType::query()->get()->keyBy('code');
        $existing = VerificationRequirement::query()
            ->get(['code', 'pays', 'type_organisation_id', 'nature_preuve']);

        foreach ($this->personalDefinitions() as $definition) {
            $this->ensureRow($existing, $definition, $country, null, $now);
        }

        foreach ($this->organizationCommonDefinitions() as $definition) {
            $this->ensureRow($existing, $definition, $country, null, $now);
        }

        foreach ($this->organizationExistenceDefinitions() as $typeCode => $definition) {
            $type = $types->get($typeCode);
            if ($type === null) {
                continue;
            }

            $this->ensureRow($existing, $definition, $country, $type->id, $now);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, VerificationRequirement>  $existing
     * @param  array{code: string, type_sujet: string, niveau: string, volet: string, nature_preuve: string}  $definition
     */
    private function ensureRow($existing, array $definition, string $country, ?string $typeId, $now): void
    {
        $match = $existing->first(function (VerificationRequirement $row) use ($definition, $country, $typeId): bool {
            return $row->code === $definition['code']
                && $row->pays === $country
                && $row->type_organisation_id === $typeId;
        });

        if ($match !== null) {
            if ($match->nature_preuve !== $definition['nature_preuve']) {
                VerificationRequirement::query()->where('code', $match->code)->where('pays', $country)->where(function ($query) use ($typeId): void {
                    if ($typeId === null) {
                        $query->whereNull('type_organisation_id');
                    } else {
                        $query->where('type_organisation_id', $typeId);
                    }
                })->update(['nature_preuve' => $definition['nature_preuve']]);
            }

            return;
        }

        $requirement = new VerificationRequirement;
        $requirement->id = IdentityUlid::make();
        $requirement->code = $definition['code'];
        $requirement->version_regles = '1.0-'.$country;
        $requirement->type_sujet = $definition['type_sujet'];
        $requirement->type_organisation_id = $typeId;
        $requirement->pays = $country;
        $requirement->niveau = $definition['niveau'];
        $requirement->volet = $definition['volet'];
        $requirement->nature_preuve = $definition['nature_preuve'];
        $requirement->obligatoire = 1;
        $requirement->autorise_non_applicable = 0;
        $requirement->date_effet = $now;
        $requirement->save();

        $existing->push($requirement);
    }

    /**
     * @return list<array{code: string, type_sujet: string, niveau: string, volet: string, nature_preuve: string}>
     */
    private function personalDefinitions(): array
    {
        return [
            [
                'code' => 'identite_personne',
                'type_sujet' => 'particulier',
                'niveau' => 'partenaire_commercial',
                'volet' => 'identite',
                'nature_preuve' => 'Pièce d’identité de la personne.',
            ],
            [
                'code' => 'profil_fiscal',
                'type_sujet' => 'particulier',
                'niveau' => 'partenaire_commercial',
                'volet' => 'fiscal',
                'nature_preuve' => 'Profil fiscal du titulaire.',
            ],
            [
                'code' => 'compte_reversement',
                'type_sujet' => 'particulier',
                'niveau' => 'partenaire_commercial',
                'volet' => 'reversement',
                'nature_preuve' => 'Compte de reversement du titulaire.',
            ],
        ];
    }

    /**
     * @return list<array{code: string, type_sujet: string, niveau: string, volet: string, nature_preuve: string}>
     */
    private function organizationCommonDefinitions(): array
    {
        return [
            [
                'code' => 'identite_representant',
                'type_sujet' => 'organisation',
                'niveau' => 'organisation_verifiee',
                'volet' => 'identite',
                'nature_preuve' => 'Pièce d’identité du représentant déclarant.',
            ],
            [
                'code' => 'mandat_representant',
                'type_sujet' => 'organisation',
                'niveau' => 'organisation_verifiee',
                'volet' => 'mandat',
                'nature_preuve' => 'Mandat ou preuve de pouvoir de représenter l’organisation.',
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, type_sujet: string, niveau: string, volet: string, nature_preuve: string}>
     */
    private function organizationExistenceDefinitions(): array
    {
        $base = [
            'type_sujet' => 'organisation',
            'niveau' => 'organisation_verifiee',
            'volet' => 'identite',
        ];

        return [
            'ecole' => $base + [
                'code' => 'existence_ecole',
                'nature_preuve' => 'Document officiel attestant l’existence de l’école.',
            ],
            'universite' => $base + [
                'code' => 'existence_universite',
                'nature_preuve' => 'Document officiel attestant l’existence de l’université.',
            ],
            'bibliotheque' => $base + [
                'code' => 'existence_bibliotheque',
                'nature_preuve' => 'Document officiel attestant l’existence de la bibliothèque.',
            ],
            'editeur' => $base + [
                'code' => 'existence_editeur',
                'nature_preuve' => 'Document officiel attestant l’existence de la maison d’édition.',
            ],
            'librairie' => $base + [
                'code' => 'existence_librairie',
                'nature_preuve' => 'Document officiel attestant l’existence de la librairie.',
            ],
            'association' => $base + [
                'code' => 'existence_association',
                'nature_preuve' => 'Document officiel attestant l’existence de l’association.',
            ],
        ];
    }
}
