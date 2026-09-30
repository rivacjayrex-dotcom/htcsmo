<?php

namespace App\Services;

use App\Models\SupplyInventoryLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InventoryForecastService
{
    /**
     * Build a one-month-ahead demand forecast from the last 24 months of releases.
     */
    public function build(Collection $supplies, ?Carbon $asOf = null): array
    {
        $currentMonth = ($asOf ?? now())->copy()->startOfMonth();
        $targetMonth = $currentMonth->copy()->addMonth();
        $historyStart = $currentMonth->copy()->subMonths(23);

        $logs = SupplyInventoryLog::query()
            ->where('transaction_type', 'release_deduction')
            ->where('created_at', '>=', $historyStart)
            ->orderBy('created_at')
            ->get()
            ->groupBy('supply_id');

        $forecasts = $supplies->map(function ($supply) use ($logs, $currentMonth, $targetMonth, $historyStart) {
            $itemLogs = $logs->get($supply->id, collect());
            $monthlyUsage = [];

            foreach ($itemLogs as $log) {
                $key = $log->created_at->format('Y-m');
                $monthlyUsage[$key] = ($monthlyUsage[$key] ?? 0) + abs(min(0, (int) $log->quantity_change));
            }

            $earliestMonth = $itemLogs->isNotEmpty()
                ? $itemLogs->min('created_at')->copy()->startOfMonth()->max($historyStart)
                : $currentMonth->copy();
            $observedMonths = max(1, $earliestMonth->diffInMonths($currentMonth) + 1);
            $recentMonthCount = min(3, $observedMonths);
            $recentUsage = 0;

            for ($offset = 0; $offset < $recentMonthCount; $offset++) {
                $recentUsage += $monthlyUsage[$currentMonth->copy()->subMonths($offset)->format('Y-m')] ?? 0;
            }

            $recentAverage = $recentUsage / $recentMonthCount;
            $totalUsage = array_sum($monthlyUsage);
            $overallAverage = $totalUsage / $observedMonths;
            $seasonalOccurrences = 0;
            $seasonalTotal = 0;
            $calendarMonthTotals = [];
            $calendarMonthCounts = [];

            for ($offset = 0; $offset < $observedMonths; $offset++) {
                $month = $currentMonth->copy()->subMonths($offset);
                $monthNumber = $month->month;
                $usage = $monthlyUsage[$month->format('Y-m')] ?? 0;
                $calendarMonthTotals[$monthNumber] = ($calendarMonthTotals[$monthNumber] ?? 0) + $usage;
                $calendarMonthCounts[$monthNumber] = ($calendarMonthCounts[$monthNumber] ?? 0) + 1;

                if ($monthNumber === $targetMonth->month) {
                    $seasonalOccurrences++;
                    $seasonalTotal += $usage;
                }
            }

            $seasonalAverage = $seasonalOccurrences > 0 ? $seasonalTotal / $seasonalOccurrences : null;

            $baseForecast = $seasonalAverage !== null
                ? ($seasonalAverage * 0.65) + ($recentAverage * 0.35)
                : ($recentAverage > 0 ? $recentAverage : $overallAverage);
            $forecastDemand = (int) ceil($baseForecast);
            $safetyStock = max((int) $supply->min_stock_level, (int) ceil($baseForecast * 0.25));
            $targetStock = $forecastDemand + $safetyStock;
            $recommendedOrder = max(0, $targetStock - (int) $supply->quantity);
            $seasonalityIndex = $seasonalAverage !== null && $overallAverage > 0
                ? $seasonalAverage / $overallAverage
                : null;

            $calendarMonthAverages = collect($calendarMonthTotals)
                ->map(fn ($total, $month) => $total / $calendarMonthCounts[$month]);
            $peakMonthNumber = $calendarMonthAverages->isNotEmpty()
                ? (int) $calendarMonthAverages->sortDesc()->keys()->first()
                : null;

            $confidence = match (true) {
                $observedMonths >= 12 && $seasonalTotal > 0 => 'high',
                $observedMonths >= 6 || $itemLogs->count() >= 4 => 'medium',
                default => 'low',
            };

            $reason = $this->reason(
                $targetMonth,
                $seasonalityIndex,
                $recentAverage,
                $overallAverage,
                $itemLogs->isNotEmpty()
            );

            return [
                'supply' => $supply,
                'forecast_demand' => $forecastDemand,
                'recommended_order' => $recommendedOrder,
                'target_stock' => $targetStock,
                'safety_stock' => $safetyStock,
                'recent_average' => round($recentAverage, 1),
                'seasonality_percent' => $seasonalityIndex !== null ? (int) round(($seasonalityIndex - 1) * 100) : null,
                'peak_month' => $peakMonthNumber ? Carbon::create(2000, $peakMonthNumber, 1)->format('F') : null,
                'months_observed' => $observedMonths,
                'release_events' => $itemLogs->count(),
                'confidence' => $confidence,
                'reason' => $reason,
                'urgency' => $supply->quantity <= 0 ? 'critical' : ($forecastDemand >= $supply->quantity ? 'buy_now' : 'plan'),
            ];
        });

        $recommendations = $forecasts
            ->filter(fn ($forecast) => $forecast['recommended_order'] > 0)
            ->sortByDesc(fn ($forecast) => match ($forecast['urgency']) {
                'critical' => 300000 + $forecast['recommended_order'],
                'buy_now' => 200000 + $forecast['recommended_order'],
                default => 100000 + $forecast['recommended_order'],
            })
            ->values();

        return [
            'target_month' => $targetMonth,
            'recommendations' => $recommendations,
            'all' => $forecasts,
            'items_with_history' => $forecasts->where('release_events', '>', 0)->count(),
            'release_events' => $forecasts->sum('release_events'),
        ];
    }

    private function reason(
        Carbon $targetMonth,
        ?float $seasonalityIndex,
        float $recentAverage,
        float $overallAverage,
        bool $hasHistory
    ): string {
        if (! $hasHistory) {
            return 'No release history yet; recommendation is based on the configured safety threshold.';
        }

        if ($seasonalityIndex !== null && $seasonalityIndex >= 1.2) {
            return sprintf(
                '%s historically consumes about %d%% more than the monthly average.',
                $targetMonth->format('F'),
                round(($seasonalityIndex - 1) * 100)
            );
        }

        if ($overallAverage > 0 && $recentAverage >= $overallAverage * 1.2) {
            return 'Recent monthly consumption is above this item’s historical average.';
        }

        return 'Suggested from recent release volume plus safety stock.';
    }
}
