<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Supply;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

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
        // Share available supplies & unread notification count across all views safely
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $view->with('availableSupplies', Supply::where('quantity', '>', 0)
                    ->orderBy('requisition_tier')
                    ->orderBy('item_name')
                    ->get());
                $view->with('unreadCount', Notification::where('user_id', Auth::id())->where('is_read', false)->count());
            } else {
                $view->with('availableSupplies', collect());
                $view->with('unreadCount', 0);
            }
        });
    }
}
