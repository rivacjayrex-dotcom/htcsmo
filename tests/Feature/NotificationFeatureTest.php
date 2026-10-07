<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_requisition_submission_notifies_owner_and_matching_department_heads_only(): void
    {
        $owner = User::factory()->create(['department' => 'CETE']);
        $matchingHead = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $otherHead = User::factory()->create(['role' => 'dept_head', 'department' => 'CAS']);
        $supply = $this->supply();

        $this->actingAs($owner)->post(route('requisitions.store'), [
            'request_type' => 'minor',
            'purpose' => 'Department supplies for classroom instruction.',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ])->assertRedirect(route('dashboard'));

        $requisition = Requisition::firstOrFail();
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'event_type' => 'requisition.submitted', 'action_id' => $requisition->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $matchingHead->id, 'event_type' => 'requisition.awaiting_approval', 'action_id' => $requisition->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $otherHead->id, 'action_id' => $requisition->id]);
    }

    public function test_dedupe_key_prevents_retried_event_from_creating_a_second_notification(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $options = ['dedupe_key' => 'same-event', 'icon' => 'not-allowed', 'type' => 'not-allowed'];

        $first = $service->send($user, 'test.event', '<b>Safe title</b>', '<script>alert(1)</script>Message', $options);
        $second = $service->send($user, 'test.event', 'Changed', 'Changed', $options);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Notification::where('user_id', $user->id)->count());
        $this->assertSame('bell', $first->icon);
        $this->assertSame('info', $first->type);
        $this->assertSame('Safe title', $first->title);
    }

    public function test_center_does_not_mark_notifications_read_and_read_actions_are_user_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::create(['user_id' => $owner->id, 'title' => 'Unread', 'message' => 'Keep unread', 'is_read' => false]);
        $otherNotification = Notification::create(['user_id' => $other->id, 'title' => 'Other', 'message' => 'Private', 'is_read' => false]);

        $this->actingAs($owner)->get(route('notifications'))->assertOk()
            ->assertSee('Mark as read')
            ->assertSee('Dismiss toast');
        $this->assertFalse($notification->fresh()->is_read);

        $this->actingAs($other)->patch(route('notifications.read', $notification))->assertForbidden();
        $this->assertFalse($notification->fresh()->is_read);

        $this->actingAs($owner)->patch(route('notifications.read', $notification))->assertRedirect();
        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($owner)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertFalse($otherNotification->fresh()->is_read);
    }

    public function test_poll_returns_multiple_unread_notifications_for_only_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Notification::create(['user_id' => $other->id, 'title' => 'Private', 'message' => 'Do not expose']);
        $first = Notification::create(['user_id' => $user->id, 'title' => 'First', 'message' => 'One']);
        $second = Notification::create(['user_id' => $user->id, 'title' => 'Second', 'message' => 'Two']);

        $this->actingAs($user)->getJson(route('api.runtime-state', ['after_id' => 0]))
            ->assertOk()
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('notifications.0.id', $first->id)
            ->assertJsonPath('notifications.1.id', $second->id)
            ->assertJsonMissing(['title' => 'Private'])
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('account_id', $user->id);
    }

    public function test_action_destination_is_rechecked_and_missing_or_unauthorized_targets_fall_back_safely(): void
    {
        $requestor = User::factory()->create();
        $recipient = User::factory()->create();
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'purpose' => 'Authorization test requisition.',
            'grand_total' => 100,
        ]);
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'title' => 'Forged action',
            'message' => 'Recipient cannot view this request.',
            'action_type' => 'requisition',
            'action_id' => $requisition->id,
        ]);

        $this->actingAs($recipient)->get(route('notifications.open', $notification))
            ->assertRedirect(route('notifications'))
            ->assertSessionHas('warning');
        $this->assertTrue($notification->fresh()->is_read);

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('notifications.open', $notification))->assertForbidden();
    }

    public function test_notification_center_paginates_and_escapes_content(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 20) as $number) {
            Notification::create(['user_id' => $user->id, 'title' => "Notice {$number}", 'message' => 'Message']);
        }
        Notification::create(['user_id' => $user->id, 'title' => '<script>alert(1)</script>', 'message' => '<b>message</b>', 'icon' => 'bad-icon', 'type' => 'bad-type']);

        $this->actingAs($user)->get(route('notifications'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('data-lucide="bell"', false)
            ->assertSee('page=2', false);
    }

    private function supply(): Supply
    {
        return Supply::create([
            'item_name' => 'Test Paper', 'brand' => 'Test', 'category' => 'Office Supplies',
            'requisition_tier' => 'minor', 'quantity' => 10, 'unit' => 'Ream(s)',
            'unit_price' => 100, 'min_stock_level' => 2,
        ]);
    }
}
