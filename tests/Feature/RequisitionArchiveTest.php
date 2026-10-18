<?php

namespace Tests\Feature;

use App\Models\ApprovalLog;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_contains_released_and_rejected_requests_without_exposing_other_users_records(): void
    {
        $employee = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $released = $this->requisition($employee, 'released', '2026-09-02 09:00:00');
        $rejected = $this->requisition($employee, 'rejected', '2026-09-03 09:00:00');
        $active = $this->requisition($employee, 'pending');
        $private = $this->requisition($otherEmployee, 'released', '2026-09-04 09:00:00');

        $this->actingAs($employee)
            ->get(route('requests.archive'))
            ->assertOk()
            ->assertSee($released->tracking_code)
            ->assertSee($rejected->tracking_code)
            ->assertDontSee($active->tracking_code)
            ->assertDontSee($private->tracking_code);
    }

    public function test_status_department_and_closure_date_filters_work_together_and_ignore_updated_at(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $cete = User::factory()->create(['department' => 'CETE']);
        $cas = User::factory()->create(['department' => 'CAS']);
        $match = $this->requisition($cete, 'released', '2026-09-10 08:00:00');
        $wrongStatus = $this->requisition($cete, 'rejected', '2026-09-10 08:00:00');
        $wrongDepartment = $this->requisition($cas, 'released', '2026-09-10 08:00:00');
        $wrongClosureDate = $this->requisition($cete, 'released', '2026-08-10 08:00:00');

        Requisition::withoutTimestamps(fn () => $match->update(['remarks' => 'Edited after closure']));
        $match->forceFill(['updated_at' => '2026-10-01 08:00:00'])->saveQuietly();

        $filters = [
            'status' => 'released',
            'dept' => 'CETE',
            'date_from' => '2026-09-10',
            'date_to' => '2026-09-10',
        ];

        $this->actingAs($smo)
            ->get(route('requests.archive', $filters))
            ->assertOk()
            ->assertSee($match->tracking_code)
            ->assertDontSee($wrongStatus->tracking_code)
            ->assertDontSee($wrongDepartment->tracking_code)
            ->assertDontSee($wrongClosureDate->tracking_code);

        $this->actingAs($smo)
            ->get(route('admin.archive', $filters))
            ->assertOk()
            ->assertSee($match->tracking_code)
            ->assertDontSee($wrongStatus->tracking_code)
            ->assertDontSee($wrongDepartment->tracking_code)
            ->assertDontSee($wrongClosureDate->tracking_code);
    }

    public function test_both_archive_pages_can_search_archived_request_content(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $requestor = User::factory()->create(['name' => 'Archive Search Owner']);
        $match = $this->requisition($requestor, 'completed', '2026-09-10 08:00:00');
        $other = $this->requisition($requestor, 'released', '2026-09-11 08:00:00');
        RequisitionItem::create([
            'requisition_id' => $match->id,
            'item_name' => 'Historical projector',
            'specifications' => 'Archive-only marker',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_price' => 100,
            'subtotal' => 100,
        ]);

        foreach (['requests.archive', 'admin.archive'] as $route) {
            $this->actingAs($smo)
                ->get(route($route, ['search' => 'Archive-only marker']))
                ->assertOk()
                ->assertSee($match->tracking_code)
                ->assertDontSee($other->tracking_code)
                ->assertSee('value="Archive-only marker"', false);
        }
    }

    public function test_archive_sorts_by_closure_date_and_preserves_filters_across_pagination(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $requestor = User::factory()->create(['department' => 'CTE']);
        $older = $this->requisition($requestor, 'released', '2026-09-01 08:00:00');
        $newer = $this->requisition($requestor, 'released', '2026-09-30 08:00:00');

        foreach (range(1, 15) as $day) {
            $this->requisition($requestor, 'released', sprintf('2026-09-%02d 12:00:00', min($day + 1, 29)));
        }

        $filters = [
            'status' => 'released',
            'dept' => 'CTE',
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'order' => 'asc',
        ];

        $this->actingAs($smo)
            ->get(route('requests.archive', $filters))
            ->assertOk()
            ->assertSee($older->tracking_code)
            ->assertDontSee($newer->tracking_code)
            ->assertSee('status=released', false)
            ->assertSee('dept=CTE', false)
            ->assertSee('date_from=2026-09-01', false)
            ->assertSee('date_to=2026-09-30', false)
            ->assertSee('order=asc', false)
            ->assertSee('page=2', false);

        $this->actingAs($smo)
            ->get(route('requests.archive', [...$filters, 'page' => 2]))
            ->assertOk()
            ->assertSee($newer->tracking_code);

        $this->actingAs($smo)
            ->get(route('requests.archive', [...$filters, 'print' => 1]))
            ->assertOk()
            ->assertSee($older->tracking_code)
            ->assertSee($newer->tracking_code);
    }

    public function test_invalid_archive_date_range_returns_a_clear_validation_error(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->from(route('requests.archive'))
            ->get(route('requests.archive', [
                'date_from' => '2026-09-20',
                'date_to' => '2026-09-01',
            ]))
            ->assertRedirect(route('requests.archive'))
            ->assertSessionHasErrors('date_to');
    }

    public function test_request_details_and_pdf_follow_archive_record_permissions(): void
    {
        $owner = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $smo = User::factory()->create(['role' => 'smo']);
        $requisition = $this->requisition($owner, 'released', '2026-09-10 08:00:00');

        $this->get(route('requests.show', $requisition))->assertRedirect(route('login'));
        $this->actingAs($otherEmployee)->get(route('requests.show', $requisition))->assertForbidden();
        $this->actingAs($otherEmployee)->get(route('requests.download', $requisition))->assertForbidden();
        $this->actingAs($owner)->get(route('requests.show', $requisition))->assertOk();
        $this->actingAs($smo)->get(route('requests.show', $requisition))->assertOk();
    }

    public function test_institutional_archive_is_smo_only_and_archive_views_do_not_mutate_audit_records(): void
    {
        $employee = User::factory()->create();
        $smo = User::factory()->create(['role' => 'smo']);
        $requisition = $this->requisition($employee, 'rejected', '2026-09-10 08:00:00');
        RequisitionItem::create([
            'requisition_id' => $requisition->id,
            'item_name' => 'Archived paper',
            'quantity' => 2,
            'unit' => 'ream',
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
        ApprovalLog::create([
            'requisition_id' => $requisition->id,
            'action' => 'Rejected by Department Head',
            'role' => 'dept_head',
            'remarks' => 'Not approved',
        ]);

        $this->actingAs($employee)->get(route('admin.archive'))->assertRedirect(route('dashboard'));
        $this->actingAs($smo)->get(route('admin.archive'))->assertOk()->assertSee($requisition->tracking_code);

        $this->assertDatabaseHas('requisitions', ['id' => $requisition->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('requisition_items', ['requisition_id' => $requisition->id, 'item_name' => 'Archived paper']);
        $this->assertDatabaseHas('approval_logs', ['requisition_id' => $requisition->id, 'action' => 'Rejected by Department Head']);
    }

    public function test_terminal_transition_date_is_not_changed_by_later_edits(): void
    {
        $employee = User::factory()->create();
        $requisition = $this->requisition($employee, 'pending');

        $requisition->update(['status' => 'rejected']);
        $closedAt = $requisition->fresh()->closed_at;
        $requisition->update(['remarks' => 'Audit note added later']);

        $this->assertNotNull($closedAt);
        $this->assertTrue($requisition->fresh()->closed_at->equalTo($closedAt));
    }

    private function requisition(User $user, string $status, ?string $closedAt = null): Requisition
    {
        return Requisition::create([
            'user_id' => $user->id,
            'request_type' => 'minor',
            'status' => $status,
            'closed_at' => $closedAt,
            'purpose' => 'Archive behavior test',
            'grand_total' => 200,
        ]);
    }
}
