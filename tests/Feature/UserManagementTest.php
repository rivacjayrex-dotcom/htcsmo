<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_department_head_only_sees_personnel_from_their_department(): void
    {
        $head = User::factory()->create([
            'name' => 'CETE Dean',
            'role' => 'dept_head',
            'department' => 'CETE',
        ]);
        User::factory()->create(['name' => 'CETE Faculty Member', 'department' => 'CETE']);
        User::factory()->create(['name' => 'CTE Faculty Member', 'department' => 'CTE']);

        $this
            ->actingAs($head)
            ->get(route('admin.users.manage'))
            ->assertOk()
            ->assertSee('CETE Faculty Member')
            ->assertDontSee('CTE Faculty Member');
    }

    public function test_department_head_can_manage_an_employee_in_their_department_only(): void
    {
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $ownEmployee = User::factory()->create([
            'role' => 'employee',
            'department' => 'CETE',
            'is_approved' => false,
        ]);
        $otherEmployee = User::factory()->create(['role' => 'employee', 'department' => 'CTE']);

        $this
            ->actingAs($head)
            ->patch(route('admin.users.update', $ownEmployee), [
                'role' => 'employee',
                'department' => 'CETE',
                'is_approved' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $ownEmployee->id,
            'role' => 'employee',
            'department' => 'CETE',
            'is_approved' => true,
        ]);

        $this
            ->actingAs($head)
            ->patch(route('admin.users.update', $otherEmployee), [
                'role' => 'employee',
                'department' => 'CETE',
                'is_approved' => 0,
            ])
            ->assertForbidden();
    }

    public function test_president_can_manage_regular_personnel_across_departments(): void
    {
        $president = User::factory()->create(['role' => 'president']);
        $employee = User::factory()->create(['role' => 'employee', 'department' => 'CETE']);
        $cte = Department::where('dept_code', 'CTE')->firstOrFail();

        $this
            ->actingAs($president)
            ->patch(route('admin.users.update', $employee), [
                'role' => 'dept_head',
                'department' => 'CTE',
                'is_approved' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'role' => 'dept_head',
            'department' => 'CTE',
            'department_id' => $cte->id,
        ]);
    }

    public function test_school_administrator_cannot_grant_smo_access(): void
    {
        $provost = User::factory()->create(['role' => 'provost']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this
            ->actingAs($provost)
            ->from(route('admin.users.manage'))
            ->patch(route('admin.users.update', $employee), [
                'role' => 'smo',
                'department' => 'CETE',
                'is_approved' => 1,
            ])
            ->assertSessionHasErrors('role')
            ->assertRedirect(route('admin.users.manage'));

        $this->assertSame('employee', $employee->refresh()->role);
    }

    public function test_employee_cannot_open_personnel_management(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this
            ->actingAs($employee)
            ->get(route('admin.users.manage'))
            ->assertRedirect('/dashboard');
    }
}
