<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\UserActivityLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'smo', 403);
        $validated = $this->validatedDepartment($request);

        try {
            DB::transaction(function () use ($validated, $request) {
                $department = Department::create($validated);
                UserActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => "Added department #{$department->id}: {$department->dept_code}",
                    'ip_address' => $request->ip() ?? 'unknown',
                    'user_agent' => $request->userAgent(), 'created_at' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['dept_code' => 'That department code already exists.'])
                ->errorBag('departmentForm');
        }

        return redirect()->route('admin.users.manage', ['category' => 'staff'])
            ->with('success', "Department {$validated['dept_code']} added. It is now available during registration and personnel assignment.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->role === 'smo', 403);
        $request->merge(['editing_department_id' => $department->id]);
        $validated = $this->validatedDepartment($request, $department);
        $version = $request->validateWithBag('departmentManagement', ['version' => ['required', 'string', 'size:64']])['version'];

        try {
            DB::transaction(function () use ($department, $validated, $version, $request) {
                $locked = Department::query()->lockForUpdate()->findOrFail($department->id);
                $this->assertVersion($locked, $version);
                $before = $locked->dept_code.' ('.Str::limit($locked->dept_name, 70).')';
                $locked->update($validated);
                $this->logAction($request, "Updated department #{$locked->id}: {$before} to {$locked->dept_code} (".Str::limit($locked->dept_name, 70).')');
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['department' => 'That department code or name already exists.'])
                ->errorBag('departmentManagement');
        }

        return redirect()->route('admin.users.manage', ['category' => 'staff'])
            ->with('success', "Department {$validated['dept_code']} updated. Existing personnel assignments were preserved.");
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->role === 'smo', 403);
        $version = $request->validateWithBag('departmentManagement', ['version' => ['required', 'string', 'size:64']])['version'];

        DB::transaction(function () use ($department, $version, $request) {
            // A parent-row lock also blocks new foreign-key assignments until this decision commits.
            $locked = Department::query()->lockForUpdate()->findOrFail($department->id);
            $this->assertVersion($locked, $version);
            if ($locked->users()->exists()) {
                throw ValidationException::withMessages(['department' => 'This department still has assigned personnel. Reassign all accounts, including pending and suspended accounts, before removing it.'])
                    ->errorBag('departmentManagement');
            }
            $this->logAction($request, "Removed department #{$locked->id}: {$locked->dept_code}");
            $locked->delete();
        });

        return redirect()->route('admin.users.manage', ['category' => 'staff'])
            ->with('success', "Department {$department->dept_code} removed.");
    }

    private function validatedDepartment(Request $request, ?Department $department = null): array
    {
        foreach (['dept_code', 'dept_name'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                $request->merge([$field => $field === 'dept_code' ? strtoupper($value) : preg_replace('/\s+/u', ' ', $value)]);
            }
        }
        return $request->validateWithBag($department ? 'departmentManagement' : 'departmentForm', [
            'dept_code' => ['required', 'string', 'max:20', 'regex:/^[A-Z][A-Z0-9-]*$/', Rule::unique('departments', 'dept_code')->ignore($department?->id)],
            'dept_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'dept_name')->ignore($department?->id)],
        ], [
            'dept_code.regex' => 'Use a department code starting with a letter, followed by letters, numbers, or hyphens.',
            'dept_code.unique' => 'That department code already exists.',
            'dept_name.unique' => 'That department name already exists.',
        ]);
    }

    private function assertVersion(Department $department, string $version): void
    {
        if (!hash_equals($department->managementVersion(), $version)) {
            throw ValidationException::withMessages(['department' => 'This department changed since you opened it. Review its latest details before trying again.'])
                ->errorBag('departmentManagement');
        }
    }

    private function logAction(Request $request, string $action): void
    {
        UserActivityLog::create([
            'user_id' => $request->user()->id, 'action' => $action,
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(), 'created_at' => now(),
        ]);
    }
}
