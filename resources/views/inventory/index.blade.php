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
        $forecastRecommendations = $forecast['recommendations']->take(8);
        $catalogSelectionData = $supplies->map(function ($item) {
            $status = $item->quantity <= 0 ? 'out' : ($item->quantity <= $item->min_stock_level ? 'low' : 'healthy');

            return [
                'id' => $item->id,
                'tier' => $item->requisition_tier ?? 'minor',
                'status' => $status,
                'text' => strtolower(implode(' ', [$item->item_name, $item->brand, $item->model_number, $item->category, $item->specifications])),
            ];
        })->values();
    @endphp

    <div class="inventory-workspace" x-data="inventoryWorkspace({{ Illuminate\Support\Js::from($catalogSelectionData) }})">
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

        <nav class="inventory-section-nav" aria-label="Inventory workspace sections">
            <a href="#inventory-overview"><span>01</span><i data-lucide="layout-dashboard"></i><div><strong>Overview</strong><small>Stock at a glance</small></div></a>
            @if($attentionItems->isNotEmpty())
                <a href="#stock-alerts" class="has-alert"><span>02</span><i data-lucide="siren"></i><div><strong>Restock</strong><small>{{ $attentionItems->count() }} item(s) need action</small></div></a>
            @endif
            <a href="#stock-catalog"><span>{{ $attentionItems->isNotEmpty() ? '03' : '02' }}</span><i data-lucide="list-tree"></i><div><strong>Catalog</strong><small>Search and update items</small></div></a>
            <a href="#demand-forecast"><span>{{ $attentionItems->isNotEmpty() ? '04' : '03' }}</span><i data-lucide="chart-no-axes-combined"></i><div><strong>Forecast</strong><small>Plan future purchases</small></div></a>
        </nav>

        <section class="inventory-overview-section" id="inventory-overview">
            <div class="workspace-section-heading">
                <div><span class="section-number">01</span><div><span class="inventory-eyebrow">Inventory overview</span><h4>Stock at a glance</h4></div></div>
                <p>A quick summary of catalog size, availability, and current value.</p>
            </div>
            <div class="inventory-metrics">
                <div><span class="metric-icon total"><i data-lucide="boxes"></i></span><p>Total catalog</p><strong>{{ $totalItems }}</strong><small>registered items</small></div>
                <div><span class="metric-icon minor"><i data-lucide="shopping-basket"></i></span><p>Minor tier</p><strong>{{ $minorItems->count() }}</strong><small>routine supplies</small></div>
                <div><span class="metric-icon major"><i data-lucide="monitor-up"></i></span><p>Major tier</p><strong>{{ $majorItems->count() }}</strong><small>equipment & assets</small></div>
                <div class="{{ $attentionItems->isNotEmpty() ? 'attention' : '' }}"><span class="metric-icon warning"><i data-lucide="triangle-alert"></i></span><p>Needs attention</p><strong>{{ $attentionItems->count() }}</strong><small>{{ $outOfStockItems }} out · {{ $lowStockItems }} low</small></div>
                <div><span class="metric-icon value"><i data-lucide="badge-dollar-sign"></i></span><p>Stock value</p><strong>₱{{ number_format($inventoryValue, 0) }}</strong><small>estimated on hand</small></div>
            </div>
        </section>

        <section class="forecast-section" id="demand-forecast">
            <div class="forecast-header">
                <div class="forecast-heading">
                    <span class="section-number">{{ $attentionItems->isNotEmpty() ? '04' : '03' }}</span>
                    <span class="forecast-icon"><i data-lucide="chart-no-axes-combined"></i></span>
                    <div>
                        <span class="inventory-eyebrow">Demand intelligence</span>
                        <h5>{{ $forecast['period_label'] }} purchase forecast</h5>
                        <p>Suggested orders use actual SMO releases, seasonal demand, recent consumption, and safety stock.</p>
                    </div>
                </div>
                <div class="forecast-readiness">
                    <form method="GET" action="{{ route('inventory.index') }}" class="forecast-period-form">
                        <label for="forecastPeriod">Report period</label>
                        <select id="forecastPeriod" name="forecast_period">
                            <option value="monthly" @selected($forecast['period'] === 'monthly')>Monthly</option>
                            <option value="quarterly" @selected($forecast['period'] === 'quarterly')>Quarterly</option>
                        </select>
                        <button type="submit"><i data-lucide="sparkles"></i> Generate</button>
                    </form>
                    <a class="forecast-download" href="{{ route('inventory.forecast.report', ['period' => $forecast['period']]) }}">
                        <i data-lucide="file-down"></i> PDF report
                    </a>
                    <span><b>{{ $forecast['items_with_history'] }}</b> items learned</span>
                    <span><b>{{ $forecast['release_events'] }}</b> release records</span>
                    <details>
                        <summary>How it works</summary>
                        <div>The estimate blends the last three months of released quantities with the same calendar month from prior years. Quarterly reports combine three upcoming monthly forecasts, add one safety allowance, then deduct current stock. Low-confidence labels mean the system still needs more release history.</div>
                    </details>
                </div>
            </div>

            @if($forecastRecommendations->isNotEmpty())
                <div class="forecast-callout"><i data-lucide="lightbulb"></i><span><strong>Planning signal:</strong> Consider purchasing these items before the {{ strtolower($forecast['period']) }} demand period begins. Estimated purchase budget: <strong>₱{{ number_format($forecast['estimated_budget'], 2) }}</strong>.</span></div>
                <div class="forecast-grid">
                    @foreach($forecastRecommendations as $prediction)
                        @php($item = $prediction['supply'])
                        <article class="forecast-card {{ $prediction['urgency'] }}">
                            <div class="forecast-card-top">
                                <span class="forecast-urgency">
                                    {{ $prediction['urgency'] === 'critical' ? 'Critical' : ($prediction['urgency'] === 'buy_now' ? 'Buy now' : 'Plan purchase') }}
                                </span>
                                <span class="forecast-confidence {{ $prediction['confidence'] }}">{{ ucfirst($prediction['confidence']) }} confidence</span>
                            </div>
                            <div class="forecast-item-name">{{ $item->item_name }}</div>
                            <small>{{ strtoupper($item->requisition_tier ?? 'minor') }} · {{ $item->category }}</small>
                            <div class="forecast-quantity"><span>Suggested purchase</span><strong>{{ $prediction['recommended_order'] }} <em>{{ $item->unit }}</em></strong></div>
                            <div class="forecast-facts">
                                <div><span>Expected use</span><b>{{ $prediction['forecast_demand'] }}</b></div>
                                <div><span>On hand</span><b>{{ $item->quantity }}</b></div>
                                <div><span>Peak month</span><b>{{ $prediction['peak_month'] ?? 'Learning' }}</b></div>
                            </div>
                            <p><i data-lucide="sparkles"></i>{{ $prediction['reason'] }}</p>
                            <div class="forecast-card-footer"><span>{{ $prediction['months_observed'] }} month(s) observed</span><a href="{{ route('inventory.edit', $item->id) }}">Review item <i data-lucide="arrow-right"></i></a></div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="forecast-empty">
                    <span><i data-lucide="circle-check-big"></i></span>
                    <div><strong>No forecasted purchase gap for {{ $forecast['period_label'] }}</strong><p>Current quantities cover expected consumption and configured safety stock. The forecast will keep learning from each SMO release.</p></div>
                </div>
            @endif
        </section>

        @if($attentionItems->isNotEmpty())
            <section class="restock-section" id="stock-alerts">
                <div class="section-heading">
                    <div><span class="section-number">02</span><span class="section-icon warning"><i data-lucide="siren"></i></span><div><span class="inventory-eyebrow">Action required</span><h5>Restock priority</h5><p>Low and unavailable items are surfaced here first.</p></div></div>
                    <span class="priority-count">{{ $attentionItems->count() }} need attention</span>
                </div>
                <div class="restock-grid">
                    @foreach($attentionItems as $item)
                        <a href="{{ route('inventory.edit', $item->id) }}"
                           class="restock-card {{ $item->quantity <= 0 ? 'out' : 'low' }}"
                           @if($loop->index >= 4) x-cloak x-show="showAllRestock" x-transition.opacity @endif>
                            <span class="restock-status">{{ $item->quantity <= 0 ? 'Out of stock' : 'Low stock' }}</span>
                            <strong>{{ $item->item_name }}</strong>
                            <small>{{ strtoupper($item->requisition_tier ?? 'minor') }} · {{ $item->category }}</small>
                            <div><b>{{ $item->quantity }} {{ $item->unit }}</b><span>Threshold: {{ $item->min_stock_level }}</span></div>
                            <em>Update stock <i data-lucide="arrow-up-right"></i></em>
                        </a>
                    @endforeach
                </div>
                @if($attentionItems->count() > 4)
                    <div class="restock-more-bar">
                        <span x-show="!showAllRestock">Showing 4 of {{ $attentionItems->count() }} priority items</span>
                        <span x-cloak x-show="showAllRestock">Showing all {{ $attentionItems->count() }} priority items</span>
                        <button type="button" @click="showAllRestock = !showAllRestock" :aria-expanded="showAllRestock.toString()">
                            <span x-text="showAllRestock ? 'Show less' : 'See {{ $attentionItems->count() - 4 }} more'"></span>
                            <i data-lucide="chevron-down" :class="{ 'is-open': showAllRestock }"></i>
                        </button>
                    </div>
                @endif
            </section>
        @endif

        <section class="catalog-section" id="stock-catalog">
            <div class="catalog-controls">
                <div>
                    <span class="inventory-eyebrow">{{ $attentionItems->isNotEmpty() ? '03' : '02' }} · Catalog management</span>
                    <h5>Tier catalogs</h5>
                    <p>Out-of-stock and low-stock items always appear at the top of each list.</p>
                </div>
                <div class="inventory-filters">
                    <div class="inventory-search"><i data-lucide="search"></i><input type="search" x-model.debounce.150ms="query" placeholder="Search item, brand, model, category, or specifications…" aria-label="Search inventory"></div>
                    <select x-model="stockFilter" aria-label="Filter by stock status"><option value="all">All stock</option><option value="attention">Needs attention</option><option value="out">Out of stock</option><option value="low">Low stock</option><option value="healthy">Healthy stock</option></select>
                </div>
            </div>

            <div class="tier-tabs" role="tablist">
                <button class="active" data-bs-toggle="pill" data-bs-target="#minorInventory" type="button" role="tab"><span class="tab-icon minor"><i data-lucide="shopping-basket"></i></span><span><small>Everyday supplies</small><strong>Minor catalog</strong></span><em>{{ $minorItems->count() }}</em></button>
                <button data-bs-toggle="pill" data-bs-target="#majorInventory" type="button" role="tab"><span class="tab-icon major"><i data-lucide="monitor-up"></i></span><span><small>Equipment & assets</small><strong>Major catalog</strong></span><em>{{ $majorItems->count() }}</em></button>
            </div>

            <div class="inventory-bulk-bar" x-cloak x-show="selected.length > 0" x-transition>
                <div>
                    <span class="bulk-count" x-text="selected.length"></span>
                    <strong x-text="selected.length === 1 ? 'item selected' : 'items selected'"></strong>
                    <small>Selection is kept when switching tiers or changing filters.</small>
                </div>
                <div class="inventory-bulk-actions">
                    <button type="button" class="btn bulk-clear" @click="clearSelection()">Clear selection</button>
                    <button type="button" class="btn bulk-delete" @click="confirmBulkDelete()"><i data-lucide="trash-2"></i> Delete selected</button>
                </div>
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

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-body p-4 text-center">
            <div class="delete-icon"><i data-lucide="trash-2"></i></div><h5 class="fw-bold">Remove inventory item?</h5>
            <p class="text-muted small">This removes <strong id="deleteItemName"></strong> from the request catalog.</p>
            <form id="deleteForm" method="POST">@csrf @method('DELETE')<div class="d-grid gap-2"><button type="submit" class="btn btn-danger fw-bold py-2 rounded-3">Remove item</button><button type="button" class="btn btn-light fw-bold py-2 rounded-3 border" data-bs-dismiss="modal">Keep item</button></div></form>
        </div></div></div>
    </div>

    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-body p-4 text-center">
            <div class="delete-icon"><i data-lucide="trash-2"></i></div><h5 class="fw-bold">Remove selected items?</h5>
            <p class="text-muted small">This removes <strong x-text="selected.length"></strong> selected <span x-text="selected.length === 1 ? 'item' : 'items'"></span> from the request catalog.</p>
            <form method="POST" action="{{ route('inventory.bulk-destroy') }}">
                @csrf
                @method('DELETE')
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <div class="d-grid gap-2"><button type="submit" class="btn btn-danger fw-bold py-2 rounded-3">Remove selected items</button><button type="button" class="btn btn-light fw-bold py-2 rounded-3 border" data-bs-dismiss="modal">Keep items</button></div>
            </form>
        </div></div></div>
    </div>
    </div>

    @pushOnce('styles', 'page-styles:inventory/index.blade.php')
    <style>
        .inventory-workspace { display: flex; flex-direction: column; padding: .5rem; }
        .inventory-page-header { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem; }
        .inventory-eyebrow { color: var(--htc-green); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .inventory-page-header h3 { margin: .25rem 0; color: #17251d; font-weight: 800; }
        .inventory-page-header p, .catalog-controls p, .section-heading p { margin: 0; color: #7b8981; font-size: .78rem; }
        .inventory-add-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 1.15rem; border-radius: 12px; white-space: nowrap; }
        .inventory-add-btn svg { width: 17px; }
        .inventory-section-nav { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .75rem; margin-bottom: 1.75rem; padding: .5rem; border: 1px solid #dfe8e2; border-radius: 16px; background: #f5f8f6; }
        .inventory-section-nav > a { min-width: 0; display: grid; grid-template-columns: 24px 34px minmax(0, 1fr); align-items: center; gap: .65rem; padding: .75rem; border: 1px solid transparent; border-radius: 11px; color: #53645a; background: transparent; text-decoration: none; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
        .inventory-section-nav > a:hover { border-color: #d5e3da; color: #244c35; background: #fff; transform: translateY(-1px); }
        .inventory-section-nav > a:focus-visible { outline: 3px solid rgba(35, 113, 73, .2); outline-offset: 2px; }
        .inventory-section-nav > a > span { color: #9aa69e; font-size: .58rem; font-weight: 900; letter-spacing: .08em; }
        .inventory-section-nav > a > svg { width: 17px; height: 17px; color: var(--htc-green); }
        .inventory-section-nav > a.has-alert > svg { color: #b52d35; }
        .inventory-section-nav strong, .inventory-section-nav small { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .inventory-section-nav strong { color: #2b4034; font-size: .72rem; }
        .inventory-section-nav small { margin-top: .1rem; color: #8b978f; font-size: .6rem; }
        .inventory-overview-section { margin-bottom: 2rem; padding: 1.35rem; border: 1px solid #e1e9e4; border-radius: 18px; background: linear-gradient(145deg, #f8faf9, #fff); box-shadow: 0 8px 24px rgba(24, 55, 36, .04); scroll-margin-top: 1rem; }
        .workspace-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .workspace-section-heading > div { display: flex; align-items: center; gap: .8rem; }
        .workspace-section-heading h4 { margin: .1rem 0 0; color: #203128; font-size: 1rem; font-weight: 800; }
        .workspace-section-heading p { max-width: 430px; margin: 0; color: #7f8d85; font-size: .7rem; text-align: right; }
        .section-number { width: 32px; height: 32px; flex: 0 0 32px; display: grid; place-items: center; border: 1px solid #d5e3da; border-radius: 9px; color: #397451; background: #f1f7f3; font-size: .6rem; font-weight: 900; letter-spacing: .06em; }
        .inventory-metrics { display: grid; grid-template-columns: repeat(5, 1fr); gap: .85rem; }
        .inventory-metrics > div { position: relative; padding: 1rem; border: 1px solid #e3eae6; border-radius: 15px; background: #fff; box-shadow: 0 5px 16px rgba(24, 55, 36, .04); }
        .inventory-metrics > div.attention { border-color: #f4c6c8; background: #fffafa; }
        .inventory-metrics p { margin: .75rem 0 .1rem; color: #738078; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
        .inventory-metrics strong { display: block; color: #1f3026; font-size: 1.35rem; line-height: 1.2; }
        .inventory-metrics small { color: #96a199; font-size: .63rem; }
        .metric-icon, .section-icon, .tab-icon { display: grid; place-items: center; border-radius: 10px; }
        .metric-icon { width: 34px; height: 34px; }
        .metric-icon svg { width: 17px; }
        .metric-icon.total { color: #355da8; background: #eaf0fb; }.metric-icon.minor { color: #17643d; background: #e6f5ec; }.metric-icon.major { color: #95610a; background: #fff3d9; }.metric-icon.warning { color: #b52d35; background: #fdebec; }.metric-icon.value { color: #6a45a5; background: #f1eafa; }
        .forecast-section { order: 3; margin-bottom: 1.25rem; border: 1px solid #dce8e0; border-radius: 18px; background: linear-gradient(145deg, #f5faf7, #fff 55%); box-shadow: 0 8px 24px rgba(24, 55, 36, .06); overflow: hidden; scroll-margin-top: 1rem; }
        .forecast-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; padding: 1.35rem 1.4rem 1rem; }
        .forecast-heading { display: flex; align-items: center; gap: .85rem; }.forecast-heading h5 { margin: .15rem 0 .2rem; color: #20352a; font-weight: 800; }.forecast-heading p { margin: 0; color: #7b8981; font-size: .72rem; }
        .forecast-icon { width: 46px; height: 46px; flex: 0 0 46px; display: grid; place-items: center; border-radius: 13px; background: #dff1e6; color: var(--htc-green); }.forecast-icon svg { width: 22px; }
        .forecast-readiness { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .55rem; }.forecast-readiness > span { padding: .45rem .65rem; border: 1px solid #dde7e1; border-radius: 9px; background: #fff; color: #7a8880; font-size: .64rem; white-space: nowrap; }.forecast-readiness b { color: #2d4839; }
        .forecast-period-form { display: flex; align-items: center; gap: .4rem; padding: .3rem; border: 1px solid #d9e5dd; border-radius: 10px; background: #fff; }.forecast-period-form label { padding-left: .35rem; color: #66766c; font-size: .62rem; font-weight: 800; text-transform: uppercase; }.forecast-period-form select { min-height: 32px; padding: .25rem 1.6rem .25rem .45rem; border: 1px solid #dfe7e2; border-radius: 7px; color: #405248; background-color: #f8faf9; font-size: .68rem; }.forecast-period-form button, .forecast-download { min-height: 32px; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; padding: .35rem .65rem; border: 0; border-radius: 7px; color: #fff; background: var(--htc-green); font-size: .65rem; font-weight: 800; text-decoration: none; white-space: nowrap; }.forecast-period-form button svg, .forecast-download svg { width: 13px; height: 13px; }.forecast-download { background: #243d30; }
        .forecast-readiness details { position: relative; }.forecast-readiness summary { cursor: pointer; list-style: none; padding: .45rem .65rem; border-radius: 9px; background: var(--htc-green); color: #fff; font-size: .64rem; font-weight: 700; }.forecast-readiness details > div { position: absolute; z-index: 20; top: calc(100% + .5rem); right: 0; width: 290px; padding: .85rem; border: 1px solid #dbe5df; border-radius: 11px; background: #fff; color: #66756c; font-size: .68rem; line-height: 1.55; box-shadow: 0 12px 28px rgba(25, 52, 35, .14); }
        .forecast-callout { display: flex; align-items: center; gap: .55rem; margin: 0 1.4rem 1rem; padding: .7rem .85rem; border-radius: 10px; background: #eaf5ee; color: #39634b; font-size: .7rem; }.forecast-callout svg { width: 16px; color: #267047; }
        .forecast-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; padding: 0 1.4rem 1.4rem; }
        .forecast-card { display: flex; flex-direction: column; min-height: 260px; padding: 1rem; border: 1px solid #dfe8e3; border-top: 3px solid #6c8e78; border-radius: 13px; background: #fff; }.forecast-card.critical { border-top-color: #c63c44; }.forecast-card.buy_now { border-top-color: #dc8a18; }
        .forecast-card-top { display: flex; justify-content: space-between; align-items: center; gap: .35rem; margin-bottom: .7rem; }.forecast-urgency, .forecast-confidence { font-size: .56rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }.forecast-urgency { color: #65766c; }.critical .forecast-urgency { color: #bd3038; }.buy_now .forecast-urgency { color: #a8690e; }.forecast-confidence { padding: .25rem .4rem; border-radius: 999px; background: #eef2ef; color: #718078; }.forecast-confidence.high { background: #e2f3e8; color: #17643d; }.forecast-confidence.medium { background: #fff0ce; color: #8e5b08; }
        .forecast-item-name { color: #203128; font-weight: 800; }.forecast-card > small { color: #8a968f; font-size: .6rem; }
        .forecast-quantity { margin: .9rem 0; padding: .75rem; border-radius: 10px; background: #f5f8f6; }.forecast-quantity span { display: block; color: #839087; font-size: .58rem; font-weight: 700; text-transform: uppercase; }.forecast-quantity strong { display: block; margin-top: .15rem; color: var(--htc-green); font-size: 1.35rem; }.forecast-quantity em { font-size: .62rem; font-style: normal; font-weight: 600; }
        .forecast-facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: .35rem; }.forecast-facts div { min-width: 0; }.forecast-facts span { display: block; color: #98a19c; font-size: .54rem; text-transform: uppercase; }.forecast-facts b { display: block; color: #405148; font-size: .68rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .forecast-card > p { display: flex; gap: .4rem; margin: .8rem 0; color: #6f7e75; font-size: .64rem; line-height: 1.45; }.forecast-card > p svg { width: 13px; height: 13px; flex: 0 0 13px; color: #71927d; }
        .forecast-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: .65rem; border-top: 1px solid #edf1ef; font-size: .58rem; }.forecast-card-footer span { color: #96a199; }.forecast-card-footer a { display: flex; align-items: center; gap: .25rem; color: var(--htc-green); text-decoration: none; font-weight: 700; }.forecast-card-footer svg { width: 11px; }
        .forecast-empty { display: flex; align-items: center; gap: .9rem; margin: 0 1.4rem 1.4rem; padding: 1.2rem; border: 1px dashed #cfe0d5; border-radius: 13px; background: #fff; }.forecast-empty > span { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 50%; color: #277148; background: #e5f5eb; }.forecast-empty svg { width: 20px; }.forecast-empty strong { color: #284033; }.forecast-empty p { margin: .2rem 0 0; color: #829087; font-size: .7rem; }
        .restock-section, .catalog-section { border: 1px solid #e2e9e5; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(24, 55, 36, .05); overflow: hidden; scroll-margin-top: 1rem; }
        .restock-section { order: 1; margin-bottom: 2rem; }
        .catalog-section { order: 2; margin-bottom: 2rem; }
        .section-heading, .catalog-controls { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.4rem; }
        .section-heading > div { display: flex; align-items: center; gap: .75rem; }.section-heading h5, .catalog-controls h5 { margin: .12rem 0 .2rem; color: #24372c; font-weight: 800; }
        .section-icon { width: 38px; height: 38px; color: #b52d35; background: #fdebec; }.section-icon svg { width: 18px; }
        .priority-count { padding: .35rem .7rem; border-radius: 999px; background: #fdebec; color: #a72a31; font-size: .66rem; font-weight: 800; }
        .restock-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; padding: 0 1.4rem 1.4rem; }
        .restock-card { display: flex; flex-direction: column; min-height: 150px; padding: 1rem; border: 1px solid #f2d4d5; border-radius: 13px; color: inherit; text-decoration: none; background: #fffafa; transition: .2s; }.restock-card.low { border-color: #f2dfad; background: #fffcf4; }.restock-card:hover { color: inherit; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(58, 44, 20, .08); }
        .restock-status { align-self: flex-start; margin-bottom: .65rem; color: #b52d35; font-size: .62rem; font-weight: 800; text-transform: uppercase; }.restock-card.low .restock-status { color: #a36a09; }.restock-card strong { color: #26372e; }.restock-card > small { color: #89958e; font-size: .62rem; }.restock-card > div { display: flex; justify-content: space-between; margin-top: auto; padding-top: .75rem; font-size: .68rem; }.restock-card > div b { color: #b52d35; }.restock-card.low > div b { color: #9b680d; }.restock-card em { margin-top: .55rem; color: var(--htc-green); font-size: .66rem; font-style: normal; font-weight: 700; }.restock-card em svg { width: 12px; }
        .restock-more-bar { min-height: 52px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .65rem 1.4rem; border-top: 1px solid #e8eeea; background: #fafcfb; }
        .restock-more-bar > span { color: #849087; font-size: .65rem; }
        .restock-more-bar button { display: inline-flex; align-items: center; gap: .4rem; padding: .48rem .7rem; border: 1px solid #cfe0d5; border-radius: 9px; color: #286846; background: #fff; font-size: .65rem; font-weight: 800; }
        .restock-more-bar button:hover { border-color: #8eb39c; background: #f0f7f3; }
        .restock-more-bar button:focus-visible { outline: 3px solid rgba(35, 113, 73, .18); outline-offset: 2px; }
        .restock-more-bar svg { width: 14px; height: 14px; transition: transform .2s ease; }
        .restock-more-bar svg.is-open { transform: rotate(180deg); }
        .catalog-controls { border-bottom: 1px solid #edf1ef; }
        .inventory-filters { display: flex; gap: .6rem; }.inventory-search { width: 280px; display: flex; align-items: center; gap: .5rem; padding: 0 .75rem; border: 1px solid #dce4df; border-radius: 10px; background: #f9fbfa; }.inventory-search svg { width: 15px; color: #8b9991; }.inventory-search input { width: 100%; padding: .65rem 0; border: 0; outline: 0; background: transparent; font-size: .75rem; }.inventory-filters select { padding: .6rem 2rem .6rem .75rem; border: 1px solid #dce4df; border-radius: 10px; color: #58675f; background-color: #fff; font-size: .72rem; }
        .tier-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; padding: 1rem 1.4rem; background: #f8faf9; }.tier-tabs button { display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem; border: 1px solid #dfe7e2; border-radius: 12px; background: #fff; text-align: left; color: #65736b; }.tier-tabs button.active { border-color: var(--htc-green); box-shadow: inset 0 0 0 1px var(--htc-green); }.tab-icon { width: 38px; height: 38px; }.tab-icon svg { width: 18px; }.tab-icon.minor { color: #17643d; background: #e6f5ec; }.tab-icon.major { color: #95610a; background: #fff3d9; }.tier-tabs button > span:nth-child(2) { display: flex; flex-direction: column; }.tier-tabs small { font-size: .6rem; text-transform: uppercase; }.tier-tabs strong { color: #26372e; }.tier-tabs em { margin-left: auto; width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; background: #eef2ef; font-size: .7rem; font-style: normal; font-weight: 800; }
        .inventory-table thead th { padding-top: .85rem; padding-bottom: .85rem; border-bottom: 1px solid #e8eeea; background: #fff; color: #8a978f; font-size: .62rem; letter-spacing: .06em; text-transform: uppercase; }.inventory-table td { padding-top: .9rem; padding-bottom: .9rem; border-color: #edf1ef; }.inventory-row:hover { background: #fafcfb; }.stock-out-row { background: #fffafa; }.stock-low-row { background: #fffdf8; }
        .inventory-item-icon { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 11px; }.inventory-item-icon svg { width: 19px; }.inventory-item-icon.minor { color: #17643d; background: #e8f5ed; }.inventory-item-icon.major { color: #95610a; background: #fff3da; }.item-specification { max-width: 280px; margin-top: .25rem; color: #8a958f; font-size: .66rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .inventory-category { display: inline-block; padding: .28rem .55rem; border-radius: 999px; background: #f0f3f1; color: #536159; font-size: .64rem; font-weight: 700; }.option-preview { display: flex; flex-direction: column; gap: .15rem; margin-top: .45rem; color: #859189; font-size: .62rem; }
        .inventory-select-cell { width: 44px; text-align: center; }.inventory-select { width: 17px; height: 17px; cursor: pointer; accent-color: var(--htc-green); }.inventory-row.is-selected { background: #f0f8f3; box-shadow: inset 3px 0 #237149; }
        .inventory-bulk-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1.4rem; border-top: 1px solid #d8e7dd; border-bottom: 1px solid #d8e7dd; background: #f1f8f4; }.inventory-bulk-bar > div:first-child { display: flex; align-items: center; gap: .45rem; }.inventory-bulk-bar strong { color: #294638; font-size: .72rem; }.inventory-bulk-bar small { margin-left: .25rem; color: #78877f; font-size: .61rem; }.bulk-count { min-width: 27px; height: 27px; display: inline-grid; place-items: center; padding: 0 .4rem; border-radius: 999px; background: var(--htc-green); color: #fff; font-size: .66rem; font-weight: 900; }.inventory-bulk-actions { display: flex; gap: .45rem; }.inventory-bulk-actions .btn { min-height: 34px; padding: .4rem .65rem; border-radius: 8px; font-size: .62rem; font-weight: 800; }.bulk-clear { border: 1px solid #ccdbd1; color: #52675a; background: #fff; }.bulk-delete { display: inline-flex; align-items: center; gap: .35rem; color: #fff; background: #b82f38; }.bulk-delete:hover { color: #fff; background: #9f222b; }.bulk-delete svg { width: 13px; }
        .stock-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .6rem; border-radius: 999px; font-size: .65rem; font-weight: 800; }.stock-badge svg { width: 13px; }.stock-badge.out { color: #ac2730; background: #fde9ea; }.stock-badge.low { color: #96610a; background: #fff0cc; }.stock-badge.healthy { color: #17643d; background: #e5f5eb; }.stock-threshold { display: block; margin-top: .3rem; color: #9aa39e; font-size: .6rem; }
        .inventory-action { width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; border: 1px solid #dce4df; border-radius: 9px; color: #5f6f66; background: #fff; }.inventory-action svg { width: 14px; }.inventory-action:hover { border-color: var(--htc-green); color: var(--htc-green); background: #eff7f2; }.inventory-action.danger:hover { border-color: #dc3545; color: #dc3545; background: #fff5f5; }
        .inventory-empty { height: 220px; text-align: center; color: #94a3b8; }.inventory-empty svg { display: block; width: 34px; margin: 0 auto .5rem; }.inventory-empty strong, .inventory-empty span { display: block; }.delete-icon { width: 58px; height: 58px; display: grid; place-items: center; margin: 0 auto 1rem; border-radius: 50%; color: #b52d35; background: #fdebec; }.delete-icon svg { width: 25px; }
        @media(max-width: 1199px) { .inventory-section-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }.inventory-metrics { grid-template-columns: repeat(3, 1fr); }.restock-grid, .forecast-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 767px) {
            .inventory-workspace { padding: 0; }
            .inventory-page-header, .catalog-controls, .forecast-header { align-items: stretch; flex-direction: column; }
            .inventory-add-btn { min-height: 44px; justify-content: center; }
            .inventory-section-nav { gap: .5rem; margin-bottom: 1.25rem; padding: .4rem; border-radius: 14px; }
            .inventory-section-nav > a { grid-template-columns: 20px 26px minmax(0, 1fr); gap: .45rem; padding: .65rem .55rem; }
            .inventory-section-nav > a > svg { width: 15px; }
            .inventory-section-nav small { display: none; }
            .inventory-overview-section { margin-bottom: 1.25rem; padding: 1rem; }
            .workspace-section-heading { align-items: flex-start; flex-direction: column; }
            .workspace-section-heading p { text-align: left; }
            .inventory-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
            .inventory-metrics > div { padding: .85rem; }
            .inventory-metrics > div:last-child { grid-column: 1 / -1; }
            .forecast-header, .section-heading, .catalog-controls { padding: 1rem; }
            .forecast-heading { align-items: flex-start; }
            .forecast-readiness { flex-wrap: wrap; }
            .forecast-period-form { width: 100%; display: grid; grid-template-columns: auto 1fr auto; }
            .forecast-download { flex: 1; min-height: 40px; }
            .forecast-readiness > span { flex: 1; text-align: center; }
            .forecast-readiness details { flex-basis: 100%; }
            .forecast-readiness summary { min-height: 40px; display: grid; place-items: center; }
            .forecast-readiness details > div { position: static; width: 100%; margin-top: .5rem; }
            .forecast-callout { align-items: flex-start; margin: 0 1rem 1rem; }
            .forecast-grid, .restock-grid {
                grid-template-columns: none;
                grid-auto-flow: column;
                grid-auto-columns: minmax(270px, 86vw);
                gap: .75rem;
                padding: 0 1rem 1rem;
                overflow-x: auto;
                scroll-snap-type: x proximity;
                scrollbar-width: thin;
            }
            .forecast-card, .restock-card { scroll-snap-align: start; }
            .forecast-card { min-height: 245px; }
            .section-heading { align-items: flex-start; }
            .section-heading > div { align-items: flex-start; }
            .restock-section, .catalog-section { margin-bottom: 1.25rem; }
            .restock-more-bar { padding-inline: 1rem; }
            .priority-count { white-space: nowrap; }
            .inventory-filters { flex-direction: column; }
            .inventory-search { width: 100%; min-height: 44px; }
            .inventory-filters select { min-height: 44px; }
            .inventory-bulk-bar { align-items: stretch; flex-direction: column; padding: .75rem 1rem; }.inventory-bulk-bar > div:first-child { flex-wrap: wrap; }.inventory-bulk-bar small { flex-basis: 100%; margin-left: 0; }.inventory-bulk-actions { display: grid; grid-template-columns: 1fr 1fr; }.inventory-bulk-actions .btn { min-height: 42px; justify-content: center; }
            .tier-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; padding: .8rem; }
            .tier-tabs button { min-width: 0; padding: .7rem; }
            .tier-tabs button > span:nth-child(2) { min-width: 0; }
            .tier-tabs small { display: none; }
            .tier-tabs strong { font-size: .75rem; }
            .tier-tabs em { width: 24px; height: 24px; flex: 0 0 24px; }
            .tab-icon { width: 34px; height: 34px; flex: 0 0 34px; }

            .inventory-tab-content .table-responsive { overflow: visible; }
            .inventory-table { display: block; min-width: 0 !important; background: #f5f8f6; }
            .inventory-table thead { display: none; }
            .inventory-table tbody { display: grid; gap: .75rem; padding: .75rem; }
            .inventory-table tbody tr { display: block; overflow: hidden; border: 1px solid #e0e8e3; border-radius: 14px; background: #fff; box-shadow: 0 3px 12px rgba(24, 58, 37, .05); }
            .inventory-table tbody tr.stock-out-row { border-left: 4px solid #dc3545; }
            .inventory-table tbody tr.stock-low-row { border-left: 4px solid #e4a11b; }
            .inventory-table tbody td {
                display: grid;
                grid-template-columns: minmax(88px, 34%) minmax(0, 1fr);
                align-items: center;
                gap: .75rem;
                width: 100%;
                padding: .7rem .85rem !important;
                text-align: right !important;
                overflow-wrap: anywhere;
            }
            .inventory-table tbody td::before { content: attr(data-label); color: #7b8981; font-size: .63rem; font-weight: 800; text-align: left; text-transform: uppercase; }
            .inventory-table tbody td > * { justify-self: end; max-width: 100%; }
            .inventory-table tbody td.inventory-primary { display: block; text-align: left !important; }
            .inventory-table tbody td.inventory-primary::before { display: none; }
            .inventory-table tbody td.inventory-primary > * { justify-self: auto; }
            .inventory-table tbody td[colspan] { display: block; }
            .inventory-table tbody td[colspan]::before { display: none; }
            .inventory-table tbody td.inventory-select-cell { display: flex; justify-content: flex-end; width: 100%; }.inventory-table tbody td.inventory-select-cell::before { content: 'Select item'; margin-right: auto; }.inventory-table tbody td.inventory-select-cell .inventory-select { justify-self: auto; }
            .item-specification { max-width: 100%; white-space: normal; }
            .option-preview { align-items: flex-end; }
            .inventory-row-actions { justify-content: flex-end; }
            .inventory-action { width: 40px; height: 40px; }
        }

        @media(max-width: 420px) {
            .inventory-section-nav > a { grid-template-columns: 19px minmax(0, 1fr); }
            .inventory-section-nav > a > svg { display: none; }
            .inventory-metrics p { margin-top: .55rem; }
            .inventory-metrics strong { font-size: 1.15rem; }
            .tier-tabs button { gap: .45rem; }
            .tab-icon { display: none; }
        }
    </style>
    @endPushOnce

    <script>
        function inventoryWorkspace(catalog) {
            return {
                query: '',
                stockFilter: 'all',
                catalog,
                selected: [],
                showAllRestock: false,
                matches(text, status) {
                    const matchesSearch = !this.query.trim() || text.includes(this.query.trim().toLowerCase());
                    const matchesStatus = this.stockFilter === 'all'
                        || (this.stockFilter === 'attention' && ['out', 'low'].includes(status))
                        || this.stockFilter === status;
                    return matchesSearch && matchesStatus;
                },
                matchingIds(tier) {
                    return this.catalog
                        .filter(item => item.tier === tier && this.matches(item.text, item.status))
                        .map(item => item.id);
                },
                allMatchingSelected(tier) {
                    const ids = this.matchingIds(tier);
                    return ids.length > 0 && ids.every(id => this.selected.includes(id));
                },
                someMatchingSelected(tier) {
                    const ids = this.matchingIds(tier);
                    return ids.some(id => this.selected.includes(id)) && !ids.every(id => this.selected.includes(id));
                },
                toggleMatching(tier) {
                    const ids = this.matchingIds(tier);
                    if (ids.every(id => this.selected.includes(id))) {
                        this.selected = this.selected.filter(id => !ids.includes(id));
                    } else {
                        this.selected = [...new Set([...this.selected, ...ids])];
                    }
                },
                clearSelection() {
                    this.selected = [];
                },
                confirmBulkDelete() {
                    if (this.selected.length === 0) return;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkDeleteModal')).show();
                    setTimeout(() => lucide.createIcons(), 50);
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
