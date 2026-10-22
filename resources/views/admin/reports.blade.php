<x-app-layout>
    <x-slot name="header">Operational Analytics</x-slot>
    @php
        $reportLink = fn (array $filters = []) => route('admin.reports.builder', array_merge($periodQuery, $filters));
        $maxMonth = max(1, $months->max('count'));
        $maxDepartment = max(1, $departments->max('value'));
        $maxWorkflow = max(1, $workflow->max());
        $workflowLink = fn (string $stage) => match ($stage) {
            'Department review' => route('requests.index', ['status' => 'pending']),
            'VP review' => route('requests.index', ['status' => 'approved_dept']),
            'Provost review' => route('admin.reports.builder', ['report_type' => 'requisitions', 'period' => 'all', 'status' => 'approved_vp', 'tier' => 'major']),
            'President review' => route('requests.index', ['status' => 'approved_provost']),
            'For clarification' => route('requests.index', ['status' => 'for_clarification']),
            'SMO processing' => route('requests.index', ['status' => 'processing']),
            'Ready for pickup' => route('requests.index', ['status' => 'ready_for_pickup']),
            'Awaiting SMO processing' => route('admin.approvals'),
            default => route('requests.index'),
        };
    @endphp

    <div class="analytics-page" data-filter-workspace="analytics">
        <header class="analytics-header" data-filter-region="heading">
            <div><span class="analytics-eyebrow">SMO / Insights</span><h2>Operational Analytics</h2><p>See what needs attention and where supplies are going.</p></div>
            <a class="btn btn-htc rounded-pill" href="{{ $reportLink() }}"><i data-lucide="file-chart-column" aria-hidden="true"></i> Open Report Center</a>
        </header>

        <section aria-labelledby="operationsHeading">
            <div class="analytics-section-title"><div><h3 id="operationsHeading">Today's priorities</h3><p>Current snapshot · All active requests, regardless of the reporting period</p></div><span class="analytics-asof">As of {{ now()->format('M d, Y · g:i A') }}</span></div>
            <div class="analytics-priorities">
                <a class="analytics-priority" href="{{ route('requests.index') }}"><span class="analytics-icon"><i data-lucide="workflow" aria-hidden="true"></i></span><span><small>Active pipeline</small><strong>{{ number_format($pipeline->active) }}</strong><span>View active requests <i data-lucide="arrow-up-right" aria-hidden="true"></i></span></span></a>
                <a class="analytics-priority {{ $pipeline->overdue > 0 ? 'is-danger' : '' }}" href="{{ route('requests.index', ['status' => 'deadline']) }}"><span class="analytics-icon"><i data-lucide="alarm-clock" aria-hidden="true"></i></span><span><small>At / past deadline</small><strong>{{ number_format($pipeline->overdue) }}</strong><span>Review deadline queue <i data-lucide="arrow-up-right" aria-hidden="true"></i></span></span></a>
                <a class="analytics-priority {{ $pipeline->nearing > 0 ? 'is-warning' : '' }}" href="{{ route('requests.index', ['status' => 'deadline']) }}"><span class="analytics-icon"><i data-lucide="clock-3" aria-hidden="true"></i></span><span><small>Nearing deadline</small><strong>{{ number_format($pipeline->nearing) }}</strong><span>Review deadline queue <i data-lucide="arrow-up-right" aria-hidden="true"></i></span></span></a>
                <div class="analytics-priority {{ $inventory->out_of_stock + $inventory->low > 0 ? 'is-warning' : '' }}"><span class="analytics-icon"><i data-lucide="package-minus" aria-hidden="true"></i></span><span><small>Stock warnings</small><strong>{{ number_format($inventory->out_of_stock + $inventory->low) }}</strong><span><a href="{{ $reportLink(['report_type' => 'inventory', 'stock_filter' => 'out']) }}">{{ $inventory->out_of_stock }} out</a> · <a href="{{ $reportLink(['report_type' => 'inventory', 'stock_filter' => 'low']) }}">{{ $inventory->low }} low</a></span></span></div>
            </div>
        </section>

        <section class="analytics-period-panel" aria-labelledby="periodHeading">
            <div><h3 id="periodHeading">Explore a reporting period</h3><p>Applies to fulfilled requests, department value, and stock release history below.</p></div>
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form id="analyticsFilters" method="GET" action="{{ route('admin.reports.index') }}#analytics-results" class="analytics-period-form" data-dynamic-search>
                <div><label for="analyticsPeriod">Reporting period</label><select id="analyticsPeriod" name="period" class="form-select">
                    @foreach(['current_semester' => 'Current semester', 'this_month' => 'This month', 'this_year' => 'This calendar year', 'custom' => 'Custom date range'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('period', $periodQuery['period']) === $key)>{{ $label }}</option>
                    @endforeach
                </select></div>
                <div data-custom-date-field @if(old('period', $periodQuery['period']) !== 'custom') hidden @endif><label for="analyticsFrom">From</label><input class="form-control" id="analyticsFrom" type="date" name="date_from" value="{{ old('date_from', request('date_from', $dateFrom->toDateString())) }}" @disabled(old('period', $periodQuery['period']) !== 'custom')></div>
                <div data-custom-date-field @if(old('period', $periodQuery['period']) !== 'custom') hidden @endif><label for="analyticsTo">Through</label><input class="form-control" id="analyticsTo" type="date" name="date_to" value="{{ old('date_to', request('date_to', $dateTo->toDateString())) }}" @disabled(old('period', $periodQuery['period']) !== 'custom')></div>
                <x-filter-date-fallback :from="request('date_from', $dateFrom->toDateString())" :to="request('date_to', $dateTo->toDateString())" />
                <x-filter-submit />
                <a href="{{ route('admin.reports.index') }}#analytics-results" data-filter-link class="btn btn-light border">Reset period</a>
            </form>
            <p class="analytics-period-help">Custom ranges include both dates and can span up to ten years. Times use {{ config('app.timezone') }}.</p>
        </section>

        <div class="analytics-section-title" data-filter-region="period"><div><span class="analytics-eyebrow">Selected period</span><h3>{{ $periodLabel }}</h3><p>{{ $dateFrom->format('M d, Y') }} – {{ $dateTo->format('M d, Y') }}</p></div><a href="{{ $reportLink(['report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}">View fulfilled requests <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></div>
        <section id="analytics-results" class="analytics-metrics" aria-label="Fulfillment results for the selected period" data-filter-results data-filter-region="totals" data-result-count="{{ $periodTotals->requests }}" data-result-label="fulfilled requests" tabindex="-1">
            <article><span class="analytics-eyebrow">Fulfilled requests</span><strong>{{ number_format($periodTotals->requests) }}</strong><p>Completed or legacy released, by completion date.</p></article>
            <article class="analytics-metric-featured"><span class="analytics-eyebrow">Recorded released value</span><strong>₱{{ number_format($periodTotals->value, 2) }}</strong><p>Grand totals of fulfilled requests in this period.</p></article>
            <article><span class="analytics-eyebrow">Average fulfilled value</span><strong>₱{{ number_format($periodTotals->average, 2) }}</strong><p>Recorded released value ÷ fulfilled requests.</p></article>
        </section>

        <div class="analytics-grid" data-filter-region="charts">
            <section class="analytics-panel" aria-labelledby="trendHeading">
                <div class="analytics-panel-heading"><div><h3 id="trendHeading">Fulfillment over time</h3><p>{{ $periodLabel }} · Completion date</p></div><span class="analytics-tag">Monthly</span></div>
                @if((int) $periodTotals->requests === 0)<p class="analytics-empty">No fulfilled requests in this period. Every month is shown below.</p>@endif
                <figure class="mb-0">
                    <div class="analytics-trend" aria-hidden="true">
                        @foreach($months as $month)<div class="analytics-trend-column"><strong>{{ $month['count'] }}</strong><div class="analytics-bar-track"><span style="height: {{ round($month['count'] / $maxMonth * 100, 2) }}%"></span></div><small>{{ $month['label'] }}</small></div>@endforeach
                    </div>
                    <figcaption class="analytics-caption">Fulfilled requests per month. Partial months include only the selected dates.</figcaption>
                </figure>
                <details class="analytics-data"><summary>Monthly figures and request links</summary><div class="table-responsive"><table class="table analytics-table"><caption class="visually-hidden">Monthly fulfillment counts and recorded value for {{ $periodLabel }}</caption><thead><tr><th scope="col">Month</th><th scope="col" class="text-end">Fulfilled</th><th scope="col" class="text-end">Recorded value</th></tr></thead><tbody>
                    @foreach($months as $month)<tr><th scope="row"><a href="{{ route('admin.reports.builder', ['period' => 'custom', 'date_from' => $month['from'], 'date_to' => $month['to'], 'report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}">{{ $month['label'] }}</a></th><td class="text-end">{{ $month['count'] }}</td><td class="text-end">₱{{ number_format($month['value'], 2) }}</td></tr>@endforeach
                </tbody></table></div></details>
            </section>

            <section class="analytics-panel" aria-labelledby="departmentHeading">
                <div class="analytics-panel-heading"><div><h3 id="departmentHeading">Department fulfillment</h3><p>{{ $periodLabel }} · Current requestor department</p></div></div>
                <div class="table-responsive"><table class="table analytics-table"><caption class="visually-hidden">Department fulfilled request counts and recorded value, filtered by completion date</caption><thead><tr><th scope="col">Department</th><th scope="col" class="text-end">Requests</th><th scope="col" class="text-end">Recorded value</th></tr></thead><tbody>
                    @forelse($departments as $department)<tr><th scope="row"><a href="{{ $reportLink(['report_type' => 'dept_spending', 'dept' => $department->department === 'Unassigned' ? '__unassigned' : $department->department]) }}">{{ $department->department }}</a><span class="analytics-mini-track" aria-hidden="true"><span style="width: {{ round($department->value / $maxDepartment * 100, 2) }}%"></span></span></th><td class="text-end">{{ number_format($department->requests) }}</td><td class="text-end">₱{{ number_format($department->value, 2) }}</td></tr>@empty<tr><td colspan="3" class="analytics-empty">No department fulfillment in this period.</td></tr>@endforelse
                </tbody></table></div><a class="analytics-panel-footer" href="{{ $reportLink(['report_type' => 'dept_spending']) }}">Open department report <i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>

            <section class="analytics-panel" aria-labelledby="itemsHeading">
                <div class="analytics-panel-heading"><div><h3 id="itemsHeading">Top stock items released</h3><p>{{ $periodLabel }} · Ledger transaction date</p></div><span class="analytics-tag">Top 5</span></div>
                <div class="table-responsive"><table class="table analytics-table"><caption class="visually-hidden">Net stock quantities deducted by release transactions during {{ $periodLabel }}</caption><thead><tr><th scope="col">Catalog item</th><th scope="col">Unit</th><th scope="col" class="text-end">Net deducted</th></tr></thead><tbody>
                    @forelse($topItems as $item)<tr><th scope="row"><a href="{{ $reportLink(['report_type' => 'inventory_movements', 'transaction_type' => 'release_deduction', 'supply_id' => $item->supply_id]) }}">{{ $item->item_name ?? 'Unavailable catalog item #'.$item->supply_id }}</a></th><td>{{ $item->unit ?? 'Unavailable' }}</td><td class="text-end">{{ number_format($item->quantity) }}</td></tr>@empty<tr><td colspan="3" class="analytics-empty">No stock release deductions recorded in this period.</td></tr>@endforelse
                </tbody></table></div><p class="analytics-caption">Recorded release deductions, net of reversals. Includes stock prepared for pickup; excludes restocks and adjustments. Each item retains its own unit.</p>
            </section>

            <section class="analytics-panel" aria-labelledby="workflowHeading">
                <div class="analytics-panel-heading"><div><h3 id="workflowHeading">Where active requests stand</h3><p>Current snapshot · Not affected by the period</p></div></div>
                <div class="analytics-workflow"><dl class="mb-0">@foreach($workflow as $stage => $count)<div><dt><a href="{{ $workflowLink($stage) }}">{{ $stage }}</a><span class="analytics-mini-track" aria-hidden="true"><span style="width: {{ round($count / $maxWorkflow * 100, 2) }}%"></span></span></dt><dd>{{ number_format($count) }}</dd></div>@endforeach</dl></div>
                <a class="analytics-panel-footer" href="{{ route('admin.approvals') }}">Open fulfillment queue <i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>
        </div>

        <section data-filter-region="inventory" class="analytics-inventory" aria-labelledby="inventoryValueHeading"><div class="analytics-icon"><i data-lucide="boxes" aria-hidden="true"></i></div><div><h3 id="inventoryValueHeading">Estimated on-hand inventory value</h3><p>Current snapshot · {{ number_format($inventory->items) }} catalog items · Current quantity × current catalog price</p></div><strong>₱{{ number_format($inventory->estimated_value, 2) }}</strong><a href="{{ $reportLink(['report_type' => 'inventory']) }}">Review inventory <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></section>
        <details class="analytics-definitions"><summary>How to read these figures</summary><ul><li><b>Fulfilled:</b> completed and legacy released requests with a completion date (closed_at). Ready-for-pickup requests remain active.</li><li><b>Recorded value:</b> the fulfilled requests' saved grand totals. These operational totals are not audited accounting expenditure.</li><li><b>Stock releases:</b> ledger deductions on the transaction date, which may occur before pickup and completion. The ledger does not store historical item prices.</li><li><b>Deadline risk:</b> active requests reach their warning after two working days and deadline after three, measured from submission, excluding weekends and configured holidays.</li><li><b>Stock health:</b> out means quantity ≤ 0; low means positive quantity at or below the minimum. Deleted catalog entries do not count toward current stock.</li><li><b>History limits:</b> requests missing a completion date cannot be assigned to a period. Department and catalog labels use their current records; historical names and costs are not reconstructed.</li></ul></details>
    </div>

    @pushOnce('styles', 'page-styles:admin/reports.blade.php')
    <style>
        .analytics-page { max-width: 1600px; margin: 0 auto; color: #263e31; }
        .analytics-page h2,.analytics-page h3,.analytics-page p { margin: 0; }
        .analytics-page a { color: #19613c; text-underline-offset: 3px; }
        .analytics-page .btn-htc { color: #fff; }
        .analytics-page svg { width: 16px; height: 16px; flex-shrink: 0; vertical-align: middle; }
        .analytics-page :is(a,button,select,input,summary):focus-visible { outline: 3px solid #b98b2c; outline-offset: 3px; }
        .analytics-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.6rem; }
        .analytics-eyebrow { display: block; color: #527260; font-size: .72rem; text-transform: uppercase; letter-spacing: .07em; font-weight: 800; }
        .analytics-header h2 { margin: .35rem 0; font-weight: 800; font-size: clamp(1.5rem,2.5vw,2rem); letter-spacing: -.035em; }
        .analytics-header p,.analytics-section-title p { color: #62766a; font-size: .85rem; }
        .analytics-header .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1rem; font-weight: 700; }
        .analytics-section-title { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; margin: 1.35rem 0 .8rem; }
        .analytics-section-title h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: .3rem; }
        .analytics-section-title > a,.analytics-asof { font-size: .8rem; }
        .analytics-asof { color: #697b70; }
        .analytics-priorities { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: .85rem; }
        .analytics-priority { --accent: #22653f; --soft: #eaf3ed; display: flex; align-items: flex-start; gap: .85rem; padding: 1.15rem; border: 1px solid #dce7e0; background: #fff; border-radius: 15px; text-decoration: none; }
        a.analytics-priority:hover { border-color: #719e82; box-shadow: 0 5px 18px #214c3010; }
        .analytics-priority.is-danger { --accent: #ab3540; --soft: #fff0f0; border-color: #eeced2; }
        .analytics-priority.is-warning { --accent: #8c6011; --soft: #fff6e2; border-color: #e9dcbb; }
        .analytics-icon { display: grid; place-items: center; background: var(--soft,#eaf3ed); color: var(--accent,#22653f); border-radius: 11px; width: 39px; height: 39px; flex: 0 0 39px; }
        .analytics-icon svg { width: 20px; height: 20px; }
        .analytics-priority > span:last-child { min-width: 0; }
        .analytics-priority small { display: block; font-size: .78rem; color: #586e60; font-weight: 700; }
        .analytics-priority strong { display: block; color: var(--accent); font-size: 1.8rem; line-height: 1.5; font-weight: 800; }
        .analytics-priority > span > span { font-size: .74rem; color: #64776b; }
        .analytics-priority > span > span svg { width: 12px; }
        .analytics-period-panel { margin: 1.6rem 0; padding: 1.3rem; border-radius: 17px; background: #edf5ef; border: 1px solid #d9e8dd; }
        .analytics-period-panel h3 { font-size: 1rem; font-weight: 800; margin-bottom: .3rem; }
        .analytics-period-panel p { font-size: .82rem; color: #597060; }
        .analytics-period-form { display: flex; align-items: flex-end; flex-wrap: wrap; gap: .8rem; margin-top: 1rem; }
        .analytics-period-form > div { flex: 1 1 160px; max-width: 280px; }
        .analytics-period-form label { display: block; margin-bottom: .35rem; color: #344f3e; font-size: .78rem; font-weight: 750; }
        .analytics-period-form .form-control,.analytics-period-form .form-select { min-height: 42px; font-size: .86rem; border-color: #cbdcd0; border-radius: 9px; }
        .analytics-period-form .btn { min-height: 42px; border-radius: 9px; }
        .analytics-period-panel .analytics-period-help { margin-top: .75rem; font-size: .74rem; }
        .analytics-metrics { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: .85rem; margin-bottom: 1.15rem; }
        .analytics-metrics article { padding: 1.3rem; background: #fff; border: 1px solid #dce7e0; border-radius: 15px; }
        .analytics-metrics strong { display: block; margin: .5rem 0; font-size: clamp(1.4rem,2.3vw,2rem); line-height: 1.2; letter-spacing: -.03em; font-weight: 800; overflow-wrap: anywhere; }
        .analytics-metrics p { font-size: .78rem; color: #62766a; }
        .analytics-metrics .analytics-metric-featured { background: #185b3b; color: #fff; border-color: #185b3b; }
        .analytics-metric-featured .analytics-eyebrow,.analytics-metric-featured p { color: #d3e8db; }
        .analytics-grid { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(0,1fr); gap: 1.1rem; align-items: start; }
        .analytics-panel { min-width: 0; overflow: hidden; background: #fff; border: 1px solid #dce7e0; border-radius: 17px; }
        .analytics-panel-heading { display: flex; align-items: start; justify-content: space-between; gap: .75rem; padding: 1.2rem; border-bottom: 1px solid #edf1ee; }
        .analytics-panel h3 { font-size: 1rem; font-weight: 800; }
        .analytics-panel-heading p { margin-top: .4rem; color: #62766a; font-size: .76rem; }
        .analytics-tag { white-space: nowrap; font-size: .68rem; font-weight: 750; color: #42654e; padding: .3rem .6rem; background: #edf5ef; border-radius: 20px; }
        .analytics-trend { display: flex; gap: 1rem; align-items: flex-end; overflow-x: auto; padding: 1.4rem 1.2rem .6rem; }
        .analytics-trend-column { min-width: 60px; flex: 1; text-align: center; }
        .analytics-trend-column strong { font-size: .85rem; }
        .analytics-bar-track { height: 145px; display: flex; align-items: flex-end; justify-content: center; border-bottom: 1px solid #d9e7de; margin-top: .4rem; }
        .analytics-bar-track > span { display: block; width: 65%; max-width: 48px; background: linear-gradient(#4c9970,#185b3b); border-radius: 6px 6px 0 0; }
        .analytics-trend-column small { display: block; margin-top: .5rem; color: #5a7162; font-size: .72rem; white-space: nowrap; }
        .analytics-caption { padding: .7rem 1.2rem 1rem; font-size: .76rem; color: #617567; line-height: 1.6; }
        .analytics-data { padding: .8rem 1.2rem; border-top: 1px solid #edf1ee; font-size: .8rem; }
        .analytics-data summary,.analytics-definitions summary { color: #246841; cursor: pointer; font-weight: 750; }
        .analytics-page .analytics-table { margin-bottom: 0; font-size: .82rem; min-width: 0; }
        .analytics-page .analytics-panel .table-responsive { border: 0; border-radius: 0; }
        .analytics-table :is(td,th) { padding: .8rem 1.2rem !important; vertical-align: middle; border-color: #edf1ee; }
        .analytics-table thead th { font-size: .68rem; letter-spacing: .025em; color: #5b7263; background: #f6f9f7; white-space: nowrap; }
        .analytics-table tbody th { font-weight: 650; min-width: 100px; overflow-wrap: anywhere; }
        .analytics-table td.text-end { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .analytics-mini-track { display: block; height: 4px; max-width: 160px; background: #edf3ef; border-radius: 4px; margin-top: .45rem; overflow: hidden; }
        .analytics-mini-track > span { display: block; height: 100%; background: #599775; }
        .analytics-panel-footer { display: flex; align-items: center; justify-content: space-between; padding: .85rem 1.2rem; border-top: 1px solid #edf1ee; font-size: .8rem; background: #fafcfb; }
        .analytics-empty { padding: 1.3rem !important; color: #687d6f; text-align: center; font-size: .8rem; }
        .analytics-workflow { padding: .6rem 1.2rem; }
        .analytics-workflow dl > div { display: grid; grid-template-columns: 1fr auto; gap: 1rem; padding: .6rem 0; }
        .analytics-workflow dt { font-weight: 600; font-size: .8rem; }
        .analytics-workflow dd { margin: 0; font-weight: 800; font-size: .9rem; }
        .analytics-inventory { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin: 1.1rem 0; padding: 1.3rem; border-radius: 15px; border: 1px solid #dce7e0; background: #fff; }
        .analytics-inventory h3 { font-size: 1rem; font-weight: 750; }
        .analytics-inventory p { margin-top: .4rem; font-size: .78rem; color: #62766a; }
        .analytics-inventory > strong { margin-left: auto; font-size: 1.5rem; overflow-wrap: anywhere; }
        .analytics-inventory > a { font-size: .8rem; }
        .analytics-definitions { padding: 1.1rem 1.3rem; border-radius: 15px; background: #f1f5f2; font-size: .8rem; }
        .analytics-definitions ul { margin: .9rem 0 0; padding-left: 1.2rem; color: #536b5b; line-height: 1.8; }
        @media(max-width:1199px) { .analytics-priorities { grid-template-columns: repeat(2,minmax(0,1fr)); } }
        @media(max-width:767px) { .analytics-grid { grid-template-columns: 1fr; }.analytics-metrics { grid-template-columns: 1fr; }.analytics-metrics strong { font-size: 1.7rem; }.analytics-section-title { align-items: flex-start; }.analytics-inventory > strong { margin-left: 0; }.analytics-table :is(td,th) { padding: .7rem .65rem !important; }.analytics-table thead th { white-space: normal; }body:has(.analytics-page) .top-bar .dropdown-menu { right: 0; left: auto; } }
        @media(max-width:479px) { .analytics-priorities { grid-template-columns: 1fr; }.analytics-period-form > div { max-width: none; }.analytics-period-form .btn { width: 100%; }.analytics-header .btn { width: 100%; justify-content: center; } }
        @media print { .analytics-period-form,.analytics-header > a { display: none; }.analytics-grid { display: block; }.analytics-panel { break-inside: avoid; margin-bottom: 1rem; } }
    </style>
    @endPushOnce
</x-app-layout>
