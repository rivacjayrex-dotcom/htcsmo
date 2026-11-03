<x-app-layout>
    <x-slot name="header">Report Center</x-slot>

    <div class="container-fluid py-2 reports-workspace" data-filter-workspace="reports">
        @php
            $overviewPeriod = request()->only('period', 'date_from', 'date_to');
            if (request('period') === 'semester') {
                $overviewPeriod = ['period' => 'custom', 'date_from' => $reportDateFrom->toDateString(), 'date_to' => $reportDateTo->toDateString()];
            }
            if (request('period') === 'all' || (request('period') === 'custom' && (!request('date_from') || !request('date_to')))) {
                $overviewPeriod = ['period' => 'current_semester'];
            }
        @endphp
        <header class="report-page-heading no-print">
            <div><h1>Report Center</h1><p>Choose a report, set your filters, then print or export.</p></div>
            <a href="{{ route('admin.reports.index', $overviewPeriod) }}" class="report-analytics-link"><i data-lucide="chart-no-axes-combined" aria-hidden="true"></i> Operational Analytics</a>
        </header>
        <div data-filter-region="context">
        @if(in_array($reportType, ['inventory_movements', 'item_usage']) && request('supply_id'))
            <div class="alert alert-light border d-flex flex-wrap justify-content-between gap-2">
                <span>Item filter: <strong>{{ $rows->first()['item'] ?? 'Selected catalog item' }}</strong></span>
                <a href="{{ route('admin.reports.builder', request()->except('supply_id', 'page')) }}#report-preview" data-filter-link>Show all items</a>
            </div>
        @endif
        </div>
        @include('admin.reports.index')
    </div>
</x-app-layout>
