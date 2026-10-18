<x-app-layout>
    <x-slot name="header">{{ __('Institutional Archive') }}</x-slot>

    <div class="container-fluid py-2 institutional-archive-page">
        <div class="workflow-page-heading d-flex justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-black text-dark mb-0">Institutional Archive</h4>
                <p class="text-muted small mb-0">Audit records for released/completed and rejected/disapproved requisitions.</p>
            </div>
            <a href="{{ route('admin.archive', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" class="workflow-heading-action btn btn-sm btn-htc px-4 rounded-pill shadow-sm">
                <i data-lucide="printer" class="me-2" style="width:14px"></i> Print filtered archive
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-warning rounded-3 mb-3" role="alert">
                <strong>Archive filters were not applied.</strong> {{ $errors->first() }}
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 mb-3 archive-filter-card">
            <div class="card-body p-3">
                <form action="{{ route('admin.archive') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <input type="search" name="search" value="{{ old('search', request('search')) }}" maxlength="100" class="form-control form-control-sm border-0 bg-light rounded-3 shadow-none" placeholder="Search code, item, or requestor" aria-label="Search institutional archive">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select name="status" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none">
                            <option value="">All archived statuses</option>
                            <option value="completed" @selected(old('status', request('status')) === 'completed')>Completed</option>
                            <option value="released" @selected(old('status', request('status')) === 'released')>Released (Legacy)</option>
                            <option value="rejected" @selected(old('status', request('status')) === 'rejected')>Rejected / Disapproved</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select name="dept" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none">
                            <option value="">All departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" @selected(old('dept', request('dept')) === $department)>{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-8">
                        <div class="input-group input-group-sm archive-date-range">
                            <span class="input-group-text border-0 bg-light text-muted">FROM</span>
                            <input type="date" name="date_from" value="{{ old('date_from', request('date_from')) }}" class="form-control border-0 bg-light" aria-label="Closure date from">
                            <span class="input-group-text border-0 bg-light text-muted">TO</span>
                            <input type="date" name="date_to" value="{{ old('date_to', request('date_to')) }}" class="form-control border-0 bg-light" aria-label="Closure date to">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select name="order" class="form-select form-select-sm border-0 bg-light rounded-3 shadow-none">
                            <option value="desc" @selected(old('order', request('order', 'desc')) === 'desc')>Newest closed</option>
                            <option value="asc" @selected(old('order', request('order')) === 'asc')>Oldest closed</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-htc flex-grow-1">Apply filters</button>
                        @if(request()->hasAny(['status', 'dept', 'date_from', 'date_to', 'order']))
                            <a href="{{ route('admin.archive') }}" class="btn btn-sm btn-outline-danger">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @include('requests.partials.history-table', ['requisitions' => $archives, 'type' => 'archive'])

        @if(method_exists($archives, 'hasPages') && $archives->hasPages())
            <div class="mt-3 archive-pagination">{{ $archives->links() }}</div>
        @endif
    </div>

    <style>
        @media print {
            .sidebar, .top-bar, .archive-filter-card, .archive-pagination, .workflow-heading-action { display: none !important; }
            .main-content { width: 100% !important; margin-left: 0 !important; }
            .main-content-inner { padding: 0 !important; border: 0 !important; box-shadow: none !important; }
        }
        @media (max-width: 767.98px) {
            .workflow-page-heading { align-items: stretch !important; flex-direction: column; }
            .workflow-heading-action { min-height: 42px; }
        }
    </style>

    @if(request()->boolean('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</x-app-layout>
