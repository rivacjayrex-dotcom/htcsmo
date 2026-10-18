<?php

namespace App\Console\Commands;

use App\Models\Requisition;
use App\Services\DeadlineNotificationService;
use Illuminate\Console\Command;

class CheckDeadlineSla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smo:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active requisitions and alert stakeholders when the 3-working-day SLA is nearing or overdue';

    /**
     * Execute the console command.
     */
    public function handle(DeadlineNotificationService $deadlineNotifications)
    {
        $this->info('Scanning active requisitions against the 3-working-day SLA...');

        $atRiskRequisitions = Requisition::with(['user', 'items'])
            ->deadlineRisks()
            ->get();

        $alertsCount = 0;

        foreach ($atRiskRequisitions as $requisition) {
            $alertsCount += $deadlineNotifications->notify($requisition)['created'];
        }

        $this->info("Completed. Sent {$alertsCount} alert notification(s).");

        return 0;
    }
}
