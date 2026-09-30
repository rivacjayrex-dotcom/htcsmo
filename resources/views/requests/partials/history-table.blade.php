<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 workflow-responsive-table" style="font-size: 0.85rem;">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3" style="width: 80px;">Req #</th>

                    <!-- Only SMO sees who made the request -->
                    @if(Auth::user()->role == 'smo')
                        <th class="py-3">Requestor / Dept</th>
                    @endif

                    <th class="py-3">Primary Item</th>
                    <th class="py-3 text-center">Tier</th>
                    <th class="py-3 text-end">Grand Total</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end" style="min-width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisitions as $req)
                <tr>
                    <td class="ps-4 text-muted fw-mono small fw-bold" data-label="Request">{{ $req->tracking_code }}</td>

                    @if(Auth::user()->role == 'smo')
                    <td data-label="Requestor">
                        <div class="fw-bold text-dark">{{ $req->user->name }}</div>
                        <div class="text-muted" style="font-size: 10px;">{{ $req->user->department ?? 'General' }}</div>
                    </td>
                    @endif

                    <td data-label="Primary item">
                        <div class="fw-bold text-dark">{{ $req->items->first()->item_name ?? 'N/A' }}</div>
                        @if($req->items->count() > 1)
                            <small class="text-primary fw-bold" style="font-size: 10px;">+ {{ $req->items->count() - 1 }} other items</small>
                        @endif
                    </td>

                    <!-- 1. TIER COLUMN (Minor vs Major) -->
                    <td class="text-center" data-label="Tier">
                        <span class="badge {{ $req->request_type == 'major' ? 'bg-primary' : 'bg-secondary' }} text-uppercase px-2 py-1 rounded-pill" style="font-size: 9px;">
                            {{ $req->request_type }}
                        </span>
                    </td>

                    <!-- 2. GRAND TOTAL COLUMN -->
                    <td class="text-end fw-bold text-dark" data-label="Total">₱{{ number_format($req->grand_total, 2) }}</td>

                    <!-- 3. STATUS COLUMN (With 3-day SLA Warning) -->
                    <td class="text-center" data-label="Status">
                        @php
                            $statusColor = match($req->status) {
                                'released'           => 'bg-success text-white',
                                'rejected'           => 'bg-danger text-white',
                                'pending'            => 'bg-secondary text-white',
                                'approved_president' => 'bg-success text-white',
                                default              => 'bg-warning-subtle text-dark border border-warning'
                            };

                            // Overdue or nearing deadline indicator for unreleased items
                            $isOverdue = method_exists($req, 'isOverdue') && $req->isOverdue();
                            $isNear = method_exists($req, 'isNearingDeadline') && $req->isNearingDeadline();
                        @endphp

                        <span class="badge {{ $statusColor }} text-uppercase px-2 py-1 rounded-pill" style="font-size: 9px;">
                            {{ str_replace('_', ' ', $req->status) }}
                        </span>

                        @if($isOverdue && !in_array($req->status, ['released', 'rejected']))
                            <div class="text-danger fw-bold mt-1" style="font-size: 9px;">⚠️ Overdue (>3d)</div>
                        @elseif($isNear && !in_array($req->status, ['released', 'rejected']))
                            <div class="text-warning fw-bold mt-1" style="font-size: 9px;">⏳ Day 2 Alert</div>
                        @endif
                    </td>

                    <!-- 4. ACTIONS COLUMN (Details, Edit, Delete) -->
                    <td class="pe-4 text-end" data-label="Action">
                        <div class="workflow-actions d-inline-flex gap-1 align-items-center">
                            <a href="{{ route('requests.show', $req->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none fw-bold" style="font-size: 10px;">
                                Details →
                            </a>

                            <!-- If user owns it and it's still pending, allow Edit & Delete -->
                            @if(Auth::id() == $req->user_id && $req->status == 'pending')
                                <a href="{{ route('requisitions.edit', $req->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="Edit Request" style="font-size: 10px;">
                                    Edit
                                </a>

                                <form action="{{ route('requisitions.destroy', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel and delete this requisition?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Cancel Request" style="font-size: 10px;">
                                        ✕
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ Auth::user()->role == 'smo' ? '7' : '6' }}" class="text-center py-5 text-muted">
                        No requisition records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
