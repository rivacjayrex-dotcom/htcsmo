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
        // These values are consumed by the authenticated shell and its included
        // partials. Scoping the composer prevents the same queries from running
        // again for every nested Blade view on a page.
        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                $view->with('availableSupplies', Supply::query()
                    ->select([
                        'id', 'item_name', 'brand', 'category', 'requisition_tier',
                        'specifications', 'physical_description', 'unit', 'unit_price',
                        'quantity', 'configurable_options',
                    ])
                    ->where('quantity', '>', 0)
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
