<x-app-layout>
    <x-slot name="header">
        {{ __('Notification Center') }}
    </x-slot>

    <div class="container-fluid py-2">
        <div class="row g-4">

            <!-- LEFT SIDE: CONTEXTUAL SIDEBAR (Col 4) -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">

                    @if(Auth::user()->role == 'smo')
                        <div class="notification-health-card mb-4 {{ $lowStockSupplies->isNotEmpty() ? 'has-alerts' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div><span class="notification-kicker">Inventory health</span><h5 class="fw-bold mb-0">{{ $lowStockSupplies->count() }} item(s) need attention</h5></div>
                                <span class="health-icon"><i data-lucide="package-search"></i></span>
                            </div>
                            @forelse($lowStockSupplies->take(5) as $supply)
                                <a href="{{ route('inventory.edit', $supply->id) }}" class="stock-alert-row">
                                    <span class="stock-alert-dot {{ $supply->quantity <= 0 ? 'out' : 'low' }}"></span>
                                    <span><strong>{{ $supply->item_name }}</strong><small>{{ $supply->quantity <= 0 ? 'Out of stock' : $supply->quantity.' '.$supply->unit.' remaining' }}</small></span>
                                    <i data-lucide="chevron-right"></i>
                                </a>
                            @empty
                                <div class="text-center text-muted small py-3">All inventory items are above their restock thresholds.</div>
                            @endforelse
                            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-htc w-100 rounded-3 mt-3">Open inventory forecast</a>
                        </div>

                        <!-- SMO VIEW: CRITICAL DEADLINES -->
                        <div class="d-flex align-items-center mb-3">
                            <i data-lucide="alert-triangle" class="text-danger me-2" style="width: 20px;"></i>
                            <h6 class="fw-bold mb-0 uppercase tracking-widest text-danger" style="font-size: 12px;">Critical Deadlines</h6>
                        </div>
                        @forelse($criticalRequests as $req)
                            <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4 border-danger animate-pulse">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-2" style="font-size: 9px;">ACTION REQUIRED</span>
                                        <small class="text-muted fw-bold">#{{ $req->id }}</small>
                                    </div>
                                   <h6 class="fw-bold mb-1 small">
                                        {{ $req->items->first()->item_name ?? 'Multiple Items / Detail' }}...
                                    </h6>
                                    <p class="text-muted mb-3" style="font-size: 11px;">This requisition requires immediate release.</p>
                                    <a href="{{ route('admin.approvals') }}" class="btn btn-danger btn-sm w-100 fw-bold rounded-3">Process Now</a>
                                </div>
                            </div>
                        @empty
                            <div class="card border-0 bg-white shadow-sm rounded-4 p-4 text-center">
                                <p class="text-muted small mb-0">No critical deadlines.</p>
                            </div>
                        @endforelse

                    @else
                        <!-- EMPLOYEE/STAFF VIEW: PERSONAL SUMMARY -->
                        <div class="d-flex align-items-center mb-3">
                            <i data-lucide="bar-chart-2" class="text-success me-2" style="width: 20px;"></i>
                            <h6 class="fw-bold mb-0 uppercase tracking-widest text-dark" style="font-size: 12px;">Requisition Summary</h6>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <div class="fw-bold fs-5 text-dark">{{ $personalStats['total'] }}</div>
                                    <div class="text-muted" style="font-size: 9px; text-transform: uppercase;">Total</div>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="fw-bold fs-5 text-warning">{{ $personalStats['pending'] }}</div>
                                    <div class="text-muted" style="font-size: 9px; text-transform: uppercase;">Pending</div>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold fs-5 text-success">{{ $personalStats['released'] }}</div>
                                    <div class="text-muted" style="font-size: 9px; text-transform: uppercase;">Released</div>
                                </div>
                            </div>
                        </div>

                        <!-- HTC PROCUREMENT GUIDE (Unique for Staff) -->
                        <div class="card border-0 shadow-sm rounded-4 bg-success text-white p-4">
                            <h6 class="fw-bold mb-2"><i data-lucide="help-circle" class="me-1" style="width: 16px;"></i> Quick Guide</h6>
                            <div class="small opacity-75 mb-3">Understanding the HTC Process:</div>

                            <ul class="list-unstyled mb-0" style="font-size: 11px;">
                                <li class="mb-2 d-flex">
                                    <i data-lucide="check-circle" class="me-2" style="width: 12px;"></i>
                                    <span><strong>Minor Tier:</strong> Approved by Dept Head and VP Finance only.</span>
                                </li>
                                <li class="mb-2 d-flex">
                                    <i data-lucide="check-circle" class="me-2" style="width: 12px;"></i>
                                    <span><strong>Major Tier:</strong> Requires School Provost and President signatures.</span>
                                </li>
                                <li class="d-flex">
                                    <i data-lucide="check-circle" class="me-2" style="width: 12px;"></i>
                                    <span><strong>Fulfillment:</strong> Visit the SMO once your status turns "Released".</span>
                                </li>
                            </ul>
                        </div>
                    @endif

                </div>
            </div>

            <!-- RIGHT SIDE: ACTIVITY FEED (Col 8) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 p-4 pb-0">
                        <h5 class="fw-bold mb-3">System Activity</h5>

                        <!-- TAB NAVIGATION -->
                        <ul class="nav nav-tabs border-0 gap-4" id="noteTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active border-0 px-0 fw-bold small text-uppercase tracking-wider" id="all-tab" data-bs-toggle="tab" data-bs-target="#all">All Activity</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link border-0 px-0 fw-bold small text-uppercase tracking-wider" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals">Approvals</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link border-0 px-0 fw-bold small text-uppercase tracking-wider" id="deadlines-tab" data-bs-toggle="tab" data-bs-target="#deadlines">Deadlines</button>
                            </li>
                            @if(Auth::user()->role === 'smo')
                                <li class="nav-item">
                                    <button class="nav-link border-0 px-0 fw-bold small text-uppercase tracking-wider" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-alerts">Inventory <span class="badge bg-warning-subtle text-warning ms-1">{{ $inventoryNotes->count() }}</span></button>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="tab-content" id="noteTabsContent">

                            <!-- TAB 1: ALL ACTIVITY -->
                            <div class="tab-pane fade show active" id="all">
                                @include('notifications.list', ['notes' => $allNotes])
                            </div>

                            <!-- TAB 2: APPROVALS -->
                            <div class="tab-pane fade" id="approvals">
                                @include('notifications.list', ['notes' => $approvalNotes])
                            </div>

                            <!-- TAB 3: DEADLINES -->
                            <div class="tab-pane fade" id="deadlines">
                                @include('notifications.list', ['notes' => $deadlineNotes])
                            </div>

                            @if(Auth::user()->role === 'smo')
                                <div class="tab-pane fade" id="inventory-alerts">
                                    @include('notifications.list', ['notes' => $inventoryNotes])
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .nav-tabs .nav-link { color: #94a3b8; transition: all 0.3s; border-bottom: 2px solid transparent !important; }
        .nav-tabs .nav-link.active { color: var(--htc-green) !important; border-bottom: 2px solid var(--htc-green) !important; background: transparent; }
        .animate-pulse { animation: pulse-red 2s infinite; }
        .notification-health-card { padding: 1.15rem; border: 1px solid #dce8e0; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(24,55,36,.06); }
        .notification-health-card.has-alerts { border-color: #efd7a2; background: linear-gradient(145deg, #fffaf0, #fff); }
        .notification-kicker { color: #8a978f; font-size: .6rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .health-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #9d680d; background: #fff0cf; }.health-icon svg { width: 18px; }
        .stock-alert-row { display: flex; align-items: center; gap: .65rem; padding: .65rem 0; border-bottom: 1px solid rgba(218, 203, 169, .45); color: inherit; text-decoration: none; }.stock-alert-row:hover { color: var(--htc-green); }.stock-alert-row > span:nth-child(2) { display: flex; flex: 1; min-width: 0; flex-direction: column; }.stock-alert-row strong { overflow: hidden; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }.stock-alert-row small { color: #8c9891; font-size: .62rem; }.stock-alert-row > svg { width: 14px; color: #9ba69f; }
        .stock-alert-dot { width: 8px; height: 8px; flex: 0 0 8px; border-radius: 50%; background: #e39a1d; box-shadow: 0 0 0 4px rgba(227,154,29,.12); }.stock-alert-dot.out { background: #d23f48; box-shadow: 0 0 0 4px rgba(210,63,72,.12); }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }
    </style>

    <script>
        // Re-initialize icons when switching tabs
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', () => {
                lucide.createIcons();
            });
        });
    </script>

</x-app-layout>
