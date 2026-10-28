<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticationTest extends ApiTestCase
{
    public function test_login_returns_a_hashed_expiring_token_and_a_safe_user_payload(): void
    {
        $user = User::factory()->create();
        $response = $this->api('POST', '/auth/tokens', [
            'login' => $user->username, 'password' => 'password', 'device_name' => 'Postman',
        ])->assertCreated()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.remember_token')
            ->assertJsonMissingPath('user.account_version');

        $token = PersonalAccessToken::firstOrFail();
        $this->assertSame(hash('sha256', explode('|', $response->json('access_token'), 2)[1]), $token->token);
        $this->assertTrue($token->expires_at->isFuture());
        $this->assertFalse($token->can('supplies:write'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseHas('user_activity_logs', ['user_id' => $user->id, 'action' => 'API token issued']);

        $this->api('GET', '/me', token: $response->json('access_token'))->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_school_id_login_and_smo_inventory_ability_are_supported(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $this->api('POST', '/auth/tokens', [
            'login' => $smo->school_id, 'password' => 'password', 'device_name' => 'SMO integration',
        ])->assertCreated()->assertJsonFragment(['supplies:write']);
    }

    public function test_invalid_credentials_and_inactive_accounts_cannot_issue_tokens(): void
    {
        $user = User::factory()->create();
        $credentials = ['login' => $user->username, 'password' => 'wrong', 'device_name' => 'Phone'];
        $this->api('POST', '/auth/tokens', $credentials)->assertUnprocessable()->assertJsonValidationErrors('login');
        $credentials['password'] = 'password';
        foreach ([User::ACCOUNT_PENDING, User::ACCOUNT_SUSPENDED] as $status) {
            $user->update(['account_status' => $status]);
            $this->api('POST', '/auth/tokens', $credentials)->assertForbidden();
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_input_is_validated_even_when_the_identifier_is_an_array(): void
    {
        $this->api('POST', '/auth/tokens', ['login' => ['bad'], 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['login', 'device_name']);
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $credentials = ['login' => 'unknown', 'password' => 'wrong', 'device_name' => 'Phone'];
        for ($i = 0; $i < 5; $i++) {
            $this->api('POST', '/auth/tokens', $credentials)->assertUnprocessable();
        }
        $this->api('POST', '/auth/tokens', $credentials)->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_all_data_endpoints_require_authentication_and_always_return_json(): void
    {
        foreach ([['GET', '/me'], ['GET', '/supplies'], ['GET', '/supplies/1'], ['PATCH', '/supplies/1'],
            ['GET', '/requisitions'], ['GET', '/requisitions/1'], ['POST', '/requisitions'], ['DELETE', '/auth/token']] as [$method, $path]) {
            $this->api($method, $path)->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.')
                ->assertJsonMissingPath('recovery_url');
        }
        $this->app['auth']->forgetGuards();
        $this->get('/api/v1/supplies')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $response = $this->get('/api/v1/unknown')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_web_sessions_do_not_authenticate_the_versioned_api(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_expired_revoked_and_invalid_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $expired = $user->createToken('Expired', ['*'], now()->subMinute())->plainTextToken;
        $this->api('GET', '/me', token: $expired)->assertUnauthorized();
        $token = $this->token($user);
        $otherToken = $this->token($user);
        $this->api('DELETE', '/auth/token', token: $token)->assertNoContent();
        $this->api('GET', '/me', token: $token)->assertUnauthorized();
        $this->api('GET', '/me', token: $otherToken)->assertOk();
        $this->api('GET', '/me', token: 'invalid-token')->assertUnauthorized();
    }

    public function test_global_token_lifetime_is_enforced(): void
    {
        config(['sanctum.expiration' => 30]);
        $user = User::factory()->create();
        $token = $this->token($user);
        PersonalAccessToken::query()->update(['created_at' => now()->subMinutes(31)]);
        $this->api('GET', '/me', token: $token)->assertUnauthorized();
    }

    public function test_access_changes_revoke_tokens_and_inactive_accounts_are_rechecked(): void
    {
        $user = User::factory()->create();
        foreach ([['password' => 'new-password'], ['role' => 'dept_head'], ['account_status' => User::ACCOUNT_SUSPENDED]] as $change) {
            $token = $this->token($user);
            $user->update($change);
            $this->api('GET', '/me', token: $token)->assertUnauthorized();
        }
        $user->update(['account_status' => User::ACCOUNT_ACTIVE]);
        $token = $this->token($user);
        // A direct database change bypasses model hooks; middleware must still deny access.
        DB::table('users')->where('id', $user->id)->update(['account_status' => User::ACCOUNT_SUSPENDED]);
        $this->api('GET', '/me', token: $token)->assertForbidden();
    }

    public function test_token_abilities_are_enforced(): void
    {
        $token = $this->token(User::factory()->create(), []);
        $this->api('GET', '/supplies', token: $token)->assertForbidden();
        $this->api('GET', '/requisitions', token: $token)->assertForbidden();
        $this->api('POST', '/requisitions', token: $token)->assertForbidden();
    }

    public function test_authenticated_api_traffic_is_rate_limited_per_user(): void
    {
        $token = $this->token(User::factory()->create());
        for ($i = 0; $i < 60; $i++) {
            $this->api('GET', '/me', token: $token)->assertOk();
        }
        $this->api('GET', '/me', token: $token)->assertStatus(429);
    }
}
