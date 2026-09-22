<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionModificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requestor_can_edit_and_delete_a_pending_requisition(): void
    {
        $requestor = User::factory()->create();
        $requisitionToEdit = $this->createRequisition($requestor, 'pending');
        $requisitionToDelete = $this->createRequisition($requestor, 'pending');

        $this
            ->actingAs($requestor)
            ->get(route('requisitions.edit', $requisitionToEdit))
            ->assertOk();

        $this
            ->actingAs($requestor)
            ->delete(route('requisitions.destroy', $requisitionToDelete))
            ->assertRedirect(route('requests.index'));

        $this->assertDatabaseMissing('requisitions', ['id' => $requisitionToDelete->id]);
    }

    public function test_requestor_cannot_edit_update_or_delete_after_department_head_approval(): void
    {
        $requestor = User::factory()->create();
        $requisition = $this->createRequisition($requestor, 'approved_dept');

        $this
            ->actingAs($requestor)
            ->get(route('requisitions.edit', $requisition))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this
            ->from(route('requests.index'))
            ->actingAs($requestor)
            ->patch(route('requisitions.update', $requisition), [
                'request_type' => 'minor',
                'items' => [[
                    'name' => 'Changed item',
                    'specs' => null,
                    'qty' => 1,
                    'price' => 50,
                ]],
            ])
            ->assertRedirect(route('requests.index'))
            ->assertSessionHas('error');

        $this
            ->from(route('requests.index'))
            ->actingAs($requestor)
            ->delete(route('requisitions.destroy', $requisition))
            ->assertRedirect(route('requests.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => 'approved_dept',
        ]);
    }

    private function createRequisition(User $requestor, string $status): Requisition
    {
        return Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => $status,
            'grand_total' => 100,
        ]);
    }
}
