<?php

namespace App\Modules\Catalogue\Application\Queries;

final class CatalogueCriteria
{
    public const GENERAL = 'general';

    public const FICHE = 'fiche';

    public const A_PARAITRE = 'a_paraitre';

    public const BIBLIOGRAPHIE = 'bibliographie';

    /**
     * @param  list<string>  $categorieIds
     */
    public function __construct(
        public readonly ?string $texte,
        public readonly array $categorieIds,
        public readonly ?string $formatCode,
        public readonly ?string $langueId,
        public readonly ?string $niveauId,
        public readonly ?string $prixMin,
        public readonly ?string $prixMax,
        public readonly string $devise,
        public readonly ?bool $gratuite,
        public readonly ?string $etat,
        public readonly ?string $disponibilite,
        public readonly ?string $badge,
        public readonly ?bool $africain,
        public readonly ?string $auteurId,
        public readonly ?string $editeurId,
        public readonly ?string $selectionId,
        public readonly string $tri,
        public readonly int $page,
        public readonly int $taille,
        public readonly string $contexte,
    ) {}

    public function constrainsCurrency(): bool
    {
        return $this->prixMin !== null
            || $this->prixMax !== null
            || $this->tri === 'price_asc'
            || $this->tri === 'price_desc';
    }

    public function hasOfferConstraint(): bool
    {
        return $this->formatCode !== null
            || $this->gratuite !== null
            || $this->etat !== null
            || $this->editeurId !== null
            || $this->constrainsCurrency();
    }

    public function allowsSoonWithoutOffer(): bool
    {
        return $this->contexte === self::A_PARAITRE && ! $this->hasOfferConstraint();
    }
}
