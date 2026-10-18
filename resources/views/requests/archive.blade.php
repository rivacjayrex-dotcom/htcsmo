<x-app-layout>
    <x-slot name="header">
        {{ __('Requisition Archives') }}
    </x-slot>

    <div class="container-fluid py-2">
        <div class="workflow-page-heading d-flex justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-black text-dark mb-0">Requisition Archive</h4>
                <p class="text-muted small mb-0">Released/completed and rejected/disapproved requests, ordered by their final lifecycle date.</p>
            </div>
            @if(Auth::user()->role == 'smo')
                <a href="{{ route('requests.archive', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" class="workflow-heading-action btn btn-sm btn-htc px-4 rounded-pill shadow-sm">
                    <i data-lucide="printer" class="me-2" style="width:14px"></i> Print Archives
                </a>
            @endif
        </div>

        @if($errors->any())
            <div class="alert alert-warning rounded-3 mb-3" role="alert">
                <strong>Archive filters were not applied.</strong> {{ $errors->first() }}
            </div>
        @endif

        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="card border-0 shadow-sm rounded-4 mb-3 archive-filter-card">
            <div class="card-body p-3">
                <form action="{{ route('requests.archive') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <input type="search" name="search" value="{{ old('search', request('search')) }}" maxlength="100" class="form-control form-control-sm border-0 bg-light rounded-3 shadow-none" placeholder="Search code, item, or requestor" aria-label="Search archived requisitions">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" onchange="this.form.submit()">
                            <option value="">All Archived Statuses</option>
                            <option value="completed" @selected(old('status', request('status')) === 'completed')>Completed</option>
                            <option value="released" @selected(old('status', request('status')) === 'released')>Released (Legacy)</option>
                            <option value="rejected" @selected(old('status', request('status')) === 'rejected')>Rejected / Disapproved</option>
                        </select>
                    </div>

                    @if(Auth::user()->role == 'smo')
                    <div class="col-md-3">
                        <select name="dept" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}" @selected(old('dept', request('dept')) === $department)>{{ $department }}</option>
                                @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-4">
                        <div class="input-group input-group-sm archive-date-range">
                            <span class="input-group-text border-0 bg-light text-muted" style="font-size: 10px;">FROM</span>
                            <input type="date" name="date_from" value="{{ old('date_from', request('date_from')) }}" class="form-control border-0 bg-light" aria-label="Closure date from">
                            <span class="input-group-text border-0 bg-light text-muted" style="font-size: 10px;">TO</span>
                            <input type="date" name="date_to" value="{{ old('date_to', request('date_to')) }}" class="form-control border-0 bg-light" aria-label="Closure date to">
                        </div>
                    </div>

                    <div class="col-md-2 d-flex justify-content-end gap-2">
                        <select name="order" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none" aria-label="Sort archives">
                            <option value="desc" @selected(old('order', request('order', 'desc')) === 'desc')>Newest closed</option>
                            <option value="asc" @selected(old('order', request('order')) === 'asc')>Oldest closed</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-htc">Apply</button>
                    </div>
                    @if(request()->hasAny(['search', 'status', 'dept', 'date_from', 'date_to', 'order']))
                        <div class="col-12 text-end"><a href="{{ route('requests.archive') }}" class="btn btn-sm btn-link text-danger text-decoration-none fw-bold">Reset all filters</a></div>
                    @endif
                </form>
            </div>
        </div>

        <!-- ARCHIVED TABLE -->
        @include('requests.partials.history-table', ['requisitions' => $archivedRequests, 'type' => 'archive'])

        @if(method_exists($archivedRequests, 'hasPages') && $archivedRequests->hasPages())
            <div class="mt-3 archive-pagination">{{ $archivedRequests->links() }}</div>
        @endif
    </div>

    @if(request()->boolean('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif

    <style>
        @media print {
            .sidebar, .top-bar, .archive-filter-card, .archive-pagination, .workflow-heading-action { display: none !important; }
            .main-content { width: 100% !important; margin-left: 0 !important; }
            .main-content-inner { padding: 0 !important; border: 0 !important; box-shadow: none !important; }
        }
    </style>
</x-app-layout>
