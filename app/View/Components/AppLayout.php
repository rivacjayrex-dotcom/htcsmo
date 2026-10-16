<?php

namespace App\View\Components;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $notificationState = Auth::check()
            ? Notification::query()->forUser(Auth::id())
                ->selectRaw(
                    'COALESCE(MAX(id), 0) as latest_id, COALESCE(SUM(CASE WHEN is_read = ? THEN 1 ELSE 0 END), 0) as unread_count',
                    [false]
                )->first()
            : null;

        return view('layouts.app', [
            'layoutLatestNotificationId' => (int) ($notificationState?->latest_id ?? 0),
            'layoutUnreadCount' => (int) ($notificationState?->unread_count ?? 0),
        ]);
    }
}
