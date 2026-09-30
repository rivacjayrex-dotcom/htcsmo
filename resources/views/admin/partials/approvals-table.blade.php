<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 workflow-responsive-table">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th class="ps-4 py-3">Req #</th>
                <th class="py-3">Requestor</th> <!-- This column -->
                <th class="py-3">Items</th>
                <th class="py-3">Total Amount</th>
                <th class="pe-4 py-3 text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td class="ps-4 fw-mono text-muted" data-label="Request">{{ $req->tracking_code ?? '#'.$req->id }}</td>

                    <!-- UPDATE THIS TD BLOCK -->
                    <td data-label="Requestor">
                        <div class="fw-bold text-dark">{{ $req->user->name }}</div>

                        @if(in_array($req->user->role, ['president', 'provost']))
                            <span class="badge bg-danger text-white px-2 mt-1" style="font-size: 9px; letter-spacing: 0.5px;">
                                <i data-lucide="zap" style="width: 10px; height: 10px; vertical-align: middle;"></i> HIGH PRIORITY
                            </span>
                        @else
                            <div class="small text-muted" style="font-size: 10px;">{{ $req->user->department ?? 'General' }}</div>
                        @endif
                    </td>
                    <!-- END OF UPDATE -->

                    <td data-label="Items">
                        <div class="fw-semibold text-dark">{{ $req->items->first()->item_name ?? 'Supplies' }}</div>
                        @if($req->items->count() > 1)
                            <span class="text-muted small"> (+{{ $req->items->count() - 1 }} others)</span>
                        @endif
                    </td>
                    <td class="fw-bold text-success" data-label="Total">₱{{ number_format($req->grand_total, 2) }}</td>
                    <td class="pe-4 text-end" data-label="Action">
                        <button class="btn btn-sm {{ Auth::user()->role == 'smo' ? 'btn-primary' : 'btn-htc' }} px-3 rounded-pill shadow-sm"
                                @click="openDetails({{ $req }}, {{ $req->items }}, '{{ addslashes($req->user->name) }}', '{{ $req->user->department }}')">
                            {{ Auth::user()->role == 'smo' ? 'Review & Release' : 'See More & Review' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted italic">No requisitions currently in this queue.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
