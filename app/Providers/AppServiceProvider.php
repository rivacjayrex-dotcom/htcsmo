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
use App\Services\BusinessDaySlaService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(BusinessDaySlaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TransactionRolledBack::class, fn () => app(BusinessDaySlaService::class)->clearHolidayCache());

        // A visible tab polls 20 times/minute. Allow navigation, retries, and
        // browsers without cross-tab coordination while retaining a hard cap.
        RateLimiter::for('runtime-polling', fn (Request $request) => Limit::perMinute(120)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('api-login', function (Request $request) {
            $login = $request->input('login');

            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('login:'.hash('sha256', is_string($login) ? mb_strtolower(trim($login)) : '')),
            ];
        });
        RateLimiter::for('api-v1', fn (Request $request) => Limit::perMinute(60)
            ->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('api-submissions', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->user()->id));

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
