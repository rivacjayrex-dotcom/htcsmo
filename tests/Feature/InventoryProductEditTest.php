<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Services\InventoryAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryProductEditTest extends TestCase
{
    use RefreshDatabase;

    private User $smo;

    private Supply $legacy;

    private Supply $fixed;

    private Supply $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smo = User::factory()->create(['role' => 'smo']);
        $this->legacy = Supply::create([
            ...$this->base(), 'quantity' => 92, 'unit_price' => 150, 'specifications' => 'Original SMO catalog description',
            'configurable_options' => ['Brand' => ['Hard Copy', 'Double A'], 'Size' => ['Short', 'Legal']],
        ]);
        $options = ['Brand' => ['Hard Copy'], 'Grams' => ['80gsm']];
        $this->fixed = Supply::create([
            ...$this->base(), 'size' => 'A4', 'supplier' => 'Starbright', 'quantity' => 12, 'unit_price' => 190,
            'specifications' => 'A4 variant description', 'configurable_options' => $options, 'variant_key' => Supply::combinationKey($options),
        ]);
        $this->other = Supply::create([...$this->base(), 'item_name' => 'Printer', 'quantity' => 3, 'unit_price' => 5000]);
        $this->actingAs($this->smo);
    }

    public function test_product_edit_opens_every_variant_even_from_a_filtered_catalog(): void
    {
        $catalog = $this->get(route('inventory.index', ['search' => 'A4']))->assertOk();
        $group = $catalog->viewData('catalogGroups')[0];
        $this->assertCount(1, $group['variants']);
        $this->assertSame(route('inventory.products.edit', $this->fixed), $group['edit_url']);
        $page = $this->get($group['edit_url'])->assertOk()->assertSee('Edit Bond Paper')->assertSee('Save all changes');
        $this->assertSame([$this->legacy->id, $this->fixed->id], $page->viewData('variants')->pluck('id')->all());
        $this->assertCount(2, $page->viewData('rows'));
        $this->assertSame('Original SMO catalog description', $page->viewData('rows')[0]['specifications']);
    }

    public function test_one_save_updates_the_whole_product_and_preserves_variant_ids_and_shared_choices(): void
    {
        $payload = $this->payload();
        $payload['item_name'] = 'Premium Bond Paper';
        $payload['variants'][0]['quantity'] = 90;
        $payload['variants'][0]['unit_price'] = '155.50';
        $payload['variants'][1]['quantity'] = 15;
        $payload['variants'][1]['unit_price'] = '205.00';
        $this->patch(route('inventory.products.update', $this->fixed), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'));
        $this->assertDatabaseHas('supplies', ['id' => $this->legacy->id, 'item_name' => 'Premium Bond Paper', 'quantity' => 90, 'unit_price' => 155.50]);
        $this->assertDatabaseHas('supplies', ['id' => $this->fixed->id, 'item_name' => 'Premium Bond Paper', 'quantity' => 15, 'unit_price' => 205]);
        $this->assertSame(['Brand' => ['Hard Copy', 'Double A'], 'Size' => ['Short', 'Legal']], $this->legacy->fresh()->requestOptions());
        $this->assertSame('A4 variant description', $this->fixed->fresh()->specifications);
        $this->assertSame('Original SMO catalog description', $this->legacy->fresh()->specifications);
        $this->assertSame('Printer', $this->other->fresh()->item_name);
        $this->assertSame(3, $this->other->fresh()->quantity);
        $this->assertDatabaseHas('supply_inventory_logs', ['supply_id' => $this->legacy->id, 'quantity_change' => -2, 'balance_after' => 90]);
        $this->assertDatabaseHas('supply_inventory_logs', ['supply_id' => $this->fixed->id, 'quantity_change' => 3, 'balance_after' => 15]);
    }

    public function test_one_description_can_be_applied_to_every_variant_when_explicitly_selected(): void
    {
        $payload = $this->payload();
        $payload['apply_description'] = '1';
        $payload['shared_specifications'] = 'One updated product description';
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasNoErrors();
        $this->assertSame('One updated product description', $this->legacy->fresh()->specifications);
        $this->assertSame('One updated product description', $this->fixed->fresh()->specifications);
    }

    public function test_invalid_or_duplicate_rows_leave_the_entire_product_unchanged(): void
    {
        $payload = $this->payload();
        $payload['item_name'] = 'Should not save';
        $payload['variants'][0]['quantity'] = 50;
        $payload['variants'][1]['unit_price'] = -1;
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants.1.unit_price');
        $this->assertSame(92, $this->legacy->fresh()->quantity);
        $this->assertSame('Bond Paper', $this->legacy->fresh()->item_name);
        $payload = $this->payload();
        $payload['variants'][] = [...$payload['variants'][1], 'id' => null, 'version' => null];
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants.2.item_name');
        $this->assertDatabaseCount('supplies', 3);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_an_old_form_cannot_overwrite_a_stock_release_or_price_change(): void
    {
        $payload = $this->payload();
        $this->fixed->decrement('quantity', 2);
        $payload['variants'][0]['quantity'] = 70;
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants.1.quantity');
        $this->assertSame(92, $this->legacy->fresh()->quantity);
        $this->assertSame(10, $this->fixed->fresh()->quantity);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_missing_or_foreign_variant_ids_cannot_modify_a_different_product(): void
    {
        $payload = $this->payload();
        array_pop($payload['variants']);
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants');
        $payload = $this->payload();
        $payload['variants'][1]['id'] = $this->other->id;
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants');
        $this->assertSame(3, $this->other->fresh()->quantity);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_a_new_variant_can_be_added_in_the_same_save_as_existing_variant_edits(): void
    {
        $payload = $this->payload();
        $payload['variants'][0]['unit_price'] = 160;
        $payload['variants'][] = [...$payload['variants'][1], 'id' => null, 'version' => null, 'size' => 'A5', 'unit_price' => 180, 'quantity' => 8];
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasNoErrors();
        $this->assertSame('160.00', $this->legacy->fresh()->unit_price);
        $this->assertDatabaseHas('supplies', ['item_name' => 'Bond Paper', 'size' => 'A5', 'unit_price' => 180, 'quantity' => 8]);
        $this->assertDatabaseCount('supplies', 4);
    }

    public function test_requisition_snapshots_and_linked_variant_identity_survive_product_edits(): void
    {
        $request = Requisition::create(['user_id' => $this->smo->id, 'request_type' => 'minor', 'purpose' => 'Paper for official operations', 'status' => 'pending', 'grand_total' => 190]);
        $line = $request->items()->create(['supply_id' => $this->fixed->id, 'item_name' => 'Bond Paper', 'size' => 'A4', 'supplier' => 'Starbright', 'quantity' => 1, 'unit' => 'Ream(s)', 'unit_price' => 190, 'subtotal' => 190]);
        $payload = $this->payload();
        $payload['variants'][1]['unit_price'] = 220;
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasNoErrors();
        $this->assertEquals(190, $line->fresh()->unit_price);
        $this->assertSame('220.00', $this->fixed->fresh()->unit_price);
        $payload = $this->payload();
        $payload['variants'][1]['options'][0]['values'] = 'Different brand';
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants.1.options');
        $this->assertSame(['Hard Copy'], $this->fixed->fresh()->requestOptions()['Brand']);
        $payload = $this->payload();
        $payload['variants'][1]['unit'] = 'Sheets';
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasErrors('variants.1.unit');
        $this->assertSame('Ream(s)', $this->fixed->fresh()->unit);
    }

    public function test_a_stock_log_failure_rolls_back_every_product_change(): void
    {
        $payload = $this->payload();
        $payload['item_name'] = 'Should not save';
        $payload['variants'][0]['quantity'] = 70;
        $payload['variants'][1]['quantity'] = 5;
        $events = SupplyInventoryLog::getEventDispatcher();
        SupplyInventoryLog::setEventDispatcher(clone $events);
        SupplyInventoryLog::creating(fn () => throw new \RuntimeException('Simulated movement log failure'));
        $this->withoutExceptionHandling();
        try {
            $this->patch(route('inventory.products.update', $this->legacy), $payload);
            $this->fail('Expected the movement log to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated movement log failure', $exception->getMessage());
        } finally {
            SupplyInventoryLog::setEventDispatcher($events);
        }
        $this->assertSame('Bond Paper', $this->legacy->fresh()->item_name);
        $this->assertSame(92, $this->legacy->fresh()->quantity);
        $this->assertSame(12, $this->fixed->fresh()->quantity);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_notification_failure_reports_saved_changes_without_a_false_save_failure(): void
    {
        $payload = $this->payload();
        $payload['variants'][0]['unit_price'] = 165;
        $this->mock(InventoryAlertService::class)->shouldReceive('notifyIfThresholdCrossed')->twice()->andThrow(new \RuntimeException('Alerts unavailable'));
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Product and all 2 variants saved. Some stock alerts could not be sent.');
        $this->assertSame('165.00', $this->legacy->fresh()->unit_price);
    }

    public function test_non_smo_accounts_cannot_open_or_save_the_product_editor(): void
    {
        $payload = $this->payload();
        $this->actingAs(User::factory()->create(['role' => 'employee']));
        $this->get(route('inventory.products.edit', $this->legacy))->assertRedirect(route('dashboard'));
        $this->patch(route('inventory.products.update', $this->legacy), $payload)->assertRedirect(route('dashboard'));
        $this->assertSame(92, $this->legacy->fresh()->quantity);
    }

    private function payload(): array
    {
        $page = $this->get(route('inventory.products.edit', $this->legacy))->assertOk();

        return ['item_name' => 'Bond Paper', 'category' => 'Office Supplies', 'apply_description' => '0', 'variants' => $page->viewData('rows')];
    }

    private function base(): array
    {
        return ['item_name' => 'Bond Paper', 'category' => 'Office Supplies', 'unit' => 'Ream(s)', 'min_stock_level' => 5];
    }
}
