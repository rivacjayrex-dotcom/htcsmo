<x-app-layout>
    <x-slot name="header">{{ __('Edit Requisition') }}</x-slot>

    <div class="container-fluid py-2 request-edit-page">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2">{{ strtoupper($requisition->request_type) }} tier</span>
                    <span class="text-muted small">{{ $requisition->tracking_code }}</span>
                </div>
                <h4 class="fw-black text-dark mb-1">Adjust item quantities</h4>
                <p class="text-muted small mb-0">Catalog details and prices are controlled by the SMO in-charge.</p>
            </div>
            <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-pill px-3">Cancel</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-3 shadow-sm">
                <div class="fw-bold mb-1">Please correct the following:</div>
                <ul class="mb-0 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('requisitions.update', $requisition) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h6 class="fw-bold mb-1">Approved catalog items</h6>
                    <small class="text-muted">To choose different items or options, delete this pending request and start a new requisition.</small>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label for="purpose" class="form-label fw-bold">Reason for request <span class="text-danger">*</span></label>
                        <textarea id="purpose" name="purpose" class="form-control border bg-white shadow-sm" rows="3" minlength="10" maxlength="500" required placeholder="Explain where and why these supplies will be used.">{{ old('purpose', $requisition->purpose) }}</textarea>
                        <div class="form-text">A clear reason helps approvers review your request faster.</div>
                    </div>
                    @foreach($requisition->items as $index => $item)
                        <div class="border rounded-4 p-3 mb-3 bg-light-subtle">
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-7">
                                    <span class="badge bg-success-subtle text-success mb-2">SMO CATALOG</span>
                                    <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                                    <div class="small text-muted">{{ $item->specifications ?: 'Standard specification' }}</div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label mini-label">Unit price</label>
                                    <div class="fw-bold">₱{{ number_format($item->unit_price, 2) }}</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label mini-label" for="qty-{{ $item->id }}">Quantity ({{ $item->unit }})</label>
                                    <input id="qty-{{ $item->id }}" type="number" name="items[{{ $index }}][qty]" value="{{ old("items.$index.qty", $item->quantity) }}" min="1" max="{{ $item->supply?->quantity ?? $item->quantity }}" class="form-control border-0 bg-white shadow-sm" required>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="request-edit-actions d-flex justify-content-end gap-2">
                <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-3 px-4">Cancel</a>
                <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold">Save quantities</button>
            </div>
        </form>
    </div>

    <style>
        @media (max-width: 575.98px) {
            .request-edit-page .card-header,
            .request-edit-page .card-body { padding: 1rem !important; }
            .request-edit-page textarea { min-height: 96px; font-size: 16px; }
            .request-edit-page input[type="number"] { min-height: 44px; font-size: 16px; }
            .request-edit-actions { flex-direction: column-reverse; }
            .request-edit-actions .btn { width: 100%; min-height: 44px; }
        }
    </style>
</x-app-layout>
