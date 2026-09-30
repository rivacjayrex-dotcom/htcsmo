<x-app-layout>
    <x-slot name="header">
        {{ __('Institutional Archive') }}
    </x-slot>

    <div class="container-fluid py-2 institutional-archive-page">
        <!-- FILTER BAR -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="archive-toolbar card-body p-3 d-flex justify-content-between align-items-center gap-3">
                <div class="archive-periods btn-group p-1 bg-light rounded-pill">
                    <a href="{{ route('admin.archive', ['filter' => 'all']) }}" class="btn btn-sm rounded-pill px-3 {{ $filter == 'all' ? 'btn-htc' : 'btn-light' }}">All Time</a>
                    <a href="{{ route('admin.archive', ['filter' => 'today']) }}" class="btn btn-sm rounded-pill px-3 {{ $filter == 'today' ? 'btn-htc' : 'btn-light' }}">Today</a>
                    <a href="{{ route('admin.archive', ['filter' => 'week']) }}" class="btn btn-sm rounded-pill px-3 {{ $filter == 'week' ? 'btn-htc' : 'btn-light' }}">This Week</a>
                    <a href="{{ route('admin.archive', ['filter' => 'month']) }}" class="btn btn-sm rounded-pill px-3 {{ $filter == 'month' ? 'btn-htc' : 'btn-light' }}">This Month</a>
                    <a href="{{ route('admin.archive', ['filter' => 'year']) }}" class="btn btn-sm rounded-pill px-3 {{ $filter == 'year' ? 'btn-htc' : 'btn-light' }}">This Year</a>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-dark px-3 rounded-pill fw-bold">
                        <i data-lucide="printer" class="me-1" style="width:14px"></i> Print PDF Report
                    </button>
                </div>
            </div>
        </div>

        <!-- ARCHIVE TABLE -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 workflow-responsive-table">
                    <thead class="bg-light text-muted small uppercase">
                        <tr>
                            <th class="ps-4 py-3">Control #</th>
                            <th class="py-3">Released Date</th>
                            <th class="py-3">Requestor</th>
                            <th class="py-3">Total Value</th>
                            <th class="py-3 text-center">Items</th>
                            <th class="pe-4 py-3 text-end">Documents</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.9rem;">
                        @forelse($archives as $req)
                        <tr>
                            <td class="ps-4 fw-mono text-muted" data-label="Control">#{{ str_pad($req->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="fw-bold text-dark" data-label="Released">
                                {{ $req->updated_at->format('M d, Y') }}
                                <div class="small text-muted fw-normal" style="font-size: 10px;">{{ $req->updated_at->format('h:i A') }}</div>
                            </td>
                            <td data-label="Requestor">
                                <div class="fw-bold">{{ $req->user->name }}</div>
                                <div class="small text-success fw-bold" style="font-size: 9px;">{{ strtoupper($req->user->department) }}</div>
                            </td>
                            <td class="fw-bold text-dark" data-label="Total">₱{{ number_format($req->grand_total, 2) }}</td>
                            <td class="text-center" data-label="Items">
                                <span class="badge bg-light text-dark border rounded-pill px-3">{{ $req->items->count() }} items</span>
                            </td>
                            <td class="pe-4 text-end" data-label="Documents">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('requests.show', $req->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size: 10px;">View Info</a>
                                    <a href="{{ route('requests.download', $req->id) }}" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" style="font-size: 10px;">PDF</a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i data-lucide="archive" class="mb-2 opacity-25" style="width: 48px; height: 48px;"></i>
                                <p>No released records found for the selected period.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($archives->hasPages())
                <div class="card-footer bg-white border-0 p-4">
                    {{ $archives->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
        @media (max-width: 767.98px) {
            .archive-toolbar { align-items: stretch !important; flex-direction: column; }
            .archive-periods { width: 100%; overflow-x: auto; scrollbar-width: none; }
            .archive-periods::-webkit-scrollbar { display: none; }
            .archive-periods .btn { flex: 0 0 auto; min-height: 40px; }
            .archive-toolbar > div:last-child .btn { width: 100%; min-height: 42px; }
        }
    </style>
</x-app-layout>
