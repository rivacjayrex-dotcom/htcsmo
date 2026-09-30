<x-app-layout>
    <x-slot name="header">
        {{ __('Requisition Archives') }}
    </x-slot>

    <div class="container-fluid py-2">
        <div class="workflow-page-heading d-flex justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-black text-dark mb-0">Requisition Archive</h4>
                <p class="text-muted small mb-0">History of released and closed requisitions.</p>
            </div>
            @if(Auth::user()->role == 'smo')
                <button onclick="window.print()" class="workflow-heading-action btn btn-sm btn-htc px-4 rounded-pill shadow-sm">
                    <i data-lucide="printer" class="me-2" style="width:14px"></i> Print Archives
                </button>
            @endif
        </div>

        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3">
                <form action="{{ route('requests.archive') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" onchange="this.form.submit()">
                            <option value="">All Archived Statuses</option>
                            <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released / Completed</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected / Disapproved</option>
                        </select>
                    </div>

                    @if(Auth::user()->role == 'smo')
                    <div class="col-md-3">
                        <select name="dept" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <option value="CETE" {{ request('dept') == 'CETE' ? 'selected' : '' }}>CETE</option>
                            <option value="CTE" {{ request('dept') == 'CTE' ? 'selected' : '' }}>CTE</option>
                            <option value="CBMA" {{ request('dept') == 'CBMA' ? 'selected' : '' }}>CBMA</option>
                            <option value="CCJE" {{ request('dept') == 'CCJE' ? 'selected' : '' }}>CCJE</option>
                            <option value="CAS" {{ request('dept') == 'CAS' ? 'selected' : '' }}>CAS</option>
                        </select>
                    </div>
                    @endif

                    <div class="col-md-4">
                        <div class="input-group input-group-sm archive-date-range">
                            <span class="input-group-text border-0 bg-light text-muted" style="font-size: 10px;">FROM</span>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control border-0 bg-light" onchange="this.form.submit()">
                            <span class="input-group-text border-0 bg-light text-muted" style="font-size: 10px;">TO</span>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control border-0 bg-light" onchange="this.form.submit()">
                        </div>
                    </div>

                    <div class="col-md-2 text-end">
                        <a href="{{ route('requests.archive') }}" class="btn btn-sm btn-link text-danger text-decoration-none fw-bold" style="font-size: 11px;">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- ARCHIVED TABLE -->
        @include('requests.partials.history-table', ['requisitions' => $archivedRequests, 'type' => 'archive'])

        <div class="mt-3">
            {{ $archivedRequests->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>
