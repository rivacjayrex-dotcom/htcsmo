<x-app-layout>
    <x-slot name="header">{{ __('Edit Requisition') }}</x-slot>

    <div class="container-fluid py-2 request-edit-page" x-data="requisitionEditor(@js($catalog), @js($initialItems), @js(old('items', [])))">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2">{{ strtoupper($requisition->request_type) }} tier</span>
                    <span class="text-muted small">{{ $requisition->tracking_code }}</span>
                </div>
                <h4 class="fw-black text-dark mb-1">Edit requisition items</h4>
                <p class="text-muted small mb-0">Add, configure, remove, or change quantities while this request is pending.</p>
            </div>
            <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-pill px-3">Cancel</a>
        </div>


        <form action="{{ route('requisitions.update', $requisition) }}" method="POST" data-confirm="Save the updated purpose and requested items for {{ $requisition->tracking_code }}?" data-confirm-title="Save requisition changes?" data-confirm-accept="Save changes">
            @csrf
            @method('PATCH')
            <div class="row g-4">
                <section class="col-xl-7">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-white border-bottom p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div><h6 class="fw-bold mb-1">Add from the approved catalog</h6><small class="text-muted">All catalog items are available in both tiers. Request total limits still apply.</small></div>
                                <input type="search" x-model.debounce.150ms="search" class="form-control catalog-search" placeholder="Search catalog…" aria-label="Search catalog">
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div x-show="selectedItem" x-cloak class="item-configurator mb-4">
                                <div class="d-flex justify-content-between gap-3 mb-3">
                                    <div><span class="badge bg-success-subtle text-success mb-2" x-text="selectedItem?.category"></span><h5 class="fw-bold mb-1" x-text="selectedItem?.name"></h5><p class="small text-muted mb-0" x-text="selectedItem?.specs || 'Standard specification'"></p></div>
                                    <button type="button" class="btn-close" @click="cancelConfigure()" aria-label="Close item configuration"></button>
                                </div>
                                <template x-for="dimension in variantDimensions" :key="dimension.name">
                                    <div class="mb-3" x-show="dimension.values.length > 1">
                                        <label class="form-label mini-label" x-text="dimension.name"></label>
                                        <div class="d-flex flex-wrap gap-2" role="group" :aria-label="dimension.name">
                                            <template x-for="value in dimension.values" :key="value">
                                                <button type="button" class="btn btn-sm btn-outline-success" :class="{active: variantValue(selectedItem, dimension.name) === value}" :aria-pressed="variantValue(selectedItem, dimension.name) === value" @click="chooseVariant(dimension.name, value)" x-text="value || 'Standard'"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-for="(choices, label) in customizationOptions" :key="label">
                                    <div class="mb-3"><label class="form-label mini-label" x-text="label"></label><select class="form-select" x-model="selectedOptions[label]"><option value="">Select an option</option><template x-for="choice in choices" :key="choice"><option :value="choice" x-text="choice"></option></template></select></div>
                                </template>
                                <p class="small text-muted" x-text="variantCaption(selectedItem)"></p>
                                <div class="row g-3 align-items-end">
                                    <div class="col-sm-4"><label class="form-label mini-label">Quantity</label><input type="number" class="form-control" min="1" :max="selectedItem?.stock" x-model.number="selectedQty"></div>
                                    <div class="col-sm-4"><span class="mini-label d-block mb-2">Live catalog price</span><strong class="text-success" x-text="'PHP ' + money(selectedItem?.price)"></strong></div>
                                    <div class="col-sm-4"><button type="button" class="btn btn-success w-100" :disabled="!canSaveConfigured" @click="saveConfiguredItem()" x-text="editingIndex === null ? 'Add item' : 'Apply changes'"></button></div>
                                </div>
                            </div>

                            <div class="catalog-grid">
                                <template x-for="product in filteredProducts" :key="product.key">
                                    <button type="button" class="catalog-item" :disabled="product.stock < 1" @click="configureItem(product.item)">
                                        <span><small x-text="product.category"></small><strong x-text="product.name"></strong><em x-text="product.variants.length > 1 ? product.variants.length + ' variants · Choose options' : variantCaption(product.item)"></em><em x-show="product.variants.length === 1" x-text="product.item.specs || 'Standard specification'"></em></span>
                                        <span class="text-end"><b x-text="(product.hasPriceRange ? 'From ' : '') + 'PHP ' + money(product.price)"></b><small x-text="product.stockSummary"></small></span>
                                    </button>
                                </template>
                            </div>
                            <div x-cloak x-show="filteredProducts.length === 0" class="text-center text-muted py-5">No catalog items match your search.</div>
                        </div>
                    </div>
                </section>

                <aside class="col-xl-5">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden edit-cart">
                        <div class="card-header bg-white border-bottom p-4"><h6 class="fw-bold mb-1">Requested items</h6><small class="text-muted" x-text="cart.length + ' line item' + (cart.length === 1 ? '' : 's')"></small></div>
                        <div class="card-body p-4">
                            <template x-for="(item, index) in cart" :key="item.key">
                                <div class="cart-row">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div><strong x-text="item.name"></strong><small x-text="variantCaption(item)"></small><small x-text="optionSummary(item.selected_options, item.attributes || {}) || item.specs || 'Standard specification'"></small></div>
                                        <button type="button" class="btn btn-sm btn-light border text-danger" @click="removeItem(index)" aria-label="Remove item"><i data-lucide="trash-2"></i></button>
                                    </div>
                                    <div class="row g-2 align-items-end mt-1">
                                        <div class="col-5"><label class="mini-label">Quantity (<span x-text="item.unit"></span>)</label><input type="number" class="form-control form-control-sm" min="1" :max="item.stock" x-model.number="item.qty"></div>
                                        <div class="col-4 text-end"><span class="mini-label d-block">Subtotal</span><b x-text="'PHP ' + money(item.qty * item.price)"></b></div>
                                        <div class="col-3 text-end"><button type="button" class="btn btn-sm btn-outline-success" @click="configureCartItem(index)">Edit</button></div>
                                    </div>
                                    <input type="hidden" :name="'items['+index+'][supply_id]'" :value="item.id">
                                    <input type="hidden" :name="'items['+index+'][qty]'" :value="item.qty">
                                    <template x-for="(value, label) in item.selected_options" :key="label"><input type="hidden" :name="'items['+index+'][options]['+label+']'" :value="value"></template>
                                </div>
                            </template>
                            <div x-cloak x-show="cart.length === 0" class="empty-cart"><i data-lucide="shopping-bag"></i><strong>No requested items</strong><span>Add at least one item from the catalog.</span></div>

                            <div class="mt-4"><label for="purpose" class="form-label fw-bold">Reason for request <span class="text-danger">*</span></label><textarea id="purpose" name="purpose" class="form-control" rows="3" minlength="10" maxlength="500" required>{{ old('purpose', $requisition->purpose) }}</textarea></div>
                            <div class="edit-total"><span>Updated request total</span><strong x-text="'PHP ' + money(grandTotal)"></strong></div>
                            <p x-cloak x-show="tierLimitError" x-text="tierLimitError" class="text-danger small mt-2 mb-0"></p>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="request-edit-actions d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('requests.index') }}" class="btn btn-light border rounded-3 px-4">Cancel</a>
                <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold" :disabled="!canSubmit">Save requisition</button>
            </div>
        </form>
    </div>

    @pushOnce('styles', 'page-styles:requests/edit.blade.php')
    <style>
        [x-cloak] { display: none !important; }
        .catalog-search { width: min(100%, 280px); }
        .catalog-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-12); }
        .catalog-item { display: flex; justify-content: space-between; gap: var(--space-16); padding: var(--space-16); border: 1px solid #dfe8e3; border-radius: 14px; background: #fff; color: #27382e; text-align: left; }
        .catalog-item:hover:not(:disabled) { border-color: #53a475; background: #f6fbf8; }
        .catalog-item:disabled { opacity: .5; cursor: not-allowed; }
        .catalog-item span { min-width: 0; }
        .catalog-item small, .catalog-item em { display: block; color: #77847d; font-size: var(--text-caption); font-style: normal; }
        .catalog-item strong { display: block; margin: var(--space-4) 0; font-size: var(--text-secondary); }
        .catalog-item b { display: block; color: #17633e; font-size: var(--text-caption); white-space: nowrap; }
        .item-configurator { padding: var(--space-16); border: 1px solid #b9dcc7; border-radius: 15px; background: #f4faf6; }
        .cart-row { padding: var(--space-16) 0; border-bottom: 1px solid #e6ece8; }
        .cart-row:first-child { padding-top: 0; }
        .cart-row strong, .cart-row small { display: block; }
        .cart-row small { margin-top: var(--space-4); color: #78867e; }
        .cart-row svg { width: 14px; height: 14px; }
        .empty-cart { display: grid; justify-items: center; gap: var(--space-8); padding: calc(var(--space-32) + var(--space-8)) var(--space-16); color: #7b8981; text-align: center; }
        .empty-cart svg { width: 28px; height: 28px; }
        .edit-total { display: flex; justify-content: space-between; margin-top: var(--space-16); padding-top: var(--space-16); border-top: 1px solid #e2e9e5; }
        .edit-total strong { color: #17633e; }
        @media (max-width: 767.98px) { .catalog-grid { grid-template-columns: 1fr; } .catalog-search { width: 100%; } .request-edit-actions { flex-direction: column-reverse; } .request-edit-actions .btn { width: 100%; } }
    </style>
    @endPushOnce

    <script>
        function requisitionEditor(catalog, initialItems, oldItems) {
            return {
                catalog, cart: [], search: '', selectedItem: null, selectedOptions: {}, selectedQty: 1, editingIndex: null,
                tier: @js($requisition->request_type),
                init() {
                    const source = Array.isArray(oldItems) && oldItems.length ? oldItems : initialItems;
                    this.cart = source.map((submitted) => {
                        const supplyId = Number(submitted.supply_id ?? submitted.id);
                        const item = this.catalog.find(entry => entry.id === supplyId);
                        if (!item) return null;
                        const options = submitted.options || submitted.selected_options || {};
                        return {...item, qty: Number(submitted.qty) || 1, selected_options: options, key: this.itemKey(item.id, options)};
                    }).filter(Boolean);
                    this.refreshIcons();
                },
                configureItem(item) {
                    this.selectedItem = item;
                    this.selectedOptions = Object.fromEntries(Object.entries(item.options || {}).filter(([, choices]) => choices.length === 1).map(([label, choices]) => [label, choices[0]]));
                    this.selectedQty = 1; this.editingIndex = null;
                },
                variantValue(item, label) {
                    if (label === 'Size') return item?.size || '';
                    if (label === 'Supplier') return item?.supplier || '';
                    return item?.attributes?.[label] || '';
                },
                chooseVariant(label, value) {
                    const dimensions = this.variantDimensions.map(dimension => dimension.name);
                    const preceding = dimensions.slice(0, dimensions.indexOf(label));
                    const candidates = this.productVariants.filter(item => this.variantValue(item, label) === value
                        && preceding.every(name => this.variantValue(item, name) === this.variantValue(this.selectedItem, name)));
                    const score = item => dimensions.reduce((sum, name) => sum + (this.variantValue(item, name) === this.variantValue(this.selectedItem, name) ? 1 : 0), 0);
                    candidates.sort((a, b) => score(b) - score(a));
                    const item = candidates[0];
                    if (!item) return;
                    this.selectedItem = item;
                    this.selectedOptions = Object.fromEntries(Object.entries(item.options || {}).filter(([, choices]) => choices.length === 1).map(([label, choices]) => [label, choices[0]]));
                },
                configureCartItem(index) {
                    const item = this.cart[index];
                    this.selectedItem = this.catalog.find(entry => entry.id === item.id) || item;
                    this.selectedOptions = {...item.selected_options}; this.selectedQty = item.qty; this.editingIndex = index;
                    window.scrollTo({top: 0, behavior: 'smooth'});
                },
                cancelConfigure() { this.selectedItem = null; this.selectedOptions = {}; this.selectedQty = 1; this.editingIndex = null; },
                saveConfiguredItem() {
                    if (!this.canSaveConfigured) return;
                    const item = {...this.selectedItem, qty: Number(this.selectedQty), selected_options: {...this.selectedOptions}};
                    item.key = this.itemKey(item.id, item.selected_options);
                    const duplicate = this.cart.findIndex((entry, index) => index !== this.editingIndex && entry.key === item.key);
                    if (duplicate >= 0) { this.cart[duplicate].qty = Math.min(item.stock, Number(this.cart[duplicate].qty) + item.qty); if (this.editingIndex !== null) this.cart.splice(this.editingIndex, 1); }
                    else if (this.editingIndex === null) this.cart.push(item);
                    else this.cart.splice(this.editingIndex, 1, item);
                    this.cancelConfigure(); this.refreshIcons();
                },
                removeItem(index) { this.cart.splice(index, 1); this.refreshIcons(); },
                itemKey(id, options) { return `${id}:${JSON.stringify(options || {})}`; },
                optionSummary(options, fixed = {}) { return Object.entries(options || {}).filter(([label]) => !(label in fixed)).map(([label, value]) => `${label}: ${value}`).join(' · '); },
                variantCaption(item) { return [...Object.entries(item?.attributes || {}).map(([name, value]) => `${name}: ${value}`), item?.size && `Size: ${item.size}`, item?.supplier && `Supplier: ${item.supplier}`].filter(Boolean).join(' · '); },
                money(value) { return Number(value || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                refreshIcons() { window.setTimeout(() => window.lucide?.createIcons(), 20); },
                get filteredCatalog() { const term = this.search.trim().toLowerCase(); return this.catalog.filter(item => !term || `${item.name} ${this.variantCaption(item)} ${item.category} ${item.brand || ''} ${item.specs || ''}`.toLowerCase().includes(term)); },
                get filteredProducts() { return groupRequisitionProducts(this.catalog, this.filteredCatalog); },
                get productVariants() { return this.selectedItem ? this.catalog.filter(item => requisitionProductKey(item) === requisitionProductKey(this.selectedItem)) : []; },
                get variantDimensions() {
                    const variants = this.productVariants;
                    const names = [...new Set(variants.flatMap(item => Object.keys(item.attributes || {})))];
                    if (variants.some(item => item.size)) names.push('Size');
                    if (variants.some(item => item.supplier)) names.push('Supplier');
                    return names.map((name, index) => {
                        const compatible = variants.filter(item => names.slice(0, index).every(label => this.variantValue(item, label) === this.variantValue(this.selectedItem, label)));
                        return {name, values: [...new Set(compatible.map(item => this.variantValue(item, name)))]};
                    });
                },
                get customizationOptions() { return Object.fromEntries(Object.entries(this.selectedItem?.options || {}).filter(([name]) => !(name in (this.selectedItem?.attributes || {})))); },
                get allOptionsSelected() { return this.selectedItem && Object.keys(this.selectedItem.options || {}).every(label => this.selectedOptions[label]); },
                get canSaveConfigured() { return this.allOptionsSelected && this.selectedQty >= 1 && this.selectedQty <= (this.selectedItem?.stock || 0); },
                get grandTotal() { return this.cart.reduce((total, item) => total + (Number(item.qty) * Number(item.price)), 0); },
                get tierLimitError() { if (this.tier === 'minor' && this.grandTotal > 1000) return 'Minor requisitions cannot exceed PHP 1,000.'; if (this.tier === 'major' && this.grandTotal <= 1000) return 'Major requisitions must exceed PHP 1,000.'; return ''; },
                get canSubmit() { return this.cart.length > 0 && !this.tierLimitError && this.cart.every(item => item.qty >= 1 && item.qty <= item.stock); },
            };
        }
    </script>
</x-app-layout>
