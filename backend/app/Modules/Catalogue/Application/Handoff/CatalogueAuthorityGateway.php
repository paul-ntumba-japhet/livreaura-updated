<?php

namespace App\Modules\Catalogue\Application\Handoff;

interface CatalogueAuthorityGateway
{
    /**
     * @param  array{offre_id: string, quantite: int, modalite_commerciale: string, devise: string, prix: string, correlation: string, cle_idempotence: string}  $demand
     * @return array<string, mixed>
     */
    public function requestCart(array $demand): array;

    /**
     * @param  array{offre_id: string, modalite_commerciale: string, devise: string, prix: string, compte_id: string, correlation: string, cle_idempotence: string}  $demand
     * @return array<string, mixed>
     */
    public function requestFreeAcquisition(array $demand): array;

    /**
     * @param  array{compte_id: string, oeuvre_id: string, versions: list<string>, correlation: string, cle_idempotence: string}  $demand
     * @return array<string, mixed>
     */
    public function requestReading(array $demand): array;
}
