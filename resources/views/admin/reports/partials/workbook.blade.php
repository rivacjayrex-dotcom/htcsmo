@php
    $reportOptions = [
        'requisitions' => ['Requisitions', 'file-text'],
        'dept_spending' => ['Dept. spending', 'building-2'],
        'item_usage' => ['Item usage', 'package-check'],
        'inventory' => ['Inventory', 'boxes'],
        'item_consumption' => ['Fulfilled items', 'clipboard-list'],
        'inventory_movements' => ['Stock ledger', 'arrow-left-right'],
        'users' => ['Personnel', 'users'],
        'approval_audit' => ['Approvals', 'stamp'],
        'user_activity' => ['Activity log', 'shield-check'],
    ];
    $roles = ['employee' => 'Employee', 'dept_head' => 'Department Head', 'vp_finance' => 'VP Finance', 'vp_admin' => 'VP Administration', 'provost' => 'Provost', 'president' => 'President', 'smo' => 'SMO In-Charge'];
    $departmentReports = ['requisitions', 'dept_spending', 'item_usage', 'item_consumption', 'users', 'approval_audit', 'user_activity'];
    $currentAcademicYear = now()->month >= 8 ? now()->year : now()->year - 1;
    $academicYears = collect(range($currentAcademicYear + 1, $currentAcademicYear - 5))->push((int) request('academic_year', $currentAcademicYear))->unique()->sortDesc();
    $currentTerm = now()->month >= 8 ? 'first' : (now()->month <= 5 ? 'second' : 'summer');
    $scopeCopy = match ($reportType) {
        'dept_spending' => 'Supply costs from completed and released requests, grouped by department.',
        'item_usage' => 'Quantities issued from stock, grouped by item and unit.',
        'item_consumption' => 'Requested quantities on fulfilled requests. Choose Item usage for actual stock releases.',
        'inventory' => 'Current stock and estimated value. Date filters do not apply to this snapshot.',
        'inventory_movements' => 'Stock releases, deliveries, and adjustments during the selected period.',
        default => 'Only records matching the selected filters appear in this report.',
    };
@endphp

<section class="report-workbook" aria-label="Report workbook">
    <form action="{{ route('admin.reports.builder') }}#report-preview" method="GET" id="reportForm" class="report-control-panel no-print" data-dynamic-search>
        @if(request('supply_id') && in_array($reportType, ['inventory_movements', 'item_usage']))<input type="hidden" name="supply_id" value="{{ request('supply_id') }}">@endif
        <div id="reportTabs" class="report-tab-strip" role="radiogroup" aria-label="Report sheets">
            @foreach($reportOptions as $value => [$label, $icon])
                <label @if($reportType === $value) id="selectedReportTab" @endif class="report-tab {{ $reportType === $value ? 'is-selected' : '' }}">
                    <input type="radio" name="report_type" value="{{ $value }}" class="visually-hidden" @checked($reportType === $value) data-filter-navigate>
                    <i data-lucide="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <div class="report-sheet-toolbar">
            <div class="report-sheet-title"><i data-lucide="sheet" aria-hidden="true"></i><div><strong>{{ $reportOptions[$reportType][0] }}</strong><small>{{ $scopeCopy }}</small></div></div>
            <div class="report-export-actions">
                <button type="submit" name="print" value="1" formtarget="_blank" class="report-tool-button" data-download><i data-lucide="printer" aria-hidden="true"></i> Print</button>
                <button type="button" id="downloadPdfButton" class="report-tool-button"><i data-lucide="file-down" aria-hidden="true"></i> PDF</button>
                <button type="submit" name="export" value="csv" class="report-tool-button is-primary" data-download><i data-lucide="sheet" aria-hidden="true"></i> Excel CSV</button>
                <noscript><style>#downloadPdfButton{display:none}</style><button type="submit" name="download_pdf" value="1" class="report-tool-button">PDF</button></noscript>
            </div>
        </div>
        <div class="report-filter-bar" aria-label="Report filters">
            @if(in_array($reportType, $departmentReports))
                <div class="report-filter"><label for="dept">Department</label><select id="dept" name="dept" class="form-select"><option value="">All departments</option>
                    @if(in_array($reportType, ['requisitions', 'dept_spending', 'item_usage', 'item_consumption']))<option value="__unassigned" @selected(request('dept') === '__unassigned')>Unassigned</option>@endif
                    @foreach($departmentOptions as $department)<option value="{{ $department }}" @selected(request('dept') === $department)>{{ $department }}</option>@endforeach
                </select></div>
            @endif
            @if($reportType !== 'inventory')
                <div class="report-filter"><label for="period">Period</label><select id="period" name="period" class="form-select">
                    <option value="current_semester" @selected(request('period', 'current_semester') === 'current_semester')>Current semester</option>
                    <option value="semester" @selected(request('period') === 'semester')>Choose a semester</option>
                    <option value="this_month" @selected(request('period') === 'this_month')>This month</option>
                    <option value="this_year" @selected(request('period') === 'this_year')>This year</option>
                    <option value="custom" @selected(request('period') === 'custom')>Custom dates</option>
                    <option value="all" @selected(request('period') === 'all')>All time</option>
                </select></div>
                <div class="report-filter" data-semester-field @if(request('period') !== 'semester') hidden @endif><label for="academic_year">Academic year</label><select id="academic_year" name="academic_year" class="form-select" @disabled(request('period') !== 'semester')>
                    @foreach($academicYears as $year)<option value="{{ $year }}" @selected((int) request('academic_year', $currentAcademicYear) === $year)>{{ $year }}–{{ $year + 1 }}</option>@endforeach
                </select></div>
                <div class="report-filter" data-semester-field @if(request('period') !== 'semester') hidden @endif><label for="term">Semester</label><select id="term" name="term" class="form-select" @disabled(request('period') !== 'semester')>
                    <option value="first" @selected(request('term', $currentTerm) === 'first')>First semester</option><option value="second" @selected(request('term', $currentTerm) === 'second')>Second semester</option><option value="summer" @selected(request('term', $currentTerm) === 'summer')>Summer term</option>
                </select></div>
                <div class="report-filter" data-custom-date-field @if(request('period') !== 'custom') hidden @endif><label for="date_from">From</label><input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" @disabled(request('period') !== 'custom')></div>
                <div class="report-filter" data-custom-date-field @if(request('period') !== 'custom') hidden @endif><label for="date_to">To</label><input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" @disabled(request('period') !== 'custom')></div>
                <x-filter-date-fallback :from="request('date_from')" :to="request('date_to')" />
            @else
                <input type="hidden" name="period" value="all">
            @endif
            @if(in_array($reportType, ['users', 'approval_audit', 'user_activity']))
                <div class="report-filter"><label for="role">Role</label><select id="role" name="role" class="form-select"><option value="">All roles</option>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select></div>
            @endif
            @if($reportType === 'users')
                <div class="report-filter"><label for="account_status">Account status</label><select id="account_status" name="account_status" class="form-select"><option value="">All accounts</option>@foreach(['active', 'pending', 'suspended'] as $status)<option value="{{ $status }}" @selected(request('account_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            @endif
            @if($reportType === 'requisitions')
                <div class="report-filter"><label for="date_basis">Date basis</label><select id="date_basis" name="date_basis" class="form-select"><option value="submitted" @selected(request('date_basis', 'submitted') === 'submitted')>Submitted</option><option value="fulfilled" @selected(request('date_basis') === 'fulfilled')>Completed / released</option></select></div>
                <div class="report-filter is-short"><label for="tier">Tier</label><select id="tier" name="tier" class="form-select"><option value="">All tiers</option><option value="minor" @selected(request('tier') === 'minor')>Minor</option><option value="major" @selected(request('tier') === 'major')>Major</option></select></div>
                <div class="report-filter"><label for="status">Status</label><select id="status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending', 'pending_dept', 'approved_dept', 'approved_vp', 'approved_provost', 'approved_president', 'for_clarification', 'processing', 'ready_for_pickup', 'completed', 'released', 'rejected', 'withdrawn'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            @endif
            @if(in_array($reportType, ['inventory', 'inventory_movements', 'item_usage']))
                <div class="report-filter"><label for="category">Item category</label><select id="category" name="category" class="form-select"><option value="">All categories</option>@foreach(['Cleaning Supplies', 'Construction Supplies', 'Drugs and Medicines', 'Maintenance Supplies', 'Medical Supplies', 'Non-Medical Supplies', 'Office Supplies'] as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></div>
                @if($reportType === 'inventory')<div class="report-filter"><label for="stock_filter">Stock level</label><select id="stock_filter" name="stock_filter" class="form-select"><option value="">All stock levels</option><option value="low" @selected(request('stock_filter') === 'low')>Low stock</option><option value="out" @selected(request('stock_filter') === 'out')>Out of stock</option></select></div>@endif
                @if($reportType === 'inventory_movements')<div class="report-filter"><label for="transaction_type">Transaction</label><select id="transaction_type" name="transaction_type" class="form-select"><option value="">All transactions</option><option value="release_deduction" @selected(request('transaction_type') === 'release_deduction')>Stock release</option><option value="restock_delivery" @selected(request('transaction_type') === 'restock_delivery')>Delivery</option><option value="manual_adjustment" @selected(request('transaction_type') === 'manual_adjustment')>Adjustment</option></select></div>@endif
            @endif
            <a href="{{ route('admin.reports.builder', ['report_type' => $reportType]) }}#report-preview" data-filter-link class="report-reset" title="Reset filters"><i data-lucide="rotate-ccw" aria-hidden="true"></i><span>Reset</span></a>
            <x-filter-submit />
        </div>
        @if(request('period') !== 'semester' && $reportType !== 'inventory')<noscript><div class="report-fallback"><label>Academic year <input type="number" name="academic_year" value="{{ request('academic_year', $currentAcademicYear) }}" min="2000" max="2100"></label><label>Semester <select name="term"><option value="first">First semester</option><option value="second">Second semester</option><option value="summer">Summer</option></select></label></div></noscript>@endif
    </form>

    <div id="report-preview" class="report-preview-card" data-filter-results data-filter-region="preview" data-result-count="{{ $pagination?->total() ?? $rows->count() }}" tabindex="-1" aria-label="Matching report records">
        <div class="report-preview-header">
            <div><h2>{{ $title }}</h2><p>{{ request('dept') === '__unassigned' ? 'Unassigned' : (request('dept') ?: 'All departments') }} <span aria-hidden="true">/</span> {{ $periodLabel }}</p></div>
            <div class="report-summary"><span>{{ $summaryLabel }}</span><strong>{{ $summaryType === 'money' ? '₱'.number_format($summaryValue, 2) : number_format($summaryValue) }}</strong></div>
        </div>
        <div class="report-sheet-grid" tabindex="0" aria-label="Report spreadsheet; scroll to see all columns">
            <table class="report-table">
                <thead>
                    <tr><th scope="col" class="report-row-number">#</th>@foreach($columns as $column)<th scope="col" data-column="{{ $column['key'] }}" class="{{ in_array($column['type'], ['money', 'number']) ? 'is-numeric' : '' }}">{{ $column['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr><th scope="row" class="report-row-number">{{ ($pagination?->firstItem() ?? 1) + $loop->index }}</th>
                            @foreach($columns as $column)<td data-column="{{ $column['key'] }}" class="{{ in_array($column['type'], ['money', 'number']) ? 'is-numeric' : '' }}">@if($column['type'] === 'money')₱{{ number_format((float) ($row[$column['key']] ?? 0), 2) }}@elseif($column['type'] === 'number'){{ number_format((float) ($row[$column['key']] ?? 0)) }}@else{{ $row[$column['key']] ?? '—' }}@endif</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + 1 }}" class="report-empty"><i data-lucide="sheet" aria-hidden="true"></i><strong>No records for these filters</strong><span>Choose a different department or reporting period.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="report-sheet-footer"><span>{{ $pagination ? number_format($pagination->firstItem() ?? 0).'–'.number_format($pagination->lastItem() ?? 0).' of '.number_format($pagination->total()) : number_format($rows->count()) }} rows <span aria-hidden="true">·</span> {{ count($columns) }} columns</span><span class="report-page-summary">Summary: {{ $pagination ? 'current page' : 'all rows' }}. Print, PDF, and CSV use all filtered records.</span>@if($pagination)<div data-filter-pagination>{{ $pagination->fragment('report-preview')->links() }}</div>@endif</footer>
        @if($basisLabel)<details class="report-data-notes"><summary>About this data</summary><p>{{ $basisLabel }}</p><p>Print and PDF support up to {{ number_format($pdfRowLimit) }} rows. CSV includes all filtered records.</p></details>@endif
    </div>
</section>
