<x-app-layout>
    <x-slot name="header">
        {{ __('Edit Requisition') }}
    </x-slot>

    @php
        $editableItems = $requisition->items->map(fn ($item) => [
            'name' => $item->item_name,
            'specs' => $item->specifications ?? '',
            'qty' => $item->quantity,
            'unit' => $item->unit ?? 'pc',
            'price' => (float) $item->unit_price,
        ])->values();
    @endphp

    <div class="container-fluid py-2" x-data="requisitionEditor({{ Js::from($editableItems) }})">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 class="fw-black text-dark mb-1">Edit {{ $requisition->tracking_code }}</h4>
                <p class="text-muted small mb-0">Changes are allowed only while Department Head review is pending.</p>
            </div>
            <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-pill px-3">Cancel</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-3 shadow-sm">
                <div class="fw-bold mb-1">Please correct the following:</div>
                <ul class="mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('requisitions.update', $requisition) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <label for="request_type" class="form-label small fw-bold text-uppercase text-muted">Request Type</label>
                    <select id="request_type" name="request_type" class="form-select" required>
                        <option value="minor" @selected(old('request_type', $requisition->request_type) === 'minor')>Minor</option>
                        <option value="major" @selected(old('request_type', $requisition->request_type) === 'major')>Major</option>
                    </select>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0">Requested Items</h6>
                        <small class="text-muted">Update quantities, specifications, units, or estimated prices.</small>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3" @click="addItem()">
                        Add Item
                    </button>
                </div>

                <div class="card-body p-4">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Item Name</label>
                                    <input type="text" class="form-control" :name="`items[${index}][name]`" x-model="item.name" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Specifications</label>
                                    <input type="text" class="form-control" :name="`items[${index}][specs]`" x-model="item.specs">
                                </div>
                                <div class="col-6 col-md-1">
                                    <label class="form-label small fw-bold">Qty</label>
                                    <input type="number" class="form-control" :name="`items[${index}][qty]`" x-model.number="item.qty" min="1" required>
                                </div>
                                <div class="col-6 col-md-1">
                                    <label class="form-label small fw-bold">Unit</label>
                                    <input type="text" class="form-control" :name="`items[${index}][unit]`" x-model="item.unit" maxlength="50">
                                </div>
                                <div class="col-8 col-md-2">
                                    <label class="form-label small fw-bold">Unit Price</label>
                                    <input type="number" class="form-control" :name="`items[${index}][price]`" x-model.number="item.price" min="0" step="0.01" required>
                                </div>
                                <div class="col-4 col-md-1 d-grid">
                                    <button type="button" class="btn btn-outline-danger" @click="removeItem(index)" :disabled="items.length === 1" title="Remove item">
                                        ×
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="d-flex justify-content-end align-items-center gap-3 pt-2">
                        <span class="small text-muted text-uppercase fw-bold">Estimated Total</span>
                        <span class="fs-5 fw-black text-success" x-text="formatCurrency(total)"></span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-3 px-4">Cancel</a>
                <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        function requisitionEditor(initialItems) {
            return {
                nextKey: initialItems.length,
                items: initialItems.map((item, index) => ({ ...item, key: index })),

                addItem() {
                    this.items.push({
                        key: this.nextKey++,
                        name: '',
                        specs: '',
                        qty: 1,
                        unit: 'pc',
                        price: 0,
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                get total() {
                    return this.items.reduce((sum, item) => {
                        return sum + (Number(item.qty) || 0) * (Number(item.price) || 0);
                    }, 0);
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-PH', {
                        style: 'currency',
                        currency: 'PHP',
                    }).format(value);
                },
            };
        }
    </script>
</x-app-layout>
