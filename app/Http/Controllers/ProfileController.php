<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Department;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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

    public function approveUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1. Assign the role chosen by the SMO
        // 2. Set is_approved to true
        $user->update([
            'role' => $request->role,
            'is_approved' => true,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Account Activated',
            'message' => "Your account has been verified as {$request->role}. You now have full system access.",
            'icon' => 'shield-check',
            'type' => 'success',
        ]);

        return back()->with('success', "Access granted. {$user->name} is now registered as ".strtoupper($request->role));
    }

    public function manageUsers(Request $request)
    {
        // Fetch all approved personnel
        $query = User::where('is_approved', true);

        // Optional search filter by name or ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allUsers = $query->orderBy('name', 'asc')->get();

        // 1. Executive & Key Signatories (President, Provost, VPs, SMO)
        $executives = $allUsers->whereIn('role', ['president', 'provost', 'vp_admin', 'vp_finance', 'smo']);

        // 2. Department Heads & Deans
        $deptHeads = $allUsers->where('role', 'dept_head');

        // 3. Faculty & Staff grouped by Department
        $employeesByDept = $allUsers->where('role', 'employee')->groupBy('department');

        return view('admin.manage-users', compact('allUsers', 'executives', 'deptHeads', 'employeesByDept'));
    }
}
