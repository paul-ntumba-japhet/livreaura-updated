<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportDocumentGrant;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuthorizeAppealDocument
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{compte_id: string, facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string, debut: string, fin: string, motif: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $demandKey,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $demandKey,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (
                ! ($this->hasPermission)($actor, 'traiter_support')
                || ! ($this->hasPermission)($actor, 'autoriser_recours')
            ) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $existingMessage = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

            if ($existingMessage !== null) {
                if ($existingMessage->empreinte_intention !== $intentionFingerprint) {
                    throw new IdentityAccessException(
                        'intention_conflictuelle',
                        'Cette intention entre en conflit avec une demande deja enregistree.',
                        409,
                    );
                }

                $payload = is_array($existingMessage->donnees_minimales) ? $existingMessage->donnees_minimales : [];
                unset($payload['http_status']);

                return $payload;
            }

            $document = $this->exactlyOneDocument($input);
            $startsAt = $this->parseInstant($input['debut'], 'debut');
            $endsAt = $this->parseInstant($input['fin'], 'fin');

            if ($endsAt->lte($startsAt)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'La fin du grant doit etre posterieure au debut.',
                    422,
                    ['fin' => ['Echeance anterieure ou egale au debut.']],
                );
            }

            $ids = collect([$actor->id, $input['compte_id']])->unique()->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $demande = $this->lockDemand($demandKey);

            if ($demande === null || $demande->type !== 'recours_suspension') {
                throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
            }

            if ($demande->clos_a !== null) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette demande n est plus ouverte.',
                    409,
                );
            }

            if ($demande->identite_etat !== 'verifiee') {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Le dossier n est pas encore verifie.',
                    409,
                );
            }

            $target = Account::query()->where('id', $input['compte_id'])->lockForUpdate()->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($actor->id === $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas autoriser ce document.',
                    409,
                );
            }

            if ($demande->compte_concerne_id !== $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette demande ne concerne pas ce compte.',
                    409,
                );
            }

            if ($target->statut !== AccountStatus::Suspended) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Un grant documentaire n est possible que pour un compte suspendu.',
                    409,
                );
            }

            $session = AuthenticationSession::query()
                ->where('compte_id', $target->id)
                ->where('demande_support_id', $demande->id)
                ->where('portee', SessionAccessScope::SupportOnly)
                ->where('statut', SessionStatus::Active)
                ->whereNotNull('autorise_par')
                ->orderByDesc('ouverte_a')
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Une session de recours valide est requise avant d autoriser un document.',
                    409,
                );
            }

            $this->assertDocumentVisibleTo($target->id, $document);

            $now = now();
            $grant = new SupportDocumentGrant;
            $grant->id = IdentityUlid::make();
            $grant->demande_support_id = $demande->id;
            $grant->compte_id = $target->id;
            $grant->facture_vente_id = $document['facture_vente_id'];
            $grant->facture_commission_id = $document['facture_commission_id'];
            $grant->avoir_id = $document['avoir_id'];
            $grant->recu_paiement_id = $document['recu_paiement_id'];
            $grant->debut = $startsAt;
            $grant->fin = $endsAt;
            $grant->autorise_par = $actor->id;
            $grant->motif = $input['motif'];
            $grant->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'acces_document_modifie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'grant_id' => $grant->id,
                'demande_id' => $demande->id,
                'document' => $this->documentPointer($document),
                'debut' => $startsAt->toIso8601String(),
                'fin' => $endsAt->toIso8601String(),
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->correlation = $correlationId;
            $event->save();

            $notice = new SupportExchange;
            $notice->id = IdentityUlid::make();
            $notice->demande_id = $demande->id;
            $notice->auteur_compte_id = $actor->id;
            $notice->origine = 'agent';
            $notice->visibilite = 'demandeur';
            $notice->contenu_prive = 'Un document commercial a ete autorise pour consultation pendant la session de recours. Le telechargement reste controle a chaque usage.';
            $notice->date = $now;
            $notice->canal = 'backoffice';
            $notice->save();

            $payload = [
                'http_status' => 201,
                'statut' => 'grant_autorise',
                'prochaine_action' => 'consultation_documentaire',
                'demande' => [
                    'id' => $demande->id,
                    'reference' => $demande->reference,
                ],
                'acces' => [
                    'id' => $grant->id,
                    'debut' => $startsAt->toIso8601String(),
                    'fin' => $endsAt->toIso8601String(),
                    'document' => $this->documentPointer($document),
                ],
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'acces_document_modifie',
                operation: 'A1-26',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
                demandeId: $demande->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    /**
     * @param  array{facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string}  $input
     * @return array{facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string}
     */
    private function exactlyOneDocument(array $input): array
    {
        $keys = ['facture_vente_id', 'facture_commission_id', 'avoir_id', 'recu_paiement_id'];
        $selected = [];

        foreach ($keys as $key) {
            $value = isset($input[$key]) ? strtoupper(trim((string) $input[$key])) : '';
            if ($value === '') {
                continue;
            }
            if (! IdentityUlid::isValid($value)) {
                throw new IdentityAccessException('document_inconnu', 'Ce document n est pas visible.', 404);
            }
            $selected[$key] = $value;
        }

        if (count($selected) !== 1) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Exactement un document commercial doit etre indique.',
                422,
                ['document' => ['Exactement une reference documentaire est requise.']],
            );
        }

        return [
            'facture_vente_id' => $selected['facture_vente_id'] ?? null,
            'facture_commission_id' => $selected['facture_commission_id'] ?? null,
            'avoir_id' => $selected['avoir_id'] ?? null,
            'recu_paiement_id' => $selected['recu_paiement_id'] ?? null,
        ];
    }

    /**
     * @param  array{facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string}  $document
     */
    private function assertDocumentVisibleTo(string $accountId, array $document): void
    {
        if ($document['facture_vente_id'] !== null) {
            $this->assertSaleInvoiceOwned($accountId, $document['facture_vente_id']);

            return;
        }

        if ($document['facture_commission_id'] !== null) {
            $this->assertCommissionInvoiceOwned($accountId, $document['facture_commission_id']);

            return;
        }

        if ($document['avoir_id'] !== null) {
            $this->assertCreditNoteOwned($accountId, $document['avoir_id']);

            return;
        }

        $this->assertReceiptOwned($accountId, (string) $document['recu_paiement_id']);
    }

    private function assertSaleInvoiceOwned(string $accountId, string $invoiceId): void
    {
        $row = DB::table('factures_vente')->where('id', $invoiceId)->first();
        if ($row === null) {
            throw new IdentityAccessException('document_inconnu', 'Ce document n est pas visible.', 404);
        }

        if (! $this->snapMentionsAccount($row->identite_client_snap ?? null, $accountId)
            && ! $this->snapMentionsAccount($row->identite_vendeur_snap ?? null, $accountId)) {
            throw new IdentityAccessException(
                'document_ineligible',
                'Ce document n appartient pas au titulaire verifie.',
                409,
            );
        }
    }

    private function assertCommissionInvoiceOwned(string $accountId, string $invoiceId): void
    {
        $row = DB::table('factures_commission')->where('id', $invoiceId)->first();
        if ($row === null) {
            throw new IdentityAccessException('document_inconnu', 'Ce document n est pas visible.', 404);
        }

        if (! $this->snapMentionsAccount($row->identite_vendeur_snap ?? null, $accountId)) {
            throw new IdentityAccessException(
                'document_ineligible',
                'La commission n est autorisable que pour le vendeur represente.',
                409,
            );
        }
    }

    private function assertCreditNoteOwned(string $accountId, string $creditId): void
    {
        $row = DB::table('avoirs')->where('id', $creditId)->first();
        if ($row === null) {
            throw new IdentityAccessException('document_inconnu', 'Ce document n est pas visible.', 404);
        }

        if (is_string($row->facture_vente_id) && $row->facture_vente_id !== '') {
            $this->assertSaleInvoiceOwned($accountId, $row->facture_vente_id);

            return;
        }

        if (is_string($row->facture_commission_id) && $row->facture_commission_id !== '') {
            $this->assertCommissionInvoiceOwned($accountId, $row->facture_commission_id);

            return;
        }

        throw new IdentityAccessException(
            'document_ineligible',
            'Ce document n appartient pas au titulaire verifie.',
            409,
        );
    }

    private function assertReceiptOwned(string $accountId, string $receiptId): void
    {
        $row = DB::table('recus_paiement')->where('id', $receiptId)->first();
        if ($row === null) {
            throw new IdentityAccessException('document_inconnu', 'Ce document n est pas visible.', 404);
        }

        $payment = DB::table('paiements')->where('id', $row->paiement_id)->first();
        $owner = is_object($payment) ? (string) ($payment->compte_id ?? $payment->payeur_compte_id ?? '') : '';

        if ($owner !== $accountId) {
            throw new IdentityAccessException(
                'document_ineligible',
                'Ce document n appartient pas au titulaire verifie.',
                409,
            );
        }
    }

    private function snapMentionsAccount(mixed $snap, string $accountId): bool
    {
        if (! is_string($snap) || $snap === '') {
            return false;
        }

        $decoded = json_decode($snap, true);
        if (! is_array($decoded)) {
            return false;
        }

        return strtoupper((string) ($decoded['compte_id'] ?? '')) === $accountId;
    }

    /**
     * @param  array{facture_vente_id: ?string, facture_commission_id: ?string, avoir_id: ?string, recu_paiement_id: ?string}  $document
     * @return array{type: string, id: string}
     */
    private function documentPointer(array $document): array
    {
        foreach (['facture_vente' => 'facture_vente_id', 'facture_commission' => 'facture_commission_id', 'avoir' => 'avoir_id', 'recu_paiement' => 'recu_paiement_id'] as $type => $key) {
            if ($document[$key] !== null) {
                return ['type' => $type, 'id' => $document[$key]];
            }
        }

        throw new IdentityAccessException('saisie_invalide', 'Exactement un document commercial doit etre indique.', 422);
    }

    private function parseInstant(string $value, string $field): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La periode du grant est invalide.',
                422,
                [$field => ['Date invalide.']],
            );
        }
    }

    private function lockDemand(string $demandKey): ?SupportRequest
    {
        $key = strtoupper($demandKey);

        if (IdentityUlid::isValid($key)) {
            return SupportRequest::query()->where('id', $key)->lockForUpdate()->first();
        }

        return SupportRequest::query()->where('reference', $demandKey)->lockForUpdate()->first();
    }
}
