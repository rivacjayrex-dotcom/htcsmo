<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_smo_can_view_analytics_and_report_builder_on_one_page(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        $this
            ->actingAs($smo)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Operational Analytics')
            ->assertSee('Comprehensive Report Center')
            ->assertSee('Requisition Transactions');
    }

    public function test_old_report_pages_redirect_to_the_unified_workspace(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        $this->actingAs($smo)
            ->get(route('admin.reports.analytics'))
            ->assertRedirect('/admin/reports');

        $this->actingAs($smo)
            ->get(route('admin.reports.builder'))
            ->assertRedirect('/admin/reports');
    }

    public function test_each_report_type_renders_inside_the_unified_workspace(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        foreach ([
            'requisitions',
            'inventory',
            'dept_spending',
            'users',
            'item_consumption',
            'approval_audit',
            'inventory_movements',
            'user_activity',
        ] as $reportType) {
            $this
                ->actingAs($smo)
                ->get(route('admin.reports.index', ['report_type' => $reportType]))
                ->assertOk()
                ->assertSee('matching records');
        }
    }

    public function test_pdf_export_still_works_from_the_unified_workspace(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        $this
            ->actingAs($smo)
            ->get(route('admin.reports.index', [
                'report_type' => 'requisitions',
                'download_pdf' => 1,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_excel_compatible_csv_export_works(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        $this
            ->actingAs($smo)
            ->get(route('admin.reports.index', [
                'report_type' => 'users',
                'period' => 'all',
                'export' => 'csv',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();
    }

    public function test_department_spending_report_calculates_semester_costs_and_requestors(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $requestor = User::factory()->create(['department' => 'CETE']);

        Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'major',
            'status' => 'released',
            'purpose' => 'Laboratory supplies',
            'grand_total' => 1500,
        ]);

        $this
            ->actingAs($smo)
            ->get(route('admin.reports.index', [
                'report_type' => 'dept_spending',
                'period' => 'current_semester',
            ]))
            ->assertOk()
            ->assertSee('Departmental Expenditure')
            ->assertSee('CETE')
            ->assertSee('₱1,500.00')
            ->assertSee('Active Requestors');
    }
}
