<?php

namespace App\Modules\IdentityAccess\Application\Actions\Tasks;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;

class MonitorOverdueServiceTasks
{
    public function __construct(private CoordinationGuard $coordination) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $now = now();
        $ids = ServiceTask::query()
            ->where('etat_travail', '!=', 'terminee')
            ->where(function ($query) use ($now): void {
                $query->where('echeance_prise_a', '<', $now)
                    ->orWhere('echeance_reponse_a', '<', $now);
            })
            ->orderBy('echeance_reponse_a')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $escalated = [];

        foreach ($ids as $id) {
            $changed = $this->coordination->withinIdentity(function () use ($id, $now): bool {
                $task = ServiceTask::query()->where('id', $id)->lockForUpdate()->first();

                if ($task === null || $task->etat_travail === 'terminee') {
                    return false;
                }

                $already = ServiceTaskEvent::query()
                    ->where('tache_id', $task->id)
                    ->where('type', 'escalade')
                    ->exists();

                if ($already) {
                    return false;
                }

                $fonction = $task->dossier_kyc_id !== null ? 'kyc' : 'support';
                $substitute = null;

                if ($task->responsable_id !== null) {
                    $substitute = ServiceSubstitute::query()
                        ->where('titulaire_id', $task->responsable_id)
                        ->where('fonction', $fonction)
                        ->whereNull('annule_a')
                        ->where('debut_a', '<=', $now)
                        ->where('fin_a', '>', $now)
                        ->first();
                }

                $motif = 'Echeance de service depassee.';

                if ($substitute !== null) {
                    $motif .= ' Une suppleance est en vigueur ; le suppleant n a pas ete affecte automatiquement.';
                }

                $event = new ServiceTaskEvent;
                $event->id = IdentityUlid::make();
                $event->tache_id = $task->id;
                $event->type = 'escalade';
                $event->date = $now;
                $event->origine_systeme = 'a1:monitor-service-tasks';
                $event->ancien_etat = $task->etat_travail;
                $event->nouvel_etat = $task->etat_travail;
                $event->motif = $motif;
                $event->correlation = IdentityUlid::make();
                $event->save();

                if ($task->priorite !== 'urgente') {
                    $task->priorite = 'urgente';
                    $task->motif_priorite = 'Echeance de service depassee.';
                    $task->save();
                }

                return true;
            });

            if ($changed) {
                $escalated[] = (string) $id;
            }
        }

        return $escalated;
    }
}
