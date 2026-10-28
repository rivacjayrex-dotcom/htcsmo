<?php

namespace Tests\Feature;

use App\Jobs\SendRequisitionEmail;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequisitionWorkflowEmailTest extends TestCase
{
    use RefreshDatabase;

    public static function approvalStages(): array
    {
        return [
            ['minor', 'pending', 'dept_head', 'vp_finance'],
            ['major', 'pending', 'dept_head', 'vp_admin'],
            ['minor', 'approved_dept', 'vp_finance', 'smo'],
            ['major', 'approved_dept', 'vp_admin', 'provost'],
            ['major', 'approved_vp', 'provost', 'president'],
            ['major', 'approved_provost', 'president', 'smo'],
        ];
    }

    #[DataProvider('approvalStages')]
    public function test_approval_emails_the_owner_and_only_active_next_reviewers(string $tier, string $status, string $role, string $nextRole): void
    {
        Queue::fake();
        $owner = User::factory()->create(['department' => 'CETE']);
        $approver = User::factory()->create(['role' => $role, 'department' => 'CETE']);
        $next = User::factory()->create(['role' => $nextRole]);
        User::factory()->create(['role' => $nextRole, 'account_status' => User::ACCOUNT_SUSPENDED]);
        $requisition = $this->requisition($owner, $status, $tier);

        $this->actingAs($approver)->post(route('admin.status.update', $requisition), ['status' => 'approved'])
            ->assertRedirect()->assertSessionHasNoErrors();

        Queue::assertPushed(SendRequisitionEmail::class, 2);
        Queue::assertPushedOn('mail', SendRequisitionEmail::class, fn ($job) => $job->userId === $owner->id);
        Queue::assertPushedOn('mail', SendRequisitionEmail::class, fn ($job) => $job->userId === $next->id
            && $job->actionUrl === route('admin.approvals', ['request' => $requisition->id]));
    }

    public static function decisions(): array
    {
        return [['rejected'], ['clarification']];
    }

    #[DataProvider('decisions')]
    public function test_decision_emails_include_the_remarks_and_do_not_advance_reviewers(string $decision): void
    {
        Queue::fake();
        $owner = User::factory()->create(['department' => 'CETE']);
        $approver = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        User::factory()->create(['role' => 'vp_finance']);
        $requisition = $this->requisition($owner);

        $this->actingAs($approver)->post(route('admin.status.update', $requisition), [
            'status' => $decision, 'remarks' => 'Please review the requested quantity.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Queue::assertPushed(SendRequisitionEmail::class, 1);
        Queue::assertPushed(SendRequisitionEmail::class, fn ($job) => $job->userId === $owner->id
            && str_contains($job->message, 'Please review the requested quantity.'));
    }

    public function test_fulfillment_emails_each_transition_once_and_repeated_actions_do_not_send_again(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $smo = User::factory()->create(['role' => 'smo']);
        $requisition = $this->requisition($owner, 'approved_vp');
        $supply = Supply::create([
            'item_name' => 'Demo supply', 'category' => 'Office Supplies', 'requisition_tier' => 'minor',
            'quantity' => 10, 'unit' => 'Unit(s)', 'unit_price' => 50, 'min_stock_level' => 1,
        ]);
        RequisitionItem::create([
            'requisition_id' => $requisition->id, 'supply_id' => $supply->id, 'item_name' => $supply->item_name,
            'quantity' => 1, 'unit' => 'Unit(s)', 'unit_price' => 50, 'subtotal' => 50,
        ]);

        foreach (['processing', 'ready-for-pickup', 'complete'] as $index => $action) {
            $this->actingAs($smo)->post(route('admin.requests.'.$action, $requisition))->assertRedirect()->assertSessionHas('success');
            Queue::assertPushed(SendRequisitionEmail::class, $index + 1);
            $this->post(route('admin.requests.'.$action, $requisition))->assertRedirect()->assertSessionHas('error');
            Queue::assertPushed(SendRequisitionEmail::class, $index + 1);
        }

        $this->assertTrue(Queue::pushed(SendRequisitionEmail::class)->every(fn ($job) => $job->userId === $owner->id));
        $this->assertSame(9, $supply->fresh()->quantity);
    }

    public function test_unauthorized_approval_does_not_send_email(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $requisition = $this->requisition($owner, 'approved_dept');
        $head = User::factory()->create(['role' => 'dept_head', 'department_id' => $owner->department_id]);
        $this->actingAs($head)->post(route('admin.status.update', $requisition), ['status' => 'approved'])->assertForbidden();
        Queue::assertNothingPushed();
    }

    private function requisition(User $owner, string $status = 'pending', string $tier = 'minor'): Requisition
    {
        return Requisition::create([
            'user_id' => $owner->id, 'status' => $status, 'request_type' => $tier,
            'purpose' => 'Supplies for the email demonstration.', 'grand_total' => $tier === 'major' ? 1500 : 50,
        ]);
    }
}
