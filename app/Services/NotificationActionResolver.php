<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationActionResolver
{
    /**
     * Resolve notification actions in batches to avoid one query per feed row.
     *
     * @param  Collection<int, Notification>  $notifications
     * @return array<int, array{url: string, label: string}|null>
     */
    public function resolve(Collection $notifications, User $user): array
    {
        $requisitionIds = $notifications
            ->whereIn('action_type', ['requisition', 'approval_queue'])
            ->pluck('action_id')->filter()->unique()->values();
        $supplyIds = $notifications
            ->where('action_type', 'inventory')
            ->pluck('action_id')->filter()->unique()->values();

        $requisitions = $requisitionIds->isEmpty()
            ? collect()
            : Requisition::query()
                ->select(['id', 'user_id', 'status', 'request_type'])
                ->whereIn('id', $requisitionIds)
                ->get()
                ->keyBy('id');

        // Personal view links only need ownership. Review links need an owner
        // for department checks and clarification history for the current role.
        $reviewIds = $notifications->where('action_type', 'approval_queue')->pluck('action_id');
        $reviewTargets = $requisitions->whereIn('id', $reviewIds);
        if ($reviewTargets->isNotEmpty()) {
            if ($user->role === 'dept_head') {
                $reviewTargets->load('user:id,department_id');
            }
            $reviewTargets->where('status', 'for_clarification')
                ->load(['logs' => fn ($query) => $query->where('action', 'like', '%Clarification%')]);
        }

        $supplies = $supplyIds->isEmpty()
            ? collect()
            : Supply::query()->select('id')->whereIn('id', $supplyIds)->get()->keyBy('id');

        return $notifications->mapWithKeys(function (Notification $notification) use ($user, $requisitions, $supplies) {
            $target = match ($notification->action_type) {
                'requisition', 'approval_queue' => $requisitions->get($notification->action_id),
                'inventory' => $supplies->get($notification->action_id),
                default => null,
            };

            return [$notification->id => $notification->actionForResolvedTarget($user, $target)];
        })->all();
    }
}
