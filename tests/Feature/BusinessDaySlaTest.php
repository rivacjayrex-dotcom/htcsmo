<?php

namespace Tests\Feature;

use App\Models\InstitutionalHoliday;
use App\Models\Requisition;
use App\Models\User;
use App\Services\BusinessDaySlaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessDaySlaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rolled_back_calendar_edits_do_not_remain_in_the_scope_cache(): void
    {
        $service = app(BusinessDaySlaService::class);
        $date = Carbon::parse('2026-10-19');
        DB::beginTransaction();

        try {
            InstitutionalHoliday::create(['holiday_date' => '2026-10-19', 'name' => 'Temporary holiday']);
            $this->assertFalse($service->isWorkingDay($date));
        } finally {
            DB::rollBack();
        }

        $this->assertTrue($service->isWorkingDay($date));
    }

    public function test_calendar_queries_do_not_grow_with_the_number_of_request_rows(): void
    {
        InstitutionalHoliday::create(['holiday_date' => '2026-10-19', 'name' => 'Institutional Holiday']);
        InstitutionalHoliday::create(['holiday_date' => '2027-01-04', 'name' => 'Next year holiday']);
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            foreach (range(1, 20) as $row) {
                $service = app(BusinessDaySlaService::class);
                $this->assertSame(1, $service->workingDaysElapsed(
                    Carbon::parse('2026-10-16 10:00:00'), Carbon::parse('2026-10-20 10:00:00'),
                ));
                $this->assertFalse($service->isWorkingDay(Carbon::parse('2027-01-04')));
            }

            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_calendar_edits_invalidate_the_current_scope_and_new_scopes_reload_it(): void
    {
        $service = app(BusinessDaySlaService::class);
        $date = Carbon::parse('2026-10-19');
        $this->assertTrue($service->isWorkingDay($date));

        $holiday = InstitutionalHoliday::create(['holiday_date' => '2026-10-19', 'name' => 'Holiday']);
        $this->assertFalse($service->isWorkingDay($date));
        $holiday->update(['holiday_date' => '2026-10-20']);
        $this->assertTrue($service->isWorkingDay($date));
        $this->assertFalse($service->isWorkingDay($date->copy()->addDay()));
        $holiday->delete();
        $this->assertTrue($service->isWorkingDay($date->copy()->addDay()));

        // A bulk edit simulates another process changing the calendar.
        DB::table('institutional_holidays')->insert(['holiday_date' => '2026-10-19', 'name' => 'External edit']);
        app()->forgetScopedInstances();
        $nextScope = app(BusinessDaySlaService::class);
        $this->assertNotSame($service, $nextScope);
        $this->assertFalse($nextScope->isWorkingDay($date));
    }

    public function test_three_working_day_deadline_skips_weekends(): void
    {
        $submittedAt = Carbon::parse('2026-10-16 10:00:00', 'Asia/Manila');

        $milestones = app(BusinessDaySlaService::class)->milestones($submittedAt);

        $this->assertSame('2026-10-20 10:00:00', $milestones['sla_warning_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 10:00:00', $milestones['due_at']->format('Y-m-d H:i:s'));
    }

    public function test_three_working_day_deadline_skips_configured_institutional_holidays(): void
    {
        InstitutionalHoliday::create([
            'holiday_date' => '2026-10-19',
            'name' => 'Institutional Holiday',
        ]);
        $submittedAt = Carbon::parse('2026-10-16 10:00:00', 'Asia/Manila');

        $milestones = app(BusinessDaySlaService::class)->milestones($submittedAt);

        $this->assertSame('2026-10-21 10:00:00', $milestones['sla_warning_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-22 10:00:00', $milestones['due_at']->format('Y-m-d H:i:s'));
    }

    public function test_requisition_captures_a_fixed_submission_deadline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 10:00:00', 'Asia/Manila'));
        $requestor = User::factory()->create();

        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'grand_total' => 1000,
        ]);

        $this->assertSame('2026-10-16 10:00:00', $requisition->submitted_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-20 10:00:00', $requisition->sla_warning_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 10:00:00', $requisition->due_at->format('Y-m-d H:i:s'));

        $requisition->update(['status' => 'approved_dept']);

        $this->assertSame('2026-10-16 10:00:00', $requisition->fresh()->submitted_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 10:00:00', $requisition->fresh()->due_at->format('Y-m-d H:i:s'));
    }
}
