<?php

namespace Tests\Feature\Api\V1;

use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function api(string $method, string $path, array $data = [], ?string $token = null): TestResponse
    {
        // Each simulated HTTP request must resolve its bearer token afresh.
        $this->app['auth']->forgetGuards();

        return $this->json($method, '/api/v1'.$path, $data, $token ? ['Authorization' => 'Bearer '.$token] : []);
    }

    protected function token(User $user, ?array $abilities = null): string
    {
        $abilities ??= ['supplies:read', 'requisitions:read', 'requisitions:write'];

        return $user->createToken('Test device', $abilities, now()->addDay())->plainTextToken;
    }

    protected function supply(array $attributes = []): Supply
    {
        return Supply::create(array_merge([
            'item_name' => 'API Bond Paper',
            'category' => 'Office Supplies',
            'requisition_tier' => 'minor',
            'specifications' => '80 GSM',
            'quantity' => 20,
            'unit' => 'Ream(s)',
            'unit_price' => 100,
            'min_stock_level' => 5,
        ], $attributes));
    }

    protected function requisition(User $user, array $attributes = []): Requisition
    {
        return Requisition::create(array_merge([
            'user_id' => $user->id,
            'request_type' => 'minor',
            'status' => 'pending',
            'purpose' => 'Printing classroom materials.',
            'grand_total' => 200,
        ], $attributes));
    }
}
