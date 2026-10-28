<?php

namespace Tests\Feature\Api\V1;

use App\Models\Requisition;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class RequisitionTest extends ApiTestCase
{
    public function test_requestors_and_reviewers_can_only_read_their_own_requisitions(): void
    {
        $employee = User::factory()->create();
        $own = $this->requisition($employee);
        $other = $this->requisition(User::factory()->create());
        $token = $this->token($employee);
        $this->api('GET', '/requisitions', token: $token)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)->assertJsonMissingPath('data.0.requestor.email');
        $this->api('GET', '/requisitions/'.$other->id, token: $token)->assertNotFound();
        $this->api('GET', '/requisitions/'.$own->id, token: $token)->assertOk()
            ->assertJsonStructure(['data' => ['tracking_number', 'status', 'next_step', 'items', 'history']]);

        $head = User::factory()->create(['role' => 'dept_head', 'department_id' => $employee->department_id]);
        $this->api('GET', '/requisitions/'.$own->id, token: $this->token($head))->assertNotFound();
    }

    public function test_smo_can_read_all_requisitions_and_filter_the_list(): void
    {
        $owner = User::factory()->create();
        $pending = $this->requisition($owner);
        $this->requisition($owner, ['status' => 'completed']);
        $token = $this->token(User::factory()->create(['role' => 'smo']));
        $this->api('GET', '/requisitions?status=pending&request_type=minor&per_page=1', token: $token)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $pending->id);
        $this->api('GET', '/requisitions/'.$pending->id, token: $token)->assertOk();
        $this->api('GET', '/requisitions?status=invalid', token: $token)->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->api('GET', '/requisitions?per_page=101', token: $token)->assertUnprocessable();
    }

    public function test_submission_uses_catalog_prices_options_owner_and_approval_status(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $head = User::factory()->create(['role' => 'dept_head', 'department_id' => $user->department_id]);
        $supply = $this->supply(['configurable_options' => ['Size' => ['A4', 'Legal']]]);
        $response = $this->api('POST', '/requisitions', [
            'request_type' => 'minor', 'purpose' => 'Printing classroom learning materials.',
            'user_id' => $head->id, 'status' => 'approved_president', 'grand_total' => 1,
            'items' => [['supply_id' => $supply->id, 'qty' => 2, 'options' => ['Size' => 'A4'], 'price' => 1, 'name' => 'Forged']],
        ], $this->token($user))->assertCreated()->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.grand_total', '200.00')->assertJsonPath('data.requestor.id', $user->id)
            ->assertJsonPath('data.items.0.unit_price', '100.00')->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.specifications', '80 GSM | Size: A4')->assertJsonPath('notification_warning', null);
        $id = $response->json('data.id');
        $response->assertHeader('Location', route('api.v1.requisitions.show', $id));
        $this->assertDatabaseHas('requisitions', ['id' => $id, 'user_id' => $user->id, 'grand_total' => 200]);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'action_id' => $id, 'event_type' => 'requisition.submitted']);
        $this->assertDatabaseHas('notifications', ['user_id' => $head->id, 'action_id' => $id, 'event_type' => 'requisition.awaiting_approval']);
        $this->assertSame(20, $supply->fresh()->quantity);
    }

    public function test_submission_rejects_wrong_tiers_invalid_options_and_combined_stock_overages(): void
    {
        $token = $this->token(User::factory()->create());
        $supply = $this->supply(['quantity' => 5, 'configurable_options' => ['Size' => ['A4', 'Legal']]]);
        $base = ['request_type' => 'minor', 'purpose' => 'Printing classroom learning materials.'];
        foreach ([
            [['supply_id' => $supply->id, 'qty' => 1]],
            [['supply_id' => $supply->id, 'qty' => 1, 'options' => ['Size' => 'Invalid']]],
            [['supply_id' => $supply->id, 'qty' => 6, 'options' => ['Size' => 'A4']]],
            [['supply_id' => $supply->id, 'qty' => 3, 'options' => ['Size' => 'A4']], ['supply_id' => $supply->id, 'qty' => 3, 'options' => ['Size' => 'Legal']]],
        ] as $items) {
            $this->api('POST', '/requisitions', $base + ['items' => $items], $token)->assertUnprocessable()->assertJsonValidationErrors('items');
        }
        $supply->update(['requisition_tier' => 'major']);
        $this->api('POST', '/requisitions', $base + ['items' => [['supply_id' => $supply->id, 'qty' => 1]]], $token)
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('requisitions', 0);
    }

    public function test_minor_and_major_thresholds_and_executive_routing_are_preserved(): void
    {
        Mail::fake();
        $token = $this->token(User::factory()->create(['role' => 'president']));
        $supply = $this->supply(['unit_price' => 500]);
        $data = ['request_type' => 'minor', 'purpose' => 'Printing classroom learning materials.', 'items' => [['supply_id' => $supply->id, 'qty' => 3]]];
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable()->assertJsonValidationErrors('items');
        $data['items'][0]['qty'] = 2;
        $this->api('POST', '/requisitions', $data, $token)->assertCreated()->assertJsonPath('data.status', 'approved_president')
            ->assertJsonPath('data.grand_total', '1000.00');
        $supply->update(['requisition_tier' => 'major']);
        $data['request_type'] = 'major';
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable()->assertJsonValidationErrors('items');
        $data['items'][0]['qty'] = 3;
        $this->api('POST', '/requisitions', $data, $token)->assertCreated()->assertJsonPath('data.grand_total', '1500.00');
    }

    public function test_invalid_payloads_and_deleted_catalog_items_cannot_create_requests(): void
    {
        $token = $this->token(User::factory()->create());
        $this->api('POST', '/requisitions', [], $token)->assertUnprocessable()->assertJsonValidationErrors(['request_type', 'purpose', 'items']);
        $supply = $this->supply();
        $data = ['request_type' => 'minor', 'purpose' => 'Printing classroom learning materials.', 'items' => [['supply_id' => $supply->id, 'qty' => 0]]];
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        $supply->delete();
        $data['items'][0]['qty'] = 1;
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable();
        $this->assertDatabaseCount('requisitions', 0);
    }

    public function test_notification_failures_return_a_warning_without_losing_the_saved_requisition(): void
    {
        Mail::fake();
        $supply = $this->supply();
        $this->mock(NotificationService::class)->shouldReceive('send')->once()->andThrow(new RuntimeException('Unavailable'));
        $response = $this->api('POST', '/requisitions', [
            'request_type' => 'minor', 'purpose' => 'Printing classroom learning materials.',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ], $this->token(User::factory()->create()))->assertCreated();
        $this->assertNotNull($response->json('notification_warning'));
        $this->assertDatabaseCount('requisitions', 1);
        $this->assertDatabaseCount('requisition_items', 1);
    }

    public function test_submissions_have_a_separate_rate_limit(): void
    {
        $token = $this->token(User::factory()->create());
        for ($i = 0; $i < 10; $i++) {
            $this->api('POST', '/requisitions', [], $token)->assertUnprocessable();
        }
        $this->api('POST', '/requisitions', [], $token)->assertStatus(429);
        $this->assertSame(0, Requisition::count());
    }

    public function test_values_larger_than_requisition_storage_limits_are_rejected_before_writing(): void
    {
        $token = $this->token(User::factory()->create());
        $supply = $this->supply(['requisition_tier' => 'major', 'unit_price' => 99999999.99, 'quantity' => 1000]);
        $data = ['request_type' => 'major', 'purpose' => 'Supplies for a large institutional project.', 'items' => [['supply_id' => $supply->id, 'qty' => 1000]]];
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable()->assertJsonValidationErrors('items');
        $supply->update(['unit_price' => 100000000]);
        $data['items'][0]['qty'] = 1;
        $this->api('POST', '/requisitions', $data, $token)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('requisitions', 0);
        $this->assertDatabaseCount('requisition_items', 0);
    }
}
