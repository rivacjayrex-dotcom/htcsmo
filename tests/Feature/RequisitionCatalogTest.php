<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_uses_catalog_data_and_selected_options(): void
    {
        $user = User::factory()->create();
        $supply = Supply::create([
            'item_name' => 'Bond Paper',
            'brand' => 'PaperOne, Double A',
            'category' => 'Office Supplies',
            'requisition_tier' => 'minor',
            'specifications' => '80 GSM',
            'configurable_options' => ['Size' => ['A4', 'Legal'], 'Color' => ['White']],
            'quantity' => 20,
            'unit' => 'Ream(s)',
            'unit_price' => 200,
            'min_stock_level' => 5,
        ]);

        $this->actingAs($user)->post(route('requisitions.store'), [
            'request_type' => 'minor',
            'purpose' => 'For printing midterm assessment materials.',
            'items' => [[
                'supply_id' => $supply->id,
                'qty' => 2,
                'options' => ['Brand' => 'PaperOne', 'Size' => 'A4', 'Color' => 'White'],
                'price' => 1,
                'name' => 'Forged name',
            ]],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('requisition_items', [
            'supply_id' => $supply->id,
            'item_name' => 'Bond Paper',
            'quantity' => 2,
            'unit_price' => 200,
            'subtotal' => 400,
            'specifications' => '80 GSM | Brand: PaperOne; Size: A4; Color: White',
        ]);
        $this->assertDatabaseHas('requisitions', [
            'user_id' => $user->id,
            'purpose' => 'For printing midterm assessment materials.',
        ]);
    }

    public function test_item_cannot_be_submitted_through_the_wrong_tier(): void
    {
        $user = User::factory()->create();
        $supply = Supply::create([
            'item_name' => 'Projector',
            'category' => 'Office Supplies',
            'requisition_tier' => 'major',
            'specifications' => 'Full HD',
            'quantity' => 2,
            'unit' => 'Unit(s)',
            'unit_price' => 25000,
            'min_stock_level' => 1,
        ]);

        $this->actingAs($user)->post(route('requisitions.store'), [
            'request_type' => 'minor',
            'purpose' => 'For classroom presentation use.',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('requisitions', 0);
    }
}
