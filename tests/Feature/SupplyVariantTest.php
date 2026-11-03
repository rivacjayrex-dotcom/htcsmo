<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SupplyVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_inventory_registers_separate_sizes_and_suppliers_and_rejects_duplicate_combinations(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        foreach ([['A4', 'Supplier A', 200], ['Letter', 'Supplier B', 180], ['A4', 'Supplier B', 220]] as [$size, $supplier, $price]) {
            $this->post(route('inventory.store'), $this->payload($size, $supplier, $price))
                ->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'));
        }
        $this->assertDatabaseCount('supplies', 3);
        $this->post(route('inventory.store'), $this->payload('A4', 'Supplier A', 250))
            ->assertSessionHasErrors('item_name');

        $this->get(route('inventory.index', ['search' => 'Supplier B']))->assertOk()
            ->assertViewHas('catalogSupplies', fn ($items) => $items->count() === 2)
            ->assertSee('Bond Paper · Letter · Supplier B');
        $this->getJson(route('api.requisition-catalog'))->assertOk()->assertJsonCount(3)
            ->assertJsonFragment(['size' => 'Letter', 'supplier' => 'Supplier B', 'price' => 180]);
    }

    public function test_size_and_supplier_cannot_be_saved_as_shared_stock_choices(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        foreach (['Size', 'Supplier'] as $label) {
            $this->post(route('inventory.store'), [
                ...$this->payload('', '', 200),
                'option_names' => ['Brand', $label], 'option_values' => ['PaperOne', 'First, Second'],
            ])->assertSessionHasErrors(strtolower($label));
        }
        $this->assertDatabaseCount('supplies', 0);
    }

    public function test_single_legacy_size_choice_moves_to_the_stock_identity(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->post(route('inventory.store'), [
                ...$this->payload('', 'Supplier A', 200),
                'option_names' => ['Brand', 'Size'], 'option_values' => ['PaperOne', 'A4'],
            ])->assertSessionHasNoErrors();
        $supply = Supply::firstOrFail();
        $this->assertSame('A4', $supply->size);
        $this->assertSame(['Brand' => ['PaperOne']], $supply->requestOptions());
    }

    public function test_submission_snapshots_the_selected_supplier_and_price_and_release_deducts_only_that_stock(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $owner = User::factory()->create();
        $a4 = $this->supply('A4', 'Supplier A', 200);
        $letter = $this->supply('Letter', 'Supplier B', 180);
        $otherSupplier = $this->supply('A4', 'Supplier C', 240);
        $this->actingAs($owner)->post(route('requisitions.store'), [
            'request_type' => 'minor', 'purpose' => 'Paper for classroom assessment materials.',
            'items' => [
                ['supply_id' => $a4->id, 'qty' => 2, 'price' => 1, 'supplier' => 'Forged'],
                ['supply_id' => $letter->id, 'qty' => 1],
            ],
        ])->assertSessionHasNoErrors();
        $request = Requisition::firstOrFail();
        $this->assertEquals(580, $request->grand_total);
        $this->assertDatabaseHas('requisition_items', [
            'supply_id' => $a4->id, 'size' => 'A4', 'supplier' => 'Supplier A',
            'unit_price' => 200, 'subtotal' => 400,
            'specifications' => '80 GSM white paper | Size: A4; Supplier: Supplier A | Brand: PaperOne',
        ]);
        $a4->update(['unit_price' => 250]);
        $this->assertEquals(200, $request->items()->where('supply_id', $a4->id)->firstOrFail()->unit_price);
        $request->update(['status' => 'processing']);
        $this->actingAs($smo)->post(route('admin.requests.ready-for-pickup', $request))->assertSessionHas('success');
        $this->assertSame(8, $a4->fresh()->quantity);
        $this->assertSame(9, $letter->fresh()->quantity);
        $this->assertSame(10, $otherSupplier->fresh()->quantity);
        $this->assertDatabaseHas('supply_inventory_logs', ['supply_id' => $a4->id, 'quantity_change' => -2]);
        $this->assertDatabaseHas('supply_inventory_logs', ['supply_id' => $letter->id, 'quantity_change' => -1]);
    }

    public function test_stock_limits_are_checked_per_variant_and_across_duplicate_lines(): void
    {
        $a4 = $this->supply('A4', 'Supplier A', 20);
        $this->supply('Letter', 'Supplier B', 20);
        $this->actingAs(User::factory()->create());
        foreach ([[11], [6, 5]] as $quantities) {
            $this->post(route('requisitions.store'), [
                'request_type' => 'minor', 'purpose' => 'Paper for classroom assessment materials.',
                'items' => array_map(fn ($qty) => ['supply_id' => $a4->id, 'qty' => $qty], $quantities),
            ])->assertSessionHasErrors('items');
        }
        $this->assertDatabaseCount('requisitions', 0);
    }

    public function test_legacy_name_only_requisition_cannot_deduct_an_arbitrary_variant(): void
    {
        $a4 = $this->supply('A4', 'Supplier A', 200);
        $letter = $this->supply('Letter', 'Supplier B', 180);
        $request = Requisition::create([
            'user_id' => User::factory()->create()->id, 'request_type' => 'minor', 'status' => 'processing',
            'purpose' => 'Legacy paper request', 'grand_total' => 200,
        ]);
        $request->items()->create(['item_name' => 'Bond Paper', 'quantity' => 1, 'unit' => 'Ream(s)', 'unit_price' => 200, 'subtotal' => 200]);
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->post(route('admin.requests.ready-for-pickup', $request))->assertSessionHas('error');
        $this->assertSame(10, $a4->fresh()->quantity);
        $this->assertSame(10, $letter->fresh()->quantity);
        $this->assertDatabaseCount('supply_inventory_logs', 0);
    }

    public function test_size_and_supplier_identity_of_requisition_linked_stock_cannot_be_reassigned(): void
    {
        $supply = $this->supply('A4', 'Supplier A', 200);
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('requisitions.store'), [
            'request_type' => 'minor', 'purpose' => 'Paper for classroom assessment materials.',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => 'smo']))
            ->patch(route('inventory.update', $supply), $this->payload('Letter', 'Supplier B', 180))
            ->assertSessionHasErrors('size');
        $this->assertSame('A4', $supply->fresh()->size);
        $this->assertSame('Supplier A', $supply->fresh()->supplier);
    }

    private function payload(string $size, string $supplier, int $price): array
    {
        return [
            'item_name' => 'Bond Paper', 'size' => $size, 'supplier' => $supplier,
            'category' => 'Office Supplies', 'specifications' => '80 GSM white paper',
            'quantity' => 10, 'unit' => 'Ream(s)', 'unit_price' => $price, 'min_stock_level' => 2,
            'option_names' => ['Brand'], 'option_values' => ['PaperOne'],
        ];
    }

    private function supply(string $size, string $supplier, int $price): Supply
    {
        $attributes = $this->payload($size, $supplier, $price);
        unset($attributes['option_names'], $attributes['option_values']);

        return Supply::create([...$attributes, 'brand' => 'PaperOne']);
    }
}
