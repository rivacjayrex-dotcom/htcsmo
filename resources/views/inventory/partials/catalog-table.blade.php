<div class="table-responsive">
    <table class="table inventory-table align-middle mb-0">
        <thead>
            <tr>
                <th class="inventory-select-cell">
                    <input type="checkbox" class="inventory-select" aria-label="Select all matching variants" title="Select all matching variants"
                           :checked="allMatchingSelected()" x-effect="$el.indeterminate = someMatchingSelected()" @change="toggleMatching()">
                </th>
                <th class="ps-4">Item & variant</th>
                <th>Category & details</th>
                <th class="text-center">Stock</th>
                <th class="text-end">Unit cost</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($catalogGroups as $group)
                @php($first = $group['variants'][0])
                <tr data-inventory-product="{{ $group['name'] }}"
                    x-data="inventoryProductRow({{ Illuminate\Support\Js::from($group['variants']) }})"
                    x-show="matchingVariants.length > 0"
                    :class="{ 'is-selected': someSelected(variantIds), 'stock-out-row': activeVariant.status === 'out', 'stock-low-row': activeVariant.status === 'low' }"
                    class="inventory-row">
                    <td class="inventory-select-cell" data-label="Select variants">
                        <input type="checkbox" class="inventory-select"
                               :checked="allSelected(variantIds)" x-effect="$el.indeterminate = someSelected(variantIds) && !allSelected(variantIds)"
                               @change="toggleIds(variantIds)" aria-label="Select all matching variants of {{ $group['name'] }}" title="Select all matching variants of {{ $group['name'] }}">
                    </td>
                    <td class="ps-4 inventory-primary" data-label="Item & variant">
                        <div class="d-flex align-items-start gap-3">
                            <div class="inventory-item-icon"><i data-lucide="package"></i></div>
                            <div class="inventory-product-identity">
                                <div class="fw-bold text-dark">{{ $group['name'] }}</div>
                                @if(count($group['variants']) > 1)
                                    <label for="inventory-variant-{{ $first['id'] }}" class="inventory-variant-label">{{ count($group['variants']) }} variants</label>
                                    <select id="inventory-variant-{{ $first['id'] }}" data-variant-picker class="form-select form-select-sm inventory-variant-picker"
                                            :value="activeVariant.id" @change="chosenId = Number($event.target.value)">
                                        @foreach($group['variants'] as $variant)
                                            <option value="{{ $variant['id'] }}" :selected="activeVariant.id === {{ $variant['id'] }}">{{ $variant['label'] }} — ₱{{ number_format($variant['price'], 2) }} / {{ $variant['unit'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($first['label'] !== 'Standard')
                                    <small class="text-muted">{{ $first['label'] }}</small>
                                @endif
                                <div class="item-specification" :title="activeVariant.specifications" x-text="activeVariant.specifications || 'No detailed specifications'">{{ $first['specifications'] ?: 'No detailed specifications' }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Category & details">
                        <div>
                            <span class="inventory-category">{{ $group['category'] }}</span>
                            <div class="option-preview inventory-variant-summary" :title="activeVariant.options_summary" x-text="activeVariant.options_summary || 'Standard item'">{{ $first['options_summary'] ?: 'Standard item' }}</div>
                            <small class="text-muted" x-show="activeVariant.model" x-text="activeVariant.model">{{ $first['model'] }}</small>
                        </div>
                    </td>
                    <td class="text-center" data-label="Stock">
                        <span class="stock-badge" :class="activeVariant.status" role="status" x-text="activeVariant.status === 'out' ? 'Out of stock' : activeVariant.quantity + ' ' + activeVariant.unit">{{ $first['quantity'] > 0 ? $first['quantity'].' '.$first['unit'] : 'Out of stock' }}</span>
                        <small class="stock-threshold" x-cloak x-show="activeVariant.status === 'low'" x-text="'Restock at ' + activeVariant.min_stock_level"></small>
                    </td>
                    <td class="text-end" data-label="Unit cost">
                        <div class="fw-bold text-dark" data-variant-price x-text="'₱' + formatMoney(activeVariant.price)">₱{{ number_format($first['price'], 2) }}</div>
                        <small class="text-muted" x-text="'per ' + (activeVariant.unit || '').toLowerCase()">per {{ strtolower($first['unit'] ?? '') }}</small>
                    </td>
                    <td class="pe-4 text-end" data-label="Actions">
                        <div class="inventory-row-actions d-inline-flex gap-2">
                            <a href="{{ $first['edit_url'] }}" :href="activeVariant.edit_url" class="btn inventory-action" :title="'Edit ' + activeVariant.name" :aria-label="'Edit ' + activeVariant.name"><i data-lucide="pencil"></i></a>
                            <button type="button" class="btn inventory-action danger" @click="confirmDelete(activeVariant.delete_url, activeVariant.name)" :title="'Delete ' + activeVariant.name" :aria-label="'Delete ' + activeVariant.name"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="inventory-empty"><i data-lucide="package-open"></i><strong>{{ request()->filled('search') || request('stock', 'all') !== 'all' ? 'No matching items' : 'No catalog items yet' }}</strong><span>Try another search or stock filter, or register a new item.</span></td></tr>
            @endforelse
            @if($catalogGroups->isNotEmpty())
                <tr x-cloak x-show="matchingIds().length === 0">
                    <td colspan="6" class="inventory-empty"><i data-lucide="search-x"></i><strong>No matching items</strong><span>Try another search term or stock filter.</span></td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
