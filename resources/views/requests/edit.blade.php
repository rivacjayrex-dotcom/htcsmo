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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2">{{ strtoupper($requisition->request_type) }} tier</span>
                    <span class="text-muted small">{{ $requisition->tracking_code }}</span>
                </div>
                <h4 class="fw-black text-dark mb-1">Update your requisition</h4>
                <p class="text-muted small mb-0">Adjust the items and quantities while your request is still pending review.</p>
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

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0">Requested items</h6>
                            <span class="badge rounded-pill bg-success-subtle text-success" x-text="items.length + (items.length === 1 ? ' item' : ' items')"></span>
                        </div>
                        <small class="text-muted">Keep each item specific so purchasing can prepare it correctly.</small>
                    </div>
                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3" @click="addItem()">
                        <span class="me-1" aria-hidden="true">+</span> Add item
                    </button>
                </div>

                <div class="card-body p-4">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="border rounded-3 p-3 p-md-4 mb-3 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="small fw-bold text-uppercase text-muted">Item <span x-text="index + 1"></span></span>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none px-0" @click="removeItem(index)" :disabled="items.length === 1">
                                    Remove
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold" :for="`item-name-${item.key}`">What do you need?</label>
                                    <input :id="`item-name-${item.key}`" type="text" class="form-control" :name="`items[${index}][name]`" x-model="item.name" placeholder="e.g. A4 bond paper" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold" :for="`item-specs-${item.key}`">Details <span class="text-muted fw-normal">(optional)</span></label>
                                    <input :id="`item-specs-${item.key}`" type="text" class="form-control" :name="`items[${index}][specs]`" x-model="item.specs" placeholder="Size, color, brand, or model">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-bold" :for="`item-qty-${item.key}`">Quantity</label>
                                    <input :id="`item-qty-${item.key}`" type="number" class="form-control" :name="`items[${index}][qty]`" x-model.number="item.qty" min="1" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-bold" :for="`item-unit-${item.key}`">Unit</label>
                                    <input :id="`item-unit-${item.key}`" type="text" class="form-control" :name="`items[${index}][unit]`" x-model="item.unit" maxlength="50" placeholder="pc, box, set">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold" :for="`item-price-${item.key}`">Estimated unit price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input :id="`item-price-${item.key}`" type="number" class="form-control" :name="`items[${index}][price]`" x-model.number="item.price" min="0" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="small text-muted pb-2" x-text="formatCurrency((Number(item.qty) || 0) * (Number(item.price) || 0))"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="d-flex justify-content-end align-items-center gap-3 pt-2">
                        <span class="small text-muted text-uppercase fw-bold">Estimated total</span>
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
