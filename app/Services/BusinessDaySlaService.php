<?php

namespace App\Services;

use App\Models\InstitutionalHoliday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BusinessDaySlaService
{
    public const PROCESSING_DAYS = 3;

    public const WARNING_AFTER_DAYS = 2;

    /** @var array<string, true>|null */
    private ?array $holidayDates = null;

    public function clearHolidayCache(): void
    {
        $this->holidayDates = null;
    }

    /**
     * @return array{submitted_at: Carbon, sla_warning_at: Carbon, due_at: Carbon}
     */
    public function milestones(CarbonInterface $submittedAt): array
    {
        $submitted = Carbon::instance($submittedAt)->copy();

        return [
            'submitted_at' => $submitted,
            'sla_warning_at' => $this->addWorkingDays($submitted, self::WARNING_AFTER_DAYS),
            'due_at' => $this->addWorkingDays($submitted, self::PROCESSING_DAYS),
        ];
    }

    public function addWorkingDays(CarbonInterface $start, int $days): Carbon
    {
        $date = Carbon::instance($start)->copy();
        $remaining = max(0, $days);
        $holidays = $remaining > 0 ? $this->holidayDates() : [];

        while ($remaining > 0) {
            $date->addDay();

            if ($this->isWorkingDay($date, $holidays)) {
                $remaining--;
            }
        }

        return $date;
    }

    public function workingDaysElapsed(CarbonInterface $start, ?CarbonInterface $end = null): int
    {
        $cursor = Carbon::instance($start)->copy();
        $finish = $end ? Carbon::instance($end)->copy() : now();

        if ($finish->lessThanOrEqualTo($cursor)) {
            return 0;
        }

        $holidays = $this->holidayDates();
        $elapsed = 0;

        while ($cursor->copy()->addDay()->lessThanOrEqualTo($finish)) {
            $cursor->addDay();

            if ($this->isWorkingDay($cursor, $holidays)) {
                $elapsed++;
            }
        }

        return $elapsed;
    }

    /**
     * @param  array<string, true>|null  $holidayDates
     */
    public function isWorkingDay(CarbonInterface $date, ?array $holidayDates = null): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $holidayDates ??= $this->holidayDates();

        return ! isset($holidayDates[$date->toDateString()]);
    }

    /**
     * @return array<string, true>
     */
    private function holidayDates(): array
    {
        // Shared across rows for this request/job; never persist calendar data
        // between requests, where a holiday may have been edited elsewhere.
        return $this->holidayDates ??= InstitutionalHoliday::query()
            ->pluck('holiday_date')
            ->mapWithKeys(fn ($date) => [Carbon::parse($date)->toDateString() => true])
            ->all();
    }
}
