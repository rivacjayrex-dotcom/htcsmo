<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Services\NotificationActionResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const CATEGORIES = ['all', 'approvals', 'deadlines', 'inventory', 'accounts'];

    public function index(Request $request, NotificationActionResolver $actionResolver): View|JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'category' => ['nullable', 'in:'.implode(',', self::CATEGORIES)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        abort_if($request->query('category') === 'inventory' && $user->role !== 'smo', 403);
        $category = in_array($request->query('category'), self::CATEGORIES, true)
            ? $request->query('category')
            : 'all';

        $base = Notification::query()->forUser($user);
        $notes = $this->applyCategory(clone $base, $category)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
        $resolvedActions = $actionResolver->resolve($notes->getCollection(), $user);
        // Preserve the shared category predicates while collecting the badge
        // counts in one database round trip instead of six.
        $countQuery = DB::query()->selectSub(
            (clone $base)->unread()->selectRaw('COUNT(*)'), 'unread_count',
        );
        foreach (self::CATEGORIES as $name) {
            $countQuery->selectSub($this->applyCategory(clone $base, $name)->selectRaw('COUNT(*)'), $name.'_count');
        }
        $counts = $countQuery->first();
        $unreadCount = (int) $counts->unread_count;
        $categoryCounts = collect(self::CATEGORIES)->mapWithKeys(fn (string $name) => [$name => (int) $counts->{$name.'_count'}]);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('notifications.feed', compact('notes', 'resolvedActions'))->render(),
                'category' => $category,
                'category_counts' => $categoryCounts,
                'unread_count' => $unreadCount,
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        }

        $criticalRequests = collect();
        $criticalRequestCount = 0;
        if ($user->role === 'smo') {
            $deadlineQuery = Requisition::query()->deadlineRisks();
            $criticalRequestCount = (clone $deadlineQuery)->count();
            $criticalRequests = $deadlineQuery->with(['items', 'user.academicDepartment'])
                ->oldest('due_at')->oldest('id')->limit(5)->get();
        }
        $lowStockSupplies = $user->role === 'smo'
            ? Supply::whereColumn('quantity', '<=', 'min_stock_level')
                ->orderByRaw('CASE WHEN quantity <= 0 THEN 0 ELSE 1 END')
                ->orderBy('quantity')
                ->get()
            : collect();
        $fulfilledPlaceholders = implode(',', array_fill(0, count(Requisition::FULFILLED_STATUSES), '?'));
        $personalCounts = Requisition::where('user_id', $user->id)->selectRaw(
            "COUNT(*) as total,
             COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending,
             COALESCE(SUM(CASE WHEN status IN ($fulfilledPlaceholders) THEN 1 ELSE 0 END), 0) as released",
            ['pending', ...Requisition::FULFILLED_STATUSES],
        )->first();
        $personalStats = [
            'total' => (int) $personalCounts->total,
            'pending' => (int) $personalCounts->pending,
            'released' => (int) $personalCounts->released,
        ];

        return view('notifications', compact(
            'notes', 'resolvedActions', 'category', 'categoryCounts', 'unreadCount', 'criticalRequests', 'criticalRequestCount', 'lowStockSupplies', 'personalStats'
        ));
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse|JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $notification->markAsRead();

        return $request->expectsJson()
            ? response()->json([
                'is_read' => true,
                'unread_count' => Notification::query()->forUser($request->user())->unread()->count(),
            ])
            : back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        Notification::query()->forUser($request->user())->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $request->expectsJson()
            ? response()->json(['unread_count' => 0])
            : back()->with('success', 'All notifications marked as read.');
    }

    public function open(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $action = $notification->actionFor($request->user());
        $notification->markAsRead();

        if (! $action) {
            return redirect()->route('notifications')
                ->with('warning', 'The related record is unavailable or you no longer have access to it.');
        }

        return redirect()->to($action['url']);
    }

    public function poll(Request $request, NotificationActionResolver $actionResolver): JsonResponse
    {
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $user = $request->user();
        $afterId = (int) ($validated['after_id'] ?? 0);
        $latestId = (int) Notification::query()->forUser($user)->max('id');
        $notifications = Notification::query()
            ->forUser($user)
            ->unread()
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit(10)
            ->get();
        $cursor = (int) ($notifications->last()?->id ?? $latestId);
        $resolvedActions = $actionResolver->resolve($notifications, $user);
        $payloads = $notifications->map(
            fn (Notification $notification) => $notification->pollPayload(
                $user,
                $resolvedActions[$notification->id] ?? null,
                true,
            )
        )->values();

        return response()->json([
            'account_id' => $user->id,
            'latest_id' => $latestId,
            'cursor' => $cursor,
            'unread_count' => Notification::query()->forUser($user)->unread()->count(),
            'notifications' => $payloads,
            'notification' => $payloads->first(),
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    private function applyCategory(Builder $query, string $category): Builder
    {
        return match ($category) {
            'approvals' => $query->where(function (Builder $query) {
                $query->where('event_type', 'like', 'requisition.%')
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('event_type')->where('type', 'success'));
            }),
            'deadlines' => $query->where(function (Builder $query) {
                $query->where('event_type', 'like', 'deadline.%')
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('event_type')->where('type', 'danger')->whereNotIn('icon', ['package-x']));
            }),
            'inventory' => $query->where(function (Builder $query) {
                $query->where('event_type', 'like', 'inventory.%')
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('event_type')->whereIn('icon', ['triangle-alert', 'package-x']));
            }),
            'accounts' => $query->where(function (Builder $query) {
                $query->where('event_type', 'like', 'account.%')
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('event_type')->whereIn('icon', ['user-plus', 'user-check', 'user-cog']));
            }),
            default => $query,
        };
    }
}
