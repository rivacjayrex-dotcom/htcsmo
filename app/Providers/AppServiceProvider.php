<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The application uses Bootstrap; this prevents Laravel's default
        // Tailwind paginator markup from rendering oversized, unstyled arrows.
        Paginator::useBootstrapFive();

        // The unread count is consumed by the authenticated shell.
        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                $view->with('unreadCount', Notification::where('user_id', Auth::id())->where('is_read', false)->count());
            } else {
                $view->with('unreadCount', 0);
            }
        });
    }
}
