<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Carbon\CarbonImmutable;
use Pcteckserv\CmsCore\Models\BackupPlan;

class BackupSchedulerService
{
    public function nextRunAt(BackupPlan $plan, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $timezone = $plan->timezone ?: config('cms-backups.default_timezone', config('app.timezone', 'UTC'));
        $from = ($from ?: CarbonImmutable::now($timezone))->setTimezone($timezone);
        [$hour, $minute] = array_map('intval', explode(':', substr((string) $plan->run_at, 0, 5)));

        return match ($plan->frequency) {
            'every_15_minutes' => $from->addMinutes(15 - ($from->minute % 15))->second(0),
            'every_30_minutes' => $from->addMinutes(30 - ($from->minute % 30))->second(0),
            'hourly' => $from->addHour()->minute(0)->second(0),
            'every_2_hours' => $this->nextHourlyInterval($from, 2),
            'every_3_hours' => $this->nextHourlyInterval($from, 3),
            'every_4_hours' => $this->nextHourlyInterval($from, 4),
            'every_6_hours' => $this->nextHourlyInterval($from, 6),
            'every_8_hours' => $this->nextHourlyInterval($from, 8),
            'every_12_hours' => $this->nextHourlyInterval($from, 12),
            'weekly' => $this->nextWeekday($from, [(int) (($plan->weekdays[0] ?? 1))], $hour, $minute),
            'weekdays' => $this->nextWeekday($from, array_map('intval', $plan->weekdays ?: [1, 2, 3, 4, 5]), $hour, $minute),
            'monthly' => $this->nextMonthly($from, $plan->month_day, $hour, $minute),
            default => $this->nextDaily($from, $hour, $minute),
        };
    }

    public function duePlans()
    {
        return BackupPlan::query()
            ->where('enabled', true)
            ->where(function ($query): void {
                $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->get();
    }

    private function nextDaily(CarbonImmutable $from, int $hour, int $minute): CarbonImmutable
    {
        $candidate = $from->setTime($hour, $minute);

        return $candidate->greaterThan($from) ? $candidate : $candidate->addDay();
    }

    private function nextHourlyInterval(CarbonImmutable $from, int $interval): CarbonImmutable
    {
        $hour = $from->hour + ($interval - ($from->hour % $interval));

        return $from->setTime(0, 0)->addHours($hour)->second(0);
    }

    private function nextWeekday(CarbonImmutable $from, array $weekdays, int $hour, int $minute): CarbonImmutable
    {
        for ($i = 0; $i < 14; $i++) {
            $candidate = $from->addDays($i)->setTime($hour, $minute);
            if (in_array((int) $candidate->isoWeekday(), $weekdays, true) && $candidate->greaterThan($from)) {
                return $candidate;
            }
        }

        return $from->addWeek()->setTime($hour, $minute);
    }

    private function nextMonthly(CarbonImmutable $from, ?int $day, int $hour, int $minute): CarbonImmutable
    {
        for ($i = 0; $i < 14; $i++) {
            $month = $from->startOfMonth()->addMonths($i);
            $targetDay = $day ?: $month->endOfMonth()->day;
            $candidate = $month->day(min($targetDay, $month->endOfMonth()->day))->setTime($hour, $minute);
            if ($candidate->greaterThan($from)) {
                return $candidate;
            }
        }

        return $from->addMonth()->setTime($hour, $minute);
    }
}
