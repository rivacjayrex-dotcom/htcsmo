<section x-cloak x-show="enabled" aria-label="Prices and stock by combination">
    <h6 class="fw-bold mt-4">Prices and stock by combination</h6>
    <p class="small text-muted">Add columns for choices such as Color, Style or Quality. Enter one selection per cell and a price for each row. All rows use the item name, description and unit above.</p>
    <fieldset :disabled="!enabled">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <template x-for="(name, column) in columns" :key="column">
                <div class="d-flex gap-1 align-items-center">
                    <input type="text" name="variant_names[]" x-model="columns[column]" :readonly="column === 0" class="form-control form-control-sm" maxlength="50" pattern="[A-Za-z0-9 _-]+" required :aria-label="'Attribute column ' + (column + 1)" placeholder="e.g. Quality">
                    <button type="button" class="btn btn-sm btn-light text-danger" :disabled="column === 0" @click="removeColumn(column)" :aria-label="'Remove attribute column ' + (column + 1)">×</button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-success" :disabled="columns.length >= 5" @click="addColumn()">Add attribute</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead><tr>
                    <template x-for="(name, column) in columns" :key="column"><th x-text="name || 'Attribute'"></th></template>
                    <th>Size</th><th>Supplier</th><th>Unit price (₱)</th><th>Stock</th><th>Restock at</th><th>Actions</th>
                </tr></thead>
                <tbody>
                    <template x-for="(combination, index) in combinations" :key="combination.key">
                        <tr>
                            <template x-for="(name, column) in columns" :key="column"><td>
                                <input class="form-control form-control-sm" style="min-width: 110px" type="text" maxlength="100" pattern="[^,]+" x-model="combination.values[column]" :name="'variants[' + index + '][values][' + column + ']'" :aria-label="(name || 'Attribute') + ' for combination ' + (index + 1)" required placeholder="One value">
                            </td></template>
                            <td><input class="form-control form-control-sm" style="min-width: 100px" type="text" maxlength="100" x-model="combination.size" :name="'variants[' + index + '][size]'" :aria-label="'Size for combination ' + (index + 1)" placeholder="e.g. Large"></td>
                            <td><input class="form-control form-control-sm" style="min-width: 140px" type="text" maxlength="255" x-model="combination.supplier" :name="'variants[' + index + '][supplier]'" :aria-label="'Supplier for combination ' + (index + 1)"></td>
                            <td><input class="form-control form-control-sm" style="min-width: 110px" type="number" min="0.01" max="99999999.99" step="0.01" x-model="combination.unit_price" :name="'variants[' + index + '][unit_price]'" :aria-label="'Unit price for combination ' + (index + 1)" required></td>
                            <td><input class="form-control form-control-sm" style="min-width: 90px" type="number" min="0" max="2147483647" step="1" x-model="combination.quantity" :name="'variants[' + index + '][quantity]'" :aria-label="'Stock for combination ' + (index + 1)" required></td>
                            <td><input class="form-control form-control-sm" style="min-width: 90px" type="number" min="0" max="2147483647" step="1" x-model="combination.min_stock_level" :name="'variants[' + index + '][min_stock_level]'" :aria-label="'Restock threshold for combination ' + (index + 1)" required></td>
                            <td><div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-light" :disabled="combinations.length >= 100" @click="copyCombination(index)">Copy</button>
                                <button type="button" class="btn btn-sm btn-light text-danger" :disabled="combinations.length === 1" @click="combinations.splice(index, 1)">Remove</button>
                            </div></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline-success btn-sm" :disabled="combinations.length >= 100" @click="addCombination()">Add combination</button>
        <small class="text-muted d-block mt-2">Use Copy to keep the same brand and supplier, then change the size, quality or price. Variants stay grouped under one product in Manage Stock; use Edit to update them together.</small>
    </fieldset>
</section>
