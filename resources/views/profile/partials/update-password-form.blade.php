<section>
    <form method="post" action="{{ route('password.update') }}" class="mt-2">
        @csrf
        @method('put')

        <div class="row g-4">
            <!-- Current Password -->
            <div class="col-md-12">
                <label class="form-label small fw-bold text-muted text-uppercase label-letter-spacing" style="font-size: 10px;">Current Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i data-lucide="lock" style="width: 14px;"></i></span>
                    <input id="update_password_current_password" name="current_password" type="password"
                        class="form-control border-0 bg-light py-2 px-3 rounded-end-3 shadow-none"
                        style="font-size: 0.9rem;" autocomplete="current-password" placeholder="••••••••">
                </div>
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <!-- New Password -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase label-letter-spacing" style="font-size: 10px;">New Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i data-lucide="key-round" style="width: 14px;"></i></span>
                    <input id="update_password_password" name="password" type="password"
                        class="form-control border-0 bg-light py-2 px-3 rounded-end-3 shadow-none"
                        style="font-size: 0.9rem;" autocomplete="new-password" placeholder="New secret password">
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <!-- Confirm New Password -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted text-uppercase label-letter-spacing" style="font-size: 10px;">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i data-lucide="shield-check" style="width: 14px;"></i></span>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                        class="form-control border-0 bg-light py-2 px-3 rounded-end-3 shadow-none"
                        style="font-size: 0.9rem;" autocomplete="new-password" placeholder="Repeat new password">
                </div>
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="profile-form-action mt-4 d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-sm px-4 py-2 text-white fw-bold shadow-sm" style="background-color: var(--htc-green); border-radius: 8px;">
                Update Security
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-success small mb-0 fw-bold d-flex align-items-center"
                >
                    <i data-lucide="check-circle" class="me-1" style="width: 14px;"></i> {{ __('Password updated.') }}
                </p>
            @endif
        </div>
    </form>
</section>
