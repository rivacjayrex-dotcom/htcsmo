@php
    $reportOptions = [
        'requisitions' => ['Requisition Transactions', 'Every request, item, status, and amount', 'file-text'],
        'inventory' => ['Inventory Valuation', 'Stock levels, pricing, value, and health', 'boxes'],
        'dept_spending' => ['Department Spending', 'Cost and request totals by department', 'building-2'],
        'users' => ['Users & Personnel', 'Accounts by department, role, and status', 'users'],
        'item_consumption' => ['Item Consumption', 'Distributed quantities and item costs', 'package-check'],
        'approval_audit' => ['Approval Audit', 'Signatory actions and approval history', 'stamp'],
        'inventory_movements' => ['Inventory Movements', 'Restocks, releases, and adjustments', 'arrow-left-right'],
        'user_activity' => ['User Activity', 'Authentication and security activity log', 'shield-check'],
    ];

    $departments = ['CETE', 'CTE', 'CBMA', 'CCJE', 'CAS'];
    $roles = [
        'employee' => 'Employee',
        'dept_head' => 'Department Head',
        'vp_finance' => 'VP for Finance',
        'vp_admin' => 'VP for Administration',
        'provost' => 'Provost',
        'president' => 'President',
        'smo' => 'SMO In-Charge',
    ];
@endphp

<section class="report-builder mt-5 pt-4 border-top" id="report-builder">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h4 class="fw-black text-dark mb-1">Comprehensive Report Center</h4>
            <p class="text-muted small mb-0">Filter institutional records, preview the results, and export them for Excel or printing.</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 no-print">
            Reset Report
        </a>
    </div>

    <form action="{{ route('admin.reports.index') }}" method="GET" id="reportForm" class="no-print">
        <div class="report-type-grid row g-3 mb-4">
            @foreach($reportOptions as $value => [$label, $description, $icon])
                <div class="report-type-option col-xl-3 col-md-6">
                    <label class="card report-type-card shadow-sm rounded-4 p-3 w-100 h-100 cursor-pointer {{ $reportType === $value ? 'border-success border-2 bg-success-subtle' : 'border-0 bg-white' }}">
                        <div class="d-flex align-items-start gap-3">
                            <input type="radio" name="report_type" value="{{ $value }}" class="form-check-input mt-1" @checked($reportType === $value) onchange="this.form.submit()">
                            <i data-lucide="{{ $icon }}" class="text-success flex-shrink-0" style="width: 20px;"></i>
                            <div>
                                <div class="fw-bold text-dark small">{{ $label }}</div>
                                <small class="text-muted d-block mt-1" style="line-height: 1.25;">{{ $description }}</small>
                            </div>
                        </div>
                    </label>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-header bg-white border-bottom p-4">
                <h6 class="fw-bold mb-0 d-flex align-items-center">
                    <i data-lucide="sliders-horizontal" class="me-2 text-success" style="width: 18px;"></i>
                    Report Filters
                </h6>
            </div>

            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <label for="period" class="form-label mini-label">Reporting Period</label>
                        <select id="period" name="period" class="form-select border-0 bg-light rounded-3 shadow-none">
                            <option value="current_semester" @selected(request('period', 'current_semester') === 'current_semester')>Current Semester</option>
                            <option value="this_month" @selected(request('period') === 'this_month')>This Month</option>
                            <option value="this_year" @selected(request('period') === 'this_year')>This Calendar Year</option>
                            <option value="all" @selected(request('period') === 'all')>All Available Records</option>
                            <option value="custom" @selected(request('period') === 'custom')>Custom Date Range</option>
                        </select>
                    </div>

                    <div class="col-lg-4 custom-date-field">
                        <label for="date_from" class="form-label mini-label">From</label>
                        <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control border-0 bg-light rounded-3 shadow-none">
                    </div>

                    <div class="col-lg-4 custom-date-field">
                        <label for="date_to" class="form-label mini-label">To</label>
                        <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control border-0 bg-light rounded-3 shadow-none">
                    </div>
                </div>

                @if(in_array($reportType, ['requisitions', 'dept_spending', 'users', 'item_consumption', 'approval_audit', 'user_activity']))
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="dept" class="form-label mini-label">Department</label>
                            <select id="dept" name="dept" class="form-select border-0 bg-light rounded-3 shadow-none">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}" @selected(request('dept') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if(in_array($reportType, ['users', 'approval_audit', 'user_activity']))
                            <div class="col-md-4">
                                <label for="role" class="form-label mini-label">Role</label>
                                <select id="role" name="role" class="form-select border-0 bg-light rounded-3 shadow-none">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $value => $label)
                                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($reportType === 'users')
                            <div class="col-md-4">
                                <label for="account_status" class="form-label mini-label">Account Status</label>
                                <select id="account_status" name="account_status" class="form-select border-0 bg-light rounded-3 shadow-none">
                                    <option value="">All Accounts</option>
                                    <option value="approved" @selected(request('account_status') === 'approved')>Approved</option>
                                    <option value="pending" @selected(request('account_status') === 'pending')>Pending Approval</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @endif

                @if($reportType === 'requisitions')
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label for="tier" class="form-label mini-label">Requisition Tier</label>
                            <select id="tier" name="tier" class="form-select border-0 bg-light rounded-3 shadow-none">
                                <option value="">All Tiers</option>
                                <option value="minor" @selected(request('tier') === 'minor')>Minor</option>
                                <option value="major" @selected(request('tier') === 'major')>Major</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label mini-label">Request Status</label>
                            <select id="status" name="status" class="form-select border-0 bg-light rounded-3 shadow-none">
                                <option value="">All Statuses</option>
                                @foreach(['pending', 'pending_dept', 'approved_dept', 'approved_vp', 'approved_provost', 'approved_president', 'for_clarification', 'released', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                @if(in_array($reportType, ['inventory', 'inventory_movements']))
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label mini-label">Supply Category</label>
                            <select id="category" name="category" class="form-select border-0 bg-light rounded-3 shadow-none">
                                <option value="">All Categories</option>
                                @foreach(['Cleaning Supplies', 'Construction Supplies', 'Drugs and Medicines', 'Maintenance Supplies', 'Medical Supplies', 'Non-Medical Supplies', 'Office Supplies'] as $category)
                                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($reportType === 'inventory')
                            <div class="col-md-6">
                                <label for="stock_filter" class="form-label mini-label">Stock Health</label>
                                <select id="stock_filter" name="stock_filter" class="form-select border-0 bg-light rounded-3 shadow-none">
                                    <option value="">All Stock Levels</option>
                                    <option value="low" @selected(request('stock_filter') === 'low')>Low Stock</option>
                                    <option value="out" @selected(request('stock_filter') === 'out')>Out of Stock</option>
                                </select>
                            </div>
                        @else
                            <div class="col-md-6">
                                <label for="transaction_type" class="form-label mini-label">Transaction Type</label>
                                <select id="transaction_type" name="transaction_type" class="form-select border-0 bg-light rounded-3 shadow-none">
                                    <option value="">All Transactions</option>
                                    <option value="release_deduction" @selected(request('transaction_type') === 'release_deduction')>Release Deduction</option>
                                    <option value="restock_delivery" @selected(request('transaction_type') === 'restock_delivery')>Restock Delivery</option>
                                    <option value="manual_adjustment" @selected(request('transaction_type') === 'manual_adjustment')>Manual Adjustment</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="report-actions card-footer bg-light border-0 p-4 d-flex flex-wrap justify-content-between gap-3">
                <button type="submit" class="btn btn-outline-dark px-4 rounded-pill fw-bold">
                    <i data-lucide="filter" class="me-1" style="width: 14px;"></i> Apply Filters
                </button>

                <div class="report-export-actions d-flex flex-wrap gap-2">
                    <button type="submit" name="export" value="csv" class="btn btn-outline-success rounded-pill fw-bold px-4">
                        <i data-lucide="sheet" class="me-1" style="width: 16px;"></i> Export for Excel
                    </button>
                    <button type="submit" name="download_pdf" value="1" class="btn btn-htc rounded-pill fw-bold px-4">
                        <i data-lucide="file-down" class="me-1" style="width: 16px;"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div id="report-preview" class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="report-preview-header card-header bg-white border-bottom p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-black mb-0">{{ $title }}</h5>
                    <span class="badge bg-success-subtle text-success rounded-pill">{{ $periodLabel }}</span>
                </div>
                <small class="text-muted">{{ number_format($rows->count()) }} matching records</small>
            </div>

            <div class="report-preview-summary d-flex align-items-center gap-4">
                <div class="text-end">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 9px;">{{ $summaryLabel }}</small>
                    <h5 class="fw-black text-success mb-0">
                        @if($summaryType === 'money')
                            ₱{{ number_format($summaryValue, 2) }}
                        @else
                            {{ number_format($summaryValue) }}
                        @endif
                    </h5>
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 no-print" onclick="window.print()">
                    <i data-lucide="printer" class="me-1" style="width: 14px;"></i> Print
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table report-responsive-table" style="font-size: 0.82rem;">
                <thead class="bg-light small text-muted text-uppercase">
                    <tr>
                        @foreach($columns as $column)
                            <th class="py-3 {{ $column['type'] === 'money' || $column['type'] === 'number' ? 'text-end' : '' }}">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($columns as $column)
                                <td data-label="{{ $column['label'] }}" class="{{ $column['type'] === 'money' || $column['type'] === 'number' ? 'text-end' : '' }}">
                                    @if($column['type'] === 'money')
                                        ₱{{ number_format((float) ($row[$column['key']] ?? 0), 2) }}
                                    @elseif($column['type'] === 'number')
                                        {{ number_format((float) ($row[$column['key']] ?? 0)) }}
                                    @else
                                        {{ $row[$column['key']] ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center py-5 text-muted">
                                No records match the selected report filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
    @media screen and (max-width: 767.98px) {
        .reports-workspace > .row.g-3 { --bs-gutter-x: .65rem; --bs-gutter-y: .65rem; }
        .reports-workspace > .row.g-3 .card { padding: .85rem !important; }
        .reports-workspace > .row.g-3 h4 { font-size: 1.05rem; overflow-wrap: anywhere; }
        .reports-workspace > .row.g-4 { --bs-gutter-y: 1rem; }
        .reports-workspace > .row.g-4 .card.p-4 { padding: 1rem !important; min-height: 280px; }
        .reports-workspace canvas { max-height: 245px !important; }
        .report-builder { margin-top: 1.5rem !important; padding-top: 1.25rem !important; }
        .report-type-grid {
            display: grid;
            grid-template-columns: none;
            grid-auto-flow: column;
            grid-auto-columns: minmax(255px, 82vw);
            margin-right: -.8rem;
            margin-left: -.8rem;
            padding: 0 .8rem .5rem;
            overflow-x: auto;
            scroll-snap-type: x proximity;
            scrollbar-width: thin;
        }
        .report-type-option { width: auto; padding-right: 0; padding-left: 0; scroll-snap-align: start; }
        .report-type-card { min-height: 105px; }
        #reportForm .card-header,
        #reportForm .card-body,
        #reportForm .card-footer { padding: 1rem !important; }
        #reportForm select,
        #reportForm input { min-height: 44px; font-size: 16px; }
        .report-actions, .report-export-actions { align-items: stretch; flex-direction: column; width: 100%; }
        .report-actions .btn, .report-export-actions .btn { width: 100%; min-height: 44px; }
        .report-preview-header { align-items: flex-start !important; flex-direction: column; padding: 1rem !important; }
        .report-preview-header > div:first-child .d-flex { align-items: flex-start !important; flex-direction: column; }
        .report-preview-summary { width: 100%; justify-content: space-between; }
        .report-preview-summary .text-end { text-align: left !important; }

        .table-responsive:has(> .report-responsive-table) { overflow: visible; padding: .75rem !important; background: #f5f8f6; }
        .report-responsive-table { display: block; min-width: 0 !important; }
        .report-responsive-table thead { display: none; }
        .report-responsive-table tbody { display: grid; gap: .75rem; }
        .report-responsive-table tbody tr { display: block; overflow: hidden; border: 1px solid #e0e8e3; border-radius: 13px; background: #fff; box-shadow: 0 3px 12px rgba(24, 58, 37, .05); }
        .report-responsive-table tbody td {
            display: grid;
            grid-template-columns: minmax(90px, 36%) minmax(0, 1fr);
            align-items: center;
            gap: .7rem;
            width: 100%;
            padding: .7rem .8rem !important;
            text-align: right !important;
            overflow-wrap: anywhere;
        }
        .report-responsive-table tbody td::before { content: attr(data-label); color: #7b8981; font-size: .62rem; font-weight: 800; text-align: left; text-transform: uppercase; }
        .report-responsive-table tbody td > * { justify-self: end; max-width: 100%; }
        .report-responsive-table tbody td[colspan] { display: block; text-align: center !important; }
        .report-responsive-table tbody td[colspan]::before { display: none; }
        .report-responsive-table .progress { width: min(150px, 100%) !important; }
    }

    @media print {
        body * { visibility: hidden !important; }
        #report-preview, #report-preview * { visibility: visible !important; }
        #report-preview {
            position: absolute;
            inset: 0;
            width: 100%;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        #report-preview .table-responsive { overflow: visible !important; }
        #report-preview .report-table { font-size: 9px !important; }
        .no-print { display: none !important; }
    }
</style>

<script>
    (() => {
        const period = document.getElementById('period');
        const customDateFields = document.querySelectorAll('.custom-date-field');

        const toggleCustomDates = () => {
            const isCustom = period?.value === 'custom';
            customDateFields.forEach((field) => {
                field.style.display = isCustom ? '' : 'none';
                field.querySelector('input').disabled = !isCustom;
            });
        };

        period?.addEventListener('change', toggleCustomDates);
        toggleCustomDates();
    })();
</script>
