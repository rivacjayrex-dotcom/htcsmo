<?php

namespace App\Providers;

use App\Models\ApprovalLog;
use App\Models\Department;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyInventoryLog;
use App\Models\SupplyOption;
use App\Models\SupplyOptionValue;
use App\Models\SupplyUnit;
use App\Models\User;
use App\Observers\WorkspaceObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        foreach ([
            Requisition::class, RequisitionItem::class,
            ApprovalLog::class, Supply::class,
            SupplyInventoryLog::class, SupplyOption::class,
            SupplyOptionValue::class, User::class,
            Department::class, SupplyCategory::class, SupplyUnit::class,
        ] as $model) {
            $model::observe(WorkspaceObserver::class);
        }

        // Match pagination markup to the application's Bootstrap styles.
        Paginator::useBootstrapFive();
    }
}
