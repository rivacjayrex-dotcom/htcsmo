<?php

namespace Tests\Feature;

use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_separates_tiers_and_prioritizes_low_stock(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        $healthy = $this->supply('Healthy Paper', 'minor', 40, 5);
        $major = $this->supply('Projector', 'major', 3, 1, 25000);
        $low = $this->supply('Low Ink', 'minor', 2, 5);
        $out = $this->supply('Empty Toner', 'minor', 0, 5);

        $response = $this->actingAs($smo)->get(route('inventory.index'));

        $response->assertOk()
            ->assertSee('Restock priority')
            ->assertSee('Minor catalog')
            ->assertSee('Major catalog')
            ->assertSeeInOrder([$out->item_name, $low->item_name, $healthy->item_name])
            ->assertSee($major->item_name);
    }

    private function supply(string $name, string $tier, int $quantity, int $minimum, float $price = 100): Supply
    {
        return Supply::create([
            'item_name' => $name,
            'brand' => 'HTC Test',
            'category' => 'Office Supplies',
            'requisition_tier' => $tier,
            'specifications' => 'Standard specification',
            'quantity' => $quantity,
            'unit' => 'Piece(s)',
            'unit_price' => $price,
            'min_stock_level' => $minimum,
        ]);
    }
}
