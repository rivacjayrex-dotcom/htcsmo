<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('data-auth-progress="register"', false)
            ->assertSee('id="authProgressDialog"', false)
            ->assertSee('aria-describedby="authProgressMessage"', false);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test_user',
            'school_id' => '20260-0001',
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
            'school_id' => '20260-0001',
            'account_status' => 'pending',
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

    public function test_smo_added_departments_are_available_for_registration_and_personnel_assignment(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $this->actingAs($smo)->post(route('admin.departments.store'), [
            'dept_code' => 'LIB', 'dept_name' => 'Library Office',
        ])->assertSessionHasNoErrors();
        $this->post(route('logout'));
        $this->get('/register')->assertOk()->assertSee('value="LIB"', false)->assertSee('Library Office (LIB)');
        $this->post('/register', [
            'name' => 'Library Staff', 'username' => 'library_staff', 'school_id' => '20260-0031',
            'department' => 'LIB', 'email' => 'library.staff@online.htcgsc.edu.ph',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login', absolute: false));
        $library = Department::where('dept_code', 'LIB')->firstOrFail();
        $user = User::where('username', 'library_staff')->firstOrFail();
        $this->assertSame($library->id, $user->department_id);
        $this->assertSame(User::ACCOUNT_PENDING, $user->account_status);
        $this->actingAs($smo)->post(route('admin.users.approve', $user), ['version' => 0])->assertSessionHasNoErrors();
        $this->get(route('admin.users.manage', ['category' => 'staff', 'department' => 'LIB']))
            ->assertOk()->assertSee('Library Staff')->assertSee('data-personnel-department="LIB"', false);
    }
}
