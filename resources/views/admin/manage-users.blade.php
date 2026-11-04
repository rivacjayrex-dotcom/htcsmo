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
    $personnelCategories = [
        'all' => ['label' => 'All Personnel', 'description' => 'Everyone within your management scope'],
        'admins' => ['label' => 'Administrators', 'description' => 'SMO and institutional administrators'],
        'heads' => ['label' => 'Deans & Heads', 'description' => 'Department heads and academic deans'],
        'staff' => ['label' => 'Faculty & Staff', 'description' => 'Faculty and staff grouped by department'],
    ];
    $activeCategory = $category ?? 'all';
@endphp

<x-app-layout>
    <x-slot name="header">Personnel Management</x-slot>

    <div class="container-fluid py-2 personnel-page" data-filter-workspace="personnel" x-data="{
        editModal: null,
        selectedUser: { id: '', name: '', role: '', department: '', account_status: 'active', version: 0 },
        detailedUser: {},
        openDetails(user) {
            this.detailedUser = user;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('personnelDetailsModal')).show();
        },
        openEdit(user) {
            this.selectedUser = {
                ...user,
                department: user.department || '',
                account_status: user.account_status,
                version: user.version
            };
            this.editModal ??= new bootstrap.Modal(document.getElementById('editPersonnelModal'));
            this.editModal.show();
        }
    }">
        <div class="personnel-toolbar">
            <div>
                <h4 class="personnel-title">{{ $scopeLabel }} Personnel</h4>
                <p class="personnel-description">
                    @if(Auth::user()->role === 'dept_head')
                        Manage faculty and staff accounts assigned to your department.
                    @else
                        Review personnel assignments, access status, roles, and departments.
                    @endif
                </p>
            </div>

            @if(Auth::user()->role === 'smo')
                <button type="button" class="btn btn-htc personnel-add-department" data-bs-toggle="modal" data-bs-target="#addDepartmentModal" aria-haspopup="dialog" aria-controls="addDepartmentModal"><i data-lucide="plus" aria-hidden="true"></i> Add department</button>
            @endif
        </div>

        <div class="personnel-summary" data-filter-region="summary">
            <div class="personnel-stat">
                <div><span class="personnel-stat__label">Visible Personnel</span><strong class="personnel-stat__value">{{ $stats['total'] }}</strong></div>
                <span class="personnel-stat__icon"><i data-lucide="users" aria-hidden="true"></i></span>
            </div>
            <div class="personnel-stat personnel-stat--approved">
                <div><span class="personnel-stat__label">Approved Accounts</span><strong class="personnel-stat__value">{{ $stats['active'] }}</strong></div>
                <span class="personnel-stat__icon"><i data-lucide="user-round-check" aria-hidden="true"></i></span>
            </div>
            <div class="personnel-stat personnel-stat--suspended">
                <div><span class="personnel-stat__label">Suspended Accounts</span><strong class="personnel-stat__value">{{ $stats['suspended'] }}</strong></div>
                <span class="personnel-stat__icon"><i data-lucide="user-round-minus" aria-hidden="true"></i></span>
            </div>
        </div>

        <form id="personnelFilters" action="{{ route('admin.users.manage') }}#personnel-results" method="GET" class="personnel-search" data-dynamic-search>
                @if($category)
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif
                <div class="personnel-search__field">
                    <label for="personnel-search" class="personnel-filter-label">Search personnel</label>
                    <div class="personnel-search__input">
                        <i data-lucide="search" aria-hidden="true"></i>
                        <input id="personnel-search" type="search" name="search" value="{{ request('search') }}"
                            class="form-control" maxlength="100" placeholder="Search name, ID, or email" aria-label="Search personnel">
                    </div>
                </div>
                @if(Auth::user()->role !== 'dept_head')
                <div class="personnel-search__department">
                    <label for="personnel-department" class="personnel-filter-label">Department</label>
                    <select id="personnel-department" name="department" class="form-select personnel-department-filter" aria-label="Filter personnel by department">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->dept_code }}" @selected(request('department') === $department->dept_code)>{{ $department->dept_code }} — {{ $department->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <a href="{{ route('admin.users.manage') }}#personnel-results" data-filter-link class="btn personnel-clear-filters"><i data-lucide="rotate-ccw" aria-hidden="true"></i> Clear filters</a>
                <x-filter-submit />
        </form>

        <section id="personnel-results" data-filter-results data-filter-region="results" data-result-count="{{ $users->total() }}" tabindex="-1" aria-label="Matching personnel">
        <div class="personnel-registry">
        <nav class="personnel-categories" aria-label="Personnel categories" data-filter-links>
            @foreach($personnelCategories as $categoryKey => $categoryDetails)
                    @php
                        $categoryUrl = route('admin.users.manage', array_filter([
                            'category' => $categoryKey === 'all' ? null : $categoryKey,
                            'search' => request('search'),
                            'department' => request('department'),
                        ]));
                    @endphp
                    <a href="{{ $categoryUrl }}#personnel-results"
                        class="personnel-category {{ $activeCategory === $categoryKey ? 'active' : '' }}"
                        @if($activeCategory === $categoryKey) aria-current="page" @endif>
                        <span class="personnel-category__label">{{ $categoryDetails['label'] }}</span>
                        <span class="personnel-category__count">{{ $categoryCounts[$categoryKey] ?? 0 }}</span>
                    </a>
            @endforeach
        </nav>

        <div class="personnel-registry__body">
            <div class="personnel-registry__header">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-2">{{ $personnelCategories[$activeCategory]['label'] }}</h6>
                        <p class="personnel-description">{{ $personnelCategories[$activeCategory]['description'] }}.</p>
                    </div>
                    @if($activeCategory !== 'all')
                        <a href="{{ route('admin.users.manage', array_filter(request()->only('search', 'department'))) }}#personnel-results" data-filter-link class="btn btn-sm btn-light border rounded-pill px-3">
                            View all
                        </a>
                    @endif
                </div>
                <p class="personnel-result-count">{{ number_format($users->total()) }} {{ $users->total() === 1 ? 'person' : 'people' }} found @if($users->hasPages())<span aria-hidden="true">&middot;</span> Showing {{ $users->firstItem() }}&ndash;{{ $users->lastItem() }}@endif</p>
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
                            @if($activeCategory === 'staff' && ($loop->first || $users->getCollection()->get($loop->index - 1)->department_id !== $user->department_id))
                                <tr class="personnel-department-heading" data-personnel-department="{{ $user->department ?: 'unassigned' }}">
                                    <th colspan="5" scope="rowgroup">{{ $user->academicDepartment ? $user->academicDepartment->dept_code.' — '.$user->academicDepartment->dept_name : 'Administrative Office / Not assigned' }}</th>
                                </tr>
                            @endif
                            <tr>
                                <td class="ps-4" data-label="Personnel">
                                    <div class="personnel-identity">
                                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                                        <small class="text-muted">{{ $user->school_id }} <span aria-hidden="true">&middot;</span> {{ $user->email }}</small>
                                    </div>
                                </td>
                                <td data-label="Department">
                                    <span class="badge bg-light text-dark border rounded-pill px-3">
                                        {{ $user->academicDepartment?->dept_code ?? ($user->department ?: 'Administrative Office') }}
                                    </span>
                                </td>
                                <td data-label="Designation">
                                    <span class="small fw-semibold">{{ $roleLabels[$user->role] ?? ucwords(str_replace('_', ' ', $user->role)) }}</span>
                                </td>
                                <td data-label="Access">
                                    @if($user->account_status === \App\Models\User::ACCOUNT_ACTIVE)
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">Active</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Suspended</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4" data-label="Action">
                                    <div class="personnel-row-actions">
                                    @if(Auth::user()->role === 'smo')
                                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold personnel-details-button"
                                            aria-haspopup="dialog" aria-controls="personnelDetailsModal" aria-label="View details for {{ $user->name }}"
                                            x-on:click="openDetails({{ Illuminate\Support\Js::from([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'username' => $user->username ?: 'Not set',
                                                'school_id' => $user->school_id ?: 'Not set',
                                                'email' => $user->email,
                                                'department' => $user->academicDepartment ? $user->academicDepartment->dept_name.' ('.$user->academicDepartment->dept_code.')' : 'Administrative Office / Not assigned',
                                                'designation' => $roleLabels[$user->role] ?? ucwords(str_replace('_', ' ', $user->role)),
                                                'status' => ucfirst($user->account_status),
                                                'email_verified_at' => $user->email_verified_at?->format('M j, Y g:i A') ?? 'Not verified',
                                                'created_at' => $user->created_at?->format('M j, Y g:i A') ?? 'Not recorded',
                                                'updated_at' => $user->updated_at?->format('M j, Y g:i A') ?? 'Not recorded',
                                            ]) }})">
                                            View details
                                        </button>
                                    @endif
                                    @if(in_array($user->id, $manageableUserIds, true))
                                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold personnel-manage-button"
                                            aria-haspopup="dialog" aria-controls="editPersonnelModal" aria-label="Manage {{ $user->name }}"
                                            @click="openEdit({{ json_encode([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'role' => $user->role,
                                                'department' => $user->department,
                                                'account_status' => $user->account_status,
                                                'version' => $user->account_version,
                                            ]) }})">
                                            Manage
                                        </button>
                                    @else
                                        <span class="small text-muted">View only</span>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="personnel-empty-icon mx-auto mb-2"><i data-lucide="users-round"></i></div>
                                    <div class="fw-bold text-dark">No {{ strtolower($personnelCategories[$activeCategory]['label']) }} found</div>
                                    <small class="text-muted">Try clearing the search or selecting another category.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer bg-white border-0 p-3" data-filter-pagination>{{ $users->fragment('personnel-results')->links() }}</div>
            @endif
        </div>
        </div>
        </section>
        @if(Auth::user()->role === 'smo')
            @include('admin.partials.add-department')
            <div class="modal fade" id="personnelDetailsModal" tabindex="-1" aria-hidden="true" aria-labelledby="personnelDetailsTitle">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="personnelDetailsTitle">Personnel details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <dl class="row mb-0 personnel-detail-fields">
                                @foreach([
                                    'id' => 'Account ID', 'name' => 'Full name', 'username' => 'Username',
                                    'school_id' => 'School ID', 'email' => 'Email address', 'department' => 'Department',
                                    'designation' => 'Designation', 'status' => 'Account status',
                                    'email_verified_at' => 'Email verified', 'created_at' => 'Registered', 'updated_at' => 'Last updated',
                                ] as $field => $label)
                                    <div class="col-sm-6 mb-3">
                                        <dt class="mini-label mb-1">{{ $label }}</dt>
                                        <dd class="mb-0 text-break" x-text="detailedUser.{{ $field }}"></dd>
                                    </div>
                                @endforeach
                            </dl>
                            <div class="border-top pt-3">
                                <h6 class="fw-bold">Password</h6>
                                <p class="text-muted small mb-0">Passwords are stored as one-way hashes and cannot be viewed. The user can reset their password using Forgot password on the sign-in page.</p>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button></div>
                    </div>
                </div>
            </div>
        @endif
        <div class="modal fade" id="editPersonnelModal" tabindex="-1" aria-hidden="true" aria-labelledby="editPersonnelModalTitle">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 bg-light p-4 pb-3">
                        <div>
                            <h6 class="modal-title fw-bold mb-0" id="editPersonnelModalTitle">Manage Personnel Record</h6>
                            <small class="text-muted" x-text="selectedUser.name"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form :action="'{{ url('/admin/users') }}/' + selectedUser.id" method="POST" :data-confirm="selectedUser.account_status === 'suspended' ? 'Suspend access for ' + selectedUser.name + '? They will be signed out and unable to sign in until access is restored.' : 'Save the designation, department, and account access settings for ' + selectedUser.name + '?'" :data-confirm-title="selectedUser.account_status === 'suspended' ? 'Suspend this account?' : 'Update personnel record?'" :data-confirm-tone="selectedUser.account_status === 'suspended' ? 'danger' : 'default'" data-confirm-accept="Save personnel record">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="version" :value="selectedUser.version">

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
                                <select id="managed_status" name="account_status" class="form-select bg-light border-0 shadow-none" x-model="selectedUser.account_status" required>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                <small class="text-muted d-block mt-2">Suspended accounts are signed out and cannot sign in until access is restored.</small>
                            </div>

                            <div class="mt-3" x-show="selectedUser.account_status === 'suspended'" x-cloak>
                                <label for="change_reason" class="form-label mini-label">Suspension reason</label>
                                <textarea id="change_reason" name="change_reason" class="form-control bg-light border-0 shadow-none" rows="3" maxlength="500" :required="selectedUser.account_status === 'suspended'" placeholder="Record a concise administrative reason"></textarea>
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

    @pushOnce('styles', 'page-styles:admin/manage-users.blade.php')
    <style>
        .mini-label { font-size: var(--text-secondary); font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: var(--tracking-label); }
        .btn-htc { background-color: var(--htc-green); color: white; border: none; }
        .btn-htc:hover { background-color: #0d2e16; color: white; }
        .personnel-page { display: flex; flex-direction: column; gap: var(--space-24); }
        .personnel-toolbar { display: flex; align-items: center; justify-content: space-between; gap: var(--space-24); }
        .personnel-title { margin: 0 0 var(--space-8); color: #183c29; font-size: var(--text-heading-md); font-weight: 800; }
        .personnel-description { margin: 0; color: #65746b; font-size: var(--text-secondary); line-height: var(--line-body); }
        .personnel-add-department { display: inline-flex; align-items: center; justify-content: center; gap: var(--space-8); flex-shrink: 0; min-height: 44px; border-radius: 10px; font-weight: 700; }
        .personnel-add-department svg, .personnel-clear-filters svg { width: 16px; height: 16px; flex-shrink: 0; }
        .personnel-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--space-16); }
        .personnel-stat { display: flex; align-items: center; justify-content: space-between; gap: var(--space-12); min-width: 0; padding: var(--space-24); border: 1px solid #dfe8e2; border-radius: 14px; background: #fff; }
        .personnel-stat__label { display: block; color: #65746b; font-size: var(--text-secondary); font-weight: 600; }
        .personnel-stat__value { display: block; margin-top: var(--space-8); color: #183c29; font-size: var(--text-heading-md); font-weight: 800; line-height: var(--line-heading); font-variant-numeric: tabular-nums; }
        .personnel-stat__icon { display: grid; place-items: center; flex: 0 0 44px; height: 44px; border-radius: 12px; color: #52735f; background: #f0f5f2; }
        .personnel-stat__icon svg { width: 22px; height: 22px; }
        .personnel-stat--approved .personnel-stat__value { color: #1b7148; }
        .personnel-stat--approved .personnel-stat__icon { color: #1b7148; background: #eaf5ee; }
        .personnel-stat--suspended .personnel-stat__value { color: #94620a; }
        .personnel-stat--suspended .personnel-stat__icon { color: #94620a; background: #fff6e5; }
        .personnel-search { display: flex; align-items: flex-end; flex-wrap: wrap; gap: var(--space-16); padding: var(--space-24); border: 1px solid #dfe8e2; border-radius: 14px; background: #f7faf8; }
        .personnel-filter-label { display: block; margin-bottom: var(--space-8); color: #40584a; font-size: var(--text-secondary); font-weight: 650; }
        .personnel-search__field { flex: 1 1 280px; min-width: 0; }
        .personnel-search__input { position: relative; }
        .personnel-search__input > svg { position: absolute; top: 50%; left: var(--space-12); width: 18px; height: 18px; transform: translateY(-50%); color: #7b8980; pointer-events: none; }
        .personnel-search .form-control { padding-left: 40px; }
        .personnel-search .form-control, .personnel-search .form-select { min-height: 44px; border-color: #d4e1d8; border-radius: 9px; background-color: #fff; font-size: var(--text-secondary); }
        .personnel-search .form-control:focus, .personnel-search .form-select:focus { border-color: #27764b; box-shadow: 0 0 0 3px rgb(39 118 75 / 12%); }
        .personnel-search__department { flex: 0 1 320px; min-width: 0; }
        .personnel-clear-filters { display: inline-flex; align-items: center; justify-content: center; gap: var(--space-8); min-height: 44px; border: 1px solid #d4e1d8; border-radius: 9px; color: #52685a; background: #fff; font-weight: 600; }
        .personnel-clear-filters:hover { color: #185b3b; background: #eaf3ed; border-color: #a8c4b1; }
        .personnel-registry { container-type: inline-size; border: 1px solid #dfe8e2; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgb(20 62 37 / 3%); }
        .personnel-categories { display: flex; flex-wrap: wrap; gap: var(--space-4); padding: var(--space-8) var(--space-16) 0; border-bottom: 1px solid #dfe8e2; }
        .personnel-category { position: relative; display: flex; align-items: center; justify-content: center; flex: 1 1 175px; gap: var(--space-12); min-height: 60px; padding: var(--space-12); border-bottom: 3px solid transparent; border-radius: 8px 8px 0 0; color: #65746b; text-decoration: none; transition: color .18s ease, background-color .18s ease; }
        .personnel-category:hover { color: #185b3b; background: #f4f8f5; }
        .personnel-category.active { border-bottom-color: #27764b; color: #185b3b; background: #f0f7f2; }
        .personnel-category:focus-visible, .personnel-clear-filters:focus-visible { outline: 3px solid #27764b; outline-offset: -3px; }
        .personnel-category__label { font-size: var(--text-secondary); font-weight: 700; }
        .personnel-category__count { min-width: 28px; padding: var(--space-4) var(--space-8); border-radius: 7px; color: #596e60; background: #eef2ef; font-size: var(--text-caption); font-weight: 800; text-align: center; font-variant-numeric: tabular-nums; }
        .personnel-category.active .personnel-category__count { color: #19603a; background: #dceee2; }
        .personnel-registry__header { padding: var(--space-24); }
        .personnel-registry__header h6 { font-size: var(--text-heading-sm); }
        .personnel-result-count { margin: var(--space-16) 0 0; color: #65746b; font-size: var(--text-caption); }
        .personnel-result-count > span { margin: 0 var(--space-8); }
        .personnel-page .personnel-table { --bs-table-hover-bg: #f7faf8; }
        .personnel-page .personnel-table thead th { padding: var(--space-16); border-top: 1px solid #e5ede8; border-bottom-color: #e5ede8; background: #f7faf8; color: #65746b; font-size: var(--text-caption); font-weight: 700; white-space: nowrap; }
        .personnel-page .personnel-table tbody td { padding: var(--space-24) var(--space-16); border-color: #eaf0ec; }
        .personnel-table tbody tr:last-child td { border-bottom: 0; }
        .personnel-identity { display: grid; gap: var(--space-8); min-width: 0; }
        .personnel-identity > small { overflow-wrap: anywhere; }
        .personnel-table .badge { padding-top: var(--space-8); padding-bottom: var(--space-8); }
        .personnel-row-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: var(--space-8); }
        .personnel-row-actions .btn { min-height: 36px; white-space: nowrap; font-size: var(--text-caption); }
        .personnel-row-actions .personnel-manage-button { border-color: #cee0d4 !important; color: #185b3b; background: #edf6f0; }
        .personnel-registry .card-footer { border-radius: 0 0 14px 14px; }
        .personnel-empty-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; color: #678071; background: #edf3ef; }
        .personnel-empty-icon svg { width: 20px; height: 20px; }
        .personnel-table .personnel-department-heading th { padding:var(--space-12) var(--space-24); background:#edf5ef; color:#235538; font-size:var(--text-secondary); overflow-wrap:anywhere; }
        @container (max-width: 760px) {
            .personnel-categories { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); padding: var(--space-8); gap: var(--space-8); }
            .personnel-category { justify-content: space-between; min-height: 48px; border: 1px solid transparent; border-radius: 8px; }
            .personnel-category.active { border-color: #95bca2; box-shadow: inset 3px 0 #27764b; }
        }
        @media (max-width: 767.98px) {
            .personnel-page { gap: var(--space-24); }
            .personnel-toolbar { align-items: flex-start; flex-direction: column; gap: var(--space-16); }
            .personnel-summary { gap: var(--space-8); }
            .personnel-stat { align-items: flex-start; padding: var(--space-16); }
            .personnel-stat__icon { display: none; }
            .personnel-stat__label { font-size: var(--text-caption); }
            .personnel-search { gap: var(--space-16); padding: var(--space-16); }
            .personnel-search__field { flex-basis: 100%; }
            .personnel-search__department { flex: 1 1 220px; }
            .personnel-registry__header { padding: var(--space-24) var(--space-16); }
            .personnel-table tbody td[data-label="Personnel"] { display: block; text-align: left !important; }
            .personnel-table tbody td[data-label="Personnel"]::before { display: none; }
            .personnel-table tbody td[data-label="Personnel"] > * { text-align: left; }
            .personnel-row-actions { width: 100%; }
            .personnel-row-actions .btn { min-height: 44px; }
            .personnel-table .personnel-department-heading { border:0; border-radius:8px; box-shadow:none; }
            .personnel-table .personnel-department-heading th { display:block; width:100%; padding:var(--space-12); }
            .personnel-search .form-control, .personnel-search .form-select { font-size: var(--text-body); }
        }
        @media (max-width: 399.98px) {
            .personnel-summary { grid-template-columns: 1fr; }
            .personnel-stat > div { display: flex; align-items: center; justify-content: space-between; gap: var(--space-12); width: 100%; }
            .personnel-stat__value { margin: 0; }
            .personnel-category { gap: var(--space-8); padding: var(--space-8); }
        }
        @media (max-width: 575.98px) {
            #editPersonnelModal .modal-dialog { width: 100%; max-width: none; height: 100%; min-height: 100%; margin: 0; }
            #editPersonnelModal .modal-content { height: 100dvh; max-height: 100dvh; border-radius: 0 !important; }
            #editPersonnelModal .modal-header,
            #editPersonnelModal .modal-body,
            #editPersonnelModal .modal-footer { padding: var(--space-16) !important; }
            #editPersonnelModal select,
            #editPersonnelModal input { min-height: 44px; font-size: var(--text-body); }
            #editPersonnelModal .modal-footer { align-items: stretch; flex-direction: column-reverse; }
            #editPersonnelModal .modal-footer .btn { width: 100%; min-height: 44px; margin: 0; }
        }
    </style>
    @endPushOnce
</x-app-layout>
