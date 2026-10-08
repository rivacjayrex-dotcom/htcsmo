<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalDeadlineReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_smo_can_notify_only_the_current_department_approver_once_per_day(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $requestor = User::factory()->create(['role' => 'employee', 'department' => 'CETE']);
        $assignedHead = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $otherHead = User::factory()->create(['role' => 'dept_head', 'department' => 'CTE']);
        $vpAdmin = User::factory()->create(['role' => 'vp_admin']);
        $requisition = $this->deadlineRequest($requestor, 'pending');

        $this->actingAs($smo)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Approvals needing follow-up')
            ->assertSee($requisition->tracking_code)
            ->assertSee(route('admin.requests.deadline-reminder', $requisition), false);

        $this->actingAs($smo)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee($requisition->tracking_code)
            ->assertSee('Notify approver');

        $this->actingAs($smo)
            ->post(route('admin.requests.deadline-reminder', $requisition))
            ->assertSessionHas('success');
        $this->actingAs($smo)
            ->post(route('admin.requests.deadline-reminder', $requisition))
            ->assertSessionHas('success');

        $this->assertSame(1, Notification::where([
            'user_id' => $assignedHead->id,
            'event_type' => 'deadline.approval_reminder',
            'action_id' => $requisition->id,
        ])->count());
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherHead->id,
            'event_type' => 'deadline.approval_reminder',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $vpAdmin->id,
            'event_type' => 'deadline.approval_reminder',
        ]);
    }

    public function test_approvers_see_only_deadline_risks_assigned_to_their_stage_and_department(): void
    {
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $ownRequestor = User::factory()->create(['department' => 'CETE']);
        $otherRequestor = User::factory()->create(['department' => 'CTE']);
        $assigned = $this->deadlineRequest($ownRequestor, 'pending');
        $notAssigned = $this->deadlineRequest($otherRequestor, 'pending');

        $this->actingAs($head)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Requests requiring your attention')
            ->assertSee($assigned->tracking_code)
            ->assertDontSee($notAssigned->tracking_code)
            ->assertSee('Review now');

        $this->actingAs($head)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee($assigned->tracking_code)
            ->assertDontSee($notAssigned->tracking_code);
    }

    public function test_reminder_rejects_non_deadline_requests_and_non_smo_users(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $requestor = User::factory()->create(['department' => 'CETE']);
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'purpose' => 'Routine classroom supply request.',
            'grand_total' => 100,
        ]);

        $this->actingAs($smo)
            ->post(route('admin.requests.deadline-reminder', $requisition))
            ->assertSessionHas('error');

        $this->actingAs($head)
            ->post(route('admin.requests.deadline-reminder', $requisition))
            ->assertRedirect(route('dashboard'));
    }

    public function test_stage_timestamp_resets_when_a_requisition_moves_to_the_next_approval_stage(): void
    {
        $requestor = User::factory()->create();
        $requisition = $this->deadlineRequest($requestor, 'pending');

        $requisition->update(['status' => 'approved_dept']);

        $this->assertTrue($requisition->fresh()->stage_started_at->greaterThan(now()->subMinute()));
    }

    private function deadlineRequest(User $requestor, string $status): Requisition
    {
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'major',
            'status' => $status,
            'purpose' => 'Deadline reminder feature verification.',
            'grand_total' => 1500,
        ]);
        $requisition->forceFill(['stage_started_at' => now()->subDays(2)->subHour()])->saveQuietly();

        return $requisition->fresh();
    }
}
