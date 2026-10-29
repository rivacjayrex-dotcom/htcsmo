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

    <div class="container-fluid notification-center-page" data-filter-workspace="notifications" data-notification-center data-notification-endpoint="{{ route('notifications') }}" data-notification-account="{{ Auth::id() }}">
        <div class="notification-center-grid">
            <main class="notification-activity-panel" id="notification-results" data-filter-results data-filter-region="results" data-result-count="{{ $notes->total() }}" tabindex="-1" aria-label="Matching notifications">
                <div class="notification-panel-heading">
                    <div class="notification-panel-title"><span>Activity feed</span><h2 data-current-category>{{ $categoryLabels[$category] }}</h2></div>
                    <nav class="notification-category-nav" aria-label="Notification categories" data-filter-links>
                        @foreach(['all', 'approvals', 'deadlines', 'accounts'] as $key)
                            <a href="{{ route('notifications', ['category' => $key]) }}#notification-results"
                               class="notification-category-link {{ $category === $key ? 'is-active' : '' }}"
                               data-notification-category="{{ $key }}" data-category-label="{{ $categoryLabels[$key] }}"
                               aria-label="{{ $categoryLabels[$key] }}" title="{{ $categoryLabels[$key] }}"
                               @if($category === $key) aria-current="page" @endif>
                                <i aria-hidden="true" data-lucide="{{ match($key) { 'all' => 'layout-list', 'approvals' => 'clipboard-check', 'deadlines' => 'alarm-clock', 'accounts' => 'users', default => 'bell' } }}"></i>
                                <span>{{ $categoryLabels[$key] }}</span>
                                <b data-category-count>{{ $categoryCounts[$key] }}</b>
                            </a>
                        @endforeach
                        @if(Auth::user()->role === 'smo')
                            <a href="{{ route('notifications', ['category' => 'inventory']) }}#notification-results"
                               class="notification-category-link {{ $category === 'inventory' ? 'is-active' : '' }}"
                               data-notification-category="inventory" data-category-label="Inventory"
                               aria-label="Inventory" title="Inventory"
                               @if($category === 'inventory') aria-current="page" @endif>
                                <i data-lucide="package-search" aria-hidden="true"></i>
                                <span>{{ $categoryLabels['inventory'] }}</span>
                                <b data-category-count>{{ $categoryCounts['inventory'] }}</b>
                            </a>
                        @endif
                    </nav>
                    <div class="notification-toolbar-actions">
                        <span class="notification-live-label" role="img" aria-label="Live updates" title="Live updates"><i data-lucide="radio" aria-hidden="true"></i></span>
                        <div class="notification-unread-count" title="Unread notifications">
                            <i data-lucide="mail" aria-hidden="true"></i>
                            <span class="visually-hidden">Unread notifications:</span>
                            <strong data-unread-count aria-live="polite" aria-atomic="true">{{ $unreadCount }}</strong>
                        </div>
                        <form method="POST" action="{{ route('notifications.read-all') }}" data-mark-all-read-form @if($unreadCount < 1) hidden @endif>
                            @csrf
                            <button type="submit" class="notification-icon-control" aria-label="Mark all read" title="Mark all read"><i data-lucide="check-check" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </div>
                <div class="notification-feed-container" id="notification-feed-scroll" data-notification-feed aria-live="polite" tabindex="0" aria-label="Activity feed">
                    @include('notifications.feed', ['notes' => $notes, 'resolvedActions' => $resolvedActions])
                </div>
            </main>


        </div>
    </div>

    @pushOnce('styles', 'page-styles:notifications.blade.php')
    <style>
        .notification-center-page { --note-ink:#173226; --note-muted:#708178; --note-line:#dfe8e2; padding:var(--space-12) 0 var(--space-24); }
        .notification-toolbar-actions { display:flex; flex:0 0 auto; align-items:center; gap:var(--space-8); margin-left:auto; }
        .notification-toolbar-actions form { margin:0; }
        .notification-unread-count { min-height:44px; display:inline-flex; align-items:center; gap:var(--space-8); padding:var(--space-8) var(--space-12); border-radius:10px; background:#edf7f1; color:#155936; }
        .notification-unread-count svg { width:18px; height:18px; }
        .notification-unread-count strong { font-size:var(--text-secondary); font-variant-numeric:tabular-nums; }
        .notification-icon-control { width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--note-line); border-radius:10px; background:#fff; color:#155936; text-decoration:none; cursor:pointer; }
        .notification-icon-control:hover { background:#edf7f1; border-color:#b8d6c3; }
        .notification-icon-control:disabled { opacity:.5; cursor:wait; }
        .notification-icon-control svg { width:18px; height:18px; }
        .notification-icon-control:focus-visible,.notification-category-link:focus-visible { outline:2px solid var(--htc-green); outline-offset:2px; }
        .notification-category-nav { display:flex; flex:1 1 auto; flex-wrap:nowrap; min-width:0; gap:var(--space-4); overflow-x:auto; padding:var(--space-4); }
        .notification-category-link { flex:0 0 auto; min-height:44px; display:inline-flex; align-items:center; gap:var(--space-8); padding:var(--space-8) var(--space-12); border-radius:10px; color:#677970; font-size:var(--text-caption); font-weight:800; text-decoration:none; white-space:nowrap; transition:.18s ease; }
        .notification-category-link:hover { background:#f0f6f2; color:var(--htc-green); }
        .notification-category-link.is-active { background:var(--htc-green); color:#fff; box-shadow:0 5px 13px rgba(24,91,59,.2); }
        .notification-category-link svg { width:18px; height:18px; }
        .notification-category-link b { min-width:21px; padding:.15rem var(--space-4); border-radius:999px; background:#eef3f0; color:#5e7166; font-size:var(--text-caption); text-align:center; }
        .notification-category-link.is-active b { background:rgba(255,255,255,.18); color:#fff; }
        .notification-center-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:var(--space-16); align-items:start; }
        .notification-activity-panel { display:flex; flex-direction:column; height:calc(100dvh - 168px); min-height:0; overflow:hidden; border:1px solid var(--note-line); border-radius:16px; background:#fff; box-shadow:0 7px 20px rgba(24,70,43,.05); }
        .notification-panel-heading { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); }
        .notification-panel-heading { flex-shrink:0; flex-wrap:nowrap; gap:var(--space-8); padding:var(--space-16); border-bottom:1px solid #e7eee9; background:linear-gradient(135deg,#fbfdfc,#f4f8f5); }
        .notification-panel-title { flex:0 0 auto; white-space:nowrap; }
        .notification-panel-heading > div > span { color:#87978e; font-size:var(--text-caption); font-weight:800; letter-spacing:var(--tracking-label); text-transform:uppercase; }
        .notification-panel-heading h2 { margin:.12rem 0 0; color:var(--note-ink); font-size:var(--text-heading-sm); font-weight:800; }
        .notification-live-label { display:inline-flex; align-items:center; gap:var(--space-4); color:#3c7655; font-size:var(--text-secondary); font-weight:800; text-transform:uppercase; }
        .notification-live-label svg { width:13px; height:13px; }
        .notification-feed-container { position:relative; flex:1; min-height:0; overflow-y:auto; overscroll-behavior-y:contain; scrollbar-gutter:stable; transition:opacity .15s ease; }
        .notification-center-page.is-loading .notification-feed-container { opacity:.38; pointer-events:none; }
        .notification-center-page.is-loading .notification-activity-panel { cursor:progress; }
        .notification-feed-list { display:grid; gap:var(--space-12); padding:var(--space-12); background:#f6f9f7; }
        .notification-feed-item { position:relative; display:grid; grid-template-columns:42px minmax(0,1fr); gap:var(--space-12); padding:var(--space-16); border:1px solid #e1e9e4; border-radius:13px; background:#fff; transition:border-color .18s ease,transform .18s ease,box-shadow .18s ease; }
        .notification-feed-item:hover { border-color:#bfd2c5; transform:translateY(-1px); box-shadow:0 7px 16px rgba(24,70,43,.06); }
        .notification-feed-item > form { position:absolute; inset:0; margin:0; border-radius:inherit; }
        .notification-card-action { position:absolute; inset:0; z-index:1; width:100%; height:100%; padding:0; border:0; border-radius:inherit; background:transparent; cursor:pointer; }
        .notification-card-action:focus-visible { outline:2px solid var(--htc-green); outline-offset:2px; }
        .notification-card-action:disabled { cursor:wait; }
        .notification-feed-item.is-unread { border-left:4px solid var(--htc-green); background:linear-gradient(100deg,#f1faf4,#fff 28%); }
        .notification-feed-item.type-warning.is-unread { border-left-color:#dfa316; }
        .notification-feed-item.type-danger.is-unread { border-left-color:#cf424b; }
        .notification-feed-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; background:#e6f3ea; color:#28704a; }
        .notification-feed-icon svg { width:19px; height:19px; }
        .type-warning .notification-feed-icon { background:#fff1d2; color:#96630d; }
        .type-danger .notification-feed-icon { background:#fde8ea; color:#b52d36; }
        .type-success .notification-feed-icon { background:#e1f3e7; color:#17643d; }
        .notification-feed-content { min-width:0; }
        .notification-feed-meta,.notification-feed-title-row { display:flex; align-items:center; }
        .notification-feed-meta { justify-content:space-between; gap:var(--space-12); margin-bottom:var(--space-4); }
        .notification-feed-meta span { color:#4f755f; font-size:var(--text-caption); font-weight:800; letter-spacing:var(--tracking-label); text-transform:uppercase; }
        .notification-feed-meta time { color:#96a39c; font-size:var(--text-caption); }
        .notification-feed-title-row { gap:var(--space-8); }
        .notification-feed-title-row h3 { margin:0; color:var(--note-ink); font-size:var(--text-body); font-weight:800; overflow-wrap:anywhere; }
        .notification-unread-indicator { width:7px; height:7px; flex:0 0 7px; border-radius:50%; background:#e14952; box-shadow:0 0 0 3px rgba(225,73,82,.1); }
        .notification-feed-content > p { margin:var(--space-4) 0 0; color:var(--note-muted); font-size:var(--text-secondary); line-height:var(--line-body); }
        .notification-feed-content > .notification-action-unavailable { color:#8d9a93; font-size:var(--text-caption); }
        .notification-empty-state { min-height:300px; display:flex; align-items:center; justify-content:center; flex-direction:column; padding:var(--space-32); text-align:center; }
        .notification-empty-state > span { width:54px; height:54px; display:grid; place-items:center; margin-bottom:var(--space-12); border-radius:16px; background:#eaf3ed; color:#6d8978; }
        .notification-empty-state svg { width:25px; height:25px; }
        .notification-empty-state h3 { margin:0; color:var(--note-ink); font-size:var(--text-heading-sm); }
        .notification-empty-state p { max-width:320px; margin:var(--space-4) 0 0; color:var(--note-muted); font-size:var(--text-secondary); }
        .notification-feed-pagination { padding:var(--space-12) var(--space-16); border-top:1px solid var(--note-line); background:#fff; }
        @media(max-width:767.98px){
            .notification-activity-panel{height:calc(100dvh - 128px)}
            .notification-panel-heading{display:grid;grid-template-columns:minmax(0,1fr) auto}
            .notification-category-nav{grid-column:1/-1;grid-row:2;width:100%}
            .notification-toolbar-actions{margin-left:0}
        }
        @media(max-width:575.98px){.notification-center-page{padding-top:var(--space-4)}.notification-category-nav{gap:var(--space-4)}.notification-category-link{padding:var(--space-8)}.notification-center-grid{gap:var(--space-12)}.notification-feed-item{grid-template-columns:36px minmax(0,1fr);padding:var(--space-12)}.notification-feed-icon{width:36px;height:36px}.notification-feed-meta{align-items:flex-start;flex-direction:column;gap:.1rem}.notification-panel-heading{padding:var(--space-12)}.notification-live-label{display:none}}
        @media(prefers-reduced-motion:reduce){.notification-feed-item,.notification-feed-container{transition:none}}
    </style>
    @endPushOnce

    <script>
        (() => {
            const center = document.querySelector('[data-notification-center]');
            if (!center) return;



            const unreadSyncKey = `htc-notification-unread-sync-${center.dataset.notificationAccount}`;


            const refreshIcons = () => window.lucide?.createIcons();
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
                const scrollTop = center.querySelector('[data-notification-feed]')?.scrollTop || 0;
                const scrollLeft = center.querySelector('.notification-category-nav')?.scrollLeft || 0;
                await window.htcFilters?.refresh(center, url, {
                    history: pushState ? 'push' : 'none', orient: pushState, background: !pushState,
                });
                if (!pushState) {
                    const feed = center.querySelector('[data-notification-feed]');
                    const tabs = center.querySelector('.notification-category-nav');
                    if (feed) feed.scrollTop = scrollTop;
                    if (tabs) tabs.scrollLeft = scrollLeft;
                }
            };
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
                    if (error.name === 'SessionExpiredError') return;
                    // A failed response may follow a completed write. Never replay it.
                    let status = center.querySelector('[data-notification-action-error]');
                    if (!status) {
                        status = document.createElement('p');
                        status.dataset.notificationActionError = '';
                        status.setAttribute('role', 'alert');
                        center.prepend(status);
                    }
                    status.textContent = 'We could not confirm this action. Refresh the notification list to check its status before trying again.';
                } finally {
                    delete form.dataset.submitting;
                    if (submitButton?.isConnected) submitButton.disabled = false;
                }
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

            let lastRuntimeSignature = null;
            document.addEventListener('htc:runtime-state', event => {
                const data = event.detail;
                if (!data || Number(data.account_id) !== Number(center.dataset.notificationAccount)) return;
                updateUnreadCount(data.unread_count);
                const signature = `${data.latest_id}:${data.unread_count}:${data.versions?.dashboard || ''}`;
                if (signature !== lastRuntimeSignature && (lastRuntimeSignature !== null || data.notifications?.length)) {
                    loadFeed(window.location.href, false);
                }
                lastRuntimeSignature = signature;
            });
        })();
    </script>
</x-app-layout>
