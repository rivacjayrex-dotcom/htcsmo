<?php

namespace Tests\Feature;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemLiveRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_version_changes_when_a_requisition_is_submitted(): void
    {
        $user = User::factory()->create();

        $before = $this->actingAs($user)
            ->getJson(route('api.system-version'))
            ->assertOk()
            ->json('version');

        Requisition::create([
            'user_id' => $user->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'purpose' => 'For testing live workspace updates.',
            'grand_total' => 100,
        ]);

        $after = $this->actingAs($user)
            ->getJson(route('api.system-version'))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->json('version');

        $this->assertNotSame($before, $after);
    }

    public function test_system_version_requires_authentication(): void
    {
        $this->getJson(route('api.system-version'))->assertUnauthorized();
    }
}
