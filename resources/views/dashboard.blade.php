<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between w-100">
            <span class="text-white fw-bold tracking-wide" style="font-size: 1.1rem;">
                {{ Auth::user()->role == 'smo' ? 'SMO COMMAND CENTER' : 'INSTITUTIONAL DASHBOARD' }}
            </span>
        </div>
    </x-slot>

    <div class="container-fluid py-3">

        @if(Auth::user()->role == 'smo')
            <!-- ============================================================== -->
            <!-- SMO IN-CHARGE COMMAND CENTER (THESIS CHAPTER IV FIGURE 7.08)   -->
            <!-- ============================================================== -->

            <!-- ROW 1: INSTITUTIONAL METRICS STRIP -->
            <div class="row g-3 mb-4">
                <!-- 1. Total Requisitions -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 border-start border-4 border-dark">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase tracking-wider">Total Requisitions</span>
                                <h2 class="fw-black text-dark mb-0 mt-2" x-data="countUp({{ $stats['total'] }})" x-text="current">0</h2>
                            </div>
                            <div class="p-3 bg-light text-dark rounded-3">
                                <i data-lucide="database" style="width: 22px; height: 22px;"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3" style="font-size: 10px;">All-time cumulative requisitions</small>
                    </div>
                </div>

                <!-- 2. Active in Pipeline -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase tracking-wider">Active Requisitions</span>
                                <h2 class="fw-black text-warning mb-0 mt-2" x-data="countUp({{ $stats['pending'] }})" x-text="current">0</h2>
                            </div>
                            <div class="p-3 bg-warning-subtle text-warning rounded-3">
                                <i data-lucide="clock" style="width: 22px; height: 22px;"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3" style="font-size: 10px;">Currently awaiting decisions or fulfillment</small>
                    </div>
                </div>

                <!-- 3. CRITICAL 3-DAY SLA ALERT (High-visibility pulsing indicator) -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-start border-4 {{ $stats['urgent'] > 0 ? 'border-danger bg-danger-subtle pulse-card' : 'border-secondary bg-white' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="{{ $stats['urgent'] > 0 ? 'text-danger fw-bold' : 'text-muted' }} small text-uppercase tracking-wider">
                                    3-Day SLA Overdue / Warning
                                </span>
                                <h2 class="fw-black {{ $stats['urgent'] > 0 ? 'text-danger' : 'text-dark' }} mb-0 mt-2" x-data="countUp({{ $stats['urgent'] }})" x-text="current">0</h2>
                            </div>
                            <div class="p-3 {{ $stats['urgent'] > 0 ? 'bg-danger text-white' : 'bg-light text-muted' }} rounded-3">
                                <i data-lucide="alert-triangle" style="width: 22px; height: 22px;"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            @if($stats['urgent'] > 0)
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: 9px;">ACTION REQUIRED</span>
                            @else
                                <small class="text-muted" style="font-size: 10px;">No requests exceeding turnaround limit</small>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 4. Monthly Distribution Value -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase tracking-wider">Disbursed This Month</span>
                                <h2 class="fw-black text-success mb-0 mt-2">₱<span x-data="countUp({{ $stats['monthly_value'] }})" x-text="current.toLocaleString()">0</span></h2>
                            </div>
                            <div class="p-3 bg-success-subtle text-success rounded-3">
                                <i data-lucide="trending-up" style="width: 22px; height: 22px;"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3" style="font-size: 10px;">Released institutional supply value</small>
                    </div>
                </div>
            </div>

            <!-- ROW 2: MAIN QUEUE & MINI ACTION NOTIFICATION FEED -->
            <div class="row g-4">
                <!-- LEFT: INSTITUTIONAL QUEUE MANAGEMENT (8 Cols) -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <!-- HEADER & QUICK ACTIONS -->
                        <div class="card-header bg-white border-0 p-4 pb-2">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Active Requisitions</h5>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('admin.approvals') }}" class="btn btn-sm btn-htc rounded-pill px-3 fw-bold shadow-sm" style="font-size: 11px;">
                                        <i data-lucide="package-check" class="me-1" style="width: 14px;"></i> Open Release Queue
                                    </a>
                                </div>
                            </div>

                            <!-- ADVANCED MULTI-FILTER TOOLBAR -->
                            <form action="{{ route('dashboard') }}" method="GET" class="row g-2 pb-3 border-bottom">
                                <!-- Status Filter -->
                                <!-- Status Filter (Active Requisitions Only) -->
                                <div class="col-md-3 col-6">
                                    <select name="status" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none fw-semibold" onchange="this.form.submit()" style="font-size: 11px;">
                                        <option value="">All Active Requisitions</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Dept Head</option>
                                        <option value="approved_dept" {{ request('status') == 'approved_dept' ? 'selected' : '' }}>Pending VP Review</option>
                                        <option value="approved_vp" {{ request('status') == 'approved_vp' ? 'selected' : '' }}>Ready for SMO Release (Minor)</option>
                                        <option value="approved_provost" {{ request('status') == 'approved_provost' ? 'selected' : '' }}>Pending President</option>
                                        <option value="approved_president" {{ request('status') == 'approved_president' ? 'selected' : '' }}>Ready for SMO Release (Major)</option>
                                        <option value="for_clarification" {{ request('status') == 'for_clarification' ? 'selected' : '' }}>⚠️ Office Clarification</option>
                                        <option value="near_deadline" {{ request('status') == 'near_deadline' ? 'selected' : '' }}>🚨 Near Deadline / Overdue</option>
                                    </select>
                                </div>

                                <!-- Dept Filter -->
                                <div class="col-md-3 col-6">
                                    <select name="dept" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none fw-semibold" onchange="this.form.submit()" style="font-size: 11px;">
                                        <option value="">All Departments</option>
                                        <option value="CETE" {{ request('dept') == 'CETE' ? 'selected' : '' }}>CETE</option>
                                        <option value="CTE" {{ request('dept') == 'CTE' ? 'selected' : '' }}>CTE</option>
                                        <option value="CBMA" {{ request('dept') == 'CBMA' ? 'selected' : '' }}>CBMA</option>
                                        <option value="CCJE" {{ request('dept') == 'CCJE' ? 'selected' : '' }}>CCJE</option>
                                        <option value="CAS" {{ request('dept') == 'CAS' ? 'selected' : '' }}>CAS</option>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div class="col-md-4 col-8">
                                    <div class="input-group input-group-sm">
                                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control border-0 bg-light rounded-start-3" onchange="this.form.submit()" style="font-size: 10px;">
                                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control border-0 bg-light rounded-end-3" onchange="this.form.submit()" style="font-size: 10px;">
                                    </div>
                                </div>

                                <!-- Sort Order & Reset -->
                                <div class="col-md-2 col-4 text-end d-flex gap-1 justify-content-end">
                                    <select name="order" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" onchange="this.form.submit()" style="font-size: 10px;">
                                        <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Newest</option>
                                        <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Oldest</option>
                                    </select>
                                    @if(request()->anyFilled(['status', 'dept', 'date_from', 'date_to']))
                                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-danger border-0 p-1" title="Clear Filters">
                                            <i data-lucide="x-circle" style="width: 16px;"></i>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>

                        <!-- REQUISITIONS TABLE -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                                <thead class="bg-light text-muted uppercase" style="font-size: 9px;">
                                    <tr>
                                        <th class="ps-4 py-3">Control Tracking Code</th>
                                        <th>Requestor</th>
                                        <th>Primary Item</th>
                                        <th class="text-center">Status</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allStaffRequests as $req)
                                    @php
                                        $isOverdue = method_exists($req, 'isOverdue') && $req->isOverdue();
                                        $isNearDeadline = method_exists($req, 'isNearingDeadline') && $req->isNearingDeadline();

                                        $statusClass = match($req->status) {
                                            'released'           => 'bg-success text-white',
                                            'rejected'           => 'bg-danger text-white',
                                            'for_clarification'  => 'bg-warning text-dark border border-dark',
                                            'approved_president' => 'bg-primary text-white',
                                            default              => 'bg-light text-dark border'
                                        };
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'row-overdue' : ($isNearDeadline ? 'row-near-deadline' : '') }}">
                                        <td class="ps-4">
                                            <div class="font-monospace fw-bold text-dark" style="font-size: 11px;">
                                                {{ $req->tracking_code }}
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">{{ $req->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $req->user->name }}</div>
                                            <span class="badge bg-light text-dark border" style="font-size: 9px;">{{ $req->user->department }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $req->items->first()->item_name ?? 'Supplies' }}</div>
                                            @if($req->items->count() > 1)
                                                <small class="text-primary fw-bold" style="font-size: 9px;">+{{ $req->items->count() - 1 }} additional item(s)</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $statusClass }} text-uppercase px-2 py-1 rounded-pill" style="font-size: 8px;">
                                                {{ str_replace('_', ' ', $req->status) }}
                                            </span>

                                            <!-- 3-Day SLA Warning Badges -->
                                            @if($isOverdue && !in_array($req->status, ['released', 'rejected']))
                                                <div class="text-danger fw-black mt-1" style="font-size: 8px;">🚨 OVERDUE (>3 DAYS)</div>
                                            @elseif($isNearDeadline && !in_array($req->status, ['released', 'rejected']))
                                                <div class="text-warning fw-bold mt-1" style="font-size: 8px;">⏳ DAY 2 WARNING</div>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ route('requests.show', $req->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none fw-bold" style="font-size: 10px;">
                                                Track →
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted small">No requisitions currently match your criteria.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: SMO AUDIT & ALERT FEED (4 Cols) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">System Alerts & Logs</h6>
                                <small class="text-muted">Real-time requisition notifications</small>
                            </div>
                            <a href="{{ route('notifications') }}" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" style="font-size: 10px;">
                                View All
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <div class="mini-alert-feed">
                                @forelse($recentActivity as $activity)
                                <div class="d-flex align-items-start mb-3 border-bottom pb-3">
                                    <div class="p-2 rounded-3 bg-light me-3 text-{{ $activity->type }}">
                                        <i data-lucide="{{ $activity->icon }}" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div class="fw-bold text-dark small" style="line-height: 1.2;">{{ $activity->title }}</div>
                                        <div class="text-muted mt-1" style="font-size: 10px; line-height: 1.3;">{{ Str::limit($activity->message, 80) }}</div>
                                        <small class="text-muted d-block mt-1 font-monospace" style="font-size: 9px;">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-5 text-muted small">No recent alert activity.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <!-- ============================================================== -->
            <!-- EMPLOYEE & FACULTY DASHBOARD VIEW                              -->
            <!-- ============================================================== -->

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-black text-dark mb-0">GoodHoly! {{ Auth::user()->name }}!</h4>
                    <p class="text-muted small mb-0">{{ Auth::user()->department }} Department • {{ Auth::user()->school_id }}</p>
                </div>
                <button type="button" class="btn btn-htc px-4 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#globalRequestModal">
                    <i data-lucide="plus-circle" class="me-2" style="width: 16px;"></i> Initialize Requisition
                </button>
            </div>

            <!-- EMPLOYEE STAT METRICS -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white border-start border-4 border-primary">
                        <small class="text-uppercase fw-bold text-muted mb-2 d-block tracking-widest" style="font-size: 10px;">My Active Requisitions</small>
                        <div class="d-flex justify-content-between align-items-center" x-data="countUp({{ $activeCount }})">
                            <h2 class="display-6 fw-black m-0 text-dark" x-text="current">0</h2>
                            <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                                <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 10px;">Requests in verification, endorsement, or approval</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white border-start border-4 border-success">
                        <small class="text-uppercase fw-bold text-muted mb-2 d-block tracking-widest" style="font-size: 10px;">Approved & Ready (Last 24h)</small>
                        <div class="d-flex justify-content-between align-items-center" x-data="countUp({{ $approvedLast24h }})">
                            <h2 class="display-6 fw-black m-0 text-success" x-text="current">0</h2>
                            <div class="p-3 rounded-circle bg-success-subtle text-success">
                                <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 10px;">Requisitions ready for pickup at the SMO</small>
                    </div>
                </div>
            </div>

            <!-- MY IN-PROGRESS REQUISITIONS TABLE -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 fw-bold text-dark">My Active Procurement Activity</h6>
                        <small class="text-muted">Real-time status tracking of your submitted requisitions</small>
                    </div>
                    <a href="{{ route('requests.index') }}" class="btn btn-sm btn-link text-success text-decoration-none fw-bold" style="font-size: 11px;">
                        View Full History →
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Control Code</th>
                                <th>Primary Item</th>
                                <th>Submitted Date</th>
                                <th class="text-center">Approval Status</th>
                                <th class="pe-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-bold text-dark">{{ $request->tracking_code }}</span>
                                    <span class="badge {{ $request->request_type == 'major' ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-2 ms-1" style="font-size: 8px;">
                                        {{ strtoupper($request->request_type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $request->items->first()->item_name ?? 'Supplies' }}</div>
                                    @if($request->items->count() > 1)
                                        <small class="text-muted">+{{ $request->items->count() - 1 }} other item(s)</small>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $request->created_at->format('M d, Y') }}</td>
                                <td class="text-center">
                                    @php
                                        $statusClass = match($request->status) {
                                            'released'          => 'bg-success text-white',
                                            'for_clarification' => 'bg-warning text-dark border border-warning',
                                            'rejected'          => 'bg-danger text-white',
                                            default             => 'bg-light text-dark border'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }} text-uppercase px-3 py-1 rounded-pill" style="font-size: 9px;">
                                        {{ str_replace('_', ' ', $request->status) }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('requests.show', $request->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none fw-bold" style="font-size: 10px;">
                                        Track Order →
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i data-lucide="package-open" style="width: 36px; height: 36px;" class="mb-2 opacity-25 d-block mx-auto"></i>
                                    You have no active requisitions. Click <strong>"+ Initialize Requisition"</strong> above to make a request.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <style>
        .btn-htc { background-color: var(--htc-green); color: white; border: none; }
        .btn-htc:hover { background-color: #0d2e16; color: white; }
        .row-near-deadline { background-color: rgba(253, 126, 20, 0.05) !important; border-left: 4px solid #fd7e14 !important; }
        .row-overdue { background-color: rgba(220, 53, 69, 0.06) !important; border-left: 4px solid #dc3545 !important; }
        .pulse-card { animation: pulseWarningCard 2.5s infinite; }
        @keyframes pulseWarningCard {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .mini-alert-feed { max-height: 480px; overflow-y: auto; }
    </style>
</x-app-layout>
