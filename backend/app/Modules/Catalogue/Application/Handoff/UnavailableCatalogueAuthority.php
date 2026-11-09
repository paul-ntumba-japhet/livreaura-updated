<?php

namespace App\Modules\Catalogue\Application\Handoff;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;

final class UnavailableCatalogueAuthority implements CatalogueAuthorityGateway
{
    public function requestCart(array $demand): array
    {
        throw CatalogueConsultationException::unavailable('La demande de panier ne peut pas être transmise.');
    }

    public function requestFreeAcquisition(array $demand): array
    {
        throw CatalogueConsultationException::unavailable('La demande d’obtention gratuite ne peut pas être transmise.');
    }

    public function requestReading(array $demand): array
    {
        throw CatalogueConsultationException::unavailable('La demande de lecture ne peut pas être transmise.');
    }
}
