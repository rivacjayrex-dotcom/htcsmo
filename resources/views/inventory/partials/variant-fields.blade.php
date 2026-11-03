<div class="col-12">
    <p class="small text-muted mb-0">Register each size and supplier combination separately. Each record has its own price, stock and restock threshold. For example, add Bond Paper / A4 / Supplier A and Bond Paper / Letter / Supplier B as two records.</p>
</div>
<div class="col-md-6">
    <label for="supply-size" class="form-label mini-label">Size / Format</label>
    <input id="supply-size" type="text" name="size" maxlength="100" value="{{ old('size', $item->size ?? '') }}" class="form-control border-0 bg-light py-2 shadow-none" placeholder="e.g. A4, Letter, Legal">
    <small class="text-muted">Leave blank for items without a size.</small>
</div>
<div class="col-md-6">
    <label for="supply-supplier" class="form-label mini-label">Supplier</label>
    <input id="supply-supplier" type="text" name="supplier" maxlength="255" value="{{ old('supplier', $item->supplier ?? '') }}" class="form-control border-0 bg-light py-2 shadow-none" placeholder="Company or supplier name">
    <small class="text-muted">The supplier is separate from the product brand.</small>
</div>
