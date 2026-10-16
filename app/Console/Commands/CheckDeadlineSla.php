<?php

namespace App\Console\Commands;

use App\Models\Requisition;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
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
    protected $description = 'Scan approved requisitions and send alert notifications for items nearing or exceeding the 3-day SLA';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notifications)
    {
        $this->info('Scanning requisitions against 3-day SLA...');

        // Find all SMO users
        $smoUsers = User::where('role', 'smo')->where('account_status', User::ACCOUNT_ACTIVE)->get();

        if ($smoUsers->isEmpty()) {
            $this->warn('No SMO users found in the system.');

            return 0;
        }

        // Target requisitions that are approved and ready for SMO fulfillment
        $approvedRequisitions = Requisition::with(['user', 'items'])
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('request_type', 'minor')
                        ->where('status', 'approved_vp');
                })->orWhere(function ($sub) {
                    $sub->where('request_type', 'major')
                        ->where('status', 'approved_president');
                });
            })
            ->get();

        $alertsCount = 0;

        foreach ($approvedRequisitions as $req) {
            $daysElapsed = Carbon::parse($req->stage_started_at ?? $req->updated_at)->diffInDays(Carbon::now());
            $itemName = $req->items->first()->item_name ?? 'Supplies';

            // Check if 3 days or more (CRITICAL OVERDUE)
            if ($daysElapsed >= 3) {
                foreach ($smoUsers as $smo) {
                    $notification = $notifications->send($smo, 'deadline.overdue', 'Overdue: 3-Day SLA Exceeded',
                        "Req #{$req->id} ({$itemName}) requested by ".($req->user?->name ?? 'an unavailable user')." has exceeded the 3-day processing limit ({$daysElapsed} days elapsed).", [
                            'icon' => 'alert-triangle', 'type' => 'danger', 'action_type' => 'approval_queue',
                            'action_id' => $req->id,
                            'dedupe_key' => "deadline:{$req->id}:overdue:".Carbon::today()->toDateString(),
                        ]);
                    if ($notification?->wasRecentlyCreated) {
                        $alertsCount++;
                    }
                }
            }
            // Check if 2 days elapsed (WARNING - NEARING DEADLINE)
            elseif ($daysElapsed >= 2) {
                foreach ($smoUsers as $smo) {
                    $notification = $notifications->send($smo, 'deadline.nearing', 'Near Deadline: 3-Day SLA Approaching',
                        "Req #{$req->id} ({$itemName}) has reached Day 2 of the 3-day release allowance. Please prepare for fulfillment.", [
                            'icon' => 'clock', 'type' => 'warning', 'action_type' => 'approval_queue',
                            'action_id' => $req->id,
                            'dedupe_key' => "deadline:{$req->id}:nearing:".Carbon::today()->toDateString(),
                        ]);
                    if ($notification?->wasRecentlyCreated) {
                        $alertsCount++;
                    }
                }
            }
        }

        $this->info("Completed. Sent {$alertsCount} alert notification(s).");

        return 0;
    }
}
