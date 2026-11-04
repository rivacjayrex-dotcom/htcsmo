<x-app-layout>
    <x-slot name="header">Operational Analytics</x-slot>
    @php
        $reportLink = fn (array $filters = []) => route('admin.reports.builder', array_merge($periodQuery, $filters));
        $maxMonth = max(1, $months->max('count'));
        $peakMonth = $months->sortByDesc('count')->first();
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
        <header class="analytics-header">
            <div><span class="analytics-eyebrow">Supply management</span><h1>Operational Analytics</h1><p>A clear view of request fulfillment and supply usage.</p></div>
            <a class="analytics-button is-primary" data-filter-region="report-action" href="{{ $reportLink(['report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}"><i data-lucide="file-chart-column" aria-hidden="true"></i> Open Report Center <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
        </header>

        <section class="analytics-toolbar" aria-label="Reporting period filters">
            <div class="analytics-toolbar-title"><i data-lucide="calendar-days" aria-hidden="true"></i><span>Reporting period</span></div>
            <form id="analyticsFilters" method="GET" action="{{ route('admin.reports.index') }}#analytics-results" class="analytics-period-form" data-dynamic-search>
                <div><label class="visually-hidden" for="analyticsPeriod">Reporting period</label><select id="analyticsPeriod" name="period" class="form-select">
                    @foreach(['current_semester' => 'Current semester', 'this_month' => 'This month', 'this_year' => 'This calendar year', 'custom' => 'Custom date range'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('period', $periodQuery['period']) === $key)>{{ $label }}</option>
                    @endforeach
                </select></div>
                <div data-custom-date-field @if(old('period', $periodQuery['period']) !== 'custom') hidden @endif><label for="analyticsFrom">From</label><input class="form-control" id="analyticsFrom" type="date" name="date_from" value="{{ old('date_from', request('date_from', $dateFrom->toDateString())) }}" @disabled(old('period', $periodQuery['period']) !== 'custom')></div>
                <div data-custom-date-field @if(old('period', $periodQuery['period']) !== 'custom') hidden @endif><label for="analyticsTo">Through</label><input class="form-control" id="analyticsTo" type="date" name="date_to" value="{{ old('date_to', request('date_to', $dateTo->toDateString())) }}" @disabled(old('period', $periodQuery['period']) !== 'custom')></div>
                <x-filter-date-fallback :from="request('date_from', $dateFrom->toDateString())" :to="request('date_to', $dateTo->toDateString())" />
                <x-filter-submit />
                <a href="{{ route('admin.reports.index') }}#analytics-results" data-filter-link class="analytics-button is-reset"><i data-lucide="rotate-ccw" aria-hidden="true"></i> Reset</a>
            </form>
        </section>

        <div class="analytics-period-heading" data-filter-region="period"><div><h2>{{ $periodLabel }}</h2><p>{{ $dateFrom->format('M d, Y') }} – {{ $dateTo->format('M d, Y') }}</p></div><span class="analytics-tag">Completion date</span></div>
        <section id="analytics-results" class="analytics-summary" aria-label="Fulfillment results for the selected period" data-filter-results data-filter-region="totals" data-result-count="{{ $periodTotals->requests }}" data-result-label="fulfilled requests" tabindex="-1">
            <a class="analytics-summary-card is-featured" href="{{ $reportLink(['report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}"><span class="analytics-summary-icon"><i data-lucide="circle-check-big" aria-hidden="true"></i></span><div><span>Fulfilled requests</span><strong>{{ number_format($periodTotals->requests) }}</strong><small>Completed or released in this period</small></div><i data-lucide="arrow-up-right" class="analytics-summary-arrow" aria-hidden="true"></i></a>
            <article class="analytics-summary-card"><span class="analytics-summary-icon"><i data-lucide="building-2" aria-hidden="true"></i></span><div><span>Departments served</span><strong>{{ number_format($departments->count()) }}</strong><small>With fulfilled requests in this period</small></div></article>
            <article class="analytics-summary-card"><span class="analytics-summary-icon"><i data-lucide="chart-no-axes-column" aria-hidden="true"></i></span><div><span>Peak month</span><strong>{{ $periodTotals->requests > 0 ? $peakMonth['label'] : '—' }}</strong><small>{{ $periodTotals->requests > 0 ? number_format($peakMonth['count']).' fulfilled requests' : 'No fulfillment recorded yet' }}</small></div></article>
        </section>

        <div class="analytics-grid" data-filter-region="charts">
            <section class="analytics-panel" aria-labelledby="trendHeading">
                <header class="analytics-panel-heading"><div><span class="analytics-eyebrow">Activity</span><h2 id="trendHeading">Fulfillment over time</h2><p>Requests completed each month</p></div><span class="analytics-tag">Monthly</span></header>
                @if((int) $periodTotals->requests === 0)<p class="analytics-empty">No fulfilled requests in this period.</p>@endif
                <figure class="analytics-figure">
                    <div class="analytics-trend">
                        @foreach($months as $month)
                            <a class="analytics-trend-column" href="{{ route('admin.reports.builder', ['period' => 'custom', 'date_from' => $month['from'], 'date_to' => $month['to'], 'report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}" aria-label="{{ $month['label'] }}: {{ $month['count'] }} fulfilled requests">
                                <strong aria-hidden="true">{{ $month['count'] }}</strong><span class="analytics-bar-track" aria-hidden="true"><span style="height:{{ round($month['count'] / $maxMonth * 100, 2) }}%"></span></span><span class="analytics-month-label" aria-hidden="true">{{ $month['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    <figcaption class="analytics-caption">Select a month to open its requests. Partial months include only the selected dates.</figcaption>
                </figure>
                <details class="analytics-month-data"><summary>Monthly figures</summary><div class="analytics-rows">
                    @foreach($months as $month)<a class="analytics-row" href="{{ route('admin.reports.builder', ['period' => 'custom', 'date_from' => $month['from'], 'date_to' => $month['to'], 'report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}"><span class="analytics-row-copy"><strong>{{ $month['label'] }}</strong></span><span class="analytics-row-value"><strong>{{ number_format($month['count']) }}</strong><small>fulfilled</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></a>@endforeach
                </div></details>
            </section>

            <section class="analytics-panel" id="analytics-departments" aria-labelledby="departmentHeading">
                <header class="analytics-panel-heading"><div><span class="analytics-eyebrow">Departments</span><h2 id="departmentHeading">Department fulfillment</h2><p>Ranked by fulfilled request count</p></div><span class="analytics-panel-symbol"><i data-lucide="building-2" aria-hidden="true"></i></span></header>
                <div class="analytics-rows">
                    @forelse($departments as $department)
                        <a class="analytics-row" href="{{ $reportLink(['report_type' => 'requisitions', 'date_basis' => 'fulfilled', 'dept' => $department->department === 'Unassigned' ? '__unassigned' : $department->department]) }}">
                            <span class="analytics-rank" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="analytics-row-copy"><strong>{{ $department->department }}</strong></span><span class="analytics-row-value"><strong>{{ number_format($department->requests) }}</strong><small>{{ $department->requests == 1 ? 'request' : 'requests' }}</small></span><i data-lucide="chevron-right" aria-hidden="true"></i>
                        </a>
                    @empty<p class="analytics-empty">No department fulfillment in this period.</p>@endforelse
                </div>
                <a class="analytics-panel-footer" href="{{ $reportLink(['report_type' => 'requisitions', 'date_basis' => 'fulfilled']) }}"><span>View all fulfilled requests</span><i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>

            <section class="analytics-panel" aria-labelledby="itemsHeading">
                <header class="analytics-panel-heading"><div><span class="analytics-eyebrow">Supply usage</span><h2 id="itemsHeading">Top stock items released</h2><p>Actual stock releases during this period</p></div><span class="analytics-tag">Top 5</span></header>
                <div class="analytics-rows">
                    @forelse($topItems as $item)
                        <a class="analytics-row" href="{{ $reportLink(['report_type' => 'inventory_movements', 'transaction_type' => 'release_deduction', 'supply_id' => $item->supply_id]) }}"><span class="analytics-rank" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="analytics-row-copy"><strong>{{ $item->item_name ?? 'Unavailable catalog item #'.$item->supply_id }}</strong><small>View release history</small></span><span class="analytics-row-value"><strong>{{ number_format($item->quantity) }}</strong><small>{{ $item->unit ?? 'Unavailable' }}</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></a>
                    @empty<p class="analytics-empty">No stock release deductions recorded in this period.</p>@endforelse
                </div>
                <p class="analytics-caption">Net of reversals, by release date. Each item keeps its own unit; quantities can include supplies prepared for pickup.</p>
                <a class="analytics-panel-footer" href="{{ $reportLink(['report_type' => 'inventory_movements', 'transaction_type' => 'release_deduction']) }}"><span>View all stock releases</span><i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>

            <section class="analytics-panel" aria-labelledby="workflowHeading">
                <header class="analytics-panel-heading"><div><span class="analytics-eyebrow">Live workflow</span><h2 id="workflowHeading">Where active requests stand</h2><p>Current stages across all active requests</p></div><span class="analytics-tag">Current snapshot</span></header>
                <div class="analytics-rows analytics-workflow">
                    @foreach($workflow as $stage => $count)<a class="analytics-row" href="{{ $workflowLink($stage) }}"><span class="analytics-stage-dot {{ $count > 0 ? 'is-active' : '' }}" aria-hidden="true"></span><span class="analytics-row-copy"><strong>{{ $stage }}</strong></span><span class="analytics-row-value"><strong>{{ number_format($count) }}</strong></span><i data-lucide="chevron-right" aria-hidden="true"></i></a>@endforeach
                </div>
                <p class="analytics-caption">The reporting period does not change this live snapshot.</p>
                <a class="analytics-panel-footer" href="{{ route('admin.approvals') }}"><span>Open fulfillment queue</span><i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>
        </div>

        <details class="analytics-definitions"><summary>About these figures</summary><div><p><strong>Fulfillment</strong> counts completed and legacy released requests by completion date. Requests without a completion date cannot be assigned to a period.</p><p><strong>Stock releases</strong> use inventory ledger transaction dates, net of reversals. Released quantities do not establish when supplies were physically consumed.</p><p><strong>Departments and item names</strong> reflect current records. Custom date ranges include both dates and support up to ten years. Times use {{ config('app.timezone') }}.</p></div></details>
    </div>
    @include('admin.reports.partials.analytics-styles')
</x-app-layout>
