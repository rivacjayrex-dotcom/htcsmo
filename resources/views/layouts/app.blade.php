<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.initial-visibility')
    @include('layouts.partials.browser-title')
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    @vite(['resources/css/app.css', 'resources/css/workspace.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body data-runtime-scope="{{ $workspaceScope }}" data-runtime-version="{{ $workspaceVersion }}">
    @include('layouts.partials.session-recovery')
    <div class="d-flex">
        <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation"></button>
        <!-- SIDEBAR -->
        <aside class="sidebar" id="appSidebar" aria-label="Primary navigation">
            <div class="brand-section">
                <img src="{{ asset('images/android-chrome-512x5122.png') }}" alt="Logo" class="brand-logo">
                <div class="brand-title">SUPPLY<br>MANAGEMENT</div>
                <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close navigation">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="flex-grow-1">
                @php
                    $sidebarRole = Auth::user()->role;
                    $isApprovalRole = in_array($sidebarRole, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president']);
                    $canManagePersonnel = in_array($sidebarRole, ['dept_head', 'vp_admin', 'provost', 'president', 'smo']);
                @endphp

                <div class="nav-section-label">Overview</div>
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i> Home
                </a>
                <a href="{{ route('notifications') }}" class="nav-link-custom {{ request()->routeIs('notifications') ? 'active' : '' }}">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                    @php
                        $initialUnreadCount = $unreadCount ?? $layoutUnreadCount ?? 0;
                    @endphp
                    <span id="notificationUnreadCount" class="sidebar-count" @if($initialUnreadCount < 1) hidden @endif>{{ $initialUnreadCount > 99 ? '99+' : $initialUnreadCount }}</span>
                </a>

                <div class="nav-section-label">Requisitions</div>
                <a href="{{ route('requests.index') }}" class="nav-link-custom {{ request()->routeIs('requests.index') ? 'active' : '' }}">
                    <i data-lucide="clipboard-list"></i> Active Requests
                </a>
                <a href="{{ route('requests.archive') }}" class="nav-link-custom {{ request()->routeIs('requests.archive') ? 'active' : '' }}">
                    <i data-lucide="archive"></i> Request Archive
                </a>

                @if($isApprovalRole)
                    <div class="nav-section-label">Approval Workflow</div>
                    <a href="{{ route('admin.approvals') }}" class="nav-link-custom {{ request()->routeIs('admin.approvals') ? 'active' : '' }}">
                        <i data-lucide="clipboard-check"></i> Pending Approvals
                    </a>
                @endif

                @if($sidebarRole === 'smo')
                    <div class="nav-section-label">Inventory & Fulfillment</div>
                    <a href="{{ route('admin.approvals') }}" class="nav-link-custom {{ request()->routeIs('admin.approvals') ? 'active' : '' }}">
                        <i data-lucide="package-check"></i> Fulfillment Queue
                    </a>
                    <a href="{{ route('inventory.index') }}" class="nav-link-custom {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i data-lucide="boxes"></i> Manage Inventory
                    </a>

                    <div class="nav-section-label">Insights</div>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link-custom {{ request()->routeIs('admin.reports.index', 'admin.reports.analytics') ? 'active' : '' }}">
                        <i data-lucide="chart-no-axes-combined"></i> Operational Analytics
                    </a>
                    <a href="{{ route('admin.reports.builder') }}" class="nav-link-custom {{ request()->routeIs('admin.reports.builder', 'admin.reports.generate') ? 'active' : '' }}">
                        <i data-lucide="file-chart-column"></i> Report Center
                    </a>
                @endif

                @if($canManagePersonnel)
                    <div class="nav-section-label">Administration</div>
                    @if($sidebarRole === 'smo')
                        <a href="{{ route('admin.users.pending') }}" class="nav-link-custom {{ request()->routeIs('admin.users.pending') ? 'active' : '' }}">
                            <i data-lucide="user-round-check"></i> Access Requests
                        </a>
                    @endif
                    <a href="{{ route('admin.users.manage') }}" class="nav-link-custom {{ request()->routeIs('admin.users.manage') ? 'active' : '' }}">
                        <i data-lucide="users-round"></i> Manage Personnel
                    </a>
                @endif
            </div>

            <div class="sidebar-footer">
                <button type="button" class="btn btn-new-request w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#globalRequestModal">
                    + NEW REQUISITION
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <header class="top-bar">
                <button type="button" class="mobile-menu-button" id="sidebarToggle" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation">
                    <i data-lucide="menu"></i>
                </button>
                <div class="page-context">
                    <div class="page-title">{{ $header ?? 'System Dashboard' }}</div>
                </div>

                <div class="header-actions">
                    @php
                        $position = match(Auth::user()->role) {
                            'employee' => 'Faculty / Staff',
                            'dept_head' => 'Department Head',
                            'vp_finance' => 'VP for Finance',
                            'vp_admin' => 'VP for Administration',
                            'provost' => 'School Provost',
                            'president' => 'School President',
                            'smo' => 'SMO In-Charge',
                            default => 'User'
                        };
                    @endphp
                    <div class="role-badge d-none d-md-block">{{ $position }}</div>

                    <div class="dropdown">
                        <button class="btn d-flex align-items-center gap-3 rounded-3 user-menu-trigger" type="button" data-bs-toggle="dropdown" aria-label="Open account menu">
                            <div class="header-user-copy d-none d-lg-block">
                                <strong>{{ Auth::user()->name }}</strong>
                                <small>{{ Auth::user()->school_id }}</small>
                            </div>
                            <div class="header-avatar">
                                @if(Auth::user()->profile_photo)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="{{ Auth::user()->name }} profile photo">
                                @else
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                @endif
                            </div>
                            <i data-lucide="chevron-down" class="header-menu-chevron"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2 mt-2">
                            <li class="px-3 py-2 mb-2 border-bottom d-lg-none">
                                <div class="fw-bold">{{ Auth::user()->name }}</div>
                                <small class="text-muted">{{ $position }}</small>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 small d-flex align-items-center" href="{{ route('profile.edit') }}">
                                    <i data-lucide="settings" class="me-2 text-muted" style="width:14px"></i> Account Setting
                                </a>
                            </li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" data-logout-form data-confirm="End your current session? Save any changes you want to keep before signing out." data-confirm-title="Sign out?" data-confirm-accept="Sign out">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger rounded-3 small d-flex align-items-center" data-loading-text="Logging out…">
                                        <i data-lucide="log-out" class="me-2" style="width:14px"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="p-1 p-lg-3">
                <div class="main-content-inner p-3 p-md-4" @if($liveWorkspace) data-live-region="workspace" @endif>
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    @if(false)
    <!-- LEGACY REQUISITION ENGINE -->
    <div class="modal fade" id="globalRequestModal" tabindex="-1" aria-hidden="true"
        x-data="cartSystem()"
        @hidden-bs-modal.window="resetModal()">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- MODAL HEADER: DYNAMIC ACCENT -->
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom"
                    :class="tier === 'major' ? 'bg-warning-subtle' : 'bg-success-subtle'">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 me-3" :class="tier === 'major' ? 'bg-warning text-white' : 'bg-success text-white'">
                            <i :data-lucide="tier === 'major' ? 'shield-alert' : 'zap'" style="width: 20px;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Institutional Requisition</h5>
                            <span class="small fw-bold opacity-75 text-uppercase label-letter-spacing" x-text="view === 'selection' ? 'Select Priority' : tier + ' Tier Active'"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- VIEW 1: SELECTION (Modern Grid) -->
                <div x-show="view === 'selection'" x-transition class="p-5">
                    <div class="row g-4 justify-content-center">
                        <div class="col-md-5">
                            <div class="selection-card minor" @click="setTier('minor')">
                                <div class="selection-badge">< 1000</div>
                                <i data-lucide="package-search" class="mb-3"></i>
                                <h4>Minor Request</h4>
                                <p>For standard office supplies and low-value consumables.</p>
                                <div class="footer-note">Approval: Dept Head & VP Finance</div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="selection-card major" @click="setTier('major')">
                                <div class="selection-badge">> 1000</div>
                                <i data-lucide="building-2" class="mb-3"></i>
                                <h4>Major Request</h4>
                                <p>For high-value equipment, IT hardware, and bulk orders.</p>
                                <div class="footer-note">Full Administrative Chain Required</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW 2: DYNAMIC FORM -->
                <div x-show="view === 'form'" x-transition class="p-0 h-100" x-cloak>
                    <div class="row g-0">
                        <!-- LEFT: THE CART WORKSPACE -->
                        <div class="col-lg-8 p-4 border-end" style="background: #fafbfc; min-height: 550px; max-height: 650px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <button @click="view = 'selection'" class="btn btn-sm btn-light rounded-pill border px-3">
                                    <i data-lucide="chevron-left" class="me-1" style="width:14px"></i> Back
                                </button>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="manualMode" x-model="manualMode">
                                    <label class="form-check-label small fw-bold text-muted" for="manualMode">ITEM NOT IN LIST? (MANUAL INPUT)</label>
                                </div>
                            </div>

                            <!-- SEARCH / SELECT BOX (Guided Input) -->
                            <div class="mb-4 bg-white p-3 rounded-4 shadow-sm border border-success border-opacity-25" x-show="!manualMode" x-transition>
                                <label class="mini-label mb-2"><i data-lucide="search" class="me-1" style="width:10px"></i> Select Institutional Stock</label>
                                <select class="form-select border-0 bg-light py-2 fw-bold" @change="addItemFromInventory($event.target.value); $event.target.value = ''">
                                    <option value="">Search for items (e.g. Bond Paper, Ink)...</option>
                                </select>
                                <small class="text-muted mt-2 d-block" style="font-size: var(--text-caption);">Official items have pre-validated prices and specifications.</small>
                            </div>

                            <!-- THE CART LIST -->
                            <div class="pe-2">
                                <template x-for="(item, index) in cart" :key="index">
                                    <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 border-start border-4 requisition-item-card"
                                        :class="item.is_manual ? 'border-warning' : 'border-success'">

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-7">
                                                <span class="badge bg-light text-dark mb-2" style="font-size: var(--text-caption);" x-text="item.is_manual ? 'CUSTOM ITEM' : 'OFFICIAL STOCK'"></span>

                                                <input type="text" x-model="item.name" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0 shadow-none"
                                                    :readonly="!item.is_manual" :placeholder="item.is_manual ? 'Enter Item Name...' : ''" style="font-size: var(--text-body);">

                                                <textarea x-model="item.specs" class="form-control form-control-sm border-0 bg-transparent p-0 text-muted shadow-none mt-1"
                                                        rows="1" :readonly="!item.is_manual" placeholder="Add specifications..."></textarea>
                                            </div>

                                            <div class="col-md-5">
                                                <div class="row g-2">
                                                    <div class="col-7">
                                                        <label class="mini-label text-primary">Input Quantity</label>
                                                        <div class="input-group input-group-sm border rounded-3 overflow-hidden shadow-sm">
                                                            <!-- THE MANUAL QUANTITY INPUT -->
                                                            <input type="number" x-model.number="item.qty"
                                                                class="form-control border-0 text-center fw-bold bg-white"
                                                                min="1" @input="calculateTotal()">
                                                            <span class="input-group-text border-0 bg-white text-muted small px-2" x-text="item.unit"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-5 text-end">
                                                        <label class="mini-label">Unit Price</label>
                                                        <div class="fw-bold mt-1" x-text="'₱' + formatMoney(item.price)"></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                                                    <button type="button" @click="removeItem(index)" class="btn btn-sm text-danger p-0 d-flex align-items-center" style="font-size: var(--text-caption);">
                                                        <i data-lucide="trash-2" class="me-1" style="width:12px"></i> Remove
                                                    </button>
                                                    <div class="fw-bold text-success small" style="letter-spacing: var(--tracking-label);">
                                                        SUBTOTAL: ₱<span x-text="formatMoney(item.qty * item.price)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- MANUAL ENTRY BUTTON -->
                            <div x-show="manualMode" x-transition class="mt-2">
                                <button type="button" @click="addItemManual()" class="btn btn-outline-dashed w-100 py-3 rounded-4">
                                    <i data-lucide="plus-circle" class="me-2" style="width:16px"></i> Add Custom Item Description
                                </button>
                            </div>

                            <!-- EMPTY STATE -->
                            <div x-show="cart.length === 0" class="text-center py-5 opacity-25">
                                <i data-lucide="shopping-basket" style="width: 48px; height: 48px;" class="mb-2"></i>
                                <p class="small">Cart is empty. Select items above to begin.</p>
                            </div>
                        </div>

                        <!-- RIGHT: Summary Sidebar -->
                       <!-- RIGHT: Summary Sidebar (Fixed Version) -->
                        <div class="col-lg-4 p-4 bg-white border-start">
                            <div class="d-flex flex-column h-100">
                                <h6 class="fw-bold text-dark mb-4">Submission Summary</h6>

                                <!-- Total Items -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold">TOTAL ITEMS</span>
                                    <span class="fw-bold h5 mb-0" x-text="cart.length"></span>
                                </div>

                                <!-- Workflow Path (Stacked for better fit) -->
                                <div class="mb-4">
                                    <span class="text-muted small fw-bold d-block mb-2 text-uppercase">Workflow Path</span>
                                    <span class="badge rounded-pill px-3 py-2 w-100 border text-center"
                                        :class="tier === 'major' ? 'bg-warning-subtle text-warning border-warning' : 'bg-success-subtle text-success border-success'"
                                        x-text="tier.toUpperCase() + ' ROUTING'"></span>
                                </div>

                                <hr class="opacity-50">

                                <!-- Grand Total -->
                                <div class="py-3 text-center">
                                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: var(--text-caption);">ESTIMATED GRAND TOTAL</small>
                                    <h2 class="fw-black mb-0"
                                        :class="(tier === 'minor' && grandTotal > 1000) ? 'text-danger' : 'text-success'"
                                        style="font-size: var(--text-heading-md);">
                                        ₱<span x-text="formatMoney(grandTotal)"></span>
                                    </h2>

                                    <!-- Warning -->
                                    <template x-if="tier === 'minor' && grandTotal > 1000">
                                        <div class="mt-2 text-danger fw-bold animate__animated animate__shakeX" style="font-size: var(--text-caption); line-height: var(--line-heading);">
                                            ⚠️ MINOR LIMIT EXCEEDED (MAX ₱1,000)<br>SWITCH TO MAJOR TIER
                                        </div>
                                    </template>
                                </div>

                                <!-- Submit Button (Fixed) -->
                                <div class="mt-auto">
                                    <form action="{{ route('requisitions.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="request_type" :value="tier">
                                        <template x-for="(item, index) in cart">
                                            <div>
                                                <input type="hidden" :name="'items['+index+'][name]'" :value="item.name">
                                                <input type="hidden" :name="'items['+index+'][specs]'" :value="item.specs">
                                                <input type="hidden" :name="'items['+index+'][qty]'" :value="item.qty">
                                                <input type="hidden" :name="'items['+index+'][unit]'" :value="item.unit">
                                                <input type="hidden" :name="'items['+index+'][price]'" :value="item.price">
                                            </div>
                                        </template>

                                        <button type="submit"
                                                class="btn btn-success w-100 py-3 fw-bold shadow-sm d-block"
                                                style="border-radius: 12px; font-size: var(--text-secondary);"
                                                :disabled="tier === 'minor' && grandTotal > 1000"
                                                :class="tier === 'minor' && grandTotal > 1000 ? 'opacity-50 grayscale request-submit-disabled' : ''">
                                            SUBMIT REQUISITION
                                        </button>
                                    </form>
                                    <p class="text-center text-muted mt-3 mb-0" style="font-size: var(--text-body);">
                                        Secure Institutional Requisition <br> Holy Trinity College SMO
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif

    @include('layouts.partials.requisition-modal')

    <!-- LIVE PRIORITY NOTIFICATION -->
    <div x-data="notificationCenter()" x-show="toasts.length > 0" class="notification-toast-wrap" aria-label="Notifications" x-cloak>
        <template x-for="toast in toasts" :key="toast.id">
        <article x-show="toast.visible" x-transition.opacity.duration.250ms class="notification-toast" :class="'type-' + toast.type" role="alert" aria-live="assertive">
            <div class="notification-toast-accent"></div>
            <div class="notification-toast-body">
                <div class="notification-toast-icon">
                    <template x-if="toast.type === 'danger'"><i data-lucide="siren"></i></template>
                    <template x-if="toast.type === 'warning'"><i data-lucide="triangle-alert"></i></template>
                    <template x-if="toast.type === 'success'"><i data-lucide="circle-check"></i></template>
                    <template x-if="!['danger', 'warning', 'success'].includes(toast.type)"><i data-lucide="bell-ring"></i></template>
                </div>
                <div class="notification-toast-content">
                    <div class="notification-toast-meta"><span x-text="toast.label"></span><time>Just now</time></div>
                    <h6 x-text="toast.title"></h6>
                    <p x-text="toast.message"></p>
                    <div class="notification-toast-actions">
                        <form x-show="toast.actionUrl" :action="toast.actionUrl" method="POST">
                            @csrf
                            <button type="submit" class="notification-toast-open" x-text="toast.actionLabel"></button>
                        </form>
                        <button type="button" @click="toggleSound()" :title="soundEnabled ? 'Mute notification sounds' : 'Enable notification sounds'" :aria-label="soundEnabled ? 'Mute notification sounds' : 'Enable notification sounds'" :aria-pressed="soundEnabled">
                            <template x-if="soundEnabled"><i data-lucide="volume-2"></i></template>
                            <template x-if="!soundEnabled"><i data-lucide="volume-x"></i></template>
                        </button>
                        <button type="button" @click="dismiss(toast.id)" title="Hide this toast; it will remain unread">Dismiss toast</button>
                    </div>
                    <small class="notification-toast-read-note">Dismissal hides this toast but keeps the notification unread.</small>
                </div>
                <button type="button" class="notification-toast-close" @click="dismiss(toast.id)" aria-label="Dismiss notification"><i data-lucide="x"></i></button>
            </div>
            <div class="notification-toast-timer"></div>
        </article>
        </template>
    </div>

    <!-- Live updates stay silent unless recovery is needed. -->
    <div id="liveUpdateStatus" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>
    <div id="liveRefreshNotice" class="live-refresh-notice" role="status" aria-live="polite" hidden>
        <span id="liveRefreshMessage"></span>
        <button type="button" id="liveRefreshButton" class="btn btn-sm btn-outline-success">Retry updates</button>
    </div>

    @include('layouts.partials.runtime-guard')
    @include('layouts.partials.dynamic-search')

    <!-- Scripts -->
    <script>
        // Initialize Icons
        window.lucide?.createIcons();

        (() => {
            const sidebar = document.getElementById('appSidebar');
            const toggle = document.getElementById('sidebarToggle');
            const closeButton = document.getElementById('sidebarClose');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobileQuery = window.matchMedia('(max-width: 991.98px)');

            if (!sidebar || !toggle || !backdrop) return;

            let sidebarScrollY = 0;
            const setSidebar = open => {
                const shouldOpen = Boolean(open && mobileQuery.matches);
                const wasOpen = document.body.classList.contains('sidebar-open');
                if (shouldOpen && !wasOpen) {
                    sidebarScrollY = window.scrollY;
                    document.body.style.setProperty('--sidebar-scroll-top', `-${sidebarScrollY}px`);
                }
                sidebar.classList.toggle('is-open', shouldOpen);
                backdrop.classList.toggle('is-open', shouldOpen);
                document.body.classList.toggle('sidebar-open', shouldOpen);
                if (!shouldOpen && wasOpen) {
                    document.body.style.removeProperty('--sidebar-scroll-top');
                    window.scrollTo(0, sidebarScrollY);
                }
                toggle.setAttribute('aria-expanded', String(shouldOpen));
                sidebar.setAttribute('aria-hidden', String(mobileQuery.matches && !shouldOpen));

                if (shouldOpen) closeButton?.focus();
            };

            toggle.addEventListener('click', () => setSidebar(!sidebar.classList.contains('is-open')));
            closeButton?.addEventListener('click', () => {
                setSidebar(false);
                toggle.focus();
            });
            backdrop.addEventListener('click', () => setSidebar(false));
            sidebar.querySelectorAll('a, .btn-new-request').forEach(element => {
                element.addEventListener('click', () => setSidebar(false));
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                    setSidebar(false);
                    toggle.focus();
                }
            });
            mobileQuery.addEventListener('change', () => setSidebar(false));
            setSidebar(false);
        })();

        function notificationCenter() {
            // Only displayed popups advance this cursor. The former key also
            // included notifications skipped during page loads and refreshes.
            const storageKey = 'htc-presented-notification-{{ Auth::id() }}';
            const soundKey = 'htc-notification-sound';
            const accountId = {{ (int) Auth::id() }};
            const pageNotificationBaseline = {{ (int) ($layoutLatestNotificationId ?? 0) }};
            const runtimeCacheKey = `htc-runtime-state:${accountId}`;
            const tabId = window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;
            const channel = 'BroadcastChannel' in window ? new BroadcastChannel(`htc-notifications-${accountId}`) : null;
            const toastTimers = new Map();
            let audioContext = null;
            let lastSoundAt = -Infinity;
            const readPreference = key => {
                try { return localStorage.getItem(key); } catch (error) { return null; }
            };
            const savePreference = (key, value) => {
                try { localStorage.setItem(key, value); } catch (error) { /* Popups also work without browser storage. */ }
            };

            return {
                toasts: [],
                get show() { return this.toasts.some(toast => toast.visible); },
                soundEnabled: readPreference(soundKey) !== 'off',
                lastId: Math.min(Math.max(0, Number(readPreference(storageKey)) || 0), pageNotificationBaseline),
                queue: [],
                pendingSound: null,

                init() {
                    try {
                        const cachedRuntimeState = JSON.parse(sessionStorage.getItem(runtimeCacheKey) || 'null');
                        if (cachedRuntimeState) this.updateBadge(Number(cachedRuntimeState.unread_count || 0));
                    } catch (error) {
                        // The next runtime poll will populate the badge.
                    }
                    const unlock = async () => {
                        if (await this.unlockAudio() && this.pendingSound && this.show) this.playSound(this.pendingSound);
                    };
                    document.addEventListener('pointerdown', unlock);
                    document.addEventListener('keydown', unlock);
                    document.addEventListener('htc:runtime-state', event => this.consumeRuntimeState(event.detail));
                    channel?.addEventListener('message', event => {
                        if (event.data?.type === 'consumed') this.advanceCursor(Number(event.data.id || 0));
                    });
                    window.addEventListener('storage', event => {
                        if (event.key === storageKey) this.lastId = Math.max(this.lastId, Number(event.newValue || 0));
                    });
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) this.presentNext();
                    });
                    // A fast poll can finish before Alpine initializes this component.
                    if (window.htcRuntimeState) this.consumeRuntimeState(window.htcRuntimeState);
                },
                consumeRuntimeState(data) {
                    if (!data || Number(data.account_id) !== accountId) return;
                    this.updateBadge(Number(data.unread_count || 0));
                    if (document.hidden) return;
                    const notifications = Array.isArray(data.notifications)
                        ? data.notifications
                        : (data.notification ? [data.notification] : []);
                    notifications.forEach(notification => {
                        const id = Number(notification.id || 0);
                        if (id > this.lastId && !this.queue.some(item => Number(item.id) === id)) this.queue.push(notification);
                    });
                    if (!notifications.length && !this.toasts.length && !this.queue.length) this.advanceCursor(Number(data.cursor || data.latest_id || 0));
                    this.presentNext();
                },
                advanceCursor(id) {
                    if (id <= this.lastId) return;
                    this.lastId = id;
                    savePreference(storageKey, String(id));
                    channel?.postMessage({type: 'consumed', id});
                },
                claimToast(id) {
                    const key = `htc-notification-toast-${accountId}-${id}`;
                    try {
                        const existing = JSON.parse(localStorage.getItem(key) || 'null');
                        if (existing && Date.now() - Number(existing.at || 0) < 30000 && existing.tab !== tabId) return false;
                        localStorage.setItem(key, JSON.stringify({tab: tabId, at: Date.now()}));
                        return JSON.parse(localStorage.getItem(key) || 'null')?.tab === tabId;
                    } catch (error) {
                        return true;
                    }
                },
                presentNext() {
                    if (document.hidden) return;
                    while (this.toasts.length < 3 && this.queue.length) {
                        const notification = this.queue.shift();
                        const id = Number(notification.id || 0);
                        if (id > this.lastId && this.claimToast(id)) {
                            this.present(notification);
                        }
                    }
                    this.notifyActivity();
                },
                notifyActivity() {
                    document.dispatchEvent(new CustomEvent('htc:notification-activity', {
                        detail: {active: this.toasts.length > 0 || this.queue.length > 0},
                    }));
                },
                present(notification) {
                    const type = ['success', 'warning', 'danger'].includes(notification.type) ? notification.type : 'info';
                    const toast = {
                        id: Number(notification.id),
                        title: String(notification.title || 'Notification'),
                        message: String(notification.message || ''),
                        type,
                        label: type === 'success' ? 'Action completed' : type === 'danger' ? 'Action required' : type === 'warning' ? 'Attention needed' : 'System update',
                        actionUrl: notification.action_url || null,
                        actionLabel: notification.action_label || 'Open',
                        visible: true,
                    };
                    this.toasts.push(toast);
                    this.advanceCursor(toast.id);
                    this.notifyActivity();
                    toastTimers.set(toast.id, window.setTimeout(() => this.dismiss(toast.id), 9000));
                    this.playSound(type);
                    this.$nextTick(() => window.lucide?.createIcons());
                },
                dismiss(id) {
                    const toast = this.toasts.find(item => item.id === Number(id));
                    if (!toast?.visible) return;
                    toast.visible = false;
                    window.clearTimeout(toastTimers.get(toast.id));
                    toastTimers.delete(toast.id);
                    if (!this.show) this.pendingSound = null;
                    // Leave time for the exit transition before another popup or refresh.
                    window.setTimeout(() => {
                        this.toasts = this.toasts.filter(item => item.id !== toast.id);
                        this.presentNext();
                    }, 300);
                },
                updateBadge(count) {
                    const badge = document.getElementById('notificationUnreadCount');
                    if (!badge) return;
                    badge.hidden = count < 1;
                    badge.textContent = count > 99 ? '99+' : String(count);
                },
                toggleSound() {
                    this.soundEnabled = !this.soundEnabled;
                    savePreference(soundKey, this.soundEnabled ? 'on' : 'off');
                    if (this.soundEnabled) {
                        this.playSound('info');
                    } else this.pendingSound = null;
                    this.$nextTick(() => window.lucide?.createIcons());
                },
                async unlockAudio() {
                    if (!this.soundEnabled) return false;
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return false;
                    try {
                        audioContext ??= new AudioContext();
                        if (audioContext.state === 'suspended') await audioContext.resume();
                        return audioContext.state === 'running';
                    } catch (error) {
                        return false;
                    }
                },
                async playSound(type) {
                    if (!this.soundEnabled) return;
                    this.pendingSound = type;
                    if (!await this.unlockAudio() || !this.soundEnabled || !this.pendingSound) return;
                    const pendingType = this.pendingSound;
                    this.pendingSound = null;
                    // A burst of stacked popups gets one chime instead of overlapping sounds.
                    if (Date.now() - lastSoundAt < 300) return;
                    lastSoundAt = Date.now();
                    const patterns = {
                        danger: [[440, 0, .18], [330, .22, .25]],
                        warning: [[660, 0, .16], [520, .2, .24]],
                        success: [[660, 0, .14], [880, .18, .24]],
                        info: [[740, 0, .16], [988, .2, .24]],
                    };
                    const start = audioContext.currentTime;
                    (patterns[pendingType] || patterns.info).forEach(([frequency, offset, duration]) => {
                        const oscillator = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        oscillator.type = 'sine';
                        oscillator.frequency.value = frequency;
                        gain.gain.setValueAtTime(.0001, start + offset);
                        gain.gain.exponentialRampToValueAtTime(.09, start + offset + .015);
                        gain.gain.exponentialRampToValueAtTime(.0001, start + offset + duration);
                        oscillator.connect(gain).connect(audioContext.destination);
                        oscillator.start(start + offset);
                        oscillator.stop(start + offset + duration + .02);
                    });
                }
            };
        }

        // Cart Engine
        function cartSystem() {
            const previousRequest = {{ Illuminate\Support\Js::from([
                'tier' => old('request_type'),
                'purpose' => old('purpose', ''),
                'items' => old('items', []),
            ]) }};

            return {
                catalog: [],
                catalogLoaded: false,
                catalogLoading: false,
                catalogError: '',
                tier: null,
                view: 'selection',
                search: '',
                category: 'All',
                catalogLayout: 'grid',
                selectedItem: null,
                selectedOptions: {},
                productQty: 1,
                cart: [],
                cartOpen: false,
                lastAddedKey: null,
                purpose: previousRequest.purpose || '',
                notice: '',
                noticeTimer: null,
                confirmationOpen: false,
                submissionConfirmed: false,
                submitting: false,
                pendingForm: null,
                closeConfirmationPending: false,
                discardConfirmed: false,

                init() {
                    try {
                        const savedLayout = localStorage.getItem('htc-requisition-layout');
                        if (['grid', 'list'].includes(savedLayout)) this.catalogLayout = savedLayout;
                    } catch (error) { /* The layout toggle works without browser storage. */ }
                    if (!['minor', 'major'].includes(previousRequest.tier)) return;

                    this.tier = previousRequest.tier;
                    this.view = 'catalog';
                },

                setCatalogLayout(layout) {
                    if (!['grid', 'list'].includes(layout)) return;
                    this.catalogLayout = layout;
                    try {
                        localStorage.setItem('htc-requisition-layout', layout);
                    } catch (error) { /* Keep the selected layout for this session. */ }
                },

                reviewCart() {
                    this.cartOpen = true;
                    this.$nextTick(() => {
                        this.$refs.cartBody.scrollTop = 0;
                        this.$refs.cartBody.focus({preventScroll: true});
                    });
                },
                continueBrowsing() {
                    this.cartOpen = false;
                    this.$nextTick(() => this.$refs.catalogPane.focus({preventScroll: true}));
                },
                completeReason() {
                    this.cartOpen = true;
                    this.$nextTick(() => {
                        this.$refs.purpose.focus({preventScroll: true});
                        this.$refs.purpose.scrollIntoView({block: 'nearest'});
                    });
                },

                async loadCatalog() {
                    if (this.catalogLoaded || this.catalogLoading) return;

                    this.catalogLoading = true;
                    this.catalogError = '';
                    try {
                        const response = await fetch(@json(route('api.requisition-catalog')), {
                            headers: {'Accept': 'application/json'},
                            credentials: 'same-origin',
                        });
                        if (!response.ok) throw new Error('Catalog request failed.');

                        const catalog = await response.json();
                        if (window.htcSession?.expired) return;
                        if (!Array.isArray(catalog)) throw new Error('Invalid catalog response.');

                        this.catalog = catalog;
                        this.catalogLoaded = true;
                        this.restorePreviousRequest(previousRequest);
                    } catch (error) {
                        if (error.name === 'SessionExpiredError') return;
                        this.catalogError = 'Could not load supplies. Please try again.';
                    } finally {
                        this.catalogLoading = false;
                    }
                },

                restorePreviousRequest(previousRequest) {
                    if (!['minor', 'major'].includes(previousRequest.tier)) return;

                    this.cart = (previousRequest.items || []).map((submitted) => {
                        const item = this.catalog.find(catalogItem => catalogItem.id === Number(submitted.supply_id));
                        if (!item || item.tier !== this.tier) return null;
                        const options = submitted.options || {};
                        return {...item, options, qty: Math.min(item.stock, Math.max(1, Number(submitted.qty) || 1)), key: item.id + ':' + JSON.stringify(options)};
                    }).filter(Boolean);
                    this.refreshIcons();
                },

                catalogCount(tier) {
                    return this.catalog.filter(item => item.tier === tier).length;
                },

                setTier(tier) {
                    this.tier = tier;
                    this.view = 'catalog';
                    this.search = '';
                    this.category = 'All';
                    this.cart = [];
                    this.cartOpen = false;
                    this.lastAddedKey = null;
                    this.refreshIcons();
                },
                async backToTiers() {
                    if (this.cart.length && !await window.htcConfirm({
                        title: 'Change requisition tier?',
                        message: 'Changing the tier will clear the items in your cart.',
                        accept: 'Clear cart and change tier',
                        cancel: 'Keep current cart',
                        tone: 'danger',
                    })) return;
                    this.view = 'selection';
                    this.cart = [];
                    this.cartOpen = false;
                    this.lastAddedKey = null;
                    this.selectedItem = null;
                    this.refreshIcons();
                },
                openProduct(item) {
                    this.selectedItem = item;
                    this.selectedOptions = Object.fromEntries(Object.entries(item.options || {})
                        .filter(([, choices]) => choices.length === 1)
                        .map(([label, choices]) => [label, choices[0]]));
                    this.productQty = 1;
                    this.view = 'product';
                    this.refreshIcons();
                },
                addConfiguredItem() {
                    if (!this.canAddProduct) return;
                    const options = {...this.selectedOptions};
                    const key = this.selectedItem.id + ':' + JSON.stringify(options);
                    const existing = this.cart.find(item => item.key === key);
                    if (existing) {
                        existing.qty = Math.min(existing.stock, existing.qty + this.productQty);
                    } else {
                        this.cart.push({...this.selectedItem, key, options, qty: this.productQty});
                    }
                    this.lastAddedKey = key;
                    this.cartOpen = false;
                    this.view = 'catalog';
                    this.notice = `${this.selectedItem.name} was added to your requisition.`;
                    window.clearTimeout(this.noticeTimer);
                    this.noticeTimer = window.setTimeout(() => this.notice = '', 3500);
                    this.refreshIcons();
                    this.$nextTick(() => {
                        if (!window.matchMedia('(min-width: 992px)').matches) return;
                        const addedItem = Array.from(this.$refs.cartBody.querySelectorAll('[data-cart-key]'))
                            .find(element => element.dataset.cartKey === key);
                        addedItem?.scrollIntoView({block: 'nearest'});
                    });
                },
                removeItem(index) { this.cart.splice(index, 1); },
                optionSummary(options) { return Object.entries(options || {}).map(([label, value]) => `${label}: ${value}`).join(' · '); },
                brandCaption(item) {
                    const brands = item?.options?.Brand || [];
                    return brands.length > 1 ? `${brands.length} brands available` : (brands[0] || item?.brand || 'HTC catalog');
                },
                formatMoney(value) { return Number(value || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                refreshIcons() { setTimeout(() => window.lucide?.createIcons(), 30); },
                handleRequisitionSubmit(event) {
                    if (this.submissionConfirmed) return;

                    event.preventDefault();
                    if (!this.canSubmit || !event.currentTarget.reportValidity()) return;

                    this.pendingForm = event.currentTarget;
                    this.confirmationOpen = true;
                    this.$nextTick(() => {
                        this.refreshIcons();
                        this.$refs.confirmRequisitionButton?.focus();
                    });
                },
                cancelRequisitionConfirmation() {
                    this.confirmationOpen = false;
                    this.pendingForm = null;
                    this.$nextTick(() => this.$refs.requisitionForm?.querySelector('button[type="submit"]')?.focus());
                },
                confirmRequisitionSubmit() {
                    if (!this.pendingForm || this.submissionConfirmed) return;

                    this.submissionConfirmed = true;
                    this.confirmationOpen = false;
                    this.submitting = true;
                    this.pendingForm.dataset.noUnloadLoader = '';
                    this.$nextTick(() => window.requestAnimationFrame(() => this.pendingForm?.requestSubmit()));
                },
                async confirmRequisitionClose(event) {
                    if (this.submitting || this.submissionConfirmed) {
                        event.preventDefault();
                        return;
                    }
                    if (this.discardConfirmed) {
                        this.discardConfirmed = false;
                        return;
                    }
                    event.preventDefault();
                    if (this.closeConfirmationPending) return;
                    const modal = event.currentTarget;
                    this.closeConfirmationPending = true;
                    try {
                        const discard = await window.htcConfirm({
                            title: 'Cancel this requisition?',
                            message: 'Are you sure you want to close this requisition? Any selected items and entered details will be discarded.',
                            accept: 'Discard requisition',
                            cancel: 'Keep editing',
                            tone: 'danger',
                        });
                        if (!discard) return;
                        this.discardConfirmed = true;
                        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
                    } finally {
                        this.closeConfirmationPending = false;
                    }
                },
                resetModal() {
                    this.tier = null;
                    this.view = 'selection';
                    this.cart = [];
                    this.cartOpen = false;
                    this.lastAddedKey = null;
                    this.selectedItem = null;
                    this.purpose = '';
                    this.notice = '';
                    this.confirmationOpen = false;
                    this.submissionConfirmed = false;
                    this.submitting = false;
                    this.pendingForm = null;
                    this.closeConfirmationPending = false;
                    this.discardConfirmed = false;
                    this.$refs.requisitionForm?.setAttribute('data-no-loader', '');
                    this.$refs.requisitionForm?.removeAttribute('data-no-unload-loader');
                },
                get tierLabel() { return this.tier === 'major' ? 'Major requisition' : 'Minor requisition'; },
                get tierItems() { return this.catalog.filter(item => item.tier === this.tier); },
                get categories() { return [...new Set(this.tierItems.map(item => item.category))].sort(); },
                get filteredItems() {
                    const term = this.search.trim().toLowerCase();
                    return this.tierItems.filter(item => {
                        const optionText = Object.entries(item.options || {})
                            .flatMap(([label, choices]) => [label, ...(choices || [])])
                            .join(' ');
                        const searchableText = `${item.name || ''} ${item.brand || ''} ${item.model || ''} ${item.category || ''} ${item.specs || ''} ${optionText}`.toLowerCase();

                        return (this.category === 'All' || item.category === this.category)
                            && (!term || searchableText.includes(term));
                    });
                },
                get allOptionsSelected() {
                    return this.selectedItem && Object.keys(this.selectedItem.options || {}).every(label => this.selectedOptions[label]);
                },
                get canAddProduct() { return this.allOptionsSelected && this.productQty >= 1 && this.productQty <= (this.selectedItem?.stock || 0); },
                get grandTotal() { return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0); },
                get cartQuantity() { return this.cart.reduce((sum, item) => sum + item.qty, 0); },
                get purposeIsValid() { return this.purpose.trim().length >= 10; },
                get canSubmit() { return this.cart.length > 0 && this.purposeIsValid && !(this.tier === 'minor' && this.grandTotal > 1000); },
                get submitLabel() {
                    if (!this.cart.length) return 'Add at least one item';
                    if (!this.purposeIsValid) return 'Add a reason to continue';
                    if (this.tier === 'minor' && this.grandTotal > 1000) return 'Minor limit exceeded';
                    return 'Submit requisition →';
                }
            };
        }

        const requisitionModal = document.getElementById('globalRequestModal');
        requisitionModal?.addEventListener('show.bs.modal', event => {
            event.currentTarget.scrollTop = 0;
            window.Alpine.$data(event.currentTarget).loadCatalog();
        });
        requisitionModal?.addEventListener('hidden.bs.modal', event => {
            window.Alpine.$data(event.currentTarget).resetModal();
        });
        requisitionModal?.addEventListener('hide.bs.modal', event => {
            window.Alpine.$data(event.currentTarget).confirmRequisitionClose(event);
        });

        @if($errors->any() && old('request_type'))
            window.setTimeout(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('globalRequestModal')).show(), 100);
        @endif

        // Keep every signed-in workspace in sync with requisition and inventory actions.
        (() => {
            const endpoint = @json(route('api.runtime-state'));
            const expectedAccountId = {{ (int) Auth::id() }};
            const scope = document.body?.dataset.runtimeScope || 'all';
            const notificationKey = 'htc-presented-notification-{{ Auth::id() }}';
            const cacheKey = 'htc-runtime-state:{{ Auth::id() }}';
            const notice = document.getElementById('liveRefreshNotice');
            const message = document.getElementById('liveRefreshMessage');
            const status = document.getElementById('liveUpdateStatus');
            const pollInterval = 3000;
            const pollTimeout = 8000;
            let currentVersion = document.body?.dataset.runtimeVersion || null;
            let pendingVersion = currentVersion;
            let refreshPending = false;
            let checking = false;
            let timer = null;
            let failures = 0;
            let lastCheckedAt = 0;
            let refreshTimer = null;
            let refreshing = false;
            let workspaceFailed = false;
            const tabId = window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;
            const leaseKey = `htc-runtime-poller:${expectedAccountId}`;
            const retryKey = `htc-runtime-retry:${expectedAccountId}`;
            let retryAt = 0;
            const syncChannel = 'BroadcastChannel' in window ? new BroadcastChannel(`htc-runtime-${expectedAccountId}`) : null;

            const retryWait = () => {
                try {
                    const stored = Number(localStorage.getItem(retryKey));
                    if (Number.isFinite(stored)) retryAt = Math.max(retryAt, stored);
                } catch (error) { /* The in-memory cooldown still protects this tab. */ }
                return Math.max(0, retryAt - Date.now());
            };

            const pausePolling = response => {
                const retryAfter = response.headers?.get('Retry-After');
                const seconds = Number(retryAfter);
                const until = seconds > 0 && Number.isFinite(seconds)
                    ? Date.now() + seconds * 1000 : Date.parse(retryAfter || '');
                retryAt = Math.max(retryAt, Date.now() + pollInterval,
                    Number.isFinite(until) ? until : Date.now() + 60000);
                try { localStorage.setItem(retryKey, String(retryAt)); } catch (error) { /* Storage is optional. */ }
                syncChannel?.postMessage({type: 'cooldown', until: retryAt});
                message.textContent = 'Live updates are paused briefly. Retrying automatically.';
                notice.hidden = false;
            };

            const releaseLease = () => {
                if (!syncChannel) return;
                try {
                    if (JSON.parse(localStorage.getItem(leaseKey) || 'null')?.tab === tabId) {
                        localStorage.removeItem(leaseKey);
                        syncChannel.postMessage({type: 'released'});
                    }
                } catch (error) { /* Each tab can poll independently if storage is unavailable. */ }
            };

            const ownPollingLease = (nextDelay = pollInterval) => {
                if (!syncChannel) return true;
                try {
                    const lease = JSON.parse(localStorage.getItem(leaseKey) || 'null');
                    if (lease && lease.tab !== tabId && lease.until > Date.now()) return false;
                    localStorage.setItem(leaseKey, JSON.stringify({tab: tabId, until: Date.now() + nextDelay + pollTimeout + 1000}));
                    return JSON.parse(localStorage.getItem(leaseKey) || 'null')?.tab === tabId;
                } catch (error) { return true; }
            };

            const userIsWorking = () => {
                const active = document.activeElement;
                const editing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
                return window.htcSession?.expired || document.hidden || editing
                    || document.documentElement?.getAttribute('aria-busy') === 'true'
                    || document.body?.classList?.contains('sidebar-open')
                    || Boolean(document.querySelector('.modal.show, dialog[open], .main-content-inner [data-dirty], .main-content-inner [aria-busy="true"], .main-content-inner input[type="checkbox"]:checked, [data-filter-state="loading"], [data-filter-state="invalid"], [data-filter-state="error"]'));
            };

            const showUpdateError = () => {
                message.textContent = 'Live updates are temporarily unavailable. Retrying automatically.';
                notice.hidden = false;
            };

            const refreshWorkspace = async () => {
                if (window.htcSession?.expired || refreshing || !refreshPending || userIsWorking()) return;
                const regions = [...document.querySelectorAll('[data-live-region]')];
                if (!regions.length) return;
                refreshing = true;
                const targetVersion = pendingVersion;
                const targetUrl = window.location.href;
                const controller = new AbortController();
                const timeout = window.setTimeout(() => controller.abort(), 10000);
                try {
                    const response = await fetch(targetUrl, {
                        headers: {'Accept': 'text/html', 'X-Workspace-Refresh': 'true', 'X-Requested-With': 'XMLHttpRequest'},
                        credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                    });
                    if (!response.ok || response.redirected) throw new Error('Workspace refresh unavailable');
                    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const replacements = regions.map(region => ({
                        region,
                        next: parsed.querySelector(`[data-live-region="${region.dataset.liveRegion}"]`),
                    }));
                    if (replacements.some(item => !item.next)) throw new Error('Missing live content');
                    // An action can begin while the response is in flight. Keep the user's work.
                    if (userIsWorking() || window.location.href !== targetUrl) return;
                    const focused = document.activeElement;
                    const focusId = focused?.id;
                    const focusHref = focused?.getAttribute?.('href');
                    const scrollX = window.scrollX;
                    const scrollY = window.scrollY;
                    const scrollContainers = [...document.querySelectorAll('[data-live-region] [id], [data-employee-notifications]')]
                        .filter(element => (element.scrollTop || element.scrollLeft) && (element.id || element.hasAttribute('data-employee-notifications')))
                        .map(element => ({id: element.id, top: element.scrollTop, left: element.scrollLeft}));
                    const activeTabs = [...document.querySelectorAll('[data-bs-toggle="pill"].active')].map(tab => tab.id);
                    replacements.forEach(({region, next}) => {
                        next.querySelectorAll('script').forEach(script => script.remove());
                        const replace = () => {
                            if (window.bootstrap) {
                                region.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => window.bootstrap.Tab.getInstance(tab)?.dispose());
                                region.querySelectorAll('.collapse').forEach(element => window.bootstrap.Collapse.getInstance(element)?.dispose());
                            }
                            window.Alpine?.destroyTree(region);
                            region.innerHTML = next.innerHTML;
                            window.Alpine?.initTree(region);
                        };
                        if (window.Alpine?.mutateDom) window.Alpine.mutateDom(replace);
                        else replace();
                    });
                    activeTabs.forEach(id => {
                        const tab = document.getElementById(id);
                        if (tab && window.bootstrap) window.bootstrap.Tab.getOrCreateInstance(tab).show();
                    });
                    window.lucide?.createIcons();
                    currentVersion = targetVersion;
                    refreshPending = pendingVersion !== currentVersion;
                    workspaceFailed = false;
                    notice.hidden = true;
                    document.dispatchEvent(new CustomEvent('htc:workspace-updated'));
                    scrollContainers.forEach(saved => {
                        const element = saved.id ? document.getElementById(saved.id) : document.querySelector('[data-employee-notifications]');
                        if (element) { element.scrollTop = saved.top; element.scrollLeft = saved.left; }
                    });
                    if (focused && !focused.isConnected) {
                        const replacement = focusId ? document.getElementById(focusId)
                            : focusHref ? [...document.querySelectorAll('a[href]')].find(link => link.getAttribute('href') === focusHref) : null;
                        replacement?.focus({preventScroll: true});
                    }
                    if (Number.isFinite(scrollY) && Number.isFinite(scrollX)) window.scrollTo({left: scrollX, top: scrollY, behavior: 'instant'});
                    status.textContent = 'Content updated.';
                } catch (error) {
                    if (window.htcSession?.expired || error.name === 'SessionExpiredError') return;
                    workspaceFailed = true;
                    showUpdateError();
                } finally {
                    window.clearTimeout(timeout);
                    refreshing = false;
                }
            };

            const refreshWhenSafe = () => {
                if (window.htcSession?.expired || !refreshPending || refreshing) return;
                if (userIsWorking()) {
                    window.clearTimeout(refreshTimer);
                    refreshTimer = null;
                    return;
                }
                if (refreshTimer !== null) return;
                refreshTimer = window.setTimeout(() => {
                    refreshTimer = null;
                    if (userIsWorking()) refreshWhenSafe();
                    else return refreshWorkspace();
                }, 100);
            };

            const scheduleCheck = delay => {
                window.clearTimeout(timer);
                if (window.htcSession?.expired) return;
                timer = window.setTimeout(checkForUpdates, delay);
            };

            const cacheMetadata = data => {
                try {
                    sessionStorage.setItem(cacheKey, JSON.stringify({
                        version: data.version,
                        latest_id: Number(data.latest_id || 0),
                        unread_count: Number(data.unread_count || 0),
                        cached_at: Date.now(),
                    }));
                } catch (error) {
                    // Runtime caching is an optional optimization.
                }
            };

            const acceptRuntimeState = data => {
                if (Number(data.account_id) !== expectedAccountId) return;
                failures = 0;
                lastCheckedAt = Date.now();
                cacheMetadata(data);
                if (!workspaceFailed) notice.hidden = true;
                window.htcRuntimeState = data;
                document.dispatchEvent(new CustomEvent('htc:runtime-state', {detail: data}));
                const version = data.versions?.[scope] || data.version;
                if (currentVersion === null) currentVersion = version;
                else if (version !== currentVersion) {
                    pendingVersion = version;
                    refreshPending = true;
                    refreshWhenSafe();
                }
            };

            syncChannel?.addEventListener('message', event => {
                if (event.data?.type === 'state') acceptRuntimeState(event.data.data);
                if (event.data?.type === 'released' && !document.hidden) scheduleCheck(0);
                if (event.data?.type === 'cooldown' && Number.isFinite(event.data.until)) {
                    retryAt = Math.max(retryAt, event.data.until);
                    scheduleCheck(retryWait());
                }
            });

            const initialDelay = () => {
                try {
                    const cached = JSON.parse(sessionStorage.getItem(cacheKey) || 'null');
                    const age = Date.now() - Number(cached?.cached_at || 0);
                    const mutationAge = Date.now() - Number(sessionStorage.getItem('htc-runtime-mutated-at') || 0);
                    const cacheDelay = age >= 0 && age < pollInterval ? pollInterval - age : 0;
                    const mutationDelay = mutationAge >= 0 && mutationAge < 1000 ? 1000 - mutationAge : 0;
                    return Math.max(cacheDelay, mutationDelay);
                } catch (error) {
                    return 0;
                }
            };

            const checkForUpdates = async () => {
                if (window.htcSession?.expired || checking) return;
                // Navigation, tab takeover, and the Retry button must all obey
                // the server's cooldown instead of extending a 429 failure loop.
                const wait = retryWait();
                if (wait > 0) {
                    scheduleCheck(wait);
                    return;
                }
                if (document.hidden) {
                    releaseLease();
                    scheduleCheck(60000);
                    return;
                }
                // Give navigation, writes, searches, and a workspace refresh
                // priority over another background database request.
                if (refreshing || document.documentElement?.getAttribute('aria-busy') === 'true'
                    || document.querySelector('[data-filter-state="loading"]')) {
                    scheduleCheck(pollInterval);
                    return;
                }
                if (!ownPollingLease()) {
                    scheduleCheck(pollInterval);
                    return;
                }
                checking = true;
                const requestStartedAt = Date.now();
                const controller = new AbortController();
                const timeout = window.setTimeout(() => controller.abort(), pollTimeout);

                try {
                    let afterId = 0;
                    try {
                        afterId = Math.max(0, Number(localStorage.getItem(notificationKey)) || 0);
                    } catch (error) {
                        // The popup component keeps an in-memory cursor when storage is disabled.
                    }
                    const response = await fetch(`${endpoint}?after_id=${afterId}&scope=${scope}`, {
                        headers: {'Accept': 'application/json'},
                        cache: 'no-store',
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });
                    if (response.status === 429) {
                        pausePolling(response);
                        return;
                    }
                    if (!response.ok) throw new Error(`Runtime poll failed with HTTP ${response.status}`);

                    const data = await response.json();
                    if (Number(data.account_id) !== expectedAccountId) {
                        window.htcSession?.show();
                        return;
                    }
                    acceptRuntimeState(data);
                    syncChannel?.postMessage({type: 'state', data});
                } catch (error) {
                    failures++;
                    if (failures >= 3 && !window.htcSession?.expired) showUpdateError();
                } finally {
                    window.clearTimeout(timeout);
                    checking = false;
                    const retryDelay = failures ? Math.min(60000, pollInterval * (2 ** failures)) : pollInterval;
                    // A slow database should leave more time for foreground
                    // requests. Fast responses retain the normal cadence.
                    const responseDelay = Math.min(30000, Math.max(0, Date.now() - requestStartedAt) * 2);
                    const nextDelay = Math.max(retryDelay, responseDelay, retryWait());
                    if (!document.hidden && !window.htcSession?.expired) ownPollingLease(nextDelay);
                    scheduleCheck(nextDelay);
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) releaseLease();
                if (!document.hidden && Date.now() - lastCheckedAt > pollInterval) checkForUpdates();
                if (!document.hidden) refreshWhenSafe();
            });
            document.addEventListener('focusout', () => window.setTimeout(refreshWhenSafe, 150));
            document.addEventListener('htc:session-expired', () => {
                window.clearTimeout(timer);
                window.clearTimeout(refreshTimer);
                releaseLease();
                notice.hidden = true;
            });
            document.addEventListener('hidden.bs.modal', refreshWhenSafe);
            document.addEventListener('input', event => {
                const form = event.target.closest?.('.main-content-inner form');
                if (form && (form.method || 'get').toLowerCase() !== 'get') form.dataset.dirty = 'true';
            });
            window.addEventListener('pagehide', releaseLease);
            document.getElementById('liveRefreshButton')?.addEventListener('click', async () => {
                await checkForUpdates();
                refreshWhenSafe();
            });
            scheduleCheck(initialDelay());
        })();

        // Server-rendered totals must remain stable when Alpine initializes or a
        // background refresh replaces the dashboard metrics.
        function countUp(target) {
            return {
                current: Number(target) || 0,
            }
        }
    </script>
</body>
</html>
