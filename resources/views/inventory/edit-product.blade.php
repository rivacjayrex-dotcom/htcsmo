<x-app-layout>
    <x-slot name="header">Edit Product</x-slot>

    <div class="container-fluid py-4 inventory-form-page">
        <a href="{{ route('inventory.index') }}" class="btn btn-link text-muted text-decoration-none px-0 mb-3">← Back to inventory</a>
        <div class="mb-4">
            <h3 class="fw-bold">Edit {{ $product->item_name }}</h3>
            <p class="text-muted mb-0">Update the product and all {{ $variants->count() }} variants together, then save once.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Please review these changes:</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <a href="{{ route('inventory.products.edit', $product) }}" class="alert-link d-inline-block mt-2">Reload current product details</a>
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.products.update', $product) }}"
              x-data="inventoryProductEditor(@js($rows), @js(old('variants', [])))"
              @invalid.capture="showInvalidField($event)"
              :data-confirm="saveConfirmation"
              :data-confirm-title="removalCount ? 'Save changes and remove variants?' : 'Save whole product?'"
              :data-confirm-accept="removalCount ? 'Save and remove variants' : 'Save all changes'">
            @csrf
            @method('PATCH')
            <section class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Product details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="product-name" class="form-label">Product name</label>
                            <input id="product-name" name="item_name" class="form-control" value="{{ old('item_name', $product->item_name) }}" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label for="product-category" class="form-label">Category</label>
                            <select id="product-category" name="category" class="form-select" required>
                                @foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $product->category) === $category)>{{ $category }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12" x-data="{ applyDescription: @js((bool) old('apply_description', false)) }">
                            <input type="hidden" name="apply_description" value="0">
                            <label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="apply_description" value="1" x-model="applyDescription"><span class="form-check-label">Use one description for all variants</span></label>
                            <textarea class="form-control" name="shared_specifications" rows="3" maxlength="10000" minlength="3" x-cloak x-show="applyDescription" :disabled="!applyDescription" :required="applyDescription" aria-label="Description for all variants">{{ old('shared_specifications', $product->specifications) }}</textarea>
                            <small class="text-muted">Existing descriptions stay as they are unless you enable this or edit a variant's details below.</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-4 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><h6 class="fw-bold mb-1">All variants</h6><p class="small text-muted mb-0">Edit prices and stock below. Open Details for brand, attributes and descriptions. Removals take effect when you save.</p></div>
                    <button type="button" class="btn btn-outline-success btn-sm" @click="addVariant()" :disabled="rows.length >= 100">Add variant</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle product-variants-table mb-0">
                        <thead class="table-light"><tr><th>Variant</th><th>Size</th><th>Supplier</th><th>Unit</th><th>Price (₱)</th><th>Stock</th><th>Restock at</th><th>Actions</th></tr></thead>
                        <template x-for="(row, index) in rows" :key="row.key">
                            <tbody data-product-variant :data-variant-key="row.key">
                                <tr :class="{ 'table-danger': row.remove }">
                                    <td class="product-variant-label">
                                        <strong x-text="'Variant ' + (index + 1)"></strong><span class="badge bg-success-subtle text-success ms-2" x-show="!row.id">New</span>
                                        <small class="text-muted d-block mt-1" x-text="variantLabel(row)"></small>
                                        <small x-cloak x-show="row.remove" class="text-danger fw-bold">Marked for removal</small>
                                        <input type="hidden" :name="'variants[' + index + '][id]'" :value="row.id || ''">
                                        <input type="hidden" :name="'variants[' + index + '][version]'" :value="row.version || ''">
                                        <input type="hidden" :name="'variants[' + index + '][remove]'" :value="row.remove ? 1 : 0">
                                    </td>
                                    <td><input class="form-control form-control-sm" maxlength="100" :disabled="row.remove" x-model="row.size" :readonly="row.locked" :name="'variants[' + index + '][size]'" :aria-label="'Size for variant ' + (index + 1)"></td>
                                    <td><input class="form-control form-control-sm" maxlength="255" :disabled="row.remove" x-model="row.supplier" :readonly="row.locked" :name="'variants[' + index + '][supplier]'" :aria-label="'Supplier for variant ' + (index + 1)"></td>
                                    <td><input class="form-control form-control-sm" maxlength="50" :disabled="row.remove" x-model="row.unit" :readonly="row.locked" :name="'variants[' + index + '][unit]'" :aria-label="'Unit for variant ' + (index + 1)" required></td>
                                    <td><input class="form-control form-control-sm" type="number" step="0.01" min="0.01" max="99999999.99" :disabled="row.remove" x-model="row.unit_price" :name="'variants[' + index + '][unit_price]'" :aria-label="'Price for variant ' + (index + 1)" required></td>
                                    <td><input class="form-control form-control-sm" type="number" step="1" min="0" max="2147483647" :disabled="row.remove" x-model="row.quantity" :name="'variants[' + index + '][quantity]'" :aria-label="'Stock for variant ' + (index + 1)" required></td>
                                    <td><input class="form-control form-control-sm" type="number" step="1" min="0" max="2147483647" :disabled="row.remove" x-model="row.min_stock_level" :name="'variants[' + index + '][min_stock_level]'" :aria-label="'Restock threshold for variant ' + (index + 1)" required></td>
                                    <td><div class="d-flex gap-1">
                                        <button type="button" class="btn btn-light btn-sm" @click="row.detailsOpen = !row.detailsOpen" :disabled="row.remove" :aria-expanded="row.detailsOpen">Details</button>
                                        <button type="button" class="btn btn-light btn-sm" @click="copyVariant(index)" :disabled="row.remove || rows.length >= 100">Copy</button>
                                        <button type="button" class="btn btn-light btn-sm text-danger" @click="removeVariant(index)" x-text="row.remove ? 'Undo removal' : 'Remove'"></button>
                                    </div></td>
                                </tr>
                                <tr x-cloak x-show="row.detailsOpen && !row.remove">
                                    <td colspan="8" class="bg-light p-4">
                                        <p class="small text-muted" x-show="row.locked">This variant is used in requisitions. Its selections, supplier and stock unit stay linked to those requests; use Copy to register a different combination.</p>
                                        <h6 class="fw-bold">Combination attributes</h6>
                                        <p class="small text-muted">Priced variants use one value per attribute. Existing shared choices can keep their comma-separated values.</p>
                                        <template x-for="(option, optionIndex) in row.options" :key="optionIndex">
                                            <div class="row g-2 mb-2">
                                                <div class="col-sm-4"><input class="form-control form-control-sm" maxlength="50" pattern="[A-Za-z0-9 _-]+" :disabled="row.remove" x-model="option.name" :readonly="row.locked" :name="'variants[' + index + '][options][' + optionIndex + '][name]'" :aria-label="'Attribute name for variant ' + (index + 1)" required></div>
                                                <div class="col-sm-7"><input class="form-control form-control-sm" maxlength="500" :disabled="row.remove" x-model="option.values" :readonly="row.locked" :name="'variants[' + index + '][options][' + optionIndex + '][values]'" :aria-label="'Attribute value for variant ' + (index + 1)" required></div>
                                                <div class="col-sm-1"><button type="button" class="btn btn-light btn-sm text-danger" :disabled="row.locked || option.name.toLowerCase() === 'brand'" @click="row.options.splice(optionIndex, 1)" aria-label="Remove attribute">×</button></div>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-outline-success btn-sm mb-3" @click="row.options.push({name: '', values: ''})" :disabled="row.locked || row.options.length >= 5">Add attribute</button>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label class="form-label small">Model / Part number</label><input class="form-control form-control-sm" maxlength="100" :disabled="row.remove" x-model="row.model_number" :name="'variants[' + index + '][model_number]'" :aria-label="'Model for variant ' + (index + 1)"></div>
                                            <div class="col-md-8"><label class="form-label small">Variant description</label><textarea class="form-control form-control-sm" rows="2" maxlength="10000" :disabled="row.remove" x-model="row.specifications" :name="'variants[' + index + '][specifications]'" :aria-label="'Description for variant ' + (index + 1)"></textarea></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
            </section>

            <div class="product-editor-actions d-flex flex-wrap justify-content-end align-items-center gap-3 p-3 rounded-3 border bg-white">
                <small class="text-muted me-auto" x-text="(rows.length - removalCount) + ' variants in this product' + (removalCount ? ' · ' + removalCount + ' marked for removal' : '')"></small>
                <a href="{{ route('inventory.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-htc fw-bold">Save all changes</button>
            </div>
        </form>
    </div>

    @pushOnce('styles', 'page-styles:inventory/edit-product.blade.php')
    <style>
        .product-variants-table th, .product-variants-table td { padding: var(--space-12); }
        .product-variants-table th { white-space: nowrap; font-size: var(--text-caption); }
        .product-variants-table input { min-width: 95px; }
        .product-variants-table .product-variant-label { min-width: 200px; max-width: 280px; font-size: var(--text-secondary); }
        .product-variant-label small { font-size: var(--text-caption); overflow-wrap: anywhere; }
        .product-editor-actions { position: sticky; bottom: var(--space-8); z-index: 4; }
    </style>
    @endPushOnce
    @include('inventory.partials.product-editor-script')
</x-app-layout>
