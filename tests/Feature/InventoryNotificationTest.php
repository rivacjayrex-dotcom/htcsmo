<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Supply;
use App\Models\User;
use App\Services\InventoryAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_smo_users_are_warned_when_stock_crosses_the_threshold(): void
    {
        $firstSmo = User::factory()->create(['role' => 'smo']);
        $secondSmo = User::factory()->create(['role' => 'smo']);
        $supply = $this->supply(5);

        app(InventoryAlertService::class)->notifyIfThresholdCrossed($supply, false, false);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $firstSmo->id,
            'title' => 'Low Stock Warning: Bond Paper',
            'type' => 'warning',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $secondSmo->id,
            'icon' => 'triangle-alert',
        ]);

        app(InventoryAlertService::class)->notifyIfThresholdCrossed($supply, true, false);
        $this->assertSame(2, Notification::count(), 'Remaining below the threshold must not create duplicate alerts.');
    }

    public function test_notification_polling_returns_only_newer_unread_notifications(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $old = Notification::create(['user_id' => $smo->id, 'title' => 'Old', 'message' => 'Old alert']);
        $new = Notification::create(['user_id' => $smo->id, 'title' => 'New', 'message' => 'New alert']);

        $this->actingAs($smo)
            ->getJson('/api/latest-notification?after_id='.$old->id)
            ->assertOk()
            ->assertJsonPath('notification.id', $new->id)
            ->assertJsonPath('latest_id', $new->id);
    }

    private function supply(int $quantity): Supply
    {
        return Supply::create([
            'item_name' => 'Bond Paper',
            'brand' => 'HTC Test',
            'category' => 'Office Supplies',
            'requisition_tier' => 'minor',
            'specifications' => 'A4 80 GSM',
            'quantity' => $quantity,
            'unit' => 'Ream(s)',
            'unit_price' => 200,
            'min_stock_level' => 5,
        ]);
    }
}
