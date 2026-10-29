<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\UserActivityLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'smo', 403);
        foreach (['dept_code', 'dept_name'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                $request->merge([$field => $field === 'dept_code' ? strtoupper($value) : preg_replace('/\s+/u', ' ', $value)]);
            }
        }
        $validated = $request->validateWithBag('departmentForm', [
            'dept_code' => ['required', 'string', 'max:20', 'regex:/^[A-Z][A-Z0-9-]*$/', Rule::unique('departments', 'dept_code')],
            'dept_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'dept_name')],
        ], [
            'dept_code.regex' => 'Use a department code starting with a letter, followed by letters, numbers, or hyphens.',
            'dept_code.unique' => 'That department code already exists.',
            'dept_name.unique' => 'That department name already exists.',
        ]);

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
}
