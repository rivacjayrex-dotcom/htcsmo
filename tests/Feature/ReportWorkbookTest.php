<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Models\UserActivityLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportWorkbookTest extends TestCase
{
    use RefreshDatabase;

    public static function semesters(): array
    {
        return [
            ['first', '2025-08-01 00:00:00', '2025-12-31 23:59:59'],
            ['second', '2026-01-01 00:00:00', '2026-05-31 23:59:59'],
            ['summer', '2026-06-01 00:00:00', '2026-07-31 23:59:59'],
        ];
    }

    #[DataProvider('semesters')]
    public function test_department_semester_reports_include_both_boundaries_and_exclude_other_departments(string $term, string $from, string $to): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $owner = User::factory()->create(['department' => 'CETE']);
        $other = User::factory()->create(['department' => 'CAS']);
        foreach ([[$owner, $from, 1200], [$owner, $to, 350],
            [$owner, Carbon::parse($from)->subSecond(), 900], [$owner, Carbon::parse($to)->addSecond(), 800],
            [$other, $from, 7000]] as [$requestor, $closedAt, $amount]) {
            Requisition::create(['user_id' => $requestor->id, 'request_type' => 'major', 'status' => 'completed',
                'grand_total' => $amount, 'closed_at' => $closedAt]);
        }
        Requisition::create(['user_id' => $owner->id, 'request_type' => 'major', 'status' => 'pending', 'grand_total' => 5000]);
        $filters = ['report_type' => 'dept_spending', 'period' => 'semester', 'academic_year' => 2025, 'term' => $term, 'dept' => 'CETE'];
        $response = $this->actingAs($smo)->get(route('admin.reports.builder', $filters))->assertOk();
        $this->assertSame(1550.0, $response->viewData('rows')->sole()['total']);
        $this->assertSame(2, $response->viewData('rows')->sole()['requests']);
        $this->assertSame('CETE', $response->viewData('rows')->sole()['department']);
        $this->get(route('admin.reports.builder', [...$filters, 'print' => 1]))->assertOk()
            ->assertViewIs('admin.reports.print')->assertViewHas('summaryValue', 1550)
            ->assertSee('PHP 1,550.00')->assertDontSee('PHP 7,000.00');
        $csv = $this->get(route('admin.reports.builder', [...$filters, 'export' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('CETE,2,1,775,1550', $csv);
        $this->assertStringNotContainsString('CAS', $csv);
    }

    public function test_item_usage_counts_actual_releases_for_the_requesting_department_and_semester(): void
    {
        $smo = User::factory()->create(['role' => 'smo', 'department' => 'SMO']);
        $owner = User::factory()->create(['department' => 'CETE']);
        $other = User::factory()->create(['department' => 'CAS']);
        $request = Requisition::create(['user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'ready_for_pickup', 'grand_total' => 500]);
        $otherRequest = Requisition::create(['user_id' => $other->id, 'request_type' => 'minor', 'status' => 'completed', 'grand_total' => 900]);
        $paper = Supply::create(['item_name' => 'Workbook Paper', 'category' => 'Office Supplies', 'quantity' => 100, 'unit' => 'Ream', 'unit_price' => 100]);
        $pens = Supply::create(['item_name' => 'Workbook Pens', 'category' => 'Office Supplies', 'quantity' => 100, 'unit' => 'Piece', 'unit_price' => 10]);
        foreach ([
            [$paper, $request, 'release_deduction', -3, '2025-08-01 00:00:00'],
            [$paper, $request, 'release_deduction', -2, '2025-12-31 23:59:59'],
            [$pens, $request, 'release_deduction', -4, '2025-10-15 10:00:00'],
            [$paper, $request, 'restock_delivery', 20, '2025-10-15 10:00:00'],
            [$paper, $request, 'manual_adjustment', -8, '2025-10-15 10:00:00'],
            [$paper, $request, 'release_deduction', -9, '2026-01-01 00:00:00'],
            [$paper, $otherRequest, 'release_deduction', -7, '2025-10-15 10:00:00'],
        ] as [$supply, $requisition, $type, $change, $date]) {
            $log = SupplyInventoryLog::create(['supply_id' => $supply->id, 'user_id' => $smo->id,
                'requisition_id' => $requisition->id, 'transaction_type' => $type, 'quantity_change' => $change, 'balance_after' => 50]);
            $log->forceFill(['created_at' => $date])->saveQuietly();
        }
        $filters = ['report_type' => 'item_usage', 'period' => 'semester', 'academic_year' => 2025, 'term' => 'first', 'dept' => 'CETE'];
        $response = $this->actingAs($smo)->get(route('admin.reports.builder', $filters))->assertOk();
        $rows = $response->viewData('rows')->keyBy('item');
        $this->assertCount(2, $rows);
        $this->assertSame(5, $rows['Workbook Paper']['quantity']);
        $this->assertSame('Ream', $rows['Workbook Paper']['unit']);
        $this->assertSame(4, $rows['Workbook Pens']['quantity']);
        $this->assertSame('Piece', $rows['Workbook Pens']['unit']);
        $csv = $this->get(route('admin.reports.builder', [...$filters, 'export' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Workbook Paper","Office Supplies",5,Ream,1', $csv);
        $this->get(route('admin.reports.builder', [...$filters, 'print' => 1]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->sum('quantity') === 9);
    }

    public function test_print_includes_all_filtered_pages_and_oversized_prints_keep_the_filters(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $owner = User::factory()->create(['department' => 'CETE']);
        UserActivityLog::insert(collect(range(1, 60))->map(fn ($i) => ['user_id' => $owner->id,
            'action' => sprintf('Workbook activity %03d', $i), 'ip_address' => '127.0.0.1', 'created_at' => now()])->all());
        $filters = ['report_type' => 'user_activity', 'period' => 'all', 'dept' => 'CETE'];
        $preview = $this->actingAs($smo)->get(route('admin.reports.builder', $filters))->assertOk();
        $this->assertCount(50, $preview->viewData('rows'));
        $this->get(route('admin.reports.builder', [...$filters, 'page' => 2, 'print' => 1]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 60)
            ->assertViewHas('pagination', null)->assertSee('Workbook activity 001')->assertSee('Workbook activity 060');
        UserActivityLog::insert(collect(range(61, 501))->map(fn ($i) => ['user_id' => $owner->id,
            'action' => 'Extra activity '.$i, 'ip_address' => '127.0.0.1', 'created_at' => now()])->all());
        $this->get(route('admin.reports.builder', [...$filters, 'print' => 1]))
            ->assertRedirect(route('admin.reports.builder', $filters))->assertSessionHasErrors('download_pdf');
        $this->actingAs($owner)->get(route('admin.reports.builder', [...$filters, 'print' => 1]))->assertRedirect(route('dashboard'));
    }

    public function test_semester_filters_require_a_valid_academic_year_and_term(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        $this->getJson(route('admin.reports.builder', ['period' => 'semester']))->assertUnprocessable()
            ->assertJsonValidationErrors(['academic_year', 'term']);
        $this->getJson(route('admin.reports.builder', ['period' => 'semester', 'academic_year' => 1900, 'term' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors(['academic_year', 'term']);
    }
}
