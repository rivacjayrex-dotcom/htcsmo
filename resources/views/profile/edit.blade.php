<x-app-layout>
    <x-slot name="header">
        {{ __('Account Settings') }}
    </x-slot>

    <div class="container-fluid py-2">
        <div class="row g-4">
            <!-- Left Side: Navigation/Context -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width: 80px; height: 80px; color: var(--htc-green);">
                            <i data-lucide="user" style="width: 40px; height: 40px;"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ Auth::user()->name }}</h5>
                        <p class="text-muted small mb-0">{{ Auth::user()->school_id }}</p>
                        <span class="badge bg-success-subtle text-success mt-2 px-3 rounded-pill text-uppercase" style="font-size: 10px;">
                            {{ str_replace('_', ' ', Auth::user()->role) }}
                        </span>
                    </div>

                    <div class="list-group list-group-flush small">
                        <a href="#profile-info" class="list-group-item list-group-item-action border-0 rounded-3 mb-1">
                            <i data-lucide="info" class="me-2" style="width:16px;"></i> Profile Information
                        </a>
                        <a href="#password-update" class="list-group-item list-group-item-action border-0 rounded-3 mb-1">
                            <i data-lucide="lock" class="me-2" style="width:16px;"></i> Update Password
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Side: Forms -->
            <div class="col-lg-8">
                <!-- Profile Information Card -->
                <div id="profile-info" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center mb-4">
                        <i data-lucide="user-cog" class="text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Profile Information</h6>
                            <small class="text-muted">Update your name, username, department, and email address.</small>
                        </div>
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </div>

                <!-- Password Update Card -->
                <div id="password-update" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center mb-4">
                        <i data-lucide="shield-check" class="text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Security Update</h6>
                            <small class="text-muted">Ensure your account is using a long, random password to stay secure.</small>
                        </div>
                    </div>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
