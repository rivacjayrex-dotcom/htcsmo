<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PricedSupplyCombinationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_smo_enters_different_prices_for_the_same_item_size_and_supplier(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->post(route('inventory.store'), $this->payload())->assertSessionHasNoErrors();
        $supplies = Supply::orderBy('id')->get();
        $this->assertCount(3, $supplies);
        $this->assertSame('450.00', $supplies[0]->unit_price);
        $this->assertSame('650.00', $supplies[1]->unit_price);
        $this->assertSame(['Brand' => 'HTC', 'Style' => 'Vintage', 'Quality' => 'Premium'], $supplies[1]->pricedAttributes());
        $this->assertNotEquals($supplies[0]->variant_key, $supplies[1]->variant_key);
        $this->assertSame('Large', $supplies[0]->size);
        $this->assertSame('Large', $supplies[1]->size);
        $this->getJson(route('api.requisition-catalog'))->assertOk()->assertJsonFragment([
            'attributes' => ['Brand' => 'HTC', 'Style' => 'Vintage', 'Quality' => 'Premium'],
            'size' => 'Large', 'supplier' => 'Supplier A', 'price' => 650, 'stock' => 10,
        ]);
        $this->get(route('inventory.index', ['search' => 'Premium']))->assertOk()
            ->assertViewHas('catalogSupplies', fn ($items) => $items->count() === 2);
        $this->get(route('inventory.edit', $supplies[1]))->assertOk()->assertSee('Combination attributes');
        $this->get(route('inventory.create'))->assertOk()->assertSee('Different prices for different selections')->assertSee('Prices and stock by combination');
    }

    public function test_combination_prices_and_attributes_are_snapshotted_and_fulfillment_uses_only_selected_stock(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $this->actingAs($smo)->post(route('inventory.store'), $this->payload())->assertSessionHasNoErrors();
        [$black, $vintage, $medium] = Supply::orderBy('id')->get()->all();
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('requisitions.store'), [
            'request_type' => 'major', 'purpose' => 'Clothing for the institutional demonstration.',
            'items' => [
                ['supply_id' => $black->id, 'qty' => 1, 'price' => 1],
                ['supply_id' => $vintage->id, 'qty' => 1, 'price' => 1],
            ],
        ])->assertSessionHasNoErrors();
        $request = Requisition::firstOrFail();
        $this->assertEquals(1100, $request->grand_total);
        $line = $request->items()->where('supply_id', $vintage->id)->firstOrFail();
        $this->assertSame(['Brand' => 'HTC', 'Style' => 'Vintage', 'Quality' => 'Premium'], $line->variant_attributes);
        $this->assertEquals(650, $line->unit_price);
        $this->assertStringContainsString('Style: Vintage; Quality: Premium', $line->specifications);
        $vintage->update(['unit_price' => 700]);
        $this->assertEquals(650, $line->fresh()->unit_price);
        $request->update(['status' => 'processing']);
        $this->actingAs($smo)->post(route('admin.requests.ready-for-pickup', $request))->assertSessionHas('success');
        $this->assertSame(19, $black->fresh()->quantity);
        $this->assertSame(9, $vintage->fresh()->quantity);
        $this->assertSame(15, $medium->fresh()->quantity);
    }

    public function test_duplicate_or_invalid_combinations_save_no_partial_inventory(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        $duplicate = $this->payload();
        $duplicate['variants'][1] = $duplicate['variants'][0];
        $duplicate['variants'][1]['values'] = ['htc', 'black', 'standard'];
        $this->post(route('inventory.store'), $duplicate)->assertSessionHasErrors('variants.1');
        $this->assertDatabaseCount('supplies', 0);

        $invalid = $this->payload();
        $invalid['variants'][2]['unit_price'] = -1;
        $this->post(route('inventory.store'), $invalid)->assertSessionHasErrors('variants.2.unit_price');
        $this->assertDatabaseCount('supplies', 0);

        $invalid = $this->payload();
        array_pop($invalid['variants'][1]['values']);
        $this->post(route('inventory.store'), $invalid)->assertSessionHasErrors('variants.1.values');
        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_existing_combination_is_rejected_even_if_attribute_columns_are_reordered(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->post(route('inventory.store'), $this->payload())->assertSessionHasNoErrors();
        $reordered = $this->payload();
        $reordered['variant_names'] = ['Quality', 'Style', 'Brand'];
        $reordered['variants'] = [[...$reordered['variants'][0], 'values' => ['Standard', 'Black', 'HTC']]];
        $this->post(route('inventory.store'), $reordered)->assertSessionHasErrors('item_name');
        $this->assertDatabaseCount('supplies', 3);
    }

    public function test_forged_selection_cannot_use_the_cheaper_stock_record(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->post(route('inventory.store'), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->post(route('requisitions.store'), [
            'request_type' => 'minor', 'purpose' => 'Clothing for the institutional demonstration.',
            'items' => [['supply_id' => Supply::firstOrFail()->id, 'qty' => 1,
                'options' => ['Brand' => 'HTC', 'Style' => 'Vintage', 'Quality' => 'Premium']]],
        ])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('requisitions', 0);
    }

    public function test_price_and_stock_can_be_updated_without_reassigning_a_linked_combination(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $this->actingAs($smo)->post(route('inventory.store'), $this->payload())->assertSessionHasNoErrors();
        $stock = Supply::firstOrFail();
        $this->actingAs(User::factory()->create())->post(route('requisitions.store'), [
            'request_type' => 'minor', 'purpose' => 'Clothing for the institutional demonstration.',
            'items' => [['supply_id' => $stock->id, 'qty' => 1]],
        ])->assertSessionHasNoErrors();
        $update = [
            ...$this->payload(), 'size' => 'Large', 'supplier' => 'Supplier A', 'quantity' => 25,
            'unit_price' => 475, 'min_stock_level' => 2,
            'option_names' => ['Brand', 'Style', 'Quality'], 'option_values' => ['HTC', 'Black', 'Standard'],
        ];
        unset($update['variants'], $update['variant_names']);
        $this->actingAs($smo)->patch(route('inventory.update', $stock), $update)->assertSessionHasNoErrors();
        $this->assertSame('475.00', $stock->fresh()->unit_price);
        $this->assertSame(25, $stock->fresh()->quantity);
        $update['option_values'][2] = 'Premium';
        $this->patch(route('inventory.update', $stock), $update)->assertSessionHasErrors('size');
        $this->assertSame('Standard', $stock->fresh()->pricedAttributes()['Quality']);
        $this->assertEquals(450, Requisition::firstOrFail()->items()->firstOrFail()->unit_price);
    }

    private function payload(): array
    {
        return [
            'item_name' => 'Demonstration Pants', 'category' => 'Non-Medical Supplies',
            'specifications' => 'Institutional clothing demonstration', 'unit' => 'Piece(s)',
            'variant_names' => ['Brand', 'Style', 'Quality'],
            'variants' => [
                ['values' => ['HTC', 'Black', 'Standard'], 'size' => 'Large', 'supplier' => 'Supplier A', 'unit_price' => 450, 'quantity' => 20, 'min_stock_level' => 2],
                ['values' => ['HTC', 'Vintage', 'Premium'], 'size' => 'Large', 'supplier' => 'Supplier A', 'unit_price' => 650, 'quantity' => 10, 'min_stock_level' => 2],
                ['values' => ['HTC', 'Vintage', 'Premium'], 'size' => 'Medium', 'supplier' => 'Supplier A', 'unit_price' => 600, 'quantity' => 15, 'min_stock_level' => 2],
            ],
        ];
    }
}
