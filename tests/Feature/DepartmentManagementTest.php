<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Requisition;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_smo_can_edit_or_remove_departments(): void
    {
        $department = $this->department();
        $update = ['dept_code' => 'NORTH', 'dept_name' => 'North Campus', 'version' => $department->managementVersion()];
        $this->patch(route('admin.departments.update', $department), $update)->assertUnauthorized();
        $this->delete(route('admin.departments.destroy', $department), ['version' => $department->managementVersion()])->assertUnauthorized();
        foreach (['employee', 'dept_head', 'president', 'provost', 'vp_admin', 'vp_finance'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->patch(route('admin.departments.update', $department), $update)->assertRedirect(route('dashboard'));
            $this->delete(route('admin.departments.destroy', $department), ['version' => $department->managementVersion()])->assertRedirect(route('dashboard'));
        }
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'dept_code' => 'HR']);
        $this->assertDatabaseMissing('user_activity_logs', ['action' => 'Removed department #'.$department->id.': HR']);
    }

    public function test_rename_preserves_assignments_and_request_history_and_updates_selection_lists(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $department = $this->department();
        $owner = User::factory()->create(['department_id' => $department->id]);
        $request = Requisition::create(['user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'completed', 'grand_total' => 100, 'purpose' => 'History retained']);
        $this->actingAs($smo)->patch(route('admin.departments.update', $department), [
            'dept_code' => ' people ', 'dept_name' => ' People   Operations ', 'version' => $department->managementVersion(),
        ])->assertRedirect(route('admin.users.manage', ['category' => 'staff']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'dept_code' => 'PEOPLE', 'dept_name' => 'People Operations']);
        $this->assertSame($department->id, $owner->fresh()->department_id);
        $this->assertSame('PEOPLE', $owner->fresh()->department);
        $this->assertSame($owner->id, $request->fresh()->user_id);
        $this->assertSame('completed', $request->fresh()->status);
        $this->assertTrue(UserActivityLog::where('user_id', $smo->id)->where('action', 'like', 'Updated department #'.$department->id.':%')->exists());
        $this->get(route('admin.users.manage'))->assertOk()->assertSee('People Operations')->assertSee('value="PEOPLE"', false);
        $this->get(route('requests.show', $request))->assertOk()->assertSee('PEOPLE');
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('register'))->assertOk()->assertSee('value="PEOPLE"', false)->assertDontSee('value="HR"', false);
    }

    public function test_empty_department_can_be_removed_and_no_longer_appears_in_registration(): void
    {
        $department = $this->department();
        $smo = User::factory()->create(['role' => 'smo']);
        $this->actingAs($smo)->delete(route('admin.departments.destroy', $department), ['version' => $department->managementVersion()])
            ->assertRedirect(route('admin.users.manage', ['category' => 'staff']))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('user_activity_logs', ['user_id' => $smo->id, 'action' => 'Removed department #'.$department->id.': HR']);
        $this->assertModelExists($smo);
        $this->post(route('logout'));
        $this->get(route('register'))->assertOk()->assertDontSee('value="HR"', false);
    }

    public function test_any_assigned_account_blocks_removal_even_when_pending_or_suspended(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $department = $this->department();
        foreach ([User::ACCOUNT_ACTIVE, User::ACCOUNT_PENDING, User::ACCOUNT_SUSPENDED] as $status) {
            $person = User::factory()->create(['department_id' => $department->id, 'account_status' => $status]);
            $this->actingAs($smo)->from(route('admin.users.manage'))->delete(route('admin.departments.destroy', $department), ['version' => $department->managementVersion()])
                ->assertSessionHasErrorsIn('departmentManagement', 'department');
            $this->assertSame($department->id, $person->fresh()->department_id);
            $this->assertModelExists($department);
            $person->forceFill(['department_id' => null])->save();
        }
        $this->assertFalse(UserActivityLog::where('action', 'like', 'Removed department #'.$department->id.':%')->exists());
        $this->delete(route('admin.departments.destroy', $department), ['version' => $department->managementVersion()])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_duplicate_invalid_and_stale_edits_do_not_overwrite_a_department(): void
    {
        $department = $this->department();
        Department::create(['dept_code' => 'LIB', 'dept_name' => 'Library']);
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        $version = $department->managementVersion();
        foreach ([['LIB', 'Valid Name', 'dept_code'], ['VALID', 'Library', 'dept_name'], ['<bad>', 'Valid Name', 'dept_code'], ['VALID', '', 'dept_name']] as [$code, $name, $field]) {
            $this->from(route('admin.users.manage'))->patch(route('admin.departments.update', $department), ['dept_code' => $code, 'dept_name' => $name, 'version' => $version])
                ->assertSessionHasErrorsIn('departmentManagement', $field)->assertSessionHas('_old_input.editing_department_id', $department->id);
        }
        $this->patch(route('admin.departments.update', $department), ['dept_code' => 'HR', 'dept_name' => 'Renamed Resources', 'version' => $version])->assertSessionHasNoErrors();
        $this->patch(route('admin.departments.update', $department), ['dept_code' => 'OLD', 'dept_name' => 'Stale overwrite', 'version' => $version])->assertSessionHasErrorsIn('departmentManagement', 'department');
        $this->delete(route('admin.departments.destroy', $department), ['version' => $version])->assertSessionHasErrorsIn('departmentManagement', 'department');
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'dept_code' => 'HR', 'dept_name' => 'Renamed Resources']);
        $this->assertSame(1, UserActivityLog::where('action', 'like', 'Updated department #'.$department->id.':%')->count());
        $this->patch(route('admin.departments.update', $department), ['dept_code' => 'HR', 'dept_name' => 'Renamed Resources'])->assertSessionHasErrorsIn('departmentManagement', 'version');
        $this->delete(route('admin.departments.destroy', $department))->assertSessionHasErrorsIn('departmentManagement', 'version');
    }

    public function test_management_list_counts_all_accounts_and_is_hidden_from_other_managers(): void
    {
        $department = $this->department();
        User::factory()->create(['department_id' => $department->id, 'account_status' => User::ACCOUNT_PENDING]);
        $smo = User::factory()->create(['role' => 'smo']);
        $response = $this->actingAs($smo)->get(route('admin.users.manage'))->assertOk()->assertSee('data-bs-target="#manageDepartmentsModal"', false);
        $this->assertSame(1, $response->viewData('departments')->firstWhere('id', $department->id)->users_count);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $removeButton = $xpath->query('//article[@data-department-id="'.$department->id.'"]//form[contains(@action, "/admin/departments/")]//button[@type="submit" and contains(@aria-label, "Remove")]')->item(0);
        $this->assertNotNull($removeButton);
        $this->assertTrue($removeButton->hasAttribute('disabled'));
        $this->actingAs(User::factory()->create(['role' => 'president']))->get(route('admin.users.manage'))->assertOk()->assertDontSee('id="manageDepartmentsModal"', false);
    }

    public function test_failed_edit_reopens_management_and_preserves_only_its_form_values(): void
    {
        $department = $this->department();
        $this->actingAs(User::factory()->create(['role' => 'smo']))->from(route('admin.users.manage'))
            ->patch(route('admin.departments.update', $department), [
                'dept_code' => '<bad>', 'dept_name' => 'Edited "HR" & People', 'version' => $department->managementVersion(),
            ])->assertSessionHasErrorsIn('departmentManagement', 'dept_code');
        $response = $this->get(route('admin.users.manage'))->assertOk()->assertSee('Department changes were not saved.');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertTrue($xpath->query('//*[@id="manageDepartmentsModal"]')->item(0)->hasAttribute('x-init'));
        $this->assertFalse($xpath->query('//*[@id="addDepartmentModal"]')->item(0)->hasAttribute('x-init'));
        $this->assertSame('<BAD>', $xpath->query('//*[@id="department-code-'.$department->id.'"]')->item(0)->getAttribute('value'));
        $this->assertSame('Edited "HR" & People', $xpath->query('//*[@id="department-name-'.$department->id.'"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//*[@id="newDepartmentCode"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//*[@id="newDepartmentName"]')->item(0)->getAttribute('value'));
    }

    private function department(): Department
    {
        return Department::create(['dept_code' => 'HR', 'dept_name' => 'Human Resources']);
    }
}
