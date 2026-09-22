<?php

namespace Tests\Feature;

use App\Models\ApprovalLog;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionApprovalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_cannot_approve_when_it_is_vp_finances_turn(): void
    {
        $requestor = User::factory()->create(['department' => 'CETE']);
        $departmentHead = User::factory()->create([
            'role' => 'dept_head',
            'department' => 'CETE',
        ]);
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'approved_dept',
            'grand_total' => 100,
        ]);

        $this
            ->actingAs($departmentHead)
            ->post(route('admin.status.update', $requisition), ['status' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => 'approved_dept',
        ]);
    }

    public function test_department_head_cannot_resolve_a_vp_finance_clarification(): void
    {
        $requestor = User::factory()->create(['department' => 'CETE']);
        $departmentHead = User::factory()->create([
            'role' => 'dept_head',
            'department' => 'CETE',
        ]);
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'for_clarification',
            'grand_total' => 100,
        ]);
        ApprovalLog::create([
            'requisition_id' => $requisition->id,
            'action' => 'Flagged for Office Clarification by VP for Finance',
            'role' => 'vp_finance',
        ]);

        $this
            ->actingAs($departmentHead)
            ->post(route('admin.status.update', $requisition), ['status' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => 'for_clarification',
        ]);
    }
}
