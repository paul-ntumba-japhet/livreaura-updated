<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class RecordVerificationControl
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IsIndependentReviewer $independent,
        private GetBackofficeVerificationDossier $reviewQuery,
    ) {}

    /**
     * @param  array{controle_id: string, resultat: string, observation: ?string, justification_non_applicable: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(Account $account, string $dossierId, array $input, string $correlationId): array
    {
        return $this->coordination->withinIdentity(function () use ($account, $dossierId, $input, $correlationId): array {
            if (! ($this->hasPermission)($account, 'controler_kyc')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $dossier = VerificationDossier::query()
                ->with(['subject', 'serviceTask', 'decision', 'controls.requirement'])
                ->where('id', $dossierId)
                ->lockForUpdate()
                ->first();

            if ($dossier === null || $dossier->serviceTask === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            $task = ServiceTask::query()->where('id', $dossier->serviceTask->id)->lockForUpdate()->first();
            $control = VerificationControl::query()
                ->with('requirement')
                ->where('id', $input['controle_id'])
                ->where('dossier_id', $dossier->id)
                ->lockForUpdate()
                ->first();

            if ($control === null) {
                throw new IdentityAccessException('controle_inconnu', 'Ce controle n est pas visible.', 404);
            }

            if ($dossier->decision !== null) {
                throw new IdentityAccessException(
                    'dossier_decide',
                    'Une decision est deja enregistree pour cette version.',
                    409,
                );
            }

            if ($dossier->statut !== 'under_review' || $task === null || $task->etat_travail !== 'en_cours') {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Les controles ne peuvent etre consignes qu en examen.',
                    409,
                );
            }

            if (! ($this->independent)($account, $dossier) || $task->responsable_id !== $account->id) {
                throw new IdentityAccessException(
                    'conflit_interet',
                    'Ce dossier est lie a ce compte. Une reaffectation independante est necessaire.',
                    409,
                );
            }

            $result = $input['resultat'];
            $observation = $this->nullableText($input['observation'] ?? null);
            $justification = $this->nullableText($input['justification_non_applicable'] ?? null);

            if ($result === 'not_applicable') {
                if ($control->requirement === null || ! $control->requirement->autorise_non_applicable) {
                    throw new IdentityAccessException(
                        'non_applicable_interdit',
                        'Cette exigence ne peut pas etre marquee non applicable.',
                        422,
                        ['resultat' => ['Cette exigence ne peut pas etre marquee non applicable.']],
                    );
                }
                if ($justification === null) {
                    throw new IdentityAccessException(
                        'saisie_invalide',
                        'Une justification est requise pour un controle non applicable.',
                        422,
                        ['justification_non_applicable' => ['Indiquez la justification.']],
                    );
                }
            }

            if ($result === 'failed' && $observation === null) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Une observation est requise pour un controle non satisfait.',
                    422,
                    ['observation' => ['Indiquez l observation.']],
                );
            }

            $now = now();
            $previous = $control->resultat;
            $control->resultat = $result;
            $control->observation = $observation;
            $control->justification_non_applicable = $result === 'not_applicable' ? $justification : null;
            $control->controle_a = $now;
            $control->controleur_id = $account->id;
            $control->origine_systeme = null;
            $control->save();

            if ($task->premiere_reponse_a === null) {
                $task->premiere_reponse_a = $now;
                $task->save();
            }

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->controle_id = $control->id;
            $event->type = 'controle';
            $event->date = $now;
            $event->ancien_etat = json_encode(['resultat' => $previous], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode(['resultat' => $result], JSON_THROW_ON_ERROR);
            $event->motif = 'Resultat de controle consigne.';
            $event->auteur_compte_id = $account->id;
            $event->correlation = $correlationId;
            $event->save();

            $fresh = $dossier->fresh([
                'subject.organization',
                'subject.profile',
                'documents',
                'controls.requirement',
                'fiscalProfile',
                'payoutAccount',
                'decision',
                'serviceTask',
            ]);

            return [
                'statut' => $fresh->statut,
                'prochaine_action' => 'controler',
                ...($this->reviewQuery)->serialize($account, $fresh),
            ];
        });
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
