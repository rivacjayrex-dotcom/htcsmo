<x-app-layout>
    <x-slot name="header">
        <span style="color: white; font-weight: 800;">USER ACCESS VERIFICATION</span>
    </x-slot>

    <div class="container-fluid py-2 pending-users-page">
        <div class="mb-4 animate__animated animate__fadeIn">
            <h4 class="fw-black text-dark mb-1">Pending Registrations</h4>
            <p class="text-muted small">Verify institutional identity and assign administrative authority levels.</p>
        </div>

        <div class="row g-4">
            <div class="col-xl-9">
                @forelse($users as $u)
                    <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden animate__animated animate__fadeInUp" style="animation-delay: {{ $loop->index * 0.1 }}s">
                        <div class="row g-0">
                            <!-- User Identity Section -->
                            <div class="pending-user-identity col-md-5 p-4 bg-light border-end d-flex align-items-center">
                                <div class="pending-user-avatar rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; color: var(--htc-green);">
                                    <i data-lucide="user" style="width: 30px; height: 30px;"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $u->name }}</h6>
                                    <div class="text-muted small mb-1">ID: {{ $u->school_id }}</div>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3" style="font-size: 9px;">
                                        {{ $u->department }} DEPARTMENT
                                    </span>
                                </div>
                            </div>

                            <!-- Role Assignment Sectionnnnnn -->
                            <div class="pending-user-role col-md-7 p-4 bg-white">
                                <form action="{{ route('admin.users.approve', $u->id) }}" method="POST">
                                    @csrf
                                    <div class="row align-items-center">
                                        <div class="col-sm-7">
                                            <label class="form-label mini-label">Assign Institutional Role</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-0"><i data-lucide="shield-check" style="width:14px"></i></span>
                                                <select name="role" class="form-select border-0 bg-light fw-bold shadow-none" required>
                                                    <option value="employee" selected>Faculty / Staff</option>
                                                    <option value="dept_head">Department Head (Dean)</option>
                                                    <option value="vp_finance">VP for Finance</option>
                                                    <option value="vp_admin">VP for Administration</option>
                                                    <option value="provost">School Provost</option>
                                                    <option value="president">School President</option>
                                                    <option value="smo">SMO Personnel</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-5 text-end mt-3 mt-sm-0">
                                            <button type="submit" class="btn btn-htc w-100 py-2 rounded-3 fw-bold shadow-sm">
                                                GRANT ACCESS
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- EMPTY STATE -->
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                        <div class="p-4 rounded-circle bg-light d-inline-block mb-3">
                            <i data-lucide="user-check" class="text-muted" style="width: 48px; height: 48px;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Registry is Clean</h5>
                        <p class="text-muted small mx-auto" style="max-width: 300px;">All registration attempts have been processed. No users are currently awaiting verification.</p>
                    </div>
                @endforelse
            </div>

            <!-- RIGHT SIDEBAR: REQUIREMENTS REMINDER -->
            <div class="col-xl-3 d-none d-xl-block">
                <div class="card border-0 shadow-sm rounded-4 bg-dark text-white p-4 sticky-top" style="top: 100px;">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i data-lucide="info" class="me-2 text-info" style="width:18px"></i> SMO Protocol
                    </h6>
                    <ul class="list-unstyled small opacity-75 mb-0">
                        <li class="mb-3">
                            <strong class="d-block text-white">1. Identity Match</strong>
                            Check if the School ID matches the name provided in the registrar records.
                        </li>
                        <li class="mb-3">
                            <strong class="d-block text-white">2. Dept. Verification</strong>
                            Ensure the user is assigned to the correct academic or administrative office.
                        </li>
                        <li>
                            <strong class="d-block text-white">3. Role Authority</strong>
                            VPs and Deans should only be approved after official designation.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mini-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 5px; }
        .bg-success-subtle { background-color: #f0fdf4 !important; }
        .btn-htc { background-color: var(--htc-green); color: white; border: none; }
        .btn-htc:hover { background-color: #0d2e16; color: white; transform: translateY(-1px); }
        .pending-user-avatar { flex: 0 0 60px; }
        @media (max-width: 767.98px) {
            .pending-user-identity { padding: 1rem !important; border-right: 0 !important; border-bottom: 1px solid #e2e8f0; }
            .pending-user-role { padding: 1rem !important; }
            .pending-users-page select,
            .pending-users-page button[type="submit"] { min-height: 44px; font-size: 16px; }
        }
    </style>
</x-app-layout>
