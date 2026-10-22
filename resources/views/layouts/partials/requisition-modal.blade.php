<!-- GLOBAL REQUISITION ENGINE -->
<div class="modal fade" id="globalRequestModal" tabindex="-1" aria-hidden="true" aria-labelledby="requisitionModalTitle" x-data="cartSystem()">
    <div class="modal-dialog requisition-dialog">
        <div class="modal-content requisition-shell border-0 overflow-hidden">
            <div class="requisition-header">
                <h4 class="mb-0 fw-bold" id="requisitionModalTitle">Build a requisition</h4>
                <button type="button" class="requisition-cart-link" x-cloak x-show="view !== 'selection'" @click="$refs.cartPane.scrollIntoView({behavior: 'smooth', block: 'start'}); $refs.purpose.focus({preventScroll: true})"><i data-lucide="shopping-cart" aria-hidden="true"></i> Cart &amp; reason <span x-text="cartQuantity"></span></button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" :disabled="submitting" aria-label="Close requisition"></button>
            </div>

            <div x-cloak x-show="catalogLoading" class="alert alert-light border mx-4 mt-3 mb-0" role="status">
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading supplies...
            </div>
            <div x-cloak x-show="catalogError" class="alert alert-danger mx-4 mt-3 mb-0" role="alert">
                <div class="d-flex justify-content-between align-items-center">
                    <span x-text="catalogError"></span>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-3" @click="loadCatalog()">Retry</button>
                </div>
            </div>

            <div x-show="view === 'selection'" x-transition class="tier-stage p-4 p-lg-5">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1">What type of request are you making?</h5>
                    <p class="text-muted mb-0">Your choice determines the available catalog and approval route.</p>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-5">
                        <button type="button" class="tier-choice tier-minor w-100" @click="setTier('minor')">
                            <span class="tier-icon"><i data-lucide="shopping-basket"></i></span>
                            <span class="tier-kicker">Below 1000</span>
                            <strong>Minor requisition</strong>
                            <span>Office and daily-use supplies totaling up to ₱1,000.</span>
                            <small><span x-text="catalogCount('minor') + ' items available'"></span> · Dept Head → VP Finance</small>
                        </button>
                    </div>
                    <div class="col-md-5">
                        <button type="button" class="tier-choice tier-major w-100" @click="setTier('major')">
                            <span class="tier-icon"><i data-lucide="monitor-up"></i></span>
                            <span class="tier-kicker">Above 1000</span>
                            <strong>Major requisition</strong>
                            <span>Equipment, high-value assets, and approved bulk items.</span>
                            <small><span x-text="catalogCount('major') + ' items available'"></span> · Full approval chain</small>
                        </button>
                    </div>
                </div>
            </div>

            <form x-ref="requisitionForm" x-cloak x-show="view !== 'selection'" class="requisition-form" action="{{ route('requisitions.store') }}" method="POST" data-no-loader @submit="handleRequisitionSubmit($event)" @htc:session-check-failed="submitting = false; submissionConfirmed = false">
                @csrf
                <input type="hidden" name="request_type" :value="tier">
                @if($errors->any() && old('request_type'))
                    <div class="alert alert-danger rounded-0 border-0 border-bottom mb-0 px-4 py-3" role="alert">
                        <div class="fw-bold mb-1"><i data-lucide="circle-alert" style="width:16px"></i> We could not submit your requisition</div>
                        <ul class="small mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div class="requisition-workspace">
                    <section class="catalog-pane">
                        <div x-show="view === 'catalog'">
                            <div class="catalog-toolbar">
                                <button type="button" class="btn btn-sm btn-light border rounded-pill" @click="backToTiers()"><i data-lucide="chevron-left"></i> Tier</button>
                                <div class="tier-pill" :class="tier" x-text="tierLabel"></div>
                                <div class="catalog-search"><i data-lucide="search"></i><input type="search" x-model.debounce.150ms="search" placeholder="Search items, brands, models, or categories…" aria-label="Search catalog"></div>
                            </div>

                            <div x-cloak x-show="notice" x-transition class="catalog-notice" role="status"><i data-lucide="check-circle-2"></i><span x-text="notice"></span></div>

                            <div class="category-chips">
                                <button type="button" :class="category === 'All' ? 'active' : ''" @click="category = 'All'">All items</button>
                                <template x-for="name in categories" :key="name"><button type="button" :class="category === name ? 'active' : ''" @click="category = name" x-text="name"></button></template>
                            </div>

                            <div class="catalog-heading">
                                <div><h5 class="fw-bold mb-1" x-text="tierLabel + ' catalog'"></h5><p class="text-muted small mb-0">Curated and maintained by the SMO in-charge.</p></div>
                                <div class="catalog-display-controls">
                                    <span class="small text-muted" x-text="filteredItems.length + ' available'" aria-live="polite"></span>
                                    <div class="catalog-layout-toggle" role="group" aria-label="Catalog layout">
                                        <button type="button" :class="{ active: catalogLayout === 'grid' }" :aria-pressed="catalogLayout === 'grid'" @click="setCatalogLayout('grid')"><i data-lucide="layout-grid" aria-hidden="true"></i> Grid</button>
                                        <button type="button" :class="{ active: catalogLayout === 'list' }" :aria-pressed="catalogLayout === 'list'" @click="setCatalogLayout('list')"><i data-lucide="list" aria-hidden="true"></i> List</button>
                                    </div>
                                </div>
                            </div>

                            <div class="catalog-grid" :class="{ 'is-list': catalogLayout === 'list' }">
                                <template x-for="item in filteredItems" :key="item.id">
                                    <button type="button" class="product-card" @click="openProduct(item)">
                                        <span class="product-visual"><i data-lucide="package"></i><em x-text="item.category"></em></span>
                                        <span class="product-body">
                                            <small x-text="brandCaption(item)"></small><strong x-text="item.name"></strong><span class="product-spec" x-text="item.specs"></span>
                                            <span class="product-meta"><b x-text="'₱' + formatMoney(item.price)"></b><em x-text="item.stock + ' ' + item.unit + ' available'"></em></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                            <div x-cloak x-show="!catalogLoading && !catalogError && filteredItems.length === 0" class="empty-catalog"><i data-lucide="package-search"></i><strong>No matching items</strong><span>Try another search or category. Only SMO-listed items appear here.</span></div>
                            <button type="button" x-cloak x-show="cart.length" x-transition class="mobile-cart-jump" @click="$refs.cartPane.scrollIntoView({behavior: 'smooth', block: 'start'})">
                                <span><i data-lucide="shopping-cart"></i> Review requisition</span>
                                <strong x-text="cartQuantity + ' item' + (cartQuantity === 1 ? '' : 's')"></strong>
                            </button>
                        </div>

                        <div x-cloak x-show="view === 'product' && selectedItem" x-transition class="product-configurator">
                            <button type="button" class="btn btn-sm btn-light border rounded-pill mb-4" @click="view = 'catalog'"><i data-lucide="chevron-left"></i> Back to catalog</button>
                            <div class="row g-4">
                                <div class="col-md-5"><div class="product-hero"><i data-lucide="package-open"></i><span x-text="selectedItem?.category"></span></div></div>
                                <div class="col-md-7">
                                    <span class="badge bg-success-subtle text-success mb-2">SMO APPROVED</span>
                                    <h3 class="fw-bold mb-1" x-text="selectedItem?.name"></h3>
                                    <div class="text-muted mb-3" x-text="selectedItem?.options?.Brand?.length > 1 ? 'Choose your preferred brand and specifications' : selectedItem?.brand"></div>
                                    <h4 class="text-success fw-bold" x-text="'₱' + formatMoney(selectedItem?.price)"></h4>
                                    <p class="product-description" x-text="selectedItem?.specs"></p>
                                    <template x-for="(choices, label) in (selectedItem?.options || {})" :key="label">
                                        <div class="option-group"><label x-text="'Choose ' + label"></label><div><template x-for="choice in choices" :key="choice"><button type="button" :class="selectedOptions[label] === choice ? 'active' : ''" @click="selectedOptions[label] = choice" x-text="choice"></button></template></div></div>
                                    </template>
                                    <div class="d-flex align-items-end gap-3 mt-4">
                                        <div><label class="mini-label d-block" x-text="'Quantity (' + (selectedItem?.unit || 'pc') + ')'">Quantity</label><div class="quantity-picker"><button type="button" @click="productQty = Math.max(1, productQty - 1)">−</button><input type="number" min="1" :max="selectedItem?.stock" x-model.number="productQty"><button type="button" @click="productQty = Math.min(selectedItem.stock, productQty + 1)">+</button></div></div>
                                        <button type="button" class="btn btn-htc flex-grow-1 py-3 fw-bold rounded-3" :disabled="!canAddProduct" @click="addConfiguredItem()"><i data-lucide="shopping-cart"></i> Add to cart</button>
                                    </div>
                                    <small x-show="!allOptionsSelected" class="text-warning d-block mt-2"><i data-lucide="info" style="width:13px"></i> Select every required option before adding this item.</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside class="cart-pane" x-ref="cartPane" aria-label="Cart and required request reason">
                        <div class="cart-title"><div><span class="cart-icon"><i data-lucide="shopping-cart"></i></span><strong>Your cart</strong></div><span x-text="cartQuantity + ' item' + (cartQuantity === 1 ? '' : 's')" aria-live="polite"></span></div>
                        <div class="cart-reason">
                            <div class="reason-field">
                                <label for="requisition-purpose">Reason for request <span class="reason-required">Required</span></label>
                                <p id="requisition-purpose-help">Tell us why you need these supplies to enable submission.</p>
                                <textarea id="requisition-purpose" x-ref="purpose" name="purpose" x-model="purpose" maxlength="500" minlength="10" rows="3" required :readonly="submitting" aria-describedby="requisition-purpose-help" placeholder="Example: For printing student assessment materials for the midterm examination."></textarea>
                                <span><em x-text="purpose.length + '/500'"></em><em :class="purposeIsValid ? 'text-success' : ''" x-text="purposeIsValid ? 'Reason added' : 'At least 10 characters'"></em></span>
                            </div>
                        </div>
                        <div class="cart-items">
                            <template x-for="(item, index) in cart" :key="item.key">
                                <div class="cart-item">
                                    <div class="d-flex justify-content-between gap-2"><strong x-text="item.name"></strong><button type="button" @click="removeItem(index)" aria-label="Remove item"><i data-lucide="x"></i></button></div>
                                    <small x-text="optionSummary(item.options) || item.specs"></small>
                                    <div class="d-flex justify-content-between align-items-center mt-2"><div class="mini-quantity"><button type="button" @click="item.qty = Math.max(1, item.qty - 1)">−</button><span x-text="item.qty"></span><button type="button" @click="item.qty = Math.min(item.stock, item.qty + 1)">+</button></div><b x-text="'₱' + formatMoney(item.qty * item.price)"></b></div>
                                    <input type="hidden" :name="'items['+index+'][supply_id]'" :value="item.id"><input type="hidden" :name="'items['+index+'][qty]'" :value="item.qty">
                                    <template x-for="(value, label) in item.options" :key="label"><input type="hidden" :name="'items['+index+'][options]['+label+']'" :value="value"></template>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="cart-empty"><i data-lucide="shopping-bag"></i><strong>Your cart is empty</strong><span>Choose an item from the approved catalog to begin.</span></div>
                        </div>
                        <div class="cart-summary">
                            <div><span>Request tier</span><b x-text="tierLabel"></b></div><div><span>Estimated total</span><strong x-text="'₱' + formatMoney(grandTotal)"></strong></div>
                            <p x-cloak x-show="tier === 'minor' && grandTotal > 1000" class="text-danger mb-2">Minor requests are limited to ₱1,000. Remove items or switch tiers.</p>
                            <button type="submit" class="btn btn-htc w-100 py-3 fw-bold rounded-3" :disabled="!canSubmit" x-text="submitLabel"></button>
                            <small><i data-lucide="shield-check"></i> Item details and prices are verified by SMO.</small>
                        </div>
                    </aside>
                </div>
            </form>
            @include('layouts.partials.requisition-confirmation')
        </div>
    </div>
</div>
