<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicSearchWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_server_backed_search_form_uses_dynamic_search(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);

        foreach ([
            'dashboard',
            'requests.index',
            'requests.archive',
            'admin.archive',
            'admin.users.pending',
            'admin.users.manage',
        ] as $routeName) {
            $this
                ->actingAs($smo)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('data-dynamic-search', false);
        }
    }
}
