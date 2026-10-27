<?php

namespace Tests\Feature;

use App\Jobs\SendRequisitionEmail;
use App\Mail\RequisitionAlertMail;
use App\Models\ApprovalLog;
use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Services\InventoryForecastService;
use App\Services\NotificationActionResolver;
use App\Services\WorkspaceVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkspacePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_previews_share_calendar_and_relationship_queries(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $owner = User::factory()->create();
        foreach (range(1, 10) as $number) {
            $requisition = Requisition::create([
                'user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'pending',
                'purpose' => 'Dashboard query fixture '.$number, 'grand_total' => 50,
            ]);
            $requisition->forceFill([
                'submitted_at' => now()->subDays(7), 'sla_warning_at' => now()->subDays(3),
                'due_at' => now()->subDay(),
            ])->saveQuietly();
            $requisition->items()->create([
                'item_name' => 'Paper '.$number, 'quantity' => 1, 'unit' => 'Ream(s)',
                'unit_price' => 50, 'subtotal' => 50,
            ]);
        }
        app()->forgetScopedInstances();
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $response = $this->actingAs($smo)->get(route('dashboard'))->assertOk();
            $queries = collect(DB::getQueryLog())->pluck('query');
        } finally {
            DB::disableQueryLog();
        }

        foreach (['institutional_holidays', 'requisition_items', 'users'] as $table) {
            $this->assertCount(1, $queries->filter(fn ($sql) => str_contains($sql, 'from "'.$table.'"')), $table);
        }
        foreach (['approvalDeadlineRequests', 'deadlineRequests', 'allStaffRequests'] as $preview) {
            foreach ($response->viewData($preview) as $requisition) {
                $this->assertTrue($requisition->relationLoaded('items'));
                $this->assertCount(1, $requisition->items);
                $this->assertTrue($requisition->relationLoaded('user'));
                $this->assertSame($owner->id, $requisition->user->id);
            }
        }
    }

    public function test_idle_runtime_poll_does_not_query_business_tables(): void
    {
        $user = User::factory()->create();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->getJson(route('api.runtime-state'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertCount(0, $queries->filter(fn ($sql) => preg_match('/from ["`]?(requisitions|supplies|approval_logs)["`]?\b/i', $sql)));
        $this->assertCount(1, $queries);
    }

    public function test_presented_notifications_do_not_reload_payloads_and_read_state_stays_current(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id, 'title' => 'Existing notice', 'message' => 'Presented', 'is_read' => false,
        ]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->actingAs($user)->getJson(route('api.runtime-state', ['after_id' => $notification->id]))
                ->assertOk()->assertJsonPath('unread_count', 1)->assertJsonCount(0, 'notifications');
            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        $notification->markAsRead();
        $this->getJson(route('api.runtime-state'))->assertOk()
            ->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'notifications');
        $next = Notification::create([
            'user_id' => $user->id, 'title' => 'New notice', 'message' => 'New', 'is_read' => false,
        ]);
        $this->getJson(route('api.runtime-state', ['after_id' => $notification->id]))->assertOk()
            ->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.id', $next->id);
    }

    public function test_personal_notification_links_need_one_query_without_loading_approval_history(): void
    {
        $owner = User::factory()->create();
        $requisition = Requisition::create([
            'user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'pending',
            'purpose' => 'Personal notification query fixture', 'grand_total' => 50,
        ]);
        $notification = Notification::create([
            'user_id' => $owner->id, 'title' => 'Request notice', 'message' => 'Request',
            'action_type' => 'requisition', 'action_id' => $requisition->id,
        ]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $actions = app(NotificationActionResolver::class)->resolve(collect([$notification]), $owner);
            $this->assertSame(route('requests.show', $requisition), $actions[$notification->id]['url']);
            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_lean_notification_review_links_recheck_department_and_clarification_owner(): void
    {
        $owner = User::factory()->create(['department' => 'CETE']);
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $otherHead = User::factory()->create(['role' => 'dept_head', 'department' => 'CAS']);
        $finance = User::factory()->create(['role' => 'vp_finance']);
        $requisition = Requisition::create([
            'user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'pending',
            'purpose' => 'Notification authorization fixture', 'grand_total' => 50,
        ]);
        $notification = Notification::create([
            'user_id' => $head->id, 'title' => 'Review request', 'message' => 'Review',
            'action_type' => 'approval_queue', 'action_id' => $requisition->id,
        ]);
        $resolver = app(NotificationActionResolver::class);
        $notes = collect([$notification]);
        $this->assertNotNull($resolver->resolve($notes, $head)[$notification->id]);
        $this->assertNull($resolver->resolve($notes, $otherHead)[$notification->id]);

        $requisition->update(['status' => 'for_clarification']);
        foreach ([$head, $finance] as $actor) {
            ApprovalLog::create([
                'requisition_id' => $requisition->id, 'acted_by_user_id' => $actor->id,
                'role' => $actor->role, 'action' => 'Requested Clarification',
            ]);
        }
        $this->assertNull($resolver->resolve($notes, $head)[$notification->id]);
        $this->assertNotNull($resolver->resolve($notes, $finance)[$notification->id]);
    }

    public function test_inventory_changes_do_not_invalidate_request_workspaces(): void
    {
        $versions = app(WorkspaceVersionService::class);
        $before = $versions->versions();
        $this->supply();
        $after = $versions->versions();

        $this->assertNotSame($before['inventory'], $after['inventory']);
        $this->assertSame($before['requests'], $after['requests']);
        $this->assertNotSame($before['all'], $after['all']);
    }

    public function test_database_backed_version_markers_need_one_round_trip_when_warm(): void
    {
        config(['cache.default' => 'database']);
        $service = app(WorkspaceVersionService::class);
        $before = $service->versions();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->assertSame($before, $service->versions());
            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
        $service->touch('inventory');
        $after = $service->versions();
        $this->assertNotSame($before['inventory'], $after['inventory']);
        $this->assertSame($before['requests'], $after['requests']);
    }

    public function test_rolled_back_changes_do_not_invalidate_cached_workspaces(): void
    {
        $versions = app(WorkspaceVersionService::class);
        $before = $versions->version();
        DB::beginTransaction();
        $this->supply();
        $this->assertSame($before, $versions->version());
        DB::rollBack();
        $this->assertSame($before, $versions->version());
    }

    public function test_background_refresh_omits_layout_scripts_and_preserves_authorization(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $full = $this->actingAs($smo)->get(route('dashboard'))->assertOk();
        $fragment = $this->actingAs($smo)->withHeader('X-Workspace-Refresh', 'true')
            ->get(route('dashboard'))->assertOk()
            ->assertSee('data-live-region="workspace"', false)
            ->assertDontSee('function notificationCenter()', false)
            ->assertDontSee('id="appSidebar"', false);

        $this->assertLessThan(strlen($full->getContent()) / 2, strlen($fragment->getContent()));
        $employee = User::factory()->create();
        $this->actingAs($employee)->get(route('inventory.index'))->assertRedirect(route('dashboard'));
    }

    public function test_forecast_cache_avoids_reloading_history_and_invalidates_on_release(): void
    {
        $supply = $this->supply();
        $user = User::factory()->create(['role' => 'smo']);
        $service = app(InventoryForecastService::class);
        $service->build(collect([$supply]));
        DB::enableQueryLog();
        DB::flushQueryLog();
        $cached = $service->build(collect([$supply]));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(0, $cached['release_events']);

        SupplyInventoryLog::create([
            'supply_id' => $supply->id, 'user_id' => $user->id,
            'transaction_type' => 'release_deduction', 'quantity_change' => -6,
            'balance_after' => 4, 'remarks' => 'Cache invalidation test',
        ]);
        $fresh = $service->build(collect([$supply]));
        $this->assertSame(1, $fresh['release_events']);
        $this->assertGreaterThan(0, $fresh['all']->first()['forecast_demand']);
        $supply->decrement('quantity', 2);
        $this->assertSame(8, $service->build(collect([$supply]))['all']->first()['supply']->quantity);
    }

    public function test_approval_queue_is_paginated_without_losing_notification_deep_links(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $owner = User::factory()->create();
        $first = null;
        foreach (range(1, 17) as $number) {
            $record = Requisition::create([
                'user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'approved_vp',
                'purpose' => 'Performance fixture '.$number, 'grand_total' => 50,
            ]);
            $first ??= $record;
        }
        $response = $this->actingAs($smo)->get(route('admin.approvals', ['request' => $first->id]))->assertOk();
        $this->assertSame(17, $response->viewData('pendingMinor')->total());
        $this->assertSame(15, $response->viewData('pendingMinor')->count());
        $this->assertSame($first->id, $response->viewData('focusedRequest')->id);
        $this->assertStringContainsString('tab=minor', $response->viewData('pendingMinor')->url(2));
        $this->assertStringNotContainsString('request=', $response->viewData('pendingMinor')->url(2));
    }

    public function test_report_preview_is_paginated_and_export_includes_all_matches(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        User::factory()->count(52)->create();
        $parameters = ['report_type' => 'users', 'period' => 'all'];
        $response = $this->actingAs($smo)->get(route('admin.reports.builder', $parameters))->assertOk();
        $this->assertSame(53, $response->viewData('pagination')->total());
        $this->assertCount(50, $response->viewData('rows'));
        $response->assertSee('This page:')->assertSee('exports include all matches');
        $export = $this->actingAs($smo)->get(route('admin.reports.builder', [...$parameters, 'export' => 'csv']))->assertOk();
        $this->assertCount(54, array_filter(explode("\n", trim($export->streamedContent()))));
    }

    public function test_email_is_queued_instead_of_blocking_approval_and_logs_delivery_in_the_job(): void
    {
        Queue::fake();
        Mail::fake();
        $owner = User::factory()->create();
        $approver = User::factory()->create(['role' => 'vp_finance']);
        $requisition = Requisition::create([
            'user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'approved_dept',
            'purpose' => 'Email performance fixture', 'grand_total' => 50,
        ]);
        $this->actingAs($approver)->post(route('admin.status.update', $requisition), ['status' => 'approved'])->assertRedirect();
        Queue::assertPushedOn('mail', SendRequisitionEmail::class);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_logs', 0);
        $job = Queue::pushed(SendRequisitionEmail::class)->first();
        $job->handle();
        Mail::assertSent(RequisitionAlertMail::class);
        $this->assertDatabaseHas('email_logs', ['recipient_email' => $owner->email, 'status' => 'sent']);
    }

    private function supply(): Supply
    {
        return Supply::create([
            'item_name' => 'Performance Test Paper', 'category' => 'Office Supplies',
            'requisition_tier' => 'minor', 'quantity' => 10, 'unit' => 'Ream(s)',
            'unit_price' => 50, 'min_stock_level' => 2,
        ]);
    }

    public function test_exhausted_email_job_records_a_failure(): void
    {
        $job = new SendRequisitionEmail('recipient@example.test', null, 123, 'Delivery test', 'Test', 'Test message', '#185b3b');
        $job->failed(new \RuntimeException('SMTP unavailable'));

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'recipient@example.test', 'status' => 'failed',
            'error_message' => 'SMTP unavailable',
        ]);
    }
}
