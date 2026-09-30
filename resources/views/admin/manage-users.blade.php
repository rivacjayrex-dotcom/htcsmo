@php
    $roleLabels = [
        'employee' => 'Faculty / Staff',
        'dept_head' => 'Department Head / Dean',
        'vp_finance' => 'VP for Finance',
        'vp_admin' => 'VP for Administration',
        'provost' => 'School Provost',
        'president' => 'School President',
        'smo' => 'SMO In-Charge',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">Personnel Management</x-slot>

    <div class="container-fluid py-2 personnel-page" x-data="{
        editModal: null,
        selectedUser: { id: '', name: '', role: '', department: '', is_approved: '1' },
        openEdit(user) {
            this.selectedUser = {
                ...user,
                department: user.department || '',
                is_approved: user.is_approved ? '1' : '0'
            };
            this.editModal ??= new bootstrap.Modal(document.getElementById('editPersonnelModal'));
            this.editModal.show();
        }
    }">
        <div class="personnel-toolbar d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h4 class="fw-black text-dark mb-1">{{ $scopeLabel }} Personnel</h4>
                <p class="text-muted small mb-0">
                    @if(Auth::user()->role === 'dept_head')
                        Manage faculty and staff accounts assigned to your department.
                    @else
                        Review personnel assignments, access status, roles, and departments.
                    @endif
                </p>
            </div>

            <form action="{{ route('admin.users.manage') }}" method="GET" class="personnel-search d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted">
                        <i data-lucide="search" style="width: 14px;"></i>
                    </span>
                    <input type="search" name="search" value="{{ request('search') }}"
                        class="form-control border-start-0 rounded-end-pill bg-white shadow-none"
                        placeholder="Search name, ID, or email" style="min-width: 230px;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.users.manage') }}" class="btn btn-sm btn-link text-danger text-decoration-none">Clear</a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center mb-4">
                <i data-lucide="check-circle" class="me-2" style="width: 18px;"></i>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                <div class="fw-bold mb-1">The personnel record could not be updated.</div>
                <ul class="small mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 9px;">Visible Personnel</small>
                    <h3 class="fw-black mb-0 mt-2">{{ $users->count() }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-success border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 9px;">Approved Accounts</small>
                    <h3 class="fw-black text-success mb-0 mt-2">{{ $users->where('is_approved', true)->count() }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-warning border-4">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 9px;">Pending / Inactive</small>
                    <h3 class="fw-black text-warning mb-0 mt-2">{{ $users->where('is_approved', false)->count() }}</h3>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white border-0 p-4 pb-2">
                <h6 class="fw-bold text-dark mb-0">Personnel Registry</h6>
                <small class="text-muted">Actions are limited by your institutional role and assigned department.</small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 workflow-responsive-table personnel-table">
                    <thead class="bg-light small text-muted text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Personnel</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Access Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4" data-label="Personnel">
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->school_id }} • {{ $user->email }}</small>
                                </td>
                                <td data-label="Department">
                                    <span class="badge bg-light text-dark border rounded-pill px-3">
                                        {{ $user->department ?: 'Administrative Office' }}
                                    </span>
                                </td>
                                <td data-label="Designation">
                                    <span class="small fw-semibold">{{ $roleLabels[$user->role] ?? ucwords(str_replace('_', ' ', $user->role)) }}</span>
                                </td>
                                <td data-label="Access">
                                    @if($user->is_approved)
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">Approved</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Pending / Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4" data-label="Action">
                                    @if(in_array($user->id, $manageableUserIds, true))
                                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold"
                                            @click="openEdit({{ json_encode([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'role' => $user->role,
                                                'department' => $user->department,
                                                'is_approved' => $user->is_approved,
                                            ]) }})">
                                            Manage
                                        </button>
                                    @else
                                        <span class="small text-muted">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No personnel match the current scope or search.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="editPersonnelModal" tabindex="-1" aria-hidden="true" aria-labelledby="editPersonnelModalTitle">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 bg-light p-4 pb-3">
                        <div>
                            <h6 class="modal-title fw-bold mb-0" id="editPersonnelModalTitle">Manage Personnel Record</h6>
                            <small class="text-muted" x-text="selectedUser.name"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form :action="'{{ url('/admin/users') }}/' + selectedUser.id" method="POST">
                        @csrf
                        @method('patch')

                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="managed_role" class="form-label mini-label">Designation</label>
                                <select id="managed_role" name="role" class="form-select bg-light border-0 shadow-none" x-model="selectedUser.role" required>
                                    @foreach($manageableRoles as $role)
                                        <option value="{{ $role }}">{{ $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($canChangeDepartment)
                                <div class="mb-3">
                                    <label for="managed_department" class="form-label mini-label">Department</label>
                                    <select id="managed_department" name="department" class="form-select bg-light border-0 shadow-none" x-model="selectedUser.department">
                                        <option value="">Administrative Office / Not Assigned</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->dept_code }}">{{ $department->dept_name }} ({{ $department->dept_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="department" value="{{ Auth::user()->department }}">
                                <div class="mb-3">
                                    <label class="form-label mini-label">Department Scope</label>
                                    <input type="text" class="form-control bg-light border-0" value="{{ Auth::user()->department }}" readonly>
                                </div>
                            @endif

                            <div>
                                <label for="managed_status" class="form-label mini-label">Account Access</label>
                                <select id="managed_status" name="is_approved" class="form-select bg-light border-0 shadow-none" x-model="selectedUser.is_approved" required>
                                    <option value="1">Approved / Active</option>
                                    <option value="0">Pending / Inactive</option>
                                </select>
                                <small class="text-muted d-block mt-2">Inactive accounts cannot sign in until access is approved again.</small>
                            </div>
                        </div>

                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-htc rounded-pill px-4 fw-bold">Save Personnel Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mini-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
        .btn-htc { background-color: var(--htc-green); color: white; border: none; }
        .btn-htc:hover { background-color: #0d2e16; color: white; }
        @media (max-width: 767.98px) {
            .personnel-toolbar { align-items: stretch !important; flex-direction: column; }
            .personnel-search { width: 100%; }
            .personnel-search .input-group { flex: 1; }
            .personnel-search input { min-width: 0 !important; min-height: 42px; font-size: 16px; }
            .personnel-page > .row.g-3 { --bs-gutter-y: .65rem; }
            .personnel-page > .card > .card-header { padding: 1rem !important; }
        }
        @media (max-width: 575.98px) {
            #editPersonnelModal .modal-dialog { width: 100%; max-width: none; height: 100%; min-height: 100%; margin: 0; }
            #editPersonnelModal .modal-content { height: 100dvh; max-height: 100dvh; border-radius: 0 !important; }
            #editPersonnelModal .modal-header,
            #editPersonnelModal .modal-body,
            #editPersonnelModal .modal-footer { padding: 1rem !important; }
            #editPersonnelModal select,
            #editPersonnelModal input { min-height: 44px; font-size: 16px; }
            #editPersonnelModal .modal-footer { align-items: stretch; flex-direction: column-reverse; }
            #editPersonnelModal .modal-footer .btn { width: 100%; min-height: 44px; margin: 0; }
        }
    </style>
</x-app-layout>
