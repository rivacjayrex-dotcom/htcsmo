<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationCenterFeed
{
    /** Current warnings fill gaps in the event history without writing on a page visit. */
    public function warnings(User $user): array
    {
        if ($user->role !== 'smo') {
            return [];
        }

        return [
            'inventory' => Supply::query()->whereColumn('quantity', '<=', 'min_stock_level')
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')->from('notifications')
                        ->where('user_id', $user->id)->where('action_type', 'inventory')
                        ->whereColumn('action_id', 'supplies.id')
                        ->whereRaw("((supplies.quantity <= 0 AND event_type IN ('inventory.out', 'inventory.out_of_stock'))
                            OR (supplies.quantity > 0 AND event_type IN ('inventory.low', 'inventory.low_stock')))");
                }),
            'deadlines' => Requisition::query()->deadlineRisks()
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')->from('notifications')
                        ->where('user_id', $user->id)->where('action_type', 'requisition')
                        ->whereColumn('action_id', 'requisitions.id')
                        ->whereRaw("event_type = CASE WHEN requisitions.due_at <= ? THEN 'deadline.overdue' ELSE 'deadline.nearing' END", [now()]);
                }),
        ];
    }

    public function paginate(Builder $notifications, array $warnings): LengthAwarePaginator
    {
        $union = (clone $notifications)->reorder()
            ->selectRaw("'notification' as source_type, id as source_id, created_at as sort_at")
            ->toBase();
        foreach ($warnings as $category => $query) {
            $sort = $category === 'inventory' ? 'updated_at' : 'CASE WHEN due_at <= ? THEN due_at ELSE sla_warning_at END';
            $union->unionAll((clone $query)->selectRaw(
                "'{$category}' as source_type, id as source_id, {$sort} as sort_at",
                $category === 'inventory' ? [] : [now()],
            )->toBase());
        }

        $page = DB::query()->fromSub($union, 'notification_activity')
            ->orderByDesc('sort_at')->orderBy('source_type')->orderByDesc('source_id')
            ->paginate(20)->withQueryString();
        $groups = $page->getCollection()->groupBy('source_type');
        $stored = isset($groups['notification'])
            ? Notification::whereIn('id', $groups['notification']->pluck('source_id'))->get()->keyBy('id')
            : collect();
        $supplies = isset($groups['inventory'])
            ? Supply::with('supplyUnit')->whereIn('id', $groups['inventory']->pluck('source_id'))->get()->keyBy('id')
            : collect();
        $requisitions = isset($groups['deadlines'])
            ? Requisition::with('items')->whereIn('id', $groups['deadlines']->pluck('source_id'))->get()->keyBy('id')
            : collect();

        $page->setCollection($page->getCollection()->map(function ($row) use ($stored, $supplies, $requisitions) {
            if ($row->source_type === 'notification') {
                return $stored->get($row->source_id);
            }
            $target = $row->source_type === 'inventory'
                ? $supplies->get($row->source_id) : $requisitions->get($row->source_id);
            // A record may be removed between the page query and hydration.
            if (! $target) {
                return null;
            }
            if ($target instanceof Supply) {
                $critical = $target->quantity <= 0;
                $title = ($critical ? 'Out of stock: ' : 'Low stock: ').$target->item_name;
                $message = "{$target->quantity} {$target->unit} remaining; minimum stock is {$target->min_stock_level}. Review inventory to replenish this item.";
                $event = $critical ? 'inventory.out_of_stock' : 'inventory.low_stock';
                $url = route('inventory.edit', $target);
                $label = 'View inventory';
                $icon = $critical ? 'package-x' : 'triangle-alert';
            } else {
                $critical = $target->isDeadlineOverdue();
                $title = ($critical ? 'Overdue: ' : 'Due soon: ').$target->tracking_code;
                $item = $target->items->first()?->item_name ?? 'Supply requisition';
                $message = $item.'. '.($critical ? 'This requisition has reached its processing deadline.' : 'This requisition is approaching its processing deadline.')
                    .' Due '.$target->due_at?->format('M j, Y g:i A').'.';
                $event = $critical ? 'deadline.overdue' : 'deadline.nearing';
                $url = route('requests.show', $target);
                $label = 'View requisition';
                $icon = $critical ? 'alert-triangle' : 'clock';
            }

            return (new Notification())->forceFill([
                'id' => -($target->id * 2 + ($target instanceof Supply ? 0 : 1)),
                'title' => $title, 'message' => $message, 'event_type' => $event,
                'type' => $critical ? 'danger' : 'warning', 'icon' => $icon,
                'created_at' => $row->sort_at, 'is_read' => true,
                'is_current_warning' => true, 'current_action_url' => $url, 'current_action_label' => $label,
            ]);
        })->filter()->values());

        return $page;
    }
}
