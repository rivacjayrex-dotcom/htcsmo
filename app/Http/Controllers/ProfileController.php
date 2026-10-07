<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'departments' => Department::orderBy('dept_name')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Handle Profile Photo Upload
        if ($request->hasFile('profile_photo')) {
            $request->validate([
                'profile_photo' => ['nullable', 'image', 'max:2048'], // Max 2MB
            ]);

            // Delete old photo if it exists
            if ($request->user()->profile_photo) {
                Storage::disk('public')->delete($request->user()->profile_photo);
            }

            // Store the new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $request->user()->profile_photo = $path;
        }

        $validated = $request->validated();
        $validated['department_id'] = isset($validated['department'])
            ? Department::where('dept_code', $validated['department'])->value('id')
            : null;

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function pendingUsers()
    {
        // Fetch all users where is_approved is 0 (false)
        // and exclude the SMO themselves so they don't see their own account
        $users = User::where('is_approved', false)
            ->where('id', '!=', Auth::id())
            ->get();

        return view('admin.pending-users', compact('users'));
    }

    public function approveUser(Request $request, $id, NotificationService $notifications)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in($this->allAssignableRoles())],
        ]);

        $user = User::findOrFail($id);

        // 1. Assign the role chosen by the SMO
        // 2. Set is_approved to true
        $user->update([
            'role' => $validated['role'],
            'is_approved' => true,
        ]);

        $notifications->send($user, 'account.activated', 'Account Activated',
            "Your account has been verified as {$validated['role']}. You now have full system access.", [
                'icon' => 'shield-check', 'type' => 'success', 'action_type' => 'profile',
                'action_id' => $user->id, 'dedupe_key' => "account:{$user->id}:activated",
            ]);

        return back()->with('success', "Access granted. {$user->name} is now registered as ".strtoupper($validated['role']));
    }

    public function manageUsers(Request $request)
    {
        $manager = $request->user();
        $this->ensureCanManageUsers($manager);

        $query = User::query();

        if ($manager->role === 'dept_head') {
            abort_if(blank($manager->department), 403, 'A department assignment is required to manage personnel.');

            $query->where('department', $manager->department)
                ->where('role', 'employee');
        }

        // Optional search filter by name or ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('department')->orderBy('name')->get();
        $departments = Department::orderBy('dept_name')->get();
        $manageableUserIds = $users
            ->filter(fn (User $user) => $this->canManageTarget($manager, $user))
            ->pluck('id')
            ->all();

        return view('admin.manage-users', [
            'users' => $users,
            'departments' => $departments,
            'manageableUserIds' => $manageableUserIds,
            'manageableRoles' => $this->assignableRolesFor($manager),
            'canChangeDepartment' => $manager->role !== 'dept_head',
            'scopeLabel' => $manager->role === 'dept_head'
                ? $manager->department.' Department'
                : 'Institution-wide',
        ]);
    }

    public function updateManagedUser(Request $request, User $user, NotificationService $notifications): RedirectResponse
    {
        $manager = $request->user();
        $this->ensureCanManageUsers($manager);

        abort_unless($this->canManageTarget($manager, $user), 403, 'You cannot manage this user account.');

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRolesFor($manager))],
            'department' => ['nullable', 'required_if:role,dept_head', Rule::exists('departments', 'dept_code')],
            'is_approved' => ['required', 'boolean'],
        ]);

        if ($manager->role === 'dept_head') {
            $validated['role'] = 'employee';
            $validated['department'] = $manager->department;
        }

        $department = filled($validated['department'] ?? null)
            ? Department::where('dept_code', $validated['department'])->first()
            : null;

        $user->update([
            'role' => $validated['role'],
            'department' => $department?->dept_code,
            'department_id' => $department?->id,
            'is_approved' => (bool) $validated['is_approved'],
        ]);

        $notifications->send($user, 'account.assignment_updated', 'Personnel Record Updated',
            "Your account assignment was updated by {$manager->name}.", [
                'icon' => 'user-cog', 'type' => 'info', 'action_type' => 'profile',
                'action_id' => $user->id,
                'dedupe_key' => "account:{$user->id}:assignment:{$user->updated_at?->format('U.u')}",
            ]);

        return back()->with('success', "{$user->name}'s personnel record was updated.");
    }

    private function ensureCanManageUsers(User $manager): void
    {
        abort_unless(
            in_array($manager->role, ['smo', 'president', 'provost', 'vp_admin', 'dept_head'], true),
            403,
        );
    }

    private function canManageTarget(User $manager, User $target): bool
    {
        if ($manager->is($target)) {
            return false;
        }

        if ($manager->role === 'smo') {
            return true;
        }

        if ($manager->role === 'dept_head') {
            return $target->role === 'employee'
                && filled($manager->department)
                && $target->department === $manager->department;
        }

        return in_array($manager->role, ['president', 'provost', 'vp_admin'], true)
            && in_array($target->role, ['employee', 'dept_head'], true);
    }

    /**
     * @return array<int, string>
     */
    private function assignableRolesFor(User $manager): array
    {
        if ($manager->role === 'smo') {
            return $this->allAssignableRoles();
        }

        if ($manager->role === 'dept_head') {
            return ['employee'];
        }

        return ['employee', 'dept_head'];
    }

    /**
     * @return array<int, string>
     */
    private function allAssignableRoles(): array
    {
        return ['employee', 'dept_head', 'vp_finance', 'vp_admin', 'provost', 'president', 'smo'];
    }
}
