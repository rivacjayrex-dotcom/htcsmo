<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\InventoryAlertService;
use RuntimeException;

class SupplyTest extends ApiTestCase
{
    public function test_supplies_are_paginated_filterable_and_include_catalog_options(): void
    {
        $token = $this->token(User::factory()->create());
        $paper = $this->supply(['configurable_options' => ['Size' => ['A4', 'Legal']]]);
        $this->supply(['item_name' => 'Projector', 'requisition_tier' => 'major', 'unit_price' => 25000]);
        $this->supply(['item_name' => 'Out of stock paper', 'quantity' => 0]);
        $deleted = $this->supply(['item_name' => 'Deleted paper']);
        $deleted->delete();

        $this->api('GET', '/supplies?search=Paper&requisition_tier=minor&in_stock=1&per_page=1', token: $token)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $paper->id)->assertJsonPath('data.0.unit_price', '100.00')
            ->assertJsonPath('data.0.options.Size', ['A4', 'Legal']);
        $this->api('GET', '/supplies/'.$paper->id, token: $token)->assertOk()->assertJsonPath('data.quantity', 20);
        $this->api('GET', '/supplies/'.$deleted->id, token: $token)->assertNotFound();
        $this->api('GET', '/supplies?per_page=101', token: $token)->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_inventory_updates_require_both_smo_role_and_write_ability(): void
    {
        $supply = $this->supply();
        $employeeToken = $this->token(User::factory()->create(), ['*']);
        $this->api('PATCH', '/supplies/'.$supply->id, ['quantity' => 1], $employeeToken)->assertForbidden();
        $smoToken = $this->token(User::factory()->create(['role' => 'smo']));
        $this->api('PATCH', '/supplies/'.$supply->id, ['quantity' => 1], $smoToken)->assertForbidden();
        $this->assertSame(20, $supply->fresh()->quantity);
    }

    public function test_smo_can_partially_update_stock_with_audit_and_low_stock_notifications(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $token = $this->token($smo, ['supplies:write']);
        $supply = $this->supply();
        $this->api('PATCH', '/supplies/'.$supply->id, ['quantity' => 3, 'unit_price' => '120.50', 'role' => 'president'], $token)
            ->assertOk()->assertJsonPath('data.quantity', 3)->assertJsonPath('data.unit_price', '120.50')
            ->assertJsonPath('data.item_name', $supply->item_name);
        $this->assertDatabaseHas('supply_inventory_logs', [
            'supply_id' => $supply->id, 'user_id' => $smo->id, 'quantity_change' => -17, 'balance_after' => 3,
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $smo->id, 'action_id' => $supply->id, 'event_type' => 'inventory.low']);

        $this->api('PATCH', '/supplies/'.$supply->id, ['min_stock_level' => 2], $token)->assertOk();
        $this->assertDatabaseCount('supply_inventory_logs', 1);
        $this->assertSame('smo', $smo->fresh()->role);
    }

    public function test_invalid_inventory_values_and_empty_updates_are_rejected(): void
    {
        $supply = $this->supply();
        $token = $this->token(User::factory()->create(['role' => 'smo']), ['supplies:write']);
        foreach ([['quantity' => -1], ['quantity' => null], ['quantity' => 2147483648], ['unit_price' => 0],
            ['unit_price' => '1.001'], ['min_stock_level' => -2], [], ['item_name' => 'Ignored']] as $data) {
            $this->api('PATCH', '/supplies/'.$supply->id, $data, $token)->assertUnprocessable();
        }
        $this->assertSame(20, $supply->fresh()->quantity);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_notification_failure_does_not_report_a_committed_stock_update_as_failed(): void
    {
        $supply = $this->supply();
        $token = $this->token(User::factory()->create(['role' => 'smo']), ['supplies:write']);
        $this->mock(InventoryAlertService::class)->shouldReceive('notifyIfThresholdCrossed')->once()->andThrow(new RuntimeException('Unavailable'));
        $response = $this->api('PATCH', '/supplies/'.$supply->id, ['quantity' => 0], $token)
            ->assertOk()->assertJsonPath('data.quantity', 0);
        $this->assertNotNull($response->json('notification_warning'));
        $this->assertDatabaseHas('supplies', ['id' => $supply->id, 'quantity' => 0]);
    }
}
