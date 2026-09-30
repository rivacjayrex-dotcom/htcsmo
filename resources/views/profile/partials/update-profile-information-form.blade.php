<section>
    <form method="post" action="{{ route('profile.update') }}" class="mt-2" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="profile-photo-row d-flex align-items-center mb-4">
            <div class="position-relative">
                @if($user->profile_photo)
                    <img src="{{ asset('storage/' . $user->profile_photo) }}" class="rounded-circle shadow-sm border" style="width: 80px; height: 80px; object-fit: cover;" alt="{{ $user->name }} profile photo">
                @else
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 80px; height: 80px; color: var(--htc-green);">
                        <i data-lucide="user" style="width: 40px;"></i>
                    </div>
                @endif
            </div>

            <div class="ms-4 flex-grow-1">
                <label for="profile_photo" class="form-label small fw-bold text-muted uppercase d-block">Change Profile Photo</label>
                <input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="form-control form-control-sm border-0 bg-light shadow-none @error('profile_photo') is-invalid @enderror">
                <small class="text-muted" style="font-size: 10px;">Recommended: Square JPG or PNG, max 2MB.</small>
                @error('profile_photo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label small fw-bold text-muted uppercase">Full Name</label>
                <input id="name" name="name" type="text" class="form-control border-0 bg-light py-2 px-3 rounded-3 shadow-none @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="username" class="form-label small fw-bold text-muted uppercase">Username</label>
                <input id="username" name="username" type="text" class="form-control border-0 bg-light py-2 px-3 rounded-3 shadow-none @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" minlength="4" maxlength="30" required>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label small fw-bold text-muted uppercase">Email Address</label>
                <input id="email" name="email" type="email" class="form-control border-0 bg-light py-2 px-3 rounded-3 shadow-none @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="department" class="form-label small fw-bold text-muted uppercase">Department / College</label>
                <select id="department" name="department" class="form-select border-0 bg-light py-2 px-3 rounded-3 shadow-none @error('department') is-invalid @enderror">
                    <option value="">Administrative Office / Not Assigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->dept_code }}" @selected(old('department', $user->department) === $department->dept_code)>
                            {{ $department->dept_name }} ({{ $department->dept_code }})
                        </option>
                    @endforeach
                </select>
                @error('department')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="school_id" class="form-label small fw-bold text-muted uppercase">School ID (Read-only)</label>
                <input id="school_id" type="text" class="form-control border-0 bg-light py-2 px-3 rounded-3" value="{{ $user->school_id }}" readonly style="cursor: not-allowed;">
            </div>
        </div>

        <div class="profile-form-action mt-4 d-flex align-items-center gap-3">
            <button type="submit" class="btn fw-bold px-4 py-2 text-white" style="background-color: var(--htc-green); border-radius: 10px;">
                Save Changes
            </button>

            @if (session('status') === 'profile-updated')
                <p class="text-success small ms-3 mb-0 fw-bold">Saved successfully!</p>
            @endif
        </div>
    </form>
</section>
