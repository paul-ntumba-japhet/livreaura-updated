<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence;

use Illuminate\Support\Carbon;

class ServiceDeadlineCalculator
{
    /**
     * @param  array<string, mixed>  $calendar
     * @param  array<string, mixed>  $delays
     * @return array{prise: Carbon, reponse: Carbon}
     */
    public function forNewTask(array $calendar, array $delays, $from = null): array
    {
        $timezone = is_string($calendar['timezone'] ?? null) && $calendar['timezone'] !== ''
            ? $calendar['timezone']
            : 'UTC';
        $start = Carbon::parse($from ?? now())->timezone($timezone);
        $priseHours = (int) ($delays['prise_heures_ouvre'] ?? 8);
        $reponseHours = (int) ($delays['reponse_heures_ouvre'] ?? 40);

        if ($priseHours < 1) {
            $priseHours = 8;
        }

        if ($reponseHours < $priseHours) {
            $reponseHours = max(40, $priseHours);
        }

        $appTimezone = (string) config('app.timezone', 'UTC');

        return [
            'prise' => $this->addOpenMinutes($start, $priseHours * 60, $calendar)->timezone($appTimezone),
            'reponse' => $this->addOpenMinutes($start, $reponseHours * 60, $calendar)->timezone($appTimezone),
        ];
    }

    /**
     * @param  array<string, mixed>  $calendar
     */
    private function addOpenMinutes(Carbon $start, int $minutes, array $calendar): Carbon
    {
        $cursor = $start->copy();
        $remaining = $minutes;
        $guard = 0;

        while ($remaining > 0 && $guard < 400) {
            $guard++;
            $window = $this->currentOrNextWindow($cursor, $calendar);

            if ($window === null) {
                return $cursor->addMinutes($remaining);
            }

            if ($cursor->lt($window['start'])) {
                $cursor = $window['start']->copy();
            }

            $available = (int) $cursor->diffInMinutes($window['end'], false);

            if ($available <= 0) {
                $cursor = $window['end']->copy()->addSecond();

                continue;
            }

            $take = min($remaining, $available);
            $cursor->addMinutes($take);
            $remaining -= $take;
        }

        return $cursor;
    }

    /**
     * @param  array<string, mixed>  $calendar
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function currentOrNextWindow(Carbon $instant, array $calendar): ?array
    {
        $probe = $instant->copy()->second(0)->microsecond(0);

        for ($day = 0; $day < 60; $day++) {
            $dayCursor = $probe->copy()->addDays($day)->startOfDay();
            $date = $dayCursor->toDateString();

            if ($this->isHoliday($date, $calendar)) {
                continue;
            }

            foreach ($this->plages($calendar) as $plage) {
                $jours = array_map('intval', $plage['jours'] ?? []);

                if (! in_array($dayCursor->dayOfWeekIso, $jours, true)) {
                    continue;
                }

                $start = $dayCursor->copy()->addMinutes($this->minutesOf($plage['debut'] ?? '09:00'));
                $end = $dayCursor->copy()->addMinutes($this->minutesOf($plage['fin'] ?? '17:00'));

                if ($end->lte($start)) {
                    continue;
                }

                if ($day === 0 && $instant->gte($end)) {
                    continue;
                }

                if ($day === 0 && $instant->gt($start) && $instant->lt($end)) {
                    return ['start' => $instant->copy(), 'end' => $end];
                }

                if ($instant->lte($start) || $day > 0) {
                    return ['start' => $start, 'end' => $end];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $calendar
     * @return list<array<string, mixed>>
     */
    private function plages(array $calendar): array
    {
        $plages = $calendar['plages'] ?? [];

        if (! is_array($plages) || $plages === []) {
            return [
                ['jours' => [1, 2, 3, 4, 5], 'debut' => '09:00', 'fin' => '17:00'],
            ];
        }

        return array_values(array_filter($plages, 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $calendar
     */
    private function isHoliday(string $date, array $calendar): bool
    {
        $holidays = $calendar['jours_non_travailles'] ?? [];

        return is_array($holidays) && in_array($date, $holidays, true);
    }

    private function minutesOf(string $clock): int
    {
        $parts = explode(':', $clock);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }
}
