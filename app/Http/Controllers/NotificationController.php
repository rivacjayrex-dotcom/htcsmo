<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationActionResolver;
use App\Services\NotificationCenterFeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const CATEGORIES = ['all', 'approvals', 'deadlines', 'inventory', 'accounts'];

    public function index(Request $request, NotificationActionResolver $actionResolver, NotificationCenterFeed $feed): View|JsonResponse
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
        $warningQueries = $feed->warnings($user);
        $selectedWarnings = $category === 'all' ? $warningQueries : array_intersect_key($warningQueries, [$category => true]);
        $filtered = $this->applyCategory(clone $base, $category);
        $notes = $user->role === 'smo'
            ? $feed->paginate($filtered, $selectedWarnings)
            : $filtered->latest('id')->paginate(20)->withQueryString();
        $resolvedActions = $actionResolver->resolve($notes->getCollection()->reject(fn ($note) => $note->is_current_warning), $user);
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
        foreach ($warningQueries as $name => $query) {
            $count = (clone $query)->count();
            $categoryCounts[$name] += $count;
            $categoryCounts['all'] += $count;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('notifications.feed', compact('notes', 'resolvedActions'))->render(),
                'category' => $category,
                'category_counts' => $categoryCounts,
                'unread_count' => $unreadCount,
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        }

        return view('notifications', compact(
            'notes', 'resolvedActions', 'category', 'categoryCounts', 'unreadCount'
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
        $notificationState = Notification::query()->forUser($user)->withUnreadSummary()->first();
        $latestId = (int) $notificationState->latest_id;
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
            'unread_count' => (int) $notificationState->unread_count,
            'notification_summary' => $notificationState->unreadSummary(),
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
