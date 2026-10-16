<x-app-layout>
    <x-slot name="header">{{ __('Notification Center') }}</x-slot>

    @php
        $categoryLabels = [
            'all' => 'All activity',
            'approvals' => 'Requisitions',
            'deadlines' => 'Deadlines',
            'accounts' => 'Accounts',
            'inventory' => 'Inventory',
        ];
    @endphp

    <div class="container-fluid notification-center-page" data-notification-center data-notification-endpoint="{{ route('notifications') }}" data-notification-account="{{ Auth::id() }}">
        <header class="notification-center-hero">
            <div class="notification-hero-copy">
                <span class="notification-hero-icon"><i data-lucide="bell-ring"></i></span>
                <div>
                    <span class="notification-eyebrow">Updates and action items</span>
                    <h1>Notification center</h1>
                    <p>Track approvals, deadlines, inventory alerts, and account activity in one place.</p>
                </div>
            </div>
            <div class="notification-hero-summary">
                <div><span>Unread</span><strong data-unread-count>{{ $unreadCount }}</strong></div>
                <div><span>Total activity</span><strong data-total-count>{{ $categoryCounts['all'] }}</strong></div>
                <form method="POST" action="{{ route('notifications.read-all') }}" data-mark-all-read-form @if($unreadCount < 1) hidden @endif>
                    @csrf
                    <button type="submit"><i data-lucide="check-check"></i> Mark all read</button>
                </form>
            </div>
        </header>

        <nav class="notification-category-nav" aria-label="Notification categories">
            @foreach(['all', 'approvals', 'deadlines', 'accounts'] as $key)
                <a href="{{ route('notifications', ['category' => $key]) }}"
                   class="notification-category-link {{ $category === $key ? 'is-active' : '' }}"
                   data-notification-category="{{ $key }}" data-category-label="{{ $categoryLabels[$key] }}"
                   @if($category === $key) aria-current="page" @endif>
                    <i data-lucide="{{ match($key) { 'all' => 'layout-list', 'approvals' => 'clipboard-check', 'deadlines' => 'alarm-clock', 'accounts' => 'users', default => 'bell' } }}"></i>
                    <span>{{ $categoryLabels[$key] }}</span><b data-category-count>{{ $categoryCounts[$key] }}</b>
                </a>
            @endforeach
            @if(Auth::user()->role === 'smo')
                <a href="{{ route('notifications', ['category' => 'inventory']) }}"
                   class="notification-category-link {{ $category === 'inventory' ? 'is-active' : '' }}"
                   data-notification-category="inventory" data-category-label="Inventory"
                   @if($category === 'inventory') aria-current="page" @endif>
                    <i data-lucide="package-search"></i><span>Inventory</span><b data-category-count>{{ $categoryCounts['inventory'] }}</b>
                </a>
            @endif
        </nav>

        <div class="notification-center-grid">
            <main class="notification-activity-panel">
                <div class="notification-panel-heading">
                    <div><span>Activity feed</span><h2 data-current-category>{{ $categoryLabels[$category] }}</h2></div>
                    <span class="notification-live-label"><i data-lucide="radio"></i> Live updates</span>
                </div>
                <div class="notification-feed-container" data-notification-feed aria-live="polite">
                    @include('notifications.feed', ['notes' => $notes, 'resolvedActions' => $resolvedActions])
                </div>
            </main>

            <aside class="notification-insights-column">
                @if(Auth::user()->role === 'smo')
                    <section class="notification-insight-card inventory-health {{ $lowStockSupplies->isNotEmpty() ? 'has-alerts' : '' }}">
                        <div class="notification-insight-heading">
                            <div><span>Inventory health</span><h2>{{ $lowStockSupplies->count() }} {{ Str::plural('item', $lowStockSupplies->count()) }} need attention</h2></div>
                            <i data-lucide="package-search"></i>
                        </div>
                        <div class="notification-stock-list">
                            @forelse($lowStockSupplies->take(5) as $supply)
                                <a href="{{ route('inventory.edit', $supply->id) }}" class="notification-stock-row">
                                    <span class="stock-state {{ $supply->quantity <= 0 ? 'is-out' : 'is-low' }}"></span>
                                    <span><strong>{{ $supply->item_name }}</strong><small>{{ $supply->quantity <= 0 ? 'Out of stock' : $supply->quantity.' '.$supply->unit.' remaining' }}</small></span>
                                    <i data-lucide="chevron-right"></i>
                                </a>
                            @empty
                                <div class="notification-insight-empty"><i data-lucide="circle-check"></i><span>Inventory levels are healthy.</span></div>
                            @endforelse
                        </div>
                        <a href="{{ route('inventory.index') }}" class="notification-insight-link">Open inventory forecast <i data-lucide="arrow-right"></i></a>
                    </section>

                    <section class="notification-insight-card deadline-watch">
                        <div class="notification-insight-heading">
                            <div><span>Release queue</span><h2>Critical deadlines</h2></div>
                            <b>{{ $criticalRequests->count() }}</b>
                        </div>
                        <div class="notification-deadline-list">
                            @forelse($criticalRequests as $req)
                                <article class="notification-deadline-item">
                                    <div><span>Action required</span><small>#{{ $req->id }}</small></div>
                                    <h3>{{ $req->items->first()->item_name ?? 'Multiple requested items' }}</h3>
                                    <p>This approved requisition requires immediate release.</p>
                                    <a href="{{ route('admin.approvals', ['request' => $req->id]) }}">Process request <i data-lucide="arrow-up-right"></i></a>
                                </article>
                            @empty
                                <div class="notification-insight-empty"><i data-lucide="shield-check"></i><span>No critical release deadlines.</span></div>
                            @endforelse
                        </div>
                    </section>
                @else
                    <section class="notification-insight-card personal-summary">
                        <div class="notification-insight-heading"><div><span>Your requisitions</span><h2>Request summary</h2></div><i data-lucide="chart-no-axes-column"></i></div>
                        <div class="notification-stat-grid">
                            <div><strong>{{ $personalStats['total'] }}</strong><span>Total</span></div>
                            <div><strong>{{ $personalStats['pending'] }}</strong><span>Pending</span></div>
                            <div><strong>{{ $personalStats['released'] }}</strong><span>Released</span></div>
                        </div>
                    </section>
                    <section class="notification-guide-card">
                        <span class="notification-guide-icon"><i data-lucide="route"></i></span>
                        <span class="notification-eyebrow">Procurement guide</span>
                        <h2>Know what happens next</h2>
                        <ul>
                            <li><i data-lucide="check-circle"></i><span><strong>Minor:</strong> Department Head and VP Finance approval.</span></li>
                            <li><i data-lucide="check-circle"></i><span><strong>Major:</strong> VP Admin, Provost, and President approval.</span></li>
                            <li><i data-lucide="package-check"></i><span><strong>Fulfillment:</strong> Visit SMO after the request is released.</span></li>
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    <style>
        .notification-center-page { --note-ink:#173226; --note-muted:#708178; --note-line:#dfe8e2; padding:.75rem 0 1.25rem; }
        .notification-center-hero { position:relative; display:flex; align-items:center; justify-content:space-between; gap:2rem; overflow:hidden; padding:1.25rem 1.4rem; border-radius:18px; background:radial-gradient(circle at 80% -80%,rgba(134,239,172,.28),transparent 24rem),linear-gradient(125deg,#0b3d25,#17633e 62%,#1d754a); color:#fff; box-shadow:0 14px 30px rgba(14,67,39,.15); }
        .notification-center-hero::after { content:''; position:absolute; right:-5rem; bottom:-9rem; width:20rem; height:20rem; border:1px solid rgba(255,255,255,.12); border-radius:50%; }
        .notification-hero-copy,.notification-hero-summary { position:relative; z-index:1; display:flex; align-items:center; }
        .notification-hero-copy { gap:1rem; }
        .notification-hero-icon { width:52px; height:52px; flex:0 0 52px; display:grid; place-items:center; border:1px solid rgba(255,255,255,.18); border-radius:15px; background:rgba(255,255,255,.12); }
        .notification-hero-icon svg { width:25px; height:25px; }
        .notification-eyebrow { color:#a9e8bf; font-size:.59rem; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
        .notification-hero-copy h1 { margin:.12rem 0 .2rem; color:#fff; font-size:1.35rem; font-weight:800; }
        .notification-hero-copy p { margin:0; color:#c8e9d3; font-size:.7rem; }
        .notification-hero-summary { gap:.5rem; }
        .notification-hero-summary > div { min-width:92px; padding:.65rem .8rem; border:1px solid rgba(255,255,255,.14); border-radius:11px; background:rgba(4,39,20,.2); }
        .notification-hero-summary span { display:block; color:#bfe5cc; font-size:.55rem; font-weight:800; text-transform:uppercase; }
        .notification-hero-summary strong { color:#fff; font-size:1rem; }
        .notification-hero-summary form button { min-height:42px; display:inline-flex; align-items:center; gap:.4rem; padding:.55rem .8rem; border:1px solid rgba(255,255,255,.35); border-radius:10px; background:#fff; color:#155936; font-size:.65rem; font-weight:800; }
        .notification-hero-summary form button svg { width:15px; height:15px; }
        .notification-category-nav { display:flex; gap:.45rem; margin:.85rem 0; padding:.35rem; overflow-x:auto; border:1px solid var(--note-line); border-radius:14px; background:#fff; scrollbar-width:none; box-shadow:0 5px 16px rgba(24,70,43,.04); }
        .notification-category-nav::-webkit-scrollbar { display:none; }
        .notification-category-link { min-height:40px; display:inline-flex; align-items:center; gap:.45rem; padding:.55rem .75rem; border-radius:10px; color:#677970; font-size:.65rem; font-weight:800; text-decoration:none; white-space:nowrap; transition:.18s ease; }
        .notification-category-link:hover { background:#f0f6f2; color:var(--htc-green); }
        .notification-category-link.is-active { background:var(--htc-green); color:#fff; box-shadow:0 5px 13px rgba(24,91,59,.2); }
        .notification-category-link svg { width:15px; height:15px; }
        .notification-category-link b { min-width:21px; padding:.15rem .35rem; border-radius:999px; background:#eef3f0; color:#5e7166; font-size:.56rem; text-align:center; }
        .notification-category-link.is-active b { background:rgba(255,255,255,.18); color:#fff; }
        .notification-center-grid { display:grid; grid-template-columns:minmax(0,1.8fr) minmax(280px,.72fr); gap:1rem; align-items:start; }
        .notification-activity-panel,.notification-insight-card { overflow:hidden; border:1px solid var(--note-line); border-radius:16px; background:#fff; box-shadow:0 7px 20px rgba(24,70,43,.05); }
        .notification-panel-heading,.notification-insight-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .notification-panel-heading { padding:1rem 1.15rem; border-bottom:1px solid #e7eee9; background:linear-gradient(135deg,#fbfdfc,#f4f8f5); }
        .notification-panel-heading > div > span,.notification-insight-heading > div > span { color:#87978e; font-size:.56rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
        .notification-panel-heading h2,.notification-insight-heading h2 { margin:.12rem 0 0; color:var(--note-ink); font-size:.92rem; font-weight:800; }
        .notification-live-label { display:inline-flex; align-items:center; gap:.3rem; color:#3c7655; font-size:.58rem; font-weight:800; text-transform:uppercase; }
        .notification-live-label svg { width:13px; height:13px; }
        .notification-feed-container { position:relative; min-height:260px; transition:opacity .15s ease; }
        .notification-center-page.is-loading .notification-feed-container { opacity:.38; pointer-events:none; }
        .notification-center-page.is-loading .notification-activity-panel { cursor:progress; }
        .notification-feed-list { display:grid; gap:.65rem; padding:.75rem; background:#f6f9f7; }
        .notification-feed-item { position:relative; display:grid; grid-template-columns:42px minmax(0,1fr); gap:.8rem; padding:1rem; border:1px solid #e1e9e4; border-radius:13px; background:#fff; transition:border-color .18s ease,transform .18s ease,box-shadow .18s ease; }
        .notification-feed-item:hover { border-color:#bfd2c5; transform:translateY(-1px); box-shadow:0 7px 16px rgba(24,70,43,.06); }
        .notification-feed-item.is-unread { border-left:4px solid var(--htc-green); background:linear-gradient(100deg,#f1faf4,#fff 28%); }
        .notification-feed-item.type-warning.is-unread { border-left-color:#dfa316; }
        .notification-feed-item.type-danger.is-unread { border-left-color:#cf424b; }
        .notification-feed-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; background:#e6f3ea; color:#28704a; }
        .notification-feed-icon svg { width:19px; height:19px; }
        .type-warning .notification-feed-icon { background:#fff1d2; color:#96630d; }
        .type-danger .notification-feed-icon { background:#fde8ea; color:#b52d36; }
        .type-success .notification-feed-icon { background:#e1f3e7; color:#17643d; }
        .notification-feed-content { min-width:0; }
        .notification-feed-meta,.notification-feed-title-row,.notification-feed-actions { display:flex; align-items:center; }
        .notification-feed-meta { justify-content:space-between; gap:.75rem; margin-bottom:.25rem; }
        .notification-feed-meta span { color:#4f755f; font-size:.54rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .notification-feed-meta time { color:#96a39c; font-size:.58rem; }
        .notification-feed-title-row { gap:.45rem; }
        .notification-feed-title-row h3 { margin:0; color:var(--note-ink); font-size:.78rem; font-weight:800; overflow-wrap:anywhere; }
        .notification-unread-indicator { width:7px; height:7px; flex:0 0 7px; border-radius:50%; background:#e14952; box-shadow:0 0 0 3px rgba(225,73,82,.1); }
        .notification-feed-content > p { margin:.3rem 0 0; color:var(--note-muted); font-size:.68rem; line-height:1.5; }
        .notification-feed-actions { flex-wrap:wrap; gap:.85rem; margin-top:.7rem; }
        .notification-primary-action,.notification-read-action,.notification-action-unavailable { display:inline-flex; align-items:center; gap:.3rem; font-size:.62rem; font-weight:800; }
        .notification-primary-action { padding:0; border:0; background:transparent; color:var(--htc-green); text-decoration:none; cursor:pointer; }
        .notification-primary-action svg,.notification-read-action svg,.notification-action-unavailable svg { width:13px; height:13px; }
        .notification-read-action { padding:0; border:0; background:transparent; color:#718078; }
        .notification-action-unavailable { color:#8d9a93; }
        .notification-empty-state { min-height:300px; display:flex; align-items:center; justify-content:center; flex-direction:column; padding:2rem; text-align:center; }
        .notification-empty-state > span { width:54px; height:54px; display:grid; place-items:center; margin-bottom:.8rem; border-radius:16px; background:#eaf3ed; color:#6d8978; }
        .notification-empty-state svg { width:25px; height:25px; }
        .notification-empty-state h3 { margin:0; color:var(--note-ink); font-size:.85rem; }
        .notification-empty-state p { max-width:320px; margin:.35rem 0 0; color:var(--note-muted); font-size:.67rem; }
        .notification-feed-pagination { padding:.8rem 1rem; border-top:1px solid var(--note-line); background:#fff; }
        .notification-insights-column { display:grid; gap:1rem; }
        .notification-insight-card { padding:1rem; }
        .notification-insight-heading { margin-bottom:.8rem; }
        .notification-insight-heading > svg { width:34px; height:34px; padding:8px; border-radius:10px; background:#e8f3ec; color:#276d48; }
        .notification-insight-heading > b { min-width:28px; height:28px; display:grid; place-items:center; border-radius:9px; background:#fde8ea; color:#b52d36; font-size:.7rem; }
        .notification-stock-row { display:grid; grid-template-columns:8px minmax(0,1fr) 14px; align-items:center; gap:.65rem; padding:.65rem 0; border-bottom:1px solid #edf1ef; color:inherit; text-decoration:none; }
        .notification-stock-row > span:nth-child(2) { min-width:0; }
        .notification-stock-row strong,.notification-stock-row small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .notification-stock-row strong { color:#2f4438; font-size:.68rem; }
        .notification-stock-row small { color:#89978f; font-size:.58rem; }
        .notification-stock-row > svg { width:14px; color:#9ba79f; }
        .stock-state { width:8px; height:8px; border-radius:50%; background:#e1a124; box-shadow:0 0 0 4px rgba(225,161,36,.11); }
        .stock-state.is-out { background:#d4424b; box-shadow:0 0 0 4px rgba(212,66,75,.11); }
        .notification-insight-link { min-height:38px; display:flex; align-items:center; justify-content:center; gap:.4rem; margin-top:.8rem; border-radius:9px; background:#edf7f1; color:#1f6b43; font-size:.62rem; font-weight:800; text-decoration:none; }
        .notification-insight-link svg { width:14px; }
        .notification-deadline-list { display:grid; gap:.6rem; }
        .notification-deadline-item { padding:.75rem; border:1px solid #efd3d5; border-radius:11px; background:#fff8f8; }
        .notification-deadline-item > div { display:flex; justify-content:space-between; gap:.5rem; }
        .notification-deadline-item > div span { color:#b63139; font-size:.53rem; font-weight:900; text-transform:uppercase; }
        .notification-deadline-item small { color:#9a8587; font-size:.55rem; }
        .notification-deadline-item h3 { margin:.35rem 0 .2rem; color:#432e30; font-size:.7rem; font-weight:800; }
        .notification-deadline-item p { margin:0 0 .55rem; color:#806c6e; font-size:.6rem; }
        .notification-deadline-item a { display:inline-flex; align-items:center; gap:.25rem; color:#ad2f37; font-size:.59rem; font-weight:800; text-decoration:none; }
        .notification-deadline-item a svg { width:12px; }
        .notification-insight-empty { min-height:90px; display:flex; align-items:center; justify-content:center; gap:.5rem; color:#7b8b82; font-size:.63rem; text-align:center; }
        .notification-insight-empty svg { width:18px; color:#45815e; }
        .notification-stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.45rem; }
        .notification-stat-grid div { padding:.7rem .4rem; border-radius:10px; background:#f3f7f4; text-align:center; }
        .notification-stat-grid strong,.notification-stat-grid span { display:block; }
        .notification-stat-grid strong { color:var(--note-ink); font-size:1rem; }
        .notification-stat-grid span { color:#829087; font-size:.53rem; font-weight:800; text-transform:uppercase; }
        .notification-guide-card { padding:1.1rem; border-radius:16px; background:linear-gradient(145deg,#103f29,#1b7147); color:#fff; box-shadow:0 10px 24px rgba(14,67,39,.14); }
        .notification-guide-icon { width:38px; height:38px; display:grid; place-items:center; margin-bottom:.7rem; border-radius:11px; background:rgba(255,255,255,.12); }
        .notification-guide-icon svg { width:18px; }
        .notification-guide-card h2 { margin:.15rem 0 .8rem; color:#fff; font-size:.9rem; }
        .notification-guide-card ul { display:grid; gap:.65rem; margin:0; padding:0; list-style:none; }
        .notification-guide-card li { display:grid; grid-template-columns:15px minmax(0,1fr); gap:.5rem; color:#d4ebdc; font-size:.62rem; line-height:1.45; }
        .notification-guide-card li svg { width:14px; height:14px; color:#8fe0ab; }
        @media(max-width:991.98px){.notification-center-hero{align-items:flex-start;flex-direction:column}.notification-hero-summary{width:100%}.notification-center-grid{grid-template-columns:1fr}.notification-insights-column{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:575.98px){.notification-center-page{padding-top:.25rem}.notification-center-hero{padding:1rem}.notification-hero-copy{align-items:flex-start}.notification-hero-icon{width:44px;height:44px;flex-basis:44px}.notification-hero-copy p{display:none}.notification-hero-summary{display:grid;grid-template-columns:1fr 1fr}.notification-hero-summary form{grid-column:1/-1}.notification-hero-summary form button{width:100%;justify-content:center}.notification-category-nav{margin-right:-.35rem;margin-left:-.35rem;border-radius:0}.notification-center-grid{gap:.75rem}.notification-insights-column{grid-template-columns:1fr}.notification-feed-item{grid-template-columns:36px minmax(0,1fr);padding:.8rem}.notification-feed-icon{width:36px;height:36px}.notification-feed-meta{align-items:flex-start;flex-direction:column;gap:.1rem}.notification-panel-heading{padding:.85rem}.notification-live-label{display:none}}
        @media(prefers-reduced-motion:reduce){.notification-feed-item,.notification-feed-container{transition:none}}
    </style>

    <script>
        (() => {
            const center = document.querySelector('[data-notification-center]');
            if (!center) return;

            const feed = center.querySelector('[data-notification-feed]');
            const heading = center.querySelector('[data-current-category]');
            const unreadSyncKey = `htc-notification-unread-sync-${center.dataset.notificationAccount}`;
            let feedController = null;

            const refreshIcons = () => window.lucide?.createIcons();
            const updateCategoryCounts = counts => {
                if (!counts) return;
                center.querySelectorAll('[data-notification-category]').forEach(link => {
                    const count = Math.max(0, Number(counts[link.dataset.notificationCategory]) || 0);
                    const badge = link.querySelector('[data-category-count]');
                    if (badge) badge.textContent = count;
                });
                const total = center.querySelector('[data-total-count]');
                if (total) total.textContent = Math.max(0, Number(counts.all) || 0);
            };
            const updateUnreadCount = (count, broadcast = false) => {
                const normalized = Math.max(0, Number(count) || 0);
                center.querySelectorAll('[data-unread-count]').forEach(element => element.textContent = normalized);
                const sidebarBadge = document.getElementById('notificationUnreadCount');
                if (sidebarBadge) {
                    sidebarBadge.hidden = normalized < 1;
                    sidebarBadge.textContent = normalized > 99 ? '99+' : String(normalized);
                }
                const markAllForm = center.querySelector('[data-mark-all-read-form]');
                if (markAllForm) markAllForm.hidden = normalized < 1;
                if (broadcast) {
                    try {
                        localStorage.setItem(unreadSyncKey, JSON.stringify({count: normalized, at: Date.now()}));
                    } catch (error) {
                        // Cross-tab synchronization is optional.
                    }
                }
            };

            const loadFeed = async (url, pushState = true) => {
                feedController?.abort();
                const controller = new AbortController();
                feedController = controller;
                center.classList.add('is-loading');
                feed.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(url, {
                        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });
                    if (!response.ok) throw new Error(`Notification feed failed with HTTP ${response.status}`);
                    const data = await response.json();
                    feed.innerHTML = data.html;

                    center.querySelectorAll('[data-notification-category]').forEach(link => {
                        const active = link.dataset.notificationCategory === data.category;
                        link.classList.toggle('is-active', active);
                        active ? link.setAttribute('aria-current', 'page') : link.removeAttribute('aria-current');
                        if (active && heading) heading.textContent = link.dataset.categoryLabel;
                    });
                    updateCategoryCounts(data.category_counts);
                    updateUnreadCount(data.unread_count);
                    if (pushState) window.history.pushState({notificationCategory: data.category}, '', url);
                    refreshIcons();
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    window.location.assign(url);
                } finally {
                    if (feedController === controller) {
                        feedController = null;
                        center.classList.remove('is-loading');
                        feed.removeAttribute('aria-busy');
                    }
                }
            };

            center.addEventListener('click', event => {
                const categoryLink = event.target.closest('[data-notification-category]');
                if (categoryLink) {
                    event.preventDefault();
                    loadFeed(categoryLink.href);
                    return;
                }

                const paginationLink = event.target.closest('[data-notification-pagination] a');
                if (paginationLink) {
                    event.preventDefault();
                    loadFeed(paginationLink.href);
                    center.querySelector('.notification-activity-panel')?.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            });

            center.addEventListener('submit', async event => {
                const readForm = event.target.closest('[data-notification-read-form]');
                const readAllForm = event.target.closest('[data-mark-all-read-form]');
                if (!readForm && !readAllForm) return;
                event.preventDefault();

                const form = readForm || readAllForm;
                if (form.dataset.submitting === 'true') return;
                form.dataset.submitting = 'true';
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) submitButton.disabled = true;
                const token = form.querySelector('input[name="_token"]')?.value;
                try {
                    const response = await fetch(form.action, {
                        method: readForm ? 'PATCH' : 'POST',
                        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token},
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error(`Read action failed with HTTP ${response.status}`);
                    const data = await response.json();

                    if (readAllForm) {
                        center.querySelectorAll('.notification-feed-item.is-unread').forEach(item => {
                            item.classList.remove('is-unread');
                            item.classList.add('is-read');
                            item.querySelector('.notification-unread-indicator')?.remove();
                            item.querySelector('[data-notification-read-form]')?.remove();
                        });
                        updateUnreadCount(data.unread_count, true);
                    } else {
                        const item = readForm.closest('.notification-feed-item');
                        item?.classList.remove('is-unread');
                        item?.classList.add('is-read');
                        item?.querySelector('.notification-unread-indicator')?.remove();
                        readForm.remove();
                        updateUnreadCount(data.unread_count, true);
                    }
                    refreshIcons();
                } catch (error) {
                    form.submit();
                } finally {
                    delete form.dataset.submitting;
                    if (submitButton?.isConnected) submitButton.disabled = false;
                }
            });

            window.addEventListener('popstate', () => {
                if (window.location.pathname === new URL(center.dataset.notificationEndpoint).pathname) loadFeed(window.location.href, false);
            });

            window.addEventListener('storage', event => {
                if (event.key !== unreadSyncKey || !event.newValue) return;
                try {
                    updateUnreadCount(JSON.parse(event.newValue).count);
                    loadFeed(window.location.href, false);
                } catch (error) {
                    // Ignore malformed or unavailable cross-tab state.
                }
            });

            document.addEventListener('htc:runtime-state', event => {
                const data = event.detail;
                if (!data || Number(data.account_id) !== Number(center.dataset.notificationAccount)) return;
                updateUnreadCount(data.unread_count);
                if (Array.isArray(data.notifications) && data.notifications.length) {
                    loadFeed(window.location.href, false);
                }
            });
        })();
    </script>
</x-app-layout>
