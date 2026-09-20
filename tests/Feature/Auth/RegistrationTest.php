<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test_user',
            'school_id' => '2026-0001',
            'department' => 'CETE',
            'email' => 'test.user@online.htcgsc.edu.ph',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'school_id' => '2026-0001',
            'is_approved' => false,
        ]);
    }

    public function test_registration_rejects_the_old_school_id_format(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'name' => 'Test User',
                'username' => 'test_user',
                'school_id' => '26-0001-01',
                'department' => 'CETE',
                'email' => 'test.user@online.htcgsc.edu.ph',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);

        $response
            ->assertSessionHasErrors('school_id')
            ->assertRedirect('/register');
    }
}
