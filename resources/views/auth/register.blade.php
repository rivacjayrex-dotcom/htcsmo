<x-guest-layout>
    <div class="container-fluid p-0 h-100">
        <div class="row g-0 h-100">

            <!-- LEFT SIDE: GREEN PANEL -->
            <div class="col-md-5 d-flex flex-column justify-content-between p-3 text-white" style="background-color: #144521;">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white rounded-circle p-1 me-2" style="width: 25px; height: 25px;">
                            <img src="{{ asset('images/school_seal.png') }}" class="w-100" onerror="this.src='https://via.placeholder.com/20'">
                        </div>
                        <small class="fw-bold text-uppercase tracking-widest" style="font-size: 8px;">Supply Management</small>
                    </div>

                    <h2 class="fw-bold lh-sm mb-2" style="font-size: 1.3rem;">Join the <br> GoodHoly <br> Experience!</h2>
                    <p class="opacity-75" style="max-width: 180px; line-height: 1.3; font-size: 0.7rem;">
                        Create your account to manage institutional requisitions.
                    </p>
                </div>

                <div class="opacity-50 text-uppercase tracking-widest" style="font-size: 8px;">
                    Holy Trinity College <br> General Santos City
                </div>
            </div>

            <!-- RIGHT SIDE: REGISTRATION FORM -->
            <div class="col-md-7 d-flex flex-column justify-content-start p-3 bg-white h-100 shadow-inner" style="overflow-y: auto;">

                <div class="mb-2 mt-1">
                    <h3 class="fw-black text-uppercase tracking-tighter m-0" style="font-size: 1.2rem; color: #1a1a1a;"><b>REQUEST ACCESS</b></h3>
                    <p class="text-muted" style="font-size: 0.65rem; margin-bottom: 5px;">Fill in your institutional details</p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="row g-1">
                        <!-- Full Name -->
                        <div class="col-12 mb-1">
                            <label class="form-label small fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px;">Full Name</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0"><i data-lucide="user" style="width: 12px;"></i></span>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control bg-light border-0 shadow-none @error('name') is-invalid @enderror" placeholder="Juan Dela Cruz" required autofocus>
                            </div>
                            @error('name') <span class="text-danger" style="font-size: 10px;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Department Selection -->
                        <div class="col-12 mb-1">
                            <label class="form-label small fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px;">Department / College</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0"><i data-lucide="building" style="width: 12px;"></i></span>
                                <select name="department" class="form-select bg-light border-0 shadow-none @error('department') is-invalid @enderror" required style="font-size: 0.75rem;">
                                    <option value="" disabled {{ old('department') ? '' : 'selected' }}>Select your Department</option>
                                    <option value="CTE" {{ old('department') == 'CTE' ? 'selected' : '' }}>College of Teacher Education (CTE)</option>
                                    <option value="CETE" {{ old('department') == 'CETE' ? 'selected' : '' }}>College of Engineering and Tech. (CETE)</option>
                                    <option value="CCJE" {{ old('department') == 'CCJE' ? 'selected' : '' }}>College of Crim. Justice Education (CCJE)</option>
                                    <option value="CBMA" {{ old('department') == 'CBMA' ? 'selected' : '' }}>College of Business Mgmt. & Accountancy (CBMA)</option>
                                    <option value="CAS" {{ old('department') == 'CAS' ? 'selected' : '' }}>College of Arts and Sciences (CAS)</option>
                                </select>
                            </div>
                            @error('department') <span class="text-danger" style="font-size: 10px;">{{ $message }}</span> @enderror
                        </div>

                        <!-- School ID & Username (Same Row) -->
                        <div class="row g-2 mb-1">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px;">School ID</label>
                                <input type="text"
                                    id="school_id"
                                    name="school_id"
                                    value="{{ old('school_id') }}"
                                    class="form-control form-control-sm bg-light border-0 shadow-none @error('school_id') is-invalid @enderror"
                                    placeholder="00000-0000"
                                    maxlength="10"
                                    inputmode="numeric"
                                    pattern="\d{5}-\d{4}"
                                    title="Use the format 00000-0000"
                                    autocomplete="off"
                                    required>
                                @error('school_id') <span class="text-danger d-block mt-1" style="font-size: 10px;">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px;">Username</label>
                                <input type="text" name="username" value="{{ old('username') }}" class="form-control form-control-sm bg-light border-0 shadow-none @error('username') is-invalid @enderror" placeholder="user123" required>
                                @error('username') <span class="text-danger" style="font-size: 10px;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Institutional Email -->
                        <div class="col-12 mb-1">
                            <label class="form-label fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px; margin-bottom: 2px;">Institutional Email</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0 text-muted"><i data-lucide="mail" style="width: 12px;"></i></span>
                                <input type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control bg-light border-0 shadow-none @error('email') is-invalid @enderror"
                                    style="font-size: 0.75rem;"
                                    placeholder="username@online.htcgsc.edu.ph"
                                    pattern=".+@online\.htcgsc\.edu\.ph"
                                    title="Must end with @online.htcgsc.edu.ph"
                                    required>
                            </div>
                            @error('email') <span class="text-danger d-block mt-1" style="font-size: 10px;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Password & Confirm Password Row -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px; margin-bottom: 2px;">Password</label>
                                <div class="input-group input-group-sm">
                                    <input id="password" type="password" name="password" class="form-control bg-light border-0 shadow-none @error('password') is-invalid @enderror" style="font-size: 0.75rem;" placeholder="Min. 8 chars" required>
                                    <button class="btn bg-light border-0" type="button" id="toggleRegPassword">
                                        <i data-lucide="eye" id="eyeIconReg" style="width: 12px; color: #6c757d;"></i>
                                    </button>
                                </div>
                                @error('password') <span class="text-danger" style="font-size: 10px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold text-muted text-uppercase tracking-widest" style="font-size: 8px; margin-bottom: 2px;">Confirm</label>
                                <div class="input-group input-group-sm">
                                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control bg-light border-0 shadow-none" style="font-size: 0.75rem;" placeholder="Repeat password" required>
                                    <button class="btn bg-light border-0" type="button" id="toggleConfirmPassword">
                                        <i data-lucide="eye" id="eyeIconConfirm" style="width: 12px; color: #6c757d;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sm w-100 text-white fw-bold py-2 shadow-sm mt-2" style="background-color: #144521; border-radius: 6px; font-size: 0.8rem;">
                        Register Account →
                    </button>

                    <div class="mt-2 pt-2 border-top text-center">
                        <p class="text-muted" style="font-size: 0.65rem;">Already registered? <a href="{{ route('login') }}" class="fw-bold text-decoration-none" style="color: #144521;">Log In</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        const toggleRegPassword = document.querySelector('#toggleRegPassword');
        const passwordInput = document.querySelector('#password');
        const eyeIconReg = document.querySelector('#eyeIconReg');

        toggleRegPassword.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            eyeIconReg.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            lucide.createIcons();
        });

        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmInput = document.querySelector('#password_confirmation');
        const eyeIconConfirm = document.querySelector('#eyeIconConfirm');

        toggleConfirmPassword.addEventListener('click', function () {
            const isPassword = confirmInput.getAttribute('type') === 'password';
            confirmInput.setAttribute('type', isPassword ? 'text' : 'password');
            eyeIconConfirm.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            lucide.createIcons();
        });

        // Automatic masking for School ID: 00000-0000
        const schoolIdInput = document.getElementById('school_id');

        if (schoolIdInput) {
            schoolIdInput.addEventListener('input', function () {
                // Strip any non-digit characters
                let numbers = this.value.replace(/\D/g, '');

                // Limit to nine digits (5 + 4)
                if (numbers.length > 9) {
                    numbers = numbers.substring(0, 9);
                }

                this.value = numbers.length > 5
                    ? numbers.substring(0, 5) + '-' + numbers.substring(5)
                    : numbers;
            });

            // Allow backspace to delete cleanly without getting stuck on a hyphen
            schoolIdInput.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && (this.value.endsWith('-'))) {
                    this.value = this.value.slice(0, -1);
                }
            });
        }
    </script>
</x-guest-layout>
