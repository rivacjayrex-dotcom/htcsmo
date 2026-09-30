<x-app-layout>
    <x-slot name="header">Inventory Specification Registry</x-slot>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11">

                <!-- ANY VALIDATION ERRORS DISPLAY -->
                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 p-3">
                        <div class="d-flex align-items-center mb-2">
                            <i data-lucide="alert-triangle" class="me-2 text-danger" style="width: 20px;"></i>
                            <strong class="text-danger">Please correct the following errors:</strong>
                        </div>
                        <ul class="mb-0 small ps-4 text-danger">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('inventory.index') }}" class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center">
                            <i data-lucide="chevron-left" class="me-1" style="width:18px"></i> Back to Inventory
                        </a>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                        <!-- SECTION 1: IDENTITY -->
                        <div class="card-body p-4 border-bottom">
                            <div class="nav-section-label mb-3 text-primary">I. Institutional Identity</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label mini-label">Primary Item Name <span class="text-danger">*</span></label>
                                    <input type="text" name="item_name" value="{{ old('item_name') }}" class="form-control border-0 bg-light py-2 shadow-none @error('item_name') is-invalid @enderror" placeholder="e.g. A4 Bond Paper" required>
                                    @error('item_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mini-label">Brand / Manufacturer <span class="text-danger">*</span></label>
                                    <input type="text" name="brand" value="{{ old('brand') }}" class="form-control border-0 bg-light py-2 shadow-none @error('brand') is-invalid @enderror" placeholder="e.g. Hard Copy" required>
                                    @error('brand') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mini-label">Institutional Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select border-0 bg-light py-2 shadow-none @error('category') is-invalid @enderror" required>
                                        <option value="">-- Select Category --</option>
                                        @foreach([
                                            'Cleaning Supplies',
                                            'Construction Supplies',
                                            'Drugs and Medicines',
                                            'Maintenance Supplies',
                                            'Medical Supplies',
                                            'Non-Medical Supplies',
                                            'Office Supplies'
                                        ] as $cat)
                                            <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                    @error('category') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mini-label">Requisition Tier <span class="text-danger">*</span></label>
                                    <select name="requisition_tier" class="form-select border-0 bg-light py-2 shadow-none @error('requisition_tier') is-invalid @enderror" required>
                                        <option value="minor" {{ old('requisition_tier', 'minor') === 'minor' ? 'selected' : '' }}>Minor — routine supplies up to ₱1,000</option>
                                        <option value="major" {{ old('requisition_tier') === 'major' ? 'selected' : '' }}>Major — equipment and high-value items</option>
                                    </select>
                                    <small class="text-muted">This controls which catalog requestors can see.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mini-label">Model / Part Number</label>
                                    <input type="text" name="model_number" value="{{ old('model_number') }}" class="form-control border-0 bg-light py-2 shadow-none" placeholder="e.g. L-3210 or 80GSM">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: TECHNICAL CORE -->
                        <div class="card-body p-4 border-bottom bg-light bg-opacity-50">
                            <div class="nav-section-label mb-3 text-primary">II. Technical Specifications</div>
                            <div class="mb-0">
                                <label class="form-label mini-label">Detailed Physical Description (Size, Weight, Color) <span class="text-danger">*</span></label>
                                <textarea name="specifications" class="form-control border-0 bg-white py-3 shadow-sm rounded-4 @error('specifications') is-invalid @enderror" rows="3" placeholder="Specify all core attributes..." required>{{ old('specifications') }}</textarea>
                                @error('specifications') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            @php
                                $oldNames = old('option_names', ['Size', 'Color']);
                                $oldValues = old('option_values', ['', '']);
                                $optionRows = collect($oldNames)->map(fn ($name, $i) => ['name' => $name, 'values' => $oldValues[$i] ?? ''])->values();
                            @endphp
                            <div class="mt-4" x-data="{ rows: {{ Illuminate\Support\Js::from($optionRows) }} }">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <label class="form-label mini-label mb-0">Requestor Choices</label>
                                        <div class="small text-muted">Add choices such as size, color, type, or capacity. Separate choices with commas.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill" @click="rows.length < 5 && rows.push({name: '', values: ''})">
                                        <i data-lucide="plus" style="width:14px"></i> Add option
                                    </button>
                                </div>
                                <template x-for="(row, index) in rows" :key="index">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-4">
                                            <input type="text" name="option_names[]" x-model="row.name" class="form-control border-0 bg-white shadow-sm" placeholder="Option name (e.g. Size)">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" name="option_values[]" x-model="row.values" class="form-control border-0 bg-white shadow-sm" placeholder="A4, Legal, Letter">
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-light text-danger" @click="rows.splice(index, 1)" aria-label="Remove option"><i data-lucide="x" style="width:15px"></i></button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- SECTION 3: FINANCIALS -->
                        <div class="card-body p-4">
                            <div class="nav-section-label mb-3 text-primary">III. Stock & Financial Parameters</div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label mini-label text-success">Unit Price (₱) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="unit_price" value="{{ old('unit_price') }}" class="form-control border-0 bg-light py-2 fw-bold text-success shadow-none @error('unit_price') is-invalid @enderror" placeholder="0.00" required>
                                    @error('unit_price') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mini-label">Initial Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" value="{{ old('quantity', 0) }}" class="form-control border-0 bg-light py-2 shadow-none @error('quantity') is-invalid @enderror" min="0" required>
                                    @error('quantity') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark text-uppercase">Unit <span class="text-danger">*</span></label>
                                    <select name="unit" class="form-select border-0 bg-light py-2 rounded-3 shadow-none @error('unit') is-invalid @enderror" required>
                                        <option value="">-- Select Unit --</option>
                                        @foreach(['Bottle', 'Box(es)', 'Can(s)', 'Gallon(s)', 'Kilogram(s)', 'Liter(s)', 'Meter(s)', 'Pack(s)', 'PC/PCS', 'Piece(s)', 'Ream(s)', 'Roll(s)', 'Set', 'Unit(s)'] as $u)
                                            <option value="{{ $u }}" {{ old('unit') == $u ? 'selected' : '' }}>{{ $u }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mini-label text-danger">Restock Threshold <span class="text-danger">*</span></label>
                                    <input type="number" name="min_stock_level" value="{{ old('min_stock_level', 5) }}" class="form-control border-0 bg-danger-subtle py-2 text-danger fw-bold shadow-none @error('min_stock_level') is-invalid @enderror" min="0" required>
                                    @error('min_stock_level') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end mb-5">
                        <a href="{{ route('inventory.index') }}" class="btn btn-light px-5 py-3 rounded-pill fw-bold border">Discard</a>
                        <button type="submit" class="btn btn-htc px-5 py-3 rounded-pill fw-bold shadow-lg">
                            VERIFY AND REGISTER ASSET
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
