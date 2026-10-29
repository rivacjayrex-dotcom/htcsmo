<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserActivityLog;
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

    public function test_smo_can_view_complete_personnel_details_without_exposing_credentials(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $employee = User::factory()->create([
            'name' => 'Mara <script>alert("x")</script> O\'Neil',
            'username' => 'details.login', 'department' => 'CETE',
            'password' => 'Private-test-pass-123!', 'remember_token' => 'private-remember-token',
        ]);

        foreach ([[], ['X-Filter-Request' => 'true', 'X-Requested-With' => 'XMLHttpRequest']] as $headers) {
            $response = $this->actingAs($smo)->get(route('admin.users.manage', ['search' => $employee->school_id]), $headers)
                ->assertOk()->assertSee('View details')
                ->assertSee('details.login')->assertSee('Personnel details')
                ->assertSee('Passwords are stored as one-way hashes and cannot be viewed.')
                ->assertDontSee($employee->getRawOriginal('password'), false)
                ->assertDontSee('Private-test-pass-123!', false)
                ->assertDontSee('private-remember-token', false)
                ->assertDontSee('<script>alert("x")</script>', false);

            $dom = new \DOMDocument();
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $button = $xpath->query('//button[@aria-controls="personnelDetailsModal"]')->item(0);
            $this->assertNotNull($button);
            $this->assertSame('dialog', $button->getAttribute('aria-haspopup'));
            $this->assertStringContainsString('openDetails(', $button->getAttribute('x-on:click'));
            $this->assertStringContainsString('details.login', $button->getAttribute('x-on:click'));
            $this->assertSame(1, $xpath->query('//*[@id="personnelDetailsModal"]')->length);
            foreach (['id', 'name', 'username', 'school_id', 'email', 'department', 'designation', 'status', 'email_verified_at', 'created_at', 'updated_at'] as $field) {
                $this->assertSame(1, $xpath->query('//*[@id="personnelDetailsModal"]//*[@x-text="detailedUser.'.$field.'"]')->length);
            }
        }
    }

    public function test_personnel_details_button_is_only_available_to_smo(): void
    {
        $employee = User::factory()->create(['department' => 'CETE', 'username' => 'smo-only-details']);
        foreach (['dept_head', 'president', 'provost', 'vp_admin'] as $role) {
            $manager = User::factory()->create(['role' => $role, 'department' => 'CETE']);
            $this->actingAs($manager)->get(route('admin.users.manage'))->assertOk()
                ->assertSee($employee->name)
                ->assertDontSee('aria-controls="personnelDetailsModal"', false)
                ->assertDontSee('id="personnelDetailsModal"', false)
                ->assertDontSee('smo-only-details');
        }
    }

    public function test_department_head_can_manage_an_employee_in_their_department_only(): void
    {
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $ownEmployee = User::factory()->create([
            'role' => 'employee',
            'department' => 'CETE',
            'account_status' => User::ACCOUNT_SUSPENDED,
        ]);
        $otherEmployee = User::factory()->create(['role' => 'employee', 'department' => 'CTE']);

        $this
            ->actingAs($head)
            ->patch(route('admin.users.update', $ownEmployee), [
                'role' => 'employee',
                'department' => 'CETE',
                'account_status' => User::ACCOUNT_ACTIVE,
                'version' => $ownEmployee->account_version,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $ownEmployee->id,
            'role' => 'employee',
            'department_id' => Department::where('dept_code', 'CETE')->value('id'),
            'account_status' => User::ACCOUNT_ACTIVE,
        ]);

        $this
            ->actingAs($head)
            ->patch(route('admin.users.update', $otherEmployee), [
                'role' => 'employee',
                'department' => 'CETE',
                'account_status' => User::ACCOUNT_SUSPENDED,
                'change_reason' => 'Department transfer requires access review.',
                'version' => $otherEmployee->account_version,
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
                'account_status' => User::ACCOUNT_ACTIVE,
                'version' => $employee->account_version,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'role' => 'dept_head',
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
                'account_status' => User::ACCOUNT_ACTIVE,
                'version' => $employee->account_version,
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

    public function test_only_smo_can_view_and_approve_pending_access_requests(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $employee = User::factory()->create();
        $pending = User::factory()->create([
            'role' => 'president',
            'is_approved' => false,
            'account_status' => User::ACCOUNT_PENDING,
        ]);

        $this->actingAs($employee)->get(route('admin.users.pending'))->assertRedirect('/dashboard');
        $this->actingAs($employee)->post(route('admin.users.approve', $pending), ['version' => 0])->assertRedirect('/dashboard');

        $this->actingAs($smo)->post(route('admin.users.approve', $pending), [
            'version' => $pending->account_version,
            'role' => 'smo',
        ])->assertSessionHasNoErrors();

        $pending->refresh();
        $this->assertSame('employee', $pending->role);
        $this->assertSame(User::ACCOUNT_ACTIVE, $pending->account_status);
        $this->assertTrue($pending->is_approved);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $pending->id,
            'event_type' => 'account.activated',
        ]);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $smo->id,
            'action' => "Approved pending account #{$pending->id} as employee",
        ]);
    }

    public function test_access_approval_rejects_self_and_repeated_or_stale_actions_without_duplicate_notifications(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $pending = User::factory()->create(['account_status' => User::ACCOUNT_PENDING, 'is_approved' => false]);

        $this->actingAs($smo)->post(route('admin.users.approve', $smo), ['version' => $smo->account_version])->assertForbidden();
        $this->actingAs($smo)->post(route('admin.users.approve', $pending), ['version' => 99])->assertSessionHasErrors('account');
        $this->assertSame(User::ACCOUNT_PENDING, $pending->fresh()->account_status);

        $this->actingAs($smo)->post(route('admin.users.approve', $pending), ['version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($smo)->post(route('admin.users.approve', $pending), ['version' => 0])->assertSessionHasErrors('account');

        $this->assertSame(1, Notification::where('user_id', $pending->id)->where('event_type', 'account.activated')->count());
    }

    public function test_pending_and_suspended_accounts_are_kept_in_separate_workflows(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $pending = User::factory()->create(['name' => 'Pending Person', 'account_status' => User::ACCOUNT_PENDING, 'is_approved' => false]);
        $suspended = User::factory()->create(['name' => 'Suspended Person', 'account_status' => User::ACCOUNT_SUSPENDED]);

        $this->actingAs($smo)->get(route('admin.users.pending'))
            ->assertOk()->assertSee($pending->name)->assertDontSee($suspended->name);
        $this->actingAs($smo)->get(route('admin.users.manage'))
            ->assertOk()->assertSee($suspended->name)->assertDontSee($pending->name);
    }

    public function test_suspension_requires_a_reason_and_stale_personnel_updates_are_rejected(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $employee = User::factory()->create(['role' => 'employee', 'department' => 'CETE']);

        $payload = [
            'role' => 'employee', 'department' => 'CETE',
            'account_status' => User::ACCOUNT_SUSPENDED, 'version' => $employee->account_version,
        ];
        $this->actingAs($smo)->patch(route('admin.users.update', $employee), $payload)
            ->assertSessionHasErrors('change_reason');

        $payload['change_reason'] = 'Access review requested by administration.';
        $this->actingAs($smo)->patch(route('admin.users.update', $employee), $payload)
            ->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertSame(User::ACCOUNT_SUSPENDED, $employee->account_status);
        $this->assertFalse($employee->is_approved);

        $this->actingAs($smo)->patch(route('admin.users.update', $employee), $payload)
            ->assertSessionHasErrors('account');
        $this->assertSame(1, Notification::where('user_id', $employee->id)->where('event_type', 'account.suspended')->count());
        $this->assertTrue(UserActivityLog::where('user_id', $smo->id)->where('action', 'like', "Updated account #{$employee->id}:%")->exists());
    }

    public function test_manager_cannot_manage_self_or_pending_accounts(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $pending = User::factory()->create(['account_status' => User::ACCOUNT_PENDING, 'is_approved' => false]);
        $payload = ['role' => 'employee', 'department' => 'CETE', 'account_status' => 'active', 'version' => 0];

        $this->actingAs($smo)->patch(route('admin.users.update', $smo), $payload)->assertForbidden();
        $this->actingAs($smo)->patch(route('admin.users.update', $pending), $payload)->assertStatus(409);
    }

    public function test_suspended_account_is_signed_out_and_shown_the_suspended_message(): void
    {
        $user = User::factory()->create(['account_status' => User::ACCOUNT_SUSPENDED]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('approval.pending'));
        $this->assertGuest();

        $this->get(route('approval.pending'))
            ->assertOk()
            ->assertSee('Your account access is suspended');
    }

    public function test_roles_departments_and_profile_assignment_are_server_controlled(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $employee = User::factory()->create(['department' => 'CETE']);

        $this->actingAs($smo)->from(route('admin.users.manage'))
            ->patch(route('admin.users.update', $employee), [
                'role' => 'not-a-role', 'department' => 'FAKE',
                'account_status' => User::ACCOUNT_ACTIVE, 'version' => $employee->account_version,
            ])
            ->assertSessionHasErrors(['role', 'department']);

        $this->actingAs($employee)->patch(route('profile.update'), [
            'name' => $employee->name,
            'username' => $employee->username,
            'email' => $employee->email,
            'department' => 'CTE',
        ])->assertSessionHasNoErrors();

        $this->assertSame('CETE', $employee->fresh()->department);
    }

    public function test_access_and_personnel_searches_are_paginated_and_preserve_search_state(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        User::factory()->create(['name' => 'Matching Pending User', 'account_status' => User::ACCOUNT_PENDING, 'is_approved' => false]);
        User::factory()->create(['name' => 'Matching Active User']);

        $this->actingAs($smo)->get(route('admin.users.pending', ['search' => 'Matching']))
            ->assertOk()->assertSee('Matching Pending User')->assertSee('value="Matching"', false);
        $this->actingAs($smo)->get(route('admin.users.manage', ['search' => 'Matching']))
            ->assertOk()
            ->assertSee('Matching Active User')
            ->assertSee('value="Matching"', false)
            ->assertSee('aria-label="Search personnel"', false)
            ->assertSee('data-dynamic-search', false)
            ->assertSee('<noscript><button type="submit"', false);
    }

    public function test_personnel_registry_can_be_browsed_by_role_category_with_search_state_preserved(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        User::factory()->create(['name' => 'Category Administrator', 'role' => 'provost']);
        User::factory()->create(['name' => 'Category Department Head', 'role' => 'dept_head', 'department' => 'CETE']);
        User::factory()->create(['name' => 'Category Faculty Member', 'role' => 'employee', 'department' => 'CETE']);

        $this->actingAs($smo)->get(route('admin.users.manage', [
            'category' => 'admins',
            'search' => 'Category',
        ]))
            ->assertOk()
            ->assertSee('Category Administrator')
            ->assertDontSee('Category Department Head')
            ->assertDontSee('Category Faculty Member')
            ->assertSee('name="category" value="admins"', false)
            ->assertSee('value="Category"', false);

        $this->actingAs($smo)->get(route('admin.users.manage', ['category' => 'heads']))
            ->assertOk()
            ->assertSee('Category Department Head')
            ->assertDontSee('Category Administrator')
            ->assertDontSee('Category Faculty Member');

        $this->actingAs($smo)->get(route('admin.users.manage', ['category' => 'staff']))
            ->assertOk()
            ->assertSee('Category Faculty Member')
            ->assertDontSee('Category Administrator')
            ->assertDontSee('Category Department Head');
    }

    public function test_faculty_and_staff_are_grouped_by_department_and_name(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $ceteZ = User::factory()->create(['name' => 'Zoe CETE', 'department' => 'CETE']);
        $cte = User::factory()->create(['name' => 'Amy CTE', 'department' => 'CTE']);
        $ceteA = User::factory()->create(['name' => 'Ana CETE', 'department' => 'CETE']);
        $unassigned = User::factory()->create(['name' => 'Unassigned Staff', 'department_id' => null]);
        $response = $this->actingAs($smo)->get(route('admin.users.manage', ['category' => 'staff']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$ceteA->id, $ceteZ->id, $cte->id, $unassigned->id]);
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $groups = $xpath->query('//tr[@data-personnel-department]');
        $this->assertSame(3, $groups->length);
        $this->assertSame('CETE', $groups->item(0)->getAttribute('data-personnel-department'));
        $this->assertSame('CTE', $groups->item(1)->getAttribute('data-personnel-department'));
        $this->assertSame('unassigned', $groups->item(2)->getAttribute('data-personnel-department'));
    }

    public function test_department_filters_preserve_search_category_and_pagination_without_expanding_manager_scope(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        foreach (range(1, 21) as $number) {
            User::factory()->create(['name' => 'Matching CETE '.$number, 'department' => 'CETE']);
        }
        $other = User::factory()->create(['name' => 'Matching CTE', 'department' => 'CTE']);
        $filters = ['category' => 'staff', 'department' => 'CETE', 'search' => 'Matching'];
        $this->actingAs($smo)->get(route('admin.users.manage', $filters), ['X-Filter-Request' => 'true'])
            ->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 21 && $users->count() === 20)
            ->assertDontSee($other->school_id)->assertSee('department=CETE', false)->assertSee('page=2', false)
            ->assertSee('value="CETE" selected', false);
        $this->get(route('admin.users.manage', [...$filters, 'page' => 2]))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->count() === 1 && $users->first()->department === 'CETE');
        $head = User::factory()->create(['role' => 'dept_head', 'department' => 'CETE']);
        $this->actingAs($head)->get(route('admin.users.manage', ['department' => 'CTE']))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->isEmpty())->assertDontSee($other->school_id);
    }

    public function test_only_smo_can_add_departments_and_duplicate_codes_are_rejected(): void
    {
        $this->post(route('admin.departments.store'), ['dept_code' => 'HR', 'dept_name' => 'Human Resources'])
            ->assertUnauthorized();
        foreach (['employee', 'dept_head', 'president', 'provost', 'vp_admin', 'vp_finance'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('admin.departments.store'), ['dept_code' => 'HR', 'dept_name' => 'Human Resources'])
                ->assertRedirect('/dashboard');
        }
        $this->assertDatabaseMissing('departments', ['dept_code' => 'HR']);
        $smo = User::factory()->create(['role' => 'smo']);
        $this->actingAs($smo)->post(route('admin.departments.store'), ['dept_code' => ' hr ', 'dept_name' => ' Human   Resources '])
            ->assertRedirect(route('admin.users.manage', ['category' => 'staff']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departments', ['dept_code' => 'HR', 'dept_name' => 'Human Resources']);
        $this->assertTrue(UserActivityLog::where('user_id', $smo->id)->where('action', 'like', 'Added department #%: HR')->exists());
        $this->from(route('admin.users.manage'))->post(route('admin.departments.store'), ['dept_code' => 'hr', 'dept_name' => 'Another Office'])
            ->assertSessionHasErrorsIn('departmentForm', 'dept_code');
        $this->post(route('admin.departments.store'), ['dept_code' => 'OTHER', 'dept_name' => 'Human Resources'])
            ->assertSessionHasErrorsIn('departmentForm', 'dept_name');
        $this->post(route('admin.departments.store'), ['dept_code' => '<bad>', 'dept_name' => 'Invalid code'])
            ->assertSessionHasErrorsIn('departmentForm', 'dept_code');
        $this->assertSame(1, Department::where('dept_code', 'HR')->count());
        $this->get(route('admin.users.manage'))->assertOk()
            ->assertSee('data-bs-target="#addDepartmentModal"', false)
            ->assertSee('Human Resources (HR)');
    }

    public function test_incomplete_pending_registration_cannot_be_approved(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $pending = User::factory()->create([
            'department' => 'UNKNOWN', 'department_id' => null,
            'account_status' => User::ACCOUNT_PENDING, 'is_approved' => false,
        ]);

        $this->actingAs($smo)->post(route('admin.users.approve', $pending), [
            'version' => $pending->account_version,
        ])->assertSessionHasErrors('account');

        $this->assertSame(User::ACCOUNT_PENDING, $pending->fresh()->account_status);
    }
}
