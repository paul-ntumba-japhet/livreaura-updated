<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\FiscalProfile;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PayoutAccount;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class SaveVerificationDraft
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CanDepositVerification $canDeposit,
        private GetVerificationDossier $dossierQuery,
    ) {}

    /**
     * @param  array{profil_fiscal: ?array{identifiant_fiscal: ?string, pays_fiscal: string, nom_legal: string}, compte_reversement: ?array{canal: string, prestataire: ?string, titulaire: string, pays: string, devise: string, reference_prestataire: ?string, affichage_masque: string}}  $input
     * @return array<string, mixed>
     */
    public function __invoke(Account $account, string $dossierId, array $input, string $correlationId): array
    {
        return $this->coordination->withinIdentity(function () use ($account, $dossierId, $input, $correlationId): array {
            $dossier = VerificationDossier::query()->where('id', $dossierId)->lockForUpdate()->first();

            if ($dossier === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            $dossier->load('subject');

            if ($dossier->subject === null || ! ($this->canDeposit)($account, $dossier->subject)) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            if (! $dossier->isDraft()) {
                throw new IdentityAccessException(
                    'dossier_fige',
                    'Ce dossier n est plus modifiable.',
                    409,
                );
            }

            if ($input['profil_fiscal'] !== null) {
                $next = (int) FiscalProfile::query()->where('sujet_id', $dossier->sujet_id)->max('version');
                $profile = new FiscalProfile;
                $profile->id = IdentityUlid::make();
                $profile->sujet_id = $dossier->sujet_id;
                $profile->version = $next + 1;
                $profile->identifiant_fiscal = $input['profil_fiscal']['identifiant_fiscal'];
                $profile->pays_fiscal = strtoupper($input['profil_fiscal']['pays_fiscal']);
                $profile->nom_legal = $input['profil_fiscal']['nom_legal'];
                $profile->statut_controle = 'pending';
                $profile->save();
                $dossier->profil_fiscal_id = $profile->id;
            }

            if ($input['compte_reversement'] !== null) {
                $next = (int) PayoutAccount::query()->where('sujet_id', $dossier->sujet_id)->max('version');
                $payout = new PayoutAccount;
                $payout->id = IdentityUlid::make();
                $payout->sujet_id = $dossier->sujet_id;
                $payout->version = $next + 1;
                $payout->canal = $input['compte_reversement']['canal'];
                $payout->prestataire = $input['compte_reversement']['prestataire'];
                $payout->titulaire = $input['compte_reversement']['titulaire'];
                $payout->pays = strtoupper($input['compte_reversement']['pays']);
                $payout->devise = strtoupper($input['compte_reversement']['devise']);
                $payout->reference_prestataire = $input['compte_reversement']['reference_prestataire'];
                $payout->affichage_masque = $input['compte_reversement']['affichage_masque'];
                $payout->statut_controle = 'pending';
                $payout->save();
                $dossier->compte_reversement_id = $payout->id;
            }

            $dossier->save();

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->type = 'brouillon_enregistre';
            $event->date = now();
            $event->nouvel_etat = json_encode([
                'profil_fiscal_id' => $dossier->profil_fiscal_id,
                'compte_reversement_id' => $dossier->compte_reversement_id,
            ], JSON_THROW_ON_ERROR);
            $event->motif = 'Enregistrement du brouillon de verification.';
            $event->origine_systeme = 'A1-11';
            $event->correlation = $correlationId;
            $event->save();

            $dossier->refresh()->load(['subject.organization', 'subject.profile', 'documents', 'controls.requirement', 'fiscalProfile', 'payoutAccount']);

            return [
                'statut' => 'draft',
                'prochaine_action' => 'completer_dossier',
                'dossier' => ($this->dossierQuery)->serialize($dossier),
            ];
        });
    }
}
