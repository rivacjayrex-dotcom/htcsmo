<x-app-layout>
    <x-slot name="header">{{ __('Manage Stock') }}</x-slot>

    @php
        $totalItems = $supplies->count();
        $minorItems = $supplies->where('requisition_tier', 'minor')->values();
        $majorItems = $supplies->where('requisition_tier', 'major')->values();
        $attentionItems = $supplies->filter(fn ($item) => $item->quantity <= $item->min_stock_level)->values();
        $lowStockItems = $supplies->filter(fn ($item) => $item->quantity > 0 && $item->quantity <= $item->min_stock_level)->count();
        $outOfStockItems = $supplies->where('quantity', '<=', 0)->count();
        $inventoryValue = $supplies->sum(fn ($item) => $item->quantity * (float) $item->unit_price);
    @endphp

    <div class="inventory-workspace" x-data="inventoryWorkspace()">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 d-flex align-items-center gap-2"><i data-lucide="check-circle" style="width:18px"></i>{{ session('success') }}</div>
        @endif

        <div class="inventory-page-header">
            <div>
                <span class="inventory-eyebrow">SMO Inventory Control</span>
                <h3>Manage institutional stock</h3>
                <p>Monitor availability, organize request tiers, and prioritize items that need restocking.</p>
            </div>
            <a href="{{ route('inventory.create') }}" class="btn btn-htc inventory-add-btn"><i data-lucide="plus"></i> Register new item</a>
        </div>

        <div class="inventory-metrics">
            <div><span class="metric-icon total"><i data-lucide="boxes"></i></span><p>Total catalog</p><strong>{{ $totalItems }}</strong><small>registered items</small></div>
            <div><span class="metric-icon minor"><i data-lucide="shopping-basket"></i></span><p>Minor tier</p><strong>{{ $minorItems->count() }}</strong><small>routine supplies</small></div>
            <div><span class="metric-icon major"><i data-lucide="monitor-up"></i></span><p>Major tier</p><strong>{{ $majorItems->count() }}</strong><small>equipment & assets</small></div>
            <div class="{{ $attentionItems->isNotEmpty() ? 'attention' : '' }}"><span class="metric-icon warning"><i data-lucide="triangle-alert"></i></span><p>Needs attention</p><strong>{{ $attentionItems->count() }}</strong><small>{{ $outOfStockItems }} out · {{ $lowStockItems }} low</small></div>
            <div><span class="metric-icon value"><i data-lucide="badge-dollar-sign"></i></span><p>Stock value</p><strong>₱{{ number_format($inventoryValue, 0) }}</strong><small>estimated on hand</small></div>
        </div>

        @if($attentionItems->isNotEmpty())
            <section class="restock-section">
                <div class="section-heading">
                    <div><span class="section-icon warning"><i data-lucide="siren"></i></span><div><h5>Restock priority</h5><p>Low and unavailable items are surfaced here first.</p></div></div>
                    <span class="priority-count">{{ $attentionItems->count() }} need attention</span>
                </div>
                <div class="restock-grid">
                    @foreach($attentionItems as $item)
                        <a href="{{ route('inventory.edit', $item->id) }}" class="restock-card {{ $item->quantity <= 0 ? 'out' : 'low' }}">
                            <span class="restock-status">{{ $item->quantity <= 0 ? 'Out of stock' : 'Low stock' }}</span>
                            <strong>{{ $item->item_name }}</strong>
                            <small>{{ strtoupper($item->requisition_tier ?? 'minor') }} · {{ $item->category }}</small>
                            <div><b>{{ $item->quantity }} {{ $item->unit }}</b><span>Threshold: {{ $item->min_stock_level }}</span></div>
                            <em>Update stock <i data-lucide="arrow-up-right"></i></em>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="catalog-section">
            <div class="catalog-controls">
                <div>
                    <h5>Tier catalogs</h5>
                    <p>Out-of-stock and low-stock items always appear at the top of each list.</p>
                </div>
                <div class="inventory-filters">
                    <div class="inventory-search"><i data-lucide="search"></i><input type="search" x-model="query" placeholder="Search item, brand, or category…"></div>
                    <select x-model="stockFilter" aria-label="Filter by stock status"><option value="all">All stock</option><option value="attention">Needs attention</option><option value="out">Out of stock</option><option value="low">Low stock</option><option value="healthy">Healthy stock</option></select>
                </div>
            </div>

            <div class="tier-tabs" role="tablist">
                <button class="active" data-bs-toggle="pill" data-bs-target="#minorInventory" type="button" role="tab"><span class="tab-icon minor"><i data-lucide="shopping-basket"></i></span><span><small>Everyday supplies</small><strong>Minor catalog</strong></span><em>{{ $minorItems->count() }}</em></button>
                <button data-bs-toggle="pill" data-bs-target="#majorInventory" type="button" role="tab"><span class="tab-icon major"><i data-lucide="monitor-up"></i></span><span><small>Equipment & assets</small><strong>Major catalog</strong></span><em>{{ $majorItems->count() }}</em></button>
            </div>

            <div class="tab-content inventory-tab-content">
                <div class="tab-pane fade show active" id="minorInventory" role="tabpanel">
                    @include('inventory.partials.tier-table', ['items' => $minorItems, 'tier' => 'minor'])
                </div>
                <div class="tab-pane fade" id="majorInventory" role="tabpanel">
                    @include('inventory.partials.tier-table', ['items' => $majorItems, 'tier' => 'major'])
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-body p-4 text-center">
            <div class="delete-icon"><i data-lucide="trash-2"></i></div><h5 class="fw-bold">Remove inventory item?</h5>
            <p class="text-muted small">This removes <strong id="deleteItemName"></strong> from the request catalog.</p>
            <form id="deleteForm" method="POST">@csrf @method('DELETE')<div class="d-grid gap-2"><button type="submit" class="btn btn-danger fw-bold py-2 rounded-3">Remove item</button><button type="button" class="btn btn-light fw-bold py-2 rounded-3 border" data-bs-dismiss="modal">Keep item</button></div></form>
        </div></div></div>
    </div>

    <style>
        .inventory-workspace { padding: .5rem; }
        .inventory-page-header { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem; }
        .inventory-eyebrow { color: var(--htc-green); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .inventory-page-header h3 { margin: .25rem 0; color: #17251d; font-weight: 800; }
        .inventory-page-header p, .catalog-controls p, .section-heading p { margin: 0; color: #7b8981; font-size: .78rem; }
        .inventory-add-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 1.15rem; border-radius: 12px; white-space: nowrap; }
        .inventory-add-btn svg { width: 17px; }
        .inventory-metrics { display: grid; grid-template-columns: repeat(5, 1fr); gap: .85rem; margin-bottom: 1.25rem; }
        .inventory-metrics > div { position: relative; padding: 1rem; border: 1px solid #e3eae6; border-radius: 15px; background: #fff; box-shadow: 0 5px 16px rgba(24, 55, 36, .04); }
        .inventory-metrics > div.attention { border-color: #f4c6c8; background: #fffafa; }
        .inventory-metrics p { margin: .75rem 0 .1rem; color: #738078; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .inventory-metrics strong { display: block; color: #1f3026; font-size: 1.35rem; line-height: 1.2; }
        .inventory-metrics small { color: #96a199; font-size: .63rem; }
        .metric-icon, .section-icon, .tab-icon { display: grid; place-items: center; border-radius: 10px; }
        .metric-icon { width: 34px; height: 34px; }
        .metric-icon svg { width: 17px; }
        .metric-icon.total { color: #355da8; background: #eaf0fb; }.metric-icon.minor { color: #17643d; background: #e6f5ec; }.metric-icon.major { color: #95610a; background: #fff3d9; }.metric-icon.warning { color: #b52d35; background: #fdebec; }.metric-icon.value { color: #6a45a5; background: #f1eafa; }
        .restock-section, .catalog-section { border: 1px solid #e2e9e5; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(24, 55, 36, .05); overflow: hidden; }
        .restock-section { margin-bottom: 1.25rem; }
        .section-heading, .catalog-controls { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.4rem; }
        .section-heading > div { display: flex; align-items: center; gap: .75rem; }.section-heading h5, .catalog-controls h5 { margin: 0 0 .2rem; color: #24372c; font-weight: 800; }
        .section-icon { width: 38px; height: 38px; color: #b52d35; background: #fdebec; }.section-icon svg { width: 18px; }
        .priority-count { padding: .35rem .7rem; border-radius: 999px; background: #fdebec; color: #a72a31; font-size: .66rem; font-weight: 800; }
        .restock-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; padding: 0 1.4rem 1.4rem; }
        .restock-card { display: flex; flex-direction: column; min-height: 150px; padding: 1rem; border: 1px solid #f2d4d5; border-radius: 13px; color: inherit; text-decoration: none; background: #fffafa; transition: .2s; }.restock-card.low { border-color: #f2dfad; background: #fffcf4; }.restock-card:hover { color: inherit; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(58, 44, 20, .08); }
        .restock-status { align-self: flex-start; margin-bottom: .65rem; color: #b52d35; font-size: .62rem; font-weight: 800; text-transform: uppercase; }.restock-card.low .restock-status { color: #a36a09; }.restock-card strong { color: #26372e; }.restock-card > small { color: #89958e; font-size: .62rem; }.restock-card > div { display: flex; justify-content: space-between; margin-top: auto; padding-top: .75rem; font-size: .68rem; }.restock-card > div b { color: #b52d35; }.restock-card.low > div b { color: #9b680d; }.restock-card em { margin-top: .55rem; color: var(--htc-green); font-size: .66rem; font-style: normal; font-weight: 700; }.restock-card em svg { width: 12px; }
        .catalog-controls { border-bottom: 1px solid #edf1ef; }
        .inventory-filters { display: flex; gap: .6rem; }.inventory-search { width: 280px; display: flex; align-items: center; gap: .5rem; padding: 0 .75rem; border: 1px solid #dce4df; border-radius: 10px; background: #f9fbfa; }.inventory-search svg { width: 15px; color: #8b9991; }.inventory-search input { width: 100%; padding: .65rem 0; border: 0; outline: 0; background: transparent; font-size: .75rem; }.inventory-filters select { padding: .6rem 2rem .6rem .75rem; border: 1px solid #dce4df; border-radius: 10px; color: #58675f; background-color: #fff; font-size: .72rem; }
        .tier-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; padding: 1rem 1.4rem; background: #f8faf9; }.tier-tabs button { display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem; border: 1px solid #dfe7e2; border-radius: 12px; background: #fff; text-align: left; color: #65736b; }.tier-tabs button.active { border-color: var(--htc-green); box-shadow: inset 0 0 0 1px var(--htc-green); }.tab-icon { width: 38px; height: 38px; }.tab-icon svg { width: 18px; }.tab-icon.minor { color: #17643d; background: #e6f5ec; }.tab-icon.major { color: #95610a; background: #fff3d9; }.tier-tabs button > span:nth-child(2) { display: flex; flex-direction: column; }.tier-tabs small { font-size: .6rem; text-transform: uppercase; }.tier-tabs strong { color: #26372e; }.tier-tabs em { margin-left: auto; width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; background: #eef2ef; font-size: .7rem; font-style: normal; font-weight: 800; }
        .inventory-table thead th { padding-top: .85rem; padding-bottom: .85rem; border-bottom: 1px solid #e8eeea; background: #fff; color: #8a978f; font-size: .62rem; letter-spacing: .06em; text-transform: uppercase; }.inventory-table td { padding-top: .9rem; padding-bottom: .9rem; border-color: #edf1ef; }.inventory-row:hover { background: #fafcfb; }.stock-out-row { background: #fffafa; }.stock-low-row { background: #fffdf8; }
        .inventory-item-icon { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 11px; }.inventory-item-icon svg { width: 19px; }.inventory-item-icon.minor { color: #17643d; background: #e8f5ed; }.inventory-item-icon.major { color: #95610a; background: #fff3da; }.item-specification { max-width: 280px; margin-top: .25rem; color: #8a958f; font-size: .66rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .inventory-category { display: inline-block; padding: .28rem .55rem; border-radius: 999px; background: #f0f3f1; color: #536159; font-size: .64rem; font-weight: 700; }.option-preview { display: flex; flex-direction: column; gap: .15rem; margin-top: .45rem; color: #859189; font-size: .62rem; }
        .stock-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .6rem; border-radius: 999px; font-size: .65rem; font-weight: 800; }.stock-badge svg { width: 13px; }.stock-badge.out { color: #ac2730; background: #fde9ea; }.stock-badge.low { color: #96610a; background: #fff0cc; }.stock-badge.healthy { color: #17643d; background: #e5f5eb; }.stock-threshold { display: block; margin-top: .3rem; color: #9aa39e; font-size: .6rem; }
        .inventory-action { width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; border: 1px solid #dce4df; border-radius: 9px; color: #5f6f66; background: #fff; }.inventory-action svg { width: 14px; }.inventory-action:hover { border-color: var(--htc-green); color: var(--htc-green); background: #eff7f2; }.inventory-action.danger:hover { border-color: #dc3545; color: #dc3545; background: #fff5f5; }
        .inventory-empty { height: 220px; text-align: center; color: #94a3b8; }.inventory-empty svg { display: block; width: 34px; margin: 0 auto .5rem; }.inventory-empty strong, .inventory-empty span { display: block; }.delete-icon { width: 58px; height: 58px; display: grid; place-items: center; margin: 0 auto 1rem; border-radius: 50%; color: #b52d35; background: #fdebec; }.delete-icon svg { width: 25px; }
        @media(max-width: 1199px) { .inventory-metrics { grid-template-columns: repeat(3, 1fr); }.restock-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 767px) { .inventory-page-header, .catalog-controls { align-items: stretch; flex-direction: column; }.inventory-add-btn { justify-content: center; }.inventory-metrics { grid-template-columns: repeat(2, 1fr); }.inventory-filters { flex-direction: column; }.inventory-search { width: 100%; }.tier-tabs { grid-template-columns: 1fr; }.restock-grid { grid-template-columns: 1fr; } }
    </style>

    <script>
        function inventoryWorkspace() {
            return {
                query: '',
                stockFilter: 'all',
                matches(text, status) {
                    const matchesSearch = !this.query.trim() || text.includes(this.query.trim().toLowerCase());
                    const matchesStatus = this.stockFilter === 'all'
                        || (this.stockFilter === 'attention' && ['out', 'low'].includes(status))
                        || this.stockFilter === status;
                    return matchesSearch && matchesStatus;
                }
            };
        }

        function confirmDelete(url, name) {
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteForm').action = url;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
            setTimeout(() => lucide.createIcons(), 50);
        }
    </script>
</x-app-layout>
