<x-app-layout>
    <x-slot name="header">
        {{ __('Inventory Management') }}
    </x-slot>

    <div class="container-fluid py-2">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4">{{ session('success') }}</div>
        @endif

        @php
            $totalItems = $supplies->count();
            $availableItems = $supplies->where('quantity', '>', 0)->count();
            $lowStockItems = $supplies->filter(fn($i) => $i->quantity > 0 && $i->quantity <= $i->min_stock_level)->count();
            $outOfStockItems = $supplies->where('quantity', '<=', 0)->count();
        @endphp

        <!-- 1. STATS METRIC CARDS (Storyboard Figure 7.11) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-primary-subtle text-primary rounded-3 me-3">
                            <i data-lucide="boxes" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Total Catalog Items</div>
                            <h4 class="fw-bold mb-0 text-dark">{{ $totalItems }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-success-subtle text-success rounded-3 me-3">
                            <i data-lucide="check-circle" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Available In-Stock</div>
                            <h4 class="fw-bold mb-0 text-success">{{ $availableItems }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-warning-subtle text-warning rounded-3 me-3">
                            <i data-lucide="alert-circle" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Low Stock Alert</div>
                            <h4 class="fw-bold mb-0 text-warning">{{ $lowStockItems }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-danger-subtle text-danger rounded-3 me-3">
                            <i data-lucide="x-circle" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Out of Stock</div>
                            <h4 class="fw-bold mb-0 text-danger">{{ $outOfStockItems }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN INVENTORY TABLE -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0">Institutional Supply Stock</h5>
                    <p class="text-muted small mb-0">Live catalog and stock level monitoring for HTC SMO.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inventory.create') }}" class="btn btn-htc px-4 rounded-pill shadow-sm d-flex align-items-center">
                        <i data-lucide="plus-circle" class="me-2" style="width:16px;"></i> Add New Supply Item
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Item Details</th>
                            <th class="py-3">Category / Tier</th>
                            <th class="py-3">Specifications</th>
                            <th class="py-3 text-center">Stock Level</th>
                            <th class="py-3 text-end">Unit Cost</th>
                            <th class="pe-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplies as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                                <small class="text-muted">{{ $item->brand }} @if($item->model_number) • {{ $item->model_number }} @endif</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill" style="font-size: 10px;">
                                    {{ $item->category }}
                                </span>
                                <span class="badge {{ ($item->requisition_tier ?? 'minor') === 'major' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} px-2 py-1 rounded-pill ms-1" style="font-size: 9px;">
                                    {{ strtoupper($item->requisition_tier ?? 'minor') }}
                                </span>
                            </td>
                            <td class="small text-muted text-truncate" style="max-width: 250px;">
                                {{ $item->specifications ?? 'No detailed specifications' }}
                            </td>
                            <td class="text-center">
                                @if($item->quantity <= 0)
                                    <span class="badge bg-danger px-3 py-1 rounded-pill text-uppercase" style="font-size: 10px;">Out of Stock</span>
                                @elseif($item->quantity <= $item->min_stock_level)
                                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill text-uppercase" style="font-size: 10px;">
                                        ⚠️ Low: {{ $item->quantity }} {{ $item->unit }}
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill text-uppercase" style="font-size: 10px;">
                                        {{ $item->quantity }} {{ $item->unit }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary rounded-circle p-2" title="Edit Specification">
                                        <i data-lucide="edit-3" style="width:14px; height:14px;"></i>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2"
                                            onclick="confirmDelete({{ $item->id }}, '{{ addslashes($item->item_name) }}')" title="Delete Item">
                                        <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i data-lucide="package-open" style="width: 40px; height: 40px;" class="mb-2 opacity-25"></i>
                                <div>No supplies registered in the database.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-body p-4 text-center">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i data-lucide="alert-triangle" style="width: 30px; height: 30px;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Remove Item?</h6>
                    <p class="text-muted small mb-4">Are you sure you want to delete <strong id="deleteItemName"></strong> from inventory?</p>

                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger fw-bold py-2 rounded-3">Yes, Delete Item</button>
                            <button type="button" class="btn btn-light fw-bold py-2 rounded-3 border" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, name) {
            document.getElementById('deleteItemName').innerText = name;
            document.getElementById('deleteForm').action = '/inventory/' + id;

            const myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            myModal.show();

            setTimeout(() => lucide.createIcons(), 50);
        }
    </script>
</x-app-layout>
