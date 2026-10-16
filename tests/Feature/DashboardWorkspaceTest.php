<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_smo_sees_the_modern_command_center_and_active_pipeline(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $requestor = User::factory()->create(['name' => 'Faculty Requestor']);
        $request = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'purpose' => 'Classroom supplies',
            'grand_total' => 500,
        ]);
        $deadlineRequest = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'approved_vp',
            'purpose' => 'Urgent printing supplies',
            'grand_total' => 800,
        ]);
        $deadlineRequest->forceFill([
            'stage_started_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ])->saveQuietly();
        Supply::create([
            'item_name' => 'Low Stock Bond Paper',
            'brand' => 'HTC Test',
            'category' => 'Office Supplies',
            'requisition_tier' => 'minor',
            'specifications' => 'A4 paper',
            'quantity' => 2,
            'unit' => 'Ream',
            'unit_price' => 250,
            'min_stock_level' => 10,
        ]);

        $this->actingAs($smo)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SMO Command Center')
            ->assertSee('Inventory and fulfillment overview')
            ->assertSee('Active requisitions')
            ->assertSee($request->tracking_number)
            ->assertSee('Faculty Requestor')
            ->assertSee('Deadline monitor')
            ->assertSee($deadlineRequest->tracking_number)
            ->assertSee('Inventory warning')
            ->assertSee('Low Stock Bond Paper')
            ->assertSeeInOrder([
                'Overview',
                'Notifications',
                'Requisitions',
                'Inventory & Fulfillment',
                'Release Queue',
                'Manage Inventory',
                'Insights',
                'Reports & Analytics',
                'Administration',
                'Access Requests',
                'Manage Personnel',
            ], false);
    }

    public function test_employee_sees_personal_metrics_and_request_tracking(): void
    {
        $employee = User::factory()->create([
            'name' => 'Maria Santos',
            'role' => 'employee',
        ]);
        $request = Requisition::create([
            'user_id' => $employee->id,
            'request_type' => 'major',
            'status' => 'pending',
            'purpose' => 'Office equipment',
            'grand_total' => 25000,
        ]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Maria.')
            ->assertSee('New requisition')
            ->assertSee('My active requisitions')
            ->assertSee('My procurement activity')
            ->assertSee($request->tracking_number)
            ->assertSeeInOrder(['Overview', 'Notifications', 'Requisitions', 'Active Requests', 'Request Archive']);
    }
}
