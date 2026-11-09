<?php

namespace App\Modules\Catalogue\Domain\Rules;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CatalogueVisibility
{
    public function constrainPublicWork(Builder $query, string $workAlias = 'oeuvre'): void
    {
        $query->where($workAlias.'.statut', 'published')
            ->whereNull($workAlias.'.retire_a');

        $this->whereNotBlocked(
            $query,
            'retrait_catalogue',
            function (Builder $line) use ($workAlias): void {
                $line->whereColumn('res.oeuvre_id', $workAlias.'.oeuvre_id');
            },
            function (Builder $instruction) use ($workAlias): void {
                $instruction->whereColumn('i.oeuvre_id', $workAlias.'.oeuvre_id');
            },
        );
    }

    public function constrainVisibleEdition(Builder $query, string $editionAlias, string $workAlias): void
    {
        $this->constrainPublicWork($query, $workAlias);
        $query->where($editionAlias.'.statut', 'active')
            ->where($editionAlias.'.consultable', 1);

        $this->whereNotBlocked(
            $query,
            'retrait_catalogue',
            function (Builder $line) use ($editionAlias, $workAlias): void {
                $line->where(function (Builder $match) use ($editionAlias, $workAlias): void {
                    $match->whereColumn('res.edition_id', $editionAlias.'.edition_id')
                        ->orWhereColumn('res.oeuvre_id', $workAlias.'.oeuvre_id');
                });
            },
            function (Builder $instruction) use ($editionAlias, $workAlias): void {
                $instruction->where(function (Builder $match) use ($editionAlias, $workAlias): void {
                    $match->whereColumn('i.edition_id', $editionAlias.'.edition_id')
                        ->orWhereColumn('i.oeuvre_id', $workAlias.'.oeuvre_id');
                });
            },
        );
    }

    public function constrainVisibleOffer(Builder $query, string $workAlias): void
    {
        $query->from('offre')
            ->join('edition', 'edition.edition_id', '=', 'offre.edition_id')
            ->join('oeuvre as offre_oeuvre', 'offre_oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
            ->join('format_technique', 'format_technique.format_id', '=', 'offre.format_id')
            ->whereColumn('edition.oeuvre_id', $workAlias.'.oeuvre_id')
            ->where('offre.actif', 1)
            ->whereNull('offre.retire_a')
            ->where('format_technique.actif', 1)
            ->where('offre.modalite_commerciale', 'achat');

        $this->constrainVisibleEdition($query, 'edition', 'offre_oeuvre');
        $this->whereOfferNotBlocked($query, 'retrait_catalogue');
        $this->whereOfferNotBlocked($query, 'arret_ventes');
    }

    public function constrainVisibleAuthor(Builder $query, string $authorAlias = 'auteur'): void
    {
        $query->where($authorAlias.'.statut_publication', 'published')
            ->where($authorAlias.'.actif', 1);
    }

    public function constrainPublicReview(Builder $query, string $workId): void
    {
        $query->where('avis.statut', 'published');

        $this->whereNotBlocked(
            $query,
            'masquer_avis',
            function (Builder $line) use ($workId): void {
                $line->where(function (Builder $match) use ($workId): void {
                    $match->whereColumn('res.avis_id', 'avis.avis_id')
                        ->orWhere('res.oeuvre_id', $workId);
                });
            },
            function (Builder $instruction) use ($workId): void {
                $instruction->where('i.oeuvre_id', $workId);
            },
        );
    }

    public function isWithdrawn(string $workId): bool
    {
        $retired = DB::table('oeuvre')
            ->where('oeuvre_id', $workId)
            ->whereNotNull('retire_a')
            ->exists();

        if ($retired) {
            return true;
        }

        $query = DB::table('oeuvre')->where('oeuvre.oeuvre_id', $workId);
        $this->whereNotBlocked(
            $query,
            'retrait_catalogue',
            function (Builder $line) use ($workId): void {
                $line->where('res.oeuvre_id', $workId);
            },
            function (Builder $instruction) use ($workId): void {
                $instruction->where('i.oeuvre_id', $workId);
            },
        );

        return ! $query->exists();
    }

    public function constrainPublicMedia(Builder $query, string $workId): void
    {
        $query->where('media_catalogue.actif', 1)
            ->whereNull('media_catalogue.retire_a')
            ->where(function (Builder $attachment): void {
                $attachment->where(function (Builder $workMedia): void {
                    $workMedia->whereNotNull('media_catalogue.oeuvre_id')
                        ->whereNull('media_catalogue.edition_id');
                })->orWhere(function (Builder $editionMedia): void {
                    $editionMedia->whereNull('media_catalogue.oeuvre_id')
                        ->whereNotNull('media_catalogue.edition_id');
                });
            });

        $this->whereNotBlocked(
            $query,
            'masquer_media_public',
            function (Builder $line) use ($workId): void {
                $line->where(function (Builder $match) use ($workId): void {
                    $match->whereColumn('res.media_catalogue_id', 'media_catalogue.media_catalogue_id')
                        ->orWhere('res.oeuvre_id', $workId)
                        ->orWhereColumn('res.edition_id', 'media_catalogue.edition_id');
                });
            },
            function (Builder $instruction) use ($workId): void {
                $instruction->where(function (Builder $match) use ($workId): void {
                    $match->where('i.oeuvre_id', $workId)
                        ->orWhereColumn('i.edition_id', 'media_catalogue.edition_id');
                });
            },
        );
    }

    private function whereOfferNotBlocked(Builder $query, string $effect): void
    {
        $this->whereNotBlocked(
            $query,
            $effect,
            function (Builder $line): void {
                $line->where(function (Builder $match): void {
                    $match->whereColumn('res.offre_id', 'offre.offre_id')
                        ->orWhereColumn('res.edition_id', 'edition.edition_id')
                        ->orWhereColumn('res.oeuvre_id', 'offre_oeuvre.oeuvre_id');
                });
            },
            function (Builder $instruction): void {
                $instruction->where(function (Builder $match): void {
                    $match->whereColumn('i.offre_id', 'offre.offre_id')
                        ->orWhereColumn('i.edition_id', 'edition.edition_id')
                        ->orWhereColumn('i.oeuvre_id', 'offre_oeuvre.oeuvre_id');
                });
            },
        );
    }

    /**
     * @param  Closure(Builder): void  $matchResolution
     * @param  Closure(Builder): void  $matchInstruction
     */
    private function whereNotBlocked(
        Builder $query,
        string $effect,
        Closure $matchResolution,
        Closure $matchInstruction,
    ): void {
        $query->whereNotExists(function (Builder $restriction) use ($effect, $matchResolution, $matchInstruction): void {
            $restriction->selectRaw('1')
                ->from('restrictions_conformite as r')
                ->whereRaw('r.debut <= UTC_TIMESTAMP(6)')
                ->where(function (Builder $window): void {
                    $window->whereNull('r.fin')
                        ->orWhereRaw('UTC_TIMESTAMP(6) < r.fin');
                })
                ->whereNotExists(function (Builder $lift): void {
                    $lift->selectRaw('1')
                        ->from('levees_restrictions as l')
                        ->whereColumn('l.restriction_id', 'r.id')
                        ->whereRaw('l.effet_a <= UTC_TIMESTAMP(6)');
                })
                ->where(function (Builder $cause) use ($effect, $matchResolution, $matchInstruction): void {
                    $cause->whereExists(function (Builder $line) use ($effect, $matchResolution): void {
                        $line->selectRaw('1')
                            ->from('lignes_ordres_retrait as ligne')
                            ->join('resolutions_cibles as res', 'res.id', '=', 'ligne.resolution_id')
                            ->join('portees_decisions_mesures as portee', 'portee.id', '=', 'ligne.portee_id')
                            ->whereColumn('ligne.id', 'r.ligne_creatrice_id')
                            ->where('portee.effet', $effect);
                        $matchResolution($line);
                    })->orWhereExists(function (Builder $instruction) use ($effect, $matchInstruction): void {
                        $instruction->selectRaw('1')
                            ->from('instructions_retrait_publication as i')
                            ->whereColumn('i.id', 'r.instruction_publication_id')
                            ->where('i.effet', $effect);
                        $matchInstruction($instruction);
                    });
                });
        });
    }
}
