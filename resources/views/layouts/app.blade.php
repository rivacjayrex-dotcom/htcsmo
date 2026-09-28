<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-32x32.png') }}">

    <!-- Animate.css for entrance animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 2. Lucide Icons (Must be in head) -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --htc-green: #185b3b;
            --htc-light-green: #f0f7f1;
            --sidebar-width: 250px;
        }

        html { font-size: 13px; } /* Standard for professional dashboards */

        body {
            background-color: #f4f7f6;
            font-family: 'Inter', sans-serif;
            color: #334155;
            overflow-x: hidden;
            scroll-behavior: auto !important; /* Removes smooth scrolling "anchor" feel */
        }

        :target { background: none; padding-top: 0; }

        /* Sidebar Styling */
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: white; border-right: 1px solid #e2e8f0; z-index: 1000; padding: 1.25rem; display: flex; flex-direction: column; }
        .brand-section { display: flex; align-items: center; padding-bottom: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #f1f5f9; }
        .brand-logo { width: 45px; height: 45px; object-fit: contain; margin-right: 12px; }
        .brand-title { font-size: 0.95rem; font-weight: 800; letter-spacing: 0.5px; line-height: 1.2; color: var(--htc-green); text-transform: uppercase; }

        /* This is the magic part: the middle section scrolls, top and bottom stay fixed */
        .sidebar .flex-grow-1 {
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 5px; /* Space for the scrollbar */
            margin-right: -5px; /* Keeps the links aligned */
        }

        /* Optional: Make the sidebar scrollbar very thin and modern */
        .sidebar .flex-grow-1::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar .flex-grow-1::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar .flex-grow-1::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .sidebar .flex-grow-1::-webkit-scrollbar-thumb:hover {
            background: var(--htc-green);
        }

        /* Ensure the bottom button area stays at the bottom and has a solid background */
        .sidebar-footer {
            padding-top: 1rem;
            margin-top: auto;
            border-top: 1px solid #f1f5f9;
            background: white;
            z-index: 10;
        }

        .nav-section-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 1.5rem 0 0.75rem 0.75rem; }
        .nav-link-custom { display: flex; align-items: center; padding: 0.6rem 0.85rem; color: #64748b; text-decoration: none; border-radius: 12px; margin-bottom: 4px; font-weight: 500; font-size: 0.85rem; transition: all 0.2s ease; }
        .nav-link-custom:hover { background-color: var(--htc-light-green); color: var(--htc-green); transform: translateX(4px); }
        .nav-link-custom.active { background-color: var(--htc-green); color: white; box-shadow: 0 4px 12px rgba(24, 91, 59, 0.2); }
        .nav-link-custom [data-lucide] { width: 16px; height: 16px; margin-right: 12px; stroke-width: 2.5px; }

        /* Main Content & Top Bar */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); }
        .top-bar { height: 60px; background: var(--htc-green); border-bottom: 1px solid #fff5f5; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; position: sticky; top: 0; z-index: 1010; }
        .page-title { font-weight: 800; font-size: 1.1rem; color: white; }

        .role-badge { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 5px 12px; border-radius: 20px; background-color: var(--htc-light-green); color: var(--htc-green); margin-right: 15px; border: 1px solid rgba(14, 41, 21, 0.885); }
        .main-content-inner { background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); min-height: calc(100vh - 160px); border: 1px solid #e2e8f0; padding: 1.5rem !important; /* Reduced from p-5 */ }

        .btn-new-request { background-color: var(--htc-green); color: white; border-radius: 12px; padding: 0.8rem; font-weight: 700; border: none; transition: all 0.3s ease; }
        .btn-new-request:hover { background-color: #0d2e16; color: white; transform: translateY(-2px); }

        .dropdown-menu {
            display: block; /* Required for transition to work */
            visibility: hidden;
            opacity: 0;
            transform: translateY(15px); /* Starts 15px lower */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Professional easing */
            pointer-events: none; /* Prevents clicking while hidden */
            border: none !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        }

        .dropdown-menu.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0); /* Slides up to its spot */
            pointer-events: auto;
        }

        /* Optional: Add a hover effect to the items inside */
        .dropdown-item {
            transition: all 0.2s ease;
            padding: 0.6rem 1rem;
        }

        .dropdown-item:hover {
            background-color: var(--htc-light-green);
            color: var(--htc-green);
            transform: translateX(5px); /* Subtle slide to the right */
        }


        /* --- UNIVERSAL STEPPER (Shopee-style) --- */
        .tracking-stepper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            width: 100%;
            margin: 0 auto 2rem auto;
            padding: 20px 0;
            /* This ensures it stays horizontal even on smaller screens */
            flex-direction: row !important;
        }

        .step-item {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            min-width: 80px; /* Prevents icons from overlapping */
        }

        /* The Connecting Line */
        .step-item::after {
            content: "";
            position: absolute;
            top: 20px; /* Half of the icon height (40px) */
            left: 50%;
            width: 100%;
            height: 3px;
            background-color: #e2e8f0;
            z-index: -1;
            transition: all 0.5s ease;
        }

        /* Hide the line for the last item */
        .step-item:last-child::after {
            display: none;
        }

        /* The Circle Icon */
        .step-icon {
            width: 40px;
            height: 40px;
            background: white;
            border: 3px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .step-icon [data-lucide] {
            width: 18px;
            height: 18px;
            color: #94a3b8;
        }

        /*States*/

        /* Completed (Green) */
        .step-item.completed .step-icon {
            background-color: var(--htc-green);
            border-color: var(--htc-green);
            color: white !important;
        }

        .step-item.completed .step-icon [data-lucide] {
            color: white !important;
            stroke: currentColor;
        }
        .step-item.completed::after { background-color: var(--htc-green); }


        /* Active State (Yellow/Current) */
        .step-item.active .step-icon {
            border-color: #ffc107;
            color: #ffc107;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
        }

        .step-item.active .step-icon [data-lucide] {
            color: #ffc107 !important;
            stroke: currentColor;
        }

        /* Active (Yellow) */
        .step-item.active .step-icon {
            border-color: #ffc107;
            color: #ffc107;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.2);
        }

        /* Labels */
        .step-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-align: center;
            max-width: 100px;
            line-height: 1.2;
        }

        /* Questioning / Clarification State (Orennss) */
        .step-item.questioning .step-icon {
            border-color: #fd7e14 !important;
            color: #fd7e14 !important;
            background-color: #fff8f3 !important;
            box-shadow: 0 0 0 4px rgba(253, 126, 20, 0.25) !important;
            animation: pulseWarning 2s infinite !important;
        }

        .step-item.questioning .step-icon [data-lucide] {
            color: #fd7e14 !important;
            stroke: currentColor;
        }

        .step-item.questioning .step-label {
            color: #fd7e14 !important;
            font-weight: 800 !important;
        }

        .step-item.active .step-label { color: #856404; }

        /* --- ANIMATION DEFINITIONS --- */
        @keyframes lineFlow {
            0% { background-position: 100% 0%; }
            100% { background-position: -100% 0%; }
        }

        @keyframes pulseActive {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }

        @keyframes pulseWarning {
            0% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(253, 126, 20, 0); }
            100% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0); }
        }

        /* --- APPLYING TO THE STEPPER --- */

        /* The 'Moving' Line */
        .step-item.in-progress::after {
            background: linear-gradient(90deg, var(--htc-green) 0%, #e2e8f0 50%, var(--htc-green) 100%);
            background-size: 200% 100%;
            animation: lineFlow 2s linear infinite;
        }

        /* Pulsing effect for the CURRENT step icon */
        .step-item.active .step-icon {
            border-color: #ffc107;
            animation: pulseActive 2s infinite;
        }

        /* Smooth transition for when a step turns green */
        .step-item .step-icon, .step-item::after {
            transition: all 0.5s ease-in-out;
        }

        /* --- MODERN PROCUREMENT CARDS --- */
        .procurement-card {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: 100%;
        }

        .procurement-card:hover {
            border-color: var(--htc-green);
            background-color: #f8fafc;
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        .card-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 1.25rem;
            transition: all 0.3s ease;
        }

        .procurement-card:hover .card-icon-wrapper {
            transform: scale(1.1) rotate(-5deg);
        }

        .card-content {
            flex-grow: 1;
        }

        .card-arrow {
            color: #cbd5e1;
            transition: all 0.3s ease;
            padding-left: 10px;
        }

        .procurement-card:hover .card-arrow {
            color: var(--htc-green);
            transform: translateX(5px);
        }

        /* Modal Inner Background Color */
        #globalRequestModal .modal-content {
            background-color: #ffffff;
        }

        #globalRequestModal .bg-light {
            background-color: #f8fafc !important;
        }

        #globalRequestModal .btn-success {
            white-space: nowrap !important; /* Prevents text from stacking vertically */
            min-height: 50px; /* Ensures it has a proper button shape */
        }

        .grayscale {
            filter: grayscale(1);
        }

        #smoTabs .nav-link {
            color: #64748b;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        #smoTabs .nav-link.active {
            background-color: var(--htc-green) !important;
            color: white !important;
            border-color: var(--htc-green) !important;
            box-shadow: 0 4px 12px rgba(24, 91, 59, 0.2);
        }

        #smoTabs .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
        }



        /* --- PROCUREMENT ENGINE STYLES --- */
        .selection-card {
            background: white;
            border: 2px solid #f1f5f9;
            padding: 2.5rem 1.5rem;
            border-radius: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .selection-card i { width: 50px; height: 50px; transition: all 0.3s ease; }
        .selection-card h4 { font-weight: 800; margin-top: 1rem; color: #1e293b; }
        .selection-card p { font-size: 0.85rem; color: #64748b; margin-bottom: 0; }

        .selection-card.minor:hover { border-color: #10b981; background: #f0fdf4; transform: translateY(-10px); }
        .selection-card.major:hover { border-color: #f59e0b; background: #fffbeb; transform: translateY(-10px); }

        .selection-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 9px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #94a3b8;
        }

        .selection-card.minor:hover .selection-badge { background: #10b981; color: white; }
        .selection-card.major:hover .selection-badge { background: #f59e0b; color: white; }

        .mini-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 4px; }

        .summary-stat { display: flex; justify-content: space-between; font-size: 0.85rem; color: #64748b; }

        .btn-outline-dashed {
            border: 2px dashed #e2e8f0;
            color: #94a3b8;
            transition: all 0.3s;
        }
        .btn-outline-dashed:hover {
            border-color: var(--htc-green);
            color: var(--htc-green);
            background: var(--htc-light-green);
        }

        /* Custom Scrollbar for Chrome/Edge/Safari */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .footer-note { font-size: 10px; font-weight: bold; margin-top: 20px; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="brand-section">
                <img src="{{ asset('images/android-chrome-512x5122.png') }}" alt="Logo" class="brand-logo">
                <div class="brand-title">SUPPLY<br>MANAGEMENT</div>
            </div>

            <div class="flex-grow-1">
                <div class="nav-section-label">General</div>
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i> Home
                </a>

                <!-- 1. ACTIVE IN-PROGRESS REQUESTS (All Users) -->
                <a href="{{ route('requests.index') }}" class="nav-link-custom {{ request()->routeIs('requests.index') ? 'active' : '' }}">
                    <i data-lucide="clipboard-list"></i> Active Requests
                </a>

                <!-- 2. REQUISITION ARCHIVE / HISTORY (All Users) -->
                <a href="{{ route('requests.archive') }}" class="nav-link-custom {{ request()->routeIs('requests.archive') ? 'active' : '' }}">
                    <i data-lucide="archive"></i> Requisition Archive
                </a>

                <a href="{{ route('notifications') }}" class="nav-link-custom {{ request()->routeIs('notifications') ? 'active' : '' }}">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div><i data-lucide="bell"></i> Notifications</div>
                        @if(isset($unreadCount) && $unreadCount > 0)
                            <span class="badge rounded-pill bg-danger" style="font-size: 10px;">{{ $unreadCount }}</span>
                        @endif
                    </div>
                </a>

                @if(Auth::user()->role == 'smo')
                    <div class="nav-section-label">Institutional Reports</div>

                    <a href="{{ route('admin.reports.index') }}" class="nav-link-custom {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i data-lucide="bar-chart-3"></i> Reports & Analytics
                    </a>
                @endif

                @if(in_array(Auth::user()->role, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president']))
                    <div class="nav-section-label">Management</div>
                    <a href="{{ route('admin.approvals') }}" class="nav-link-custom {{ request()->routeIs('admin.approvals') ? 'active' : '' }}">
                        <i data-lucide="user-check"></i> Pending Approvals
                    </a>
                    @if(in_array(Auth::user()->role, ['dept_head', 'vp_admin', 'provost', 'president']))
                        <a href="{{ route('admin.users.manage') }}" class="nav-link-custom {{ request()->routeIs('admin.users.manage') ? 'active' : '' }}">
                            <i data-lucide="users"></i> Manage Personnel
                        </a>
                    @endif
                @endif

                @if(Auth::user()->role == 'smo')
                    <div class="nav-section-label">System Administration</div>
                    <a href="{{ route('admin.users.pending') }}" class="nav-link-custom {{ request()->routeIs('admin.users.pending') ? 'active' : '' }}">
                        <i data-lucide="user-plus"></i> Access Requests
                    </a>
                    <a href="{{ route('admin.users.manage') }}" class="nav-link-custom {{ request()->routeIs('admin.users.manage') ? 'active' : '' }}">
                        <i data-lucide="users"></i> Manage Personnel
                    </a>
                @endif

                @if(Auth::user()->role == 'smo')
                    <div class="nav-section-label">Inventory & Logistics</div>
                    <a href="{{ route('admin.approvals') }}" class="nav-link-custom {{ request()->routeIs('admin.approvals') ? 'active' : '' }}">
                        <i data-lucide="truck"></i> Release Queue
                    </a>
                    <a href="{{ route('inventory.index') }}" class="nav-link-custom {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i data-lucide="package"></i> Manage Stock
                    </a>
                @endif
            </div>

            <div class="sidebar-footer">
                <button type="button" class="btn btn-new-request w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#globalRequestModal">
                    + NEW REQUISITION
                </button>

                <!-- Document ID or Version Note (Optional extra detail) -->
                <div class="text-center mt-2">
                    <small class="text-muted" style="font-size: 9px; opacity: 0.5;">v1.0.80 Alpha</small>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <header class="top-bar">
                <div class="page-title">{{ $header ?? 'System Dashboard' }}</div>

                <div class="d-flex align-items-center">
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
                        <button class="btn border-0 d-flex align-items-center p-2 rounded-3" type="button" data-bs-toggle="dropdown">
                            <div class="text-end me-3 d-none d-lg-block">
                                <div class="fw-bold small text-white" style="line-height: 1;">{{ Auth::user()->name }}</div>
                                <small class="text-white opacity-75" style="font-size: 10px;">{{ Auth::user()->school_id }}</small>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center font-bold text-black shadow-sm overflow-hidden"
                                style="width: 40px; height: 40px; background-color: white;">
                                @if(Auth::user()->profile_photo)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                @endif
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2 mt-2">
                            <div class="px-3 py-2 mb-2 border-bottom d-lg-none">
                                <div class="fw-bold">{{ Auth::user()->name }}</div>
                                <small class="text-muted">{{ Auth::user()->role }}</small>
                            </div>
                            <li>
                                <a class="dropdown-item rounded-3 small d-flex align-items-center" href="{{ route('profile.edit') }}">
                                    <i data-lucide="settings" class="me-2 text-muted" style="width:14px"></i> Account Setting
                                </a>
                            </li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger rounded-3 small d-flex align-items-center">
                                        <i data-lucide="log-out" class="me-2" style="width:14px"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="p-1 p-lg-3">
                <div class="main-content-inner p-3 p-md-4">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <!-- GLOBAL REQUISITION ENGINE -->
    <div class="modal fade" id="globalRequestModal" tabindex="-1" aria-hidden="true"
        x-data="cartSystem()"
        @hidden-bs-modal.window="resetModal()">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">

                <!-- MODAL HEADER: DYNAMIC ACCENT -->
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom"
                    :class="tier === 'major' ? 'bg-warning-subtle' : 'bg-success-subtle'">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 me-3" :class="tier === 'major' ? 'bg-warning text-white' : 'bg-success text-white'">
                            <i :data-lucide="tier === 'major' ? 'shield-alert' : 'zap'" style="width: 20px;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Institutional Requisition</h5>
                            <span class="small fw-bold opacity-75 uppercase tracking-widest" x-text="view === 'selection' ? 'Select Priority' : tier + ' Tier Active'"></span>
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
                                    @foreach($availableSupplies as $s)
                                        <option value="{{ json_encode([
                                            'id' => $s->id,
                                            'name' => $s->item_name,
                                            'specs' => $s->brand . ' — ' . $s->physical_description,
                                            'unit' => $s->unit,
                                            'price' => $s->unit_price
                                        ]) }}">
                                            {{ $s->item_name }} ({{ $s->brand }}) — ₱{{ number_format($s->unit_price, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-2 d-block" style="font-size: 9px;">Official items have pre-validated prices and specifications.</small>
                            </div>

                            <!-- THE CART LIST -->
                            <div class="pe-2">
                                <template x-for="(item, index) in cart" :key="index">
                                    <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 border-start border-4 transition-all"
                                        :class="item.is_manual ? 'border-warning' : 'border-success'">

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-7">
                                                <span class="badge bg-light text-dark mb-2" style="font-size: 8px;" x-text="item.is_manual ? 'CUSTOM ITEM' : 'OFFICIAL STOCK'"></span>

                                                <input type="text" x-model="item.name" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0 shadow-none"
                                                    :readonly="!item.is_manual" :placeholder="item.is_manual ? 'Enter Item Name...' : ''" style="font-size: 1.1rem;">

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
                                                    <button type="button" @click="removeItem(index)" class="btn btn-sm text-danger p-0 d-flex align-items-center" style="font-size: 10px;">
                                                        <i data-lucide="trash-2" class="me-1" style="width:12px"></i> Remove
                                                    </button>
                                                    <div class="fw-bold text-success small" style="letter-spacing: 0.5px;">
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
                                    <small class="text-muted uppercase fw-bold d-block mb-1" style="font-size: 10px;">ESTIMATED GRAND TOTAL</small>
                                    <h2 class="fw-black mb-0"
                                        :class="(tier === 'minor' && grandTotal > 1000) ? 'text-danger' : 'text-success'"
                                        style="font-size: 2rem;">
                                        ₱<span x-text="formatMoney(grandTotal)"></span>
                                    </h2>

                                    <!-- Warning -->
                                    <template x-if="tier === 'minor' && grandTotal > 1000">
                                        <div class="mt-2 text-danger fw-bold animate__animated animate__shakeX" style="font-size: 9px; line-height: 1.2;">
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
                                                style="border-radius: 12px; font-size: 0.9rem;"
                                                :disabled="tier === 'minor' && grandTotal > 1000"
                                                :class="tier === 'minor' && grandTotal > 1000 ? 'opacity-50 grayscale cursor-not-allowed' : ''">
                                            SUBMIT REQUISITION
                                        </button>
                                    </form>
                                    <p class="text-center text-muted mt-3 mb-0" style="font-size: 9px;">
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

    <!-- LIVE TOAST NOTIFICATION SYSTEM -->
    <div x-data="{
            show: false,
            message: '',
            title: '',
            type: 'info',
            lastId: null,

            init() {
                // Check for new notifications every 15 seconds
                setInterval(() => {
                    fetch('/api/latest-notification')
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.id !== this.lastId) {
                                this.title = data.title;
                                this.message = data.message;
                                this.type = data.type;
                                this.lastId = data.id;
                                this.show = true;

                                // Play a subtle sound (Optional)
                                // new Audio('/sounds/notification.mp3').play();

                                // Auto-hide after 8 seconds
                                setTimeout(() => { this.show = false; }, 8000);
                            }
                        });
                }, 15000);
            }
        }"
        x-show="show"
        x-transition:enter="animate__animated animate__fadeInUp"
        x-transition:leave="animate__animated animate__fadeOutDown"
        class="position-fixed bottom-0 end-0 p-4"
        style="z-index: 9999; display: none;"
        x-cloak>

        <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="width: 320px; border-left: 5px solid var(--htc-green) !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-start">
                    <div class="p-2 rounded-circle bg-light me-3">
                        <i data-lucide="bell-ring" class="text-success" style="width: 18px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 small text-dark" x-text="title"></h6>
                        <p class="text-muted mb-2" style="font-size: 11px; line-height: 1.3;" x-text="message"></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('notifications') }}" class="small fw-bold text-success text-decoration-none" style="font-size: 10px;">VIEW DETAILS</a>
                            <button @click="show = false" class="btn btn-link p-0 text-muted shadow-none" style="font-size: 10px;">Dismiss</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Initialize Icons
        lucide.createIcons();

        // Cart Engine
        function cartSystem() {
            return {
                tier: 'minor',
                view: 'selection',
                manualMode: false,
                cart: [],

                setTier(t) {
                    this.tier = t;
                    this.view = 'form';
                    this.cart = []; // Clear cart for fresh start
                    this.manualMode = false;
                    setTimeout(() => lucide.createIcons(), 50);
                },

                // ADD FROM OFFICIAL SMO REGISTRY
                addItemFromInventory(jsonString) {
                    if (!jsonString) return;
                    const data = JSON.parse(jsonString);

                    // If item already in cart, just increment quantity
                    const existing = this.cart.find(i => i.item_id === data.id);
                    if (existing) {
                        existing.qty++;
                        return;
                    }

                    this.cart.push({
                        item_id: data.id,
                        name: data.name,
                        specs: data.specs,
                        qty: 1, // User can change this in the UI
                        unit: data.unit,
                        price: data.price,
                        is_manual: false
                    });
                    setTimeout(() => lucide.createIcons(), 10);
                },

                // ADD CUSTOM ITEM
                addItemManual() {
                    this.cart.push({
                        item_id: null,
                        name: '',
                        specs: '',
                        qty: 1,
                        unit: 'PC/PCS',
                        price: 0,
                        is_manual: true
                    });
                    setTimeout(() => lucide.createIcons(), 10);
                },

                removeItem(index) { this.cart.splice(index, 1); },
                get grandTotal() { return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0); },
                formatMoney(val) { return parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits: 2}); },
                resetModal() { this.view = 'selection'; this.cart = []; }
            }
        }
        // Modal Controls
        const myModalEl = document.getElementById('globalRequestModal')
            myModalEl.addEventListener('hidden.bs.modal', event => {
                // This ensures if they open it again, it starts at the cards
                const alpine = document.querySelector('[x-data]').__x.$data;
                alpine.view = 'selection';
                alpine.cart = [{ name: '', specs: '', qty: 1, unit: 'pc', price: 0 }];
            })

        function countUp(target, duration = 1000) {
            return {
                current: 0,
                target: target,
                init() {
                    let start = 0;
                    const increment = this.target / (duration / 16);
                    const timer = setInterval(() => {
                        start += increment;
                        if (start >= this.target) {
                            this.current = this.target;
                            clearInterval(timer);
                        } else {
                            this.current = Math.floor(start);
                        }
                    }, 16);
                }
            }
        }
    </script>
</body>
</html>
