<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.browser-title')
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

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

        html { font-size: 14px; } /* Standard for professional dashboards */

        body {
            background:
                radial-gradient(circle at 8% 8%, rgba(34, 197, 94, .07), transparent 26rem),
                radial-gradient(circle at 92% 92%, rgba(20, 83, 45, .055), transparent 24rem),
                #f3f7f4;
            font-family: 'Inter', sans-serif;
            color: #334155;
            overflow-x: hidden;
            scroll-behavior: auto !important; /* Removes smooth scrolling "anchor" feel */
        }

        :target { background: none; padding-top: 0; }

        /* Sidebar Styling */
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; isolation: isolate; overflow: hidden; background: linear-gradient(145deg, #052e16 0%, #14532d 48%, #166534 100%); border-right: 1px solid rgba(7, 42, 25, .45); z-index: 1000; padding: 1.25rem; display: flex; flex-direction: column; box-shadow: 8px 0 28px rgba(15, 56, 36, .16); }
        .sidebar::before, .sidebar::after { content: ''; position: absolute; z-index: -1; border-radius: 999px; pointer-events: none; background: rgba(255, 255, 255, .055); }
        .sidebar::before { width: 18rem; height: 18rem; top: -10rem; right: -10rem; }
        .sidebar::after { width: 13rem; height: 13rem; right: -7rem; bottom: -5rem; background: rgba(74, 222, 128, .07); }
        .sidebar > * { position: relative; z-index: 1; }
        .brand-section { display: flex; align-items: center; padding-bottom: 1.35rem; margin-bottom: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, .14); }
        .brand-logo { width: 45px; height: 45px; object-fit: contain; margin-right: 12px; padding: 3px; border-radius: 12px; background: rgba(255, 255, 255, .96); box-shadow: 0 5px 16px rgba(5, 30, 17, .18); }
        .brand-title { font-size: 0.95rem; font-weight: 800; letter-spacing: 0.5px; line-height: 1.2; color: #fff; text-transform: uppercase; }

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
            background: rgba(255, 255, 255, .24);
            border-radius: 10px;
        }
        .sidebar .flex-grow-1::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, .5);
        }

        /* Ensure the bottom button area stays at the bottom and has a solid background */
        .sidebar-footer {
            padding-top: 1rem;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, .14);
            background: transparent;
            z-index: 10;
        }

        .nav-section-label { font-size: 0.66rem; font-weight: 800; color: rgba(225, 242, 232, .58); text-transform: uppercase; letter-spacing: 1.15px; margin: 1.5rem 0 0.75rem 0.75rem; }
        .sidebar .flex-grow-1 > .nav-section-label:first-child { margin-top: .75rem; }
        .nav-link-custom { position: relative; display: flex; align-items: center; padding: 0.65rem 0.85rem; color: rgba(246, 252, 248, .78); text-decoration: none; border: 1px solid transparent; border-radius: 12px; margin-bottom: 4px; font-weight: 600; font-size: 0.85rem; transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease; }
        .nav-link-custom:hover { background-color: rgba(255, 255, 255, .1); border-color: rgba(255, 255, 255, .08); color: #fff; transform: translateX(3px); }
        .nav-link-custom.active { background-color: rgba(255, 255, 255, .17); border-color: rgba(255, 255, 255, .2); color: #fff; box-shadow: 0 7px 18px rgba(5, 33, 19, .18); }
        .nav-link-custom.active::before { content: ''; position: absolute; top: 22%; bottom: 22%; left: -1px; width: 3px; border-radius: 0 5px 5px 0; background: #f5cf61; }
        .nav-link-custom:focus-visible, .btn-new-request:focus-visible, .sidebar-close:focus-visible { outline: 3px solid rgba(245, 207, 97, .7); outline-offset: 2px; }
        .nav-link-custom [data-lucide] { width: 16px; height: 16px; margin-right: 12px; stroke-width: 2.5px; }
        .sidebar-count { min-width: 21px; height: 21px; display: inline-grid; margin-left: auto; padding: 0 .35rem; place-items: center; border: 1px solid rgba(255,255,255,.2); border-radius: 999px; color: #fff; background: #c73a43; font-size: .55rem; font-weight: 900; box-shadow: 0 4px 10px rgba(77,10,15,.2); }

        /* Main Content & Top Bar */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); }
        .top-bar { min-height: 72px; background: radial-gradient(circle at 14% -170%, rgba(74, 222, 128, .26), transparent 21rem), radial-gradient(circle at 92% 180%, rgba(255, 255, 255, .11), transparent 15rem), linear-gradient(120deg, #052e16, #14532d 55%, #166534); border-bottom: 1px solid rgba(255, 255, 255, .12); display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0 1.5rem; position: sticky; top: 0; z-index: 1010; box-shadow: 0 7px 22px rgba(9, 54, 29, .16); }
        .page-context { min-width: 0; display: flex; flex-direction: column; gap: .12rem; }
        .page-eyebrow { color: #a7e7bd; font-size: .58rem; font-weight: 800; letter-spacing: .14em; line-height: 1; text-transform: uppercase; }
        .page-title { overflow: hidden; color: white; font-weight: 800; font-size: 1.08rem; line-height: 1.35; text-overflow: ellipsis; white-space: nowrap; }
        .mobile-menu-button, .sidebar-close { display: none; }
        .sidebar-backdrop { display: none; }

        .header-actions { display: flex; align-items: center; gap: .65rem; }
        .role-badge { font-size: 0.61rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; padding: 6px 10px; border-radius: 999px; background: rgba(255, 255, 255, .1); color: #d8f5e2; border: 1px solid rgba(255, 255, 255, .16); }
        .user-menu-trigger { min-height: 50px; padding: .35rem .4rem .35rem .75rem !important; border: 1px solid transparent !important; color: #fff; transition: background-color .2s ease, border-color .2s ease; }
        .user-menu-trigger:hover, .user-menu-trigger:focus, .user-menu-trigger[aria-expanded="true"] { background: rgba(255, 255, 255, .1); border-color: rgba(255, 255, 255, .14) !important; color: #fff; }
        .header-user-copy { text-align: right; line-height: 1.1; }
        .header-user-copy strong { display: block; max-width: 170px; overflow: hidden; color: #fff; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
        .header-user-copy small { color: rgba(230, 247, 236, .7); font-size: .6rem; }
        .header-avatar { width: 40px; height: 40px; flex: 0 0 40px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid rgba(255, 255, 255, .72); border-radius: 50%; background: #fff; color: #14532d; font-weight: 800; box-shadow: 0 5px 14px rgba(2, 28, 13, .24); }
        .header-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .header-menu-chevron { width: 14px; height: 14px; color: rgba(235, 248, 239, .75); transition: transform .2s ease; }
        .user-menu-trigger[aria-expanded="true"] .header-menu-chevron { transform: rotate(180deg); }
        .top-bar .dropdown-menu { min-width: 220px; padding: .55rem !important; border: 1px solid rgba(20, 83, 45, .1) !important; border-radius: 14px; box-shadow: 0 16px 38px rgba(11, 50, 28, .18) !important; }
        .top-bar .dropdown-item { min-height: 38px; }
        .main-content-inner { background: linear-gradient(145deg, rgba(255, 255, 255, .98), rgba(248, 251, 249, .96)); border-radius: 18px; box-shadow: 0 12px 34px rgba(17, 62, 38, .07); min-height: calc(100vh - 160px); border: 1px solid rgba(24, 91, 59, .1); padding: 1.5rem !important; }

        /* Shared application component system */
        .main-content-inner .card { border: 1px solid #e0e9e3 !important; border-radius: 16px !important; background: rgba(255, 255, 255, .96); box-shadow: 0 7px 22px rgba(24, 70, 43, .055) !important; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
        .main-content-inner .card:hover { border-color: rgba(24, 91, 59, .22) !important; box-shadow: 0 12px 28px rgba(24, 70, 43, .085) !important; }
        .main-content-inner .card-header { padding: 1rem 1.15rem; border-bottom: 1px solid #e5ece7; border-radius: 16px 16px 0 0 !important; background: linear-gradient(135deg, #f7fbf8, #eef6f1); color: #213c2d; font-weight: 800; }
        .main-content-inner .card-footer { padding: .9rem 1.15rem; border-top: 1px solid #e7ede9; border-radius: 0 0 16px 16px !important; background: #fafcfb; }

        .main-content-inner .btn { border-radius: 10px; font-weight: 700; transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease; }
        .main-content-inner .btn:hover:not(:disabled) { transform: translateY(-1px); }
        .main-content-inner :is(.btn-success, .btn-primary, .btn-htc) { border-color: #17633e; background: linear-gradient(135deg, #17633e, #218a55); color: #fff; box-shadow: 0 6px 15px rgba(24, 91, 59, .18); }
        .main-content-inner :is(.btn-success, .btn-primary, .btn-htc):hover { border-color: #104a2e; background: linear-gradient(135deg, #104a2e, #176f44); color: #fff; box-shadow: 0 8px 18px rgba(24, 91, 59, .24); }
        .main-content-inner .btn-light { border-color: #dce6df; background: #f8fbf9; color: #42564a; }
        .main-content-inner .btn-light:hover { border-color: #bdcec3; background: #eef5f0; color: #234432; }
        .main-content-inner .btn-outline-success { border-color: #7eaa90; color: #17633e; }
        .main-content-inner .btn-outline-success:hover { border-color: #17633e; background: #17633e; color: #fff; }
        .main-content-inner .btn-danger { border-color: #bd3840; background: linear-gradient(135deg, #bd3840, #dc4d55); box-shadow: 0 5px 14px rgba(189, 56, 64, .16); }
        .main-content-inner .btn:focus-visible { outline: 3px solid rgba(44, 153, 91, .24); outline-offset: 2px; box-shadow: none; }

        .main-content-inner :is(.form-label, label.form-label) { margin-bottom: .45rem; color: #53665a !important; font-size: .67rem; font-weight: 800 !important; letter-spacing: .045em; text-transform: uppercase; }
        .main-content-inner :is(.form-control, .form-select) { min-height: 40px; border: 1px solid #d8e4dc; border-radius: 10px; background-color: #f9fbfa; color: #26372d; box-shadow: inset 0 0 0 1px rgba(216, 228, 220, .35); transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }
        .main-content-inner :is(.form-control, .form-select):hover:not(:disabled):not([readonly]) { border-color: #b8ccbf; background-color: #fff; }
        .main-content-inner :is(.form-control, .form-select):focus { border-color: #2d8a57; background-color: #fff; box-shadow: 0 0 0 3px rgba(31, 125, 75, .12); }
        .main-content-inner .form-control[readonly], .main-content-inner .form-control:disabled { background-color: #eef2ef; color: #7b887f; }
        .main-content-inner .input-group-text { border-color: #d8e4dc; border-radius: 10px 0 0 10px; background: #eef6f1 !important; color: #39704f; }
        .main-content-inner .form-check-input:checked { border-color: #1b7045; background-color: #1b7045; }
        .main-content-inner .invalid-feedback { font-size: .67rem; font-weight: 600; }

        .main-content-inner .table-responsive { border: 1px solid #e1e9e4; border-radius: 14px; background: #fff; }
        .main-content-inner .table { margin-bottom: 0; --bs-table-bg: transparent; --bs-table-hover-bg: #f4f9f6; }
        .main-content-inner .table thead th { padding: .85rem .75rem; border-bottom-color: #dce6df; background: #f3f8f5; color: #607168; font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; vertical-align: middle; }
        .main-content-inner .table tbody td { padding: .85rem .75rem; border-color: #e8eeea; color: #3d4d43; vertical-align: middle; }
        .main-content-inner .table tbody tr:last-child td { border-bottom: 0; }

        .main-content-inner .alert { border: 1px solid transparent !important; border-left-width: 4px !important; border-radius: 12px !important; box-shadow: 0 6px 18px rgba(24, 60, 39, .055); }
        .main-content-inner .alert-success { border-color: #8fc7a5 !important; background: #edf8f1; color: #205f3b; }
        .main-content-inner .alert-warning { border-color: #e1b54e !important; background: #fff9e9; color: #795810; }
        .main-content-inner .alert-danger { border-color: #df7b81 !important; background: #fff1f2; color: #8d2930; }
        .main-content-inner .badge { padding: .38rem .58rem; border-radius: 999px; font-weight: 800; letter-spacing: .02em; }
        .main-content-inner .nav-pills { gap: .35rem; padding: .3rem; border: 1px solid #e0e9e3; border-radius: 12px; background: #f4f8f5; }
        .main-content-inner .nav-pills .nav-link { border-radius: 9px; color: #5e7065; font-weight: 700; }
        .main-content-inner .nav-pills .nav-link.active { background: linear-gradient(135deg, #17633e, #218a55); color: #fff; box-shadow: 0 5px 13px rgba(24, 91, 59, .18); }
        .main-content-inner .list-group-item { border-color: #e5ece7; color: #4d5f54; }
        .main-content-inner .list-group-item-action:hover { background: #eef7f1; color: #185b3b; }
        .main-content-inner nav.d-flex.justify-items-center { width: 100%; align-items: center; gap: 1rem; }
        .main-content-inner nav.d-flex.justify-items-center p { margin: 0; color: #7b8981 !important; font-size: .62rem; }
        .main-content-inner .pagination { flex-wrap: wrap; gap: .25rem; margin: 0; }
        .main-content-inner .page-item { margin: 0; }
        .main-content-inner .page-link { min-width: 34px; min-height: 34px; display: inline-flex; align-items: center; justify-content: center; padding: .4rem .65rem; border: 1px solid #dce6df; border-radius: 8px !important; color: #286343; background: #fff; font-size: .66rem; font-weight: 700; line-height: 1; text-align: center; box-shadow: none; }
        .main-content-inner .page-link:hover { border-color: #76a88a; color: #155d38; background: #edf7f0; }
        .main-content-inner .page-link:focus { border-color: #2e8255; box-shadow: 0 0 0 3px rgba(31, 125, 75, .12); }
        .main-content-inner .page-item.active > .page-link { border-color: #185b3b; background: linear-gradient(135deg, #185b3b, #218a55); color: #fff; box-shadow: 0 5px 12px rgba(24, 91, 59, .2); }
        .main-content-inner .page-item.disabled > .page-link { border-color: #e8ede9; color: #a8b1ab; background: #f6f8f7; opacity: .75; }

        .modal-backdrop.show { opacity: .58; backdrop-filter: blur(3px); }
        .modal-content { border: 1px solid rgba(24, 91, 59, .11) !important; border-radius: 18px !important; box-shadow: 0 24px 65px rgba(8, 42, 23, .22) !important; }
        .modal-header { padding: 1rem 1.2rem; border-bottom-color: #e3ebe6; background: linear-gradient(135deg, #f8fbf9, #eef6f1); }
        .modal-footer { padding: .85rem 1.2rem; border-top-color: #e5ece7; background: #fafcfb; }

        .btn-new-request { background-color: #fff; color: var(--htc-green); border-radius: 12px; padding: 0.8rem; font-weight: 800; border: 1px solid rgba(255, 255, 255, .5); box-shadow: 0 7px 18px rgba(5, 33, 19, .18); transition: all 0.25s ease; }
        .btn-new-request:hover { background-color: #edf8f1; color: #10462e; transform: translateY(-2px); }
        .sidebar-footer .text-muted { color: rgba(235, 247, 239, .7) !important; opacity: 1 !important; }

        .dropdown-menu {
            display: block;
            visibility: hidden;
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            border: none !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-menu.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-item {
            transition: all 0.2s ease;
            padding: 0.6rem 1rem;
        }

        .dropdown-item:hover {
            background-color: var(--htc-light-green);
            color: var(--htc-green);
            transform: translateX(5px);
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
            flex-direction: row !important;
        }

        .step-item {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            min-width: 80px;
        }

        .step-item::after {
            content: "";
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 3px;
            background-color: #e2e8f0;
            z-index: -1;
            transition: all 0.5s ease;
        }

        .step-item:last-child::after {
            display: none;
        }

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

        .step-item.completed .step-icon {
            background-color: var(--htc-green);
            border-color: var(--htc-green);
            color: white !important;
        }

        .step-item.completed .step-icon [data-lucide] {
            color: white !important;
            stroke: currentColor;
        }

        .step-item.completed::after {
            background-color: var(--htc-green);
        }

        .step-item.active .step-icon {
            border-color: #ffc107;
            color: #ffc107;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
        }

        .step-item.active .step-icon [data-lucide] {
            color: #ffc107 !important;
            stroke: currentColor;
        }

        .step-item.active .step-icon {
            border-color: #ffc107;
            color: #ffc107;
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.2);
        }

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

        .step-item.active .step-label {
            color: #856404;
        }

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

        .step-item.in-progress::after {
            background: linear-gradient(90deg, var(--htc-green) 0%, #e2e8f0 50%, var(--htc-green) 100%);
            background-size: 200% 100%;
            animation: lineFlow 2s linear infinite;
        }

        .step-item.active .step-icon {
            border-color: #ffc107;
            animation: pulseActive 2s infinite;
        }

        .step-item .step-icon,
        .step-item::after {
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

        /* Requisition marketplace */
        .requisition-shell {
            border-radius: 22px !important;
            min-height: 680px;
            background: #fff;
        }

        .requisition-header {
            padding: 1.4rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e8eeeb;
        }

        .requisition-eyebrow {
            color: var(--htc-green);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .requisition-progress {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding-top: 1rem;
            padding-bottom: 1rem;
            background: #fbfcfb;
            border-bottom: 1px solid #edf1ef;
        }

        .requisition-progress div {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: #94a3b8;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .requisition-progress div::after {
            content: '';
            height: 2px;
            flex: 1;
            background: #e2e8f0;
        }

        .requisition-progress div:last-child::after {
            display: none;
        }

        .requisition-progress span {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-grid;
            place-items: center;
            background: #e2e8f0;
            color: #64748b;
        }

        .requisition-progress .active {
            color: var(--htc-green);
        }

        .requisition-progress .active span,
        .requisition-progress .done span {
            background: var(--htc-green);
            color: #fff;
        }

        .requisition-progress .done::after {
            background: var(--htc-green);
        }

        .tier-stage {
            min-height: 510px;
            background: radial-gradient(circle at 50% 0, #f0f8f3, #fff 52%);
        }

        .tier-choice {
            min-height: 330px;
            border: 1px solid #dfe8e3;
            border-radius: 22px;
            background: #fff;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            transition: .2s ease;
            box-shadow: 0 12px 30px rgba(15, 45, 29, .05);
        }

        .tier-choice:hover {
            transform: translateY(-5px);
            border-color: var(--htc-green);
            box-shadow: 0 20px 44px rgba(24, 91, 59, .12);
        }

        .tier-choice .tier-icon {
            width: 58px;
            height: 58px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            margin-bottom: 1.5rem;
        }

        .tier-choice .tier-icon svg {
            width: 27px;
            height: 27px;
        }

        .tier-minor .tier-icon {
            background: #e9f7ef;
            color: #137044;
        }

        .tier-major .tier-icon {
            background: #fff5dd;
            color: #a16207;
        }

        .tier-choice .tier-kicker {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .tier-choice strong {
            display: block;
            color: #14251c;
            font-size: 1.35rem;
            margin: .35rem 0 .65rem;
        }

        .tier-choice > span:not(.tier-icon):not(.tier-kicker) {
            color: #64748b;
            line-height: 1.65;
        }

        .tier-choice small {
            margin-top: auto;
            padding-top: 1.25rem;
            border-top: 1px solid #edf1ef;
            width: 100%;
            color: #64748b;
        }

        .requisition-workspace {
            min-height: 540px;
        }

        .catalog-pane {
            padding: 1.5rem 1.75rem;
            background: #f7f9f8;
            max-height: 610px;
            overflow-y: auto;
        }

        .catalog-toolbar {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .catalog-toolbar svg,
        .product-configurator .btn svg,
        .btn-htc svg {
            width: 15px;
            height: 15px;
        }

        .tier-pill {
            padding: .45rem .8rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .tier-pill.minor {
            color: #137044;
            background: #e7f6ed;
        }

        .tier-pill.major {
            color: #946200;
            background: #fff3d1;
        }

        .catalog-search {
            margin-left: auto;
            flex: 1;
            max-width: 320px;
            height: 40px;
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: 0 .85rem;
            border: 1px solid #dfe7e2;
            border-radius: 12px;
            background: #fff;
        }

        .catalog-search svg {
            width: 16px;
            color: #94a3b8;
        }

        .catalog-search input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: .82rem;
            background: transparent;
        }

        .category-chips {
            display: flex;
            gap: .5rem;
            padding: 1.25rem 0;
            overflow-x: auto;
        }

        .category-chips button {
            border: 1px solid #dfe7e2;
            background: #fff;
            color: #64748b;
            border-radius: 999px;
            padding: .45rem .8rem;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .category-chips button.active {
            background: var(--htc-green);
            color: #fff;
            border-color: var(--htc-green);
        }

        .catalog-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            margin: .4rem 0 1rem;
        }

        .catalog-notice {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin: 1rem 0 0;
            padding: .7rem .85rem;
            border: 1px solid #bfe2ca;
            border-radius: 10px;
            background: #edf8f1;
            color: #17613b;
            font-size: .75rem;
            font-weight: 700;
        }

        .catalog-notice svg {
            width: 16px;
            height: 16px;
        }

        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .product-card {
            padding: 0;
            overflow: hidden;
            border: 1px solid #e1e8e4;
            border-radius: 16px;
            background: #fff;
            text-align: left;
            transition: .2s ease;
        }

        .product-card:hover {
            border-color: var(--htc-green);
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(24, 91, 59, .1);
        }

        .product-visual {
            min-height: 105px;
            background: linear-gradient(135deg, #e7f2eb, #f9fbfa);
            display: grid;
            place-items: center;
            position: relative;
            color: var(--htc-green);
        }

        .product-visual svg {
            width: 36px;
            height: 36px;
        }

        .product-visual em {
            position: absolute;
            left: .75rem;
            bottom: .6rem;
            font-size: .62rem;
            font-style: normal;
            font-weight: 700;
            color: #5c7567;
        }

        .product-body {
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }

        .product-body > small {
            color: #8b9b92;
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .product-body > strong {
            color: #15251c;
            margin: .2rem 0;
            font-size: .9rem;
        }

        .product-spec {
            color: #718078;
            font-size: .72rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-top: 1rem;
        }

        .product-meta b {
            color: var(--htc-green);
            font-size: 1rem;
        }

        .product-meta em {
            color: #87958d;
            font-size: .62rem;
            font-style: normal;
        }

        .empty-catalog,
        .cart-empty {
            min-height: 260px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
            gap: .5rem;
        }

        .empty-catalog svg,
        .cart-empty svg {
            width: 40px;
            height: 40px;
            opacity: .45;
        }

        .empty-catalog strong,
        .cart-empty strong {
            color: #526158;
        }

        .product-configurator {
            padding: .5rem 0;
        }

        .product-hero {
            height: 330px;
            border-radius: 20px;
            background: linear-gradient(145deg, #dceee3, #f5f9f6);
            color: var(--htc-green);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            justify-content: center;
        }

        .product-hero svg {
            width: 75px;
            height: 75px;
        }

        .product-hero span {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .product-description {
            color: #6b7c72;
            font-size: .8rem;
            line-height: 1.6;
        }

        .option-group {
            margin-top: 1.15rem;
        }

        .option-group label {
            display: block;
            font-size: .72rem;
            font-weight: 800;
            margin-bottom: .55rem;
            color: #34473c;
        }

        .option-group > div {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .option-group button {
            padding: .5rem .8rem;
            border: 1px solid #d9e2dd;
            border-radius: 8px;
            background: #fff;
            color: #5a6c62;
            font-size: .74rem;
        }

        .option-group button.active {
            border-color: var(--htc-green);
            color: var(--htc-green);
            background: #eff8f2;
            box-shadow: inset 0 0 0 1px var(--htc-green);
        }

        .quantity-picker,
        .mini-quantity {
            display: flex;
            align-items: center;
            border: 1px solid #dce4df;
            border-radius: 9px;
            overflow: hidden;
            background: #fff;
        }

        .quantity-picker button,
        .mini-quantity button {
            border: 0;
            background: #f5f7f6;
            color: #43544a;
        }

        .quantity-picker button {
            width: 34px;
            height: 42px;
        }

        .quantity-picker input {
            width: 48px;
            height: 42px;
            border: 0;
            text-align: center;
            font-weight: 700;
        }

        .cart-pane {
            display: flex;
            flex-direction: column;
            max-height: 610px;
            background: #fff;
            border-left: 1px solid #e4ebe7;
        }

        .cart-title {
            padding: 1.4rem;
            border-bottom: 1px solid #edf1ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cart-title > div {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .cart-title > span {
            color: #839088;
            font-size: .7rem;
        }

        .cart-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            display: grid;
            place-items: center;
            color: var(--htc-green);
            background: #eaf5ee;
        }

        .cart-icon svg {
            width: 16px;
        }

        .cart-items {
            padding: 1rem 1.35rem;
            flex: 1;
            overflow-y: auto;
        }

        .cart-item {
            border-bottom: 1px solid #edf1ef;
            padding: .9rem 0;
        }

        .cart-item strong {
            color: #26382e;
            font-size: .82rem;
        }

        .cart-item small {
            display: block;
            color: #87958d;
            font-size: .67rem;
            margin-top: .2rem;
        }

        .cart-item > div button {
            border: 0;
            background: transparent;
            color: #94a3b8;
        }

        .cart-item > div button svg {
            width: 14px;
            height: 14px;
        }

        .mini-quantity button {
            width: 25px;
            height: 25px;
        }

        .mini-quantity span {
            min-width: 28px;
            text-align: center;
            font-size: .7rem;
            font-weight: 700;
        }

        .cart-summary {
            padding: 1.25rem 1.4rem;
            background: #f8faf9;
            border-top: 1px solid #e4ebe7;
        }

        .reason-field {
            display: block !important;
            margin-bottom: 1rem !important;
            color: inherit !important;
        }

        .reason-field label {
            display: block;
            margin-bottom: .4rem;
            color: #34473c;
            font-size: .72rem;
            font-weight: 800;
        }

        .reason-field textarea {
            display: block;
            width: 100%;
            resize: vertical;
            min-height: 74px;
            padding: .7rem .8rem;
            border: 1px solid #d7e1db;
            border-radius: 10px;
            background: #fff;
            color: #283a30;
            font: inherit;
            font-size: .75rem;
            line-height: 1.45;
            outline: none;
            transition: .2s ease;
        }

        .reason-field textarea:focus {
            border-color: var(--htc-green);
            box-shadow: 0 0 0 3px rgba(24, 91, 59, .1);
        }

        .reason-field textarea::placeholder {
            color: #a0aaa4;
        }

        .reason-field > span {
            display: flex;
            justify-content: space-between;
            margin-top: .3rem;
            color: #94a3b8;
            font-size: .62rem;
        }

        .reason-field em {
            font-style: normal;
        }

        .live-refresh-notice {
            position: fixed;
            top: 76px;
            right: 20px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: .65rem;
            max-width: 360px;
            padding: .8rem 1rem;
            border: 1px solid #cfe2d6;
            border-radius: 12px;
            background: #fff;
            color: #244c34;
            font-size: .75rem;
            font-weight: 700;
            box-shadow: 0 12px 32px rgba(20, 55, 34, .16);
        }

        .live-refresh-notice[hidden] {
            display: none !important;
        }

        .live-refresh-notice .spinner-border {
            color: var(--htc-green);
        }

        .notification-toast-wrap {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 10050;
            width: min(390px, calc(100vw - 28px));
        }

        .notification-toast {
            position: relative;
            overflow: hidden;
            border: 1px solid #dce7e0;
            border-radius: 17px;
            background: rgba(255,255,255,.98);
            box-shadow: 0 20px 55px rgba(20, 48, 31, .2);
            backdrop-filter: blur(12px);
        }

        .notification-toast-accent {
            height: 4px;
            background: #397a56;
        }

        .notification-toast.type-warning .notification-toast-accent {
            background: #e2a126;
        }

        .notification-toast.type-danger .notification-toast-accent {
            background: #d13e47;
        }

        .notification-toast.type-success .notification-toast-accent {
            background: #21804c;
        }

        .notification-toast-body {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            padding: 1rem;
        }

        .notification-toast-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: #276947;
            background: #e6f4eb;
        }

        .notification-toast-icon svg {
            width: 20px;
        }

        .type-warning .notification-toast-icon {
            color: #97620a;
            background: #fff0ce;
        }

        .type-danger .notification-toast-icon {
            color: #b32933;
            background: #fde8e9;
        }

        .type-success .notification-toast-icon {
            color: #17643d;
            background: #e2f3e8;
        }

        .notification-toast-content {
            min-width: 0;
            flex: 1;
        }

        .notification-toast-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .25rem;
        }

        .notification-toast-meta span {
            color: #738078;
            font-size: .56rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .notification-toast-meta time {
            color: #a0aaa4;
            font-size: .58rem;
        }

        .notification-toast h6 {
            margin: 0 1.4rem .25rem 0;
            color: #203128;
            font-size: .82rem;
            font-weight: 800;
        }

        .notification-toast p {
            margin: 0;
            color: #6f7d75;
            font-size: .69rem;
            line-height: 1.55;
        }

        .notification-toast-actions {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-top: .75rem;
        }

        .notification-toast-actions a {
            color: var(--htc-green);
            font-size: .63rem;
            font-weight: 800;
            text-decoration: none;
            text-transform: uppercase;
        }

        .notification-toast-actions button {
            padding: 0;
            border: 0;
            background: transparent;
            color: #849088;
            font-size: .62rem;
        }

        .notification-toast-actions button svg {
            width: 14px;
            height: 14px;
        }

        .notification-toast-close {
            position: absolute;
            top: .8rem;
            right: .8rem;
            padding: .15rem;
            border: 0;
            background: transparent;
            color: #98a39d;
        }

        .notification-toast-close svg {
            width: 15px;
        }

        .notification-toast-timer {
            height: 3px;
            background: #397a56;
            transform-origin: left;
            animation: notificationCountdown 9s linear forwards;
        }

        .type-warning .notification-toast-timer {
            background: #e2a126;
        }

        .type-danger .notification-toast-timer {
            background: #d13e47;
        }

        .type-success .notification-toast-timer {
            background: #21804c;
        }

        @keyframes notificationCountdown {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        .cart-summary > div {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .65rem;
            color: #738178;
            font-size: .75rem;
        }

        .cart-summary > div strong {
            color: #173d27;
            font-size: 1.25rem;
        }

        .cart-summary p {
            font-size: .68rem;
            font-weight: 700;
        }

        .cart-summary > small {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            color: #829087;
            font-size: .62rem;
            margin-top: .7rem;
        }

        .cart-summary > small svg {
            width: 13px;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (max-width: 991.98px) {
            .requisition-shell {
                min-height: auto;
            }

            .catalog-pane,
            .cart-pane {
                max-height: none;
            }

            .cart-pane {
                border-left: 0;
                border-top: 1px solid #e4ebe7;
            }
        }

        @media (max-width: 575.98px) {
            .catalog-grid {
                grid-template-columns: 1fr;
            }

            .catalog-toolbar {
                flex-wrap: wrap;
            }

            .catalog-search {
                order: 3;
                max-width: none;
                flex-basis: 100%;
            }

            .requisition-progress {
                gap: .3rem;
            }

            .requisition-progress div {
                font-size: .58rem;
            }
        }

        /* Shared responsive application shell */
        @media (max-width: 991.98px) {
            body.sidebar-open { overflow: hidden; }
            .sidebar {
                width: min(290px, 86vw);
                padding: 1rem;
                z-index: 1045;
                transform: translateX(-105%);
                transition: transform .25s ease, box-shadow .25s ease;
                box-shadow: none;
            }
            .sidebar.is-open { transform: translateX(0); box-shadow: 18px 0 50px rgba(15, 37, 25, .22); }
            .sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1040;
                border: 0;
                background: rgba(13, 32, 22, .48);
                opacity: 0;
                visibility: hidden;
                transition: opacity .25s ease, visibility .25s ease;
            }
            .sidebar-backdrop.is-open { opacity: 1; visibility: visible; }
            .sidebar-close {
                display: inline-grid;
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                margin-left: auto;
                place-items: center;
                border: 0;
                border-radius: 10px;
                border: 1px solid rgba(255, 255, 255, .16);
                background: rgba(255, 255, 255, .12);
                color: #fff;
            }
            .sidebar-close svg { width: 18px; height: 18px; }
            .main-content { width: 100%; margin-left: 0; min-width: 0; }
            .top-bar { min-height: 62px; padding: 0 .9rem; gap: .65rem; }
            .mobile-menu-button {
                display: inline-grid;
                width: 40px;
                height: 40px;
                flex: 0 0 40px;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, .24);
                border-radius: 11px;
                background: rgba(255, 255, 255, .1);
                color: #fff;
            }
            .mobile-menu-button svg { width: 20px; height: 20px; }
            .page-context { min-width: 0; flex: 1; }
            .page-eyebrow { display: none; }
            .page-title { font-size: 1rem; }
            .main-content-inner { min-height: calc(100vh - 82px); padding: 1rem !important; overflow-x: auto; }
            .nav-link-custom { min-height: 44px; padding: .7rem .85rem; }
            .btn-new-request { min-height: 44px; }
        }

        @media (max-width: 575.98px) {
            html { font-size: 15px; }
            .brand-section { padding-bottom: 1rem; }
            .brand-logo { width: 39px; height: 39px; }
            .top-bar .dropdown > button { padding: .25rem !important; }
            .top-bar .dropdown-menu { max-width: calc(100vw - 1rem); }
            .header-avatar { width: 38px; height: 38px; flex-basis: 38px; }
            .header-menu-chevron { display: none; }
            .main-content > .p-1 { padding: .5rem !important; }
            .main-content-inner { border-radius: 12px; padding: .8rem !important; }
            .main-content-inner .container,
            .main-content-inner .container-fluid { padding-left: .35rem; padding-right: .35rem; }
            .main-content-inner h1 { font-size: 1.55rem; }
            .main-content-inner h2 { font-size: 1.35rem; }
            .main-content-inner .card-body { padding: 1rem; }
            .main-content-inner .table { min-width: 680px; }
            .main-content-inner nav.d-flex.justify-items-center { display: block !important; }
            .main-content-inner nav.d-flex.justify-items-center .pagination { width: 100%; flex-wrap: nowrap; justify-content: space-between; }
            .main-content-inner nav.d-flex.justify-items-center .page-item { flex: 1; }
            .main-content-inner nav.d-flex.justify-items-center .page-link { width: 100%; min-height: 40px; }
            .modal-dialog { width: calc(100% - 1rem); margin: .5rem; }
            .modal-content { max-height: calc(100dvh - 1rem); }
            .requisition-header { padding: 1rem; }
            .requisition-progress { padding-left: 1rem !important; padding-right: 1rem !important; overflow-x: auto; }
            .notification-toast-wrap { right: 10px; bottom: 10px; width: calc(100vw - 20px); }
            .live-refresh-notice { top: 68px; right: 10px; left: 10px; max-width: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar, .sidebar-backdrop { transition: none; }
        }

        .mobile-cart-jump { display: none; }

        @media (max-width: 991.98px) {
            #globalRequestModal .requisition-shell { overflow-y: auto !important; }
            #globalRequestModal .requisition-workspace { min-height: 0; }
            #globalRequestModal .catalog-pane,
            #globalRequestModal .cart-pane { max-height: none; overflow: visible; }
            #globalRequestModal .catalog-pane { padding: 1.25rem; }
            #globalRequestModal .cart-pane { border-left: 0; border-top: 1px solid #dce6e0; }
            #globalRequestModal .product-hero { height: 230px; }
            #globalRequestModal .cart-title { position: sticky; top: 0; z-index: 3; background: #fff; }
            #globalRequestModal .option-group button,
            #globalRequestModal .quantity-picker button,
            #globalRequestModal .mini-quantity button { min-height: 40px; }
            #globalRequestModal .mini-quantity button { width: 38px; }
            #globalRequestModal .mobile-cart-jump {
                position: sticky;
                bottom: .75rem;
                z-index: 4;
                width: 100%;
                min-height: 48px;
                margin-top: 1rem;
                padding: .7rem 1rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border: 0;
                border-radius: 12px;
                background: var(--htc-green);
                color: #fff;
                box-shadow: 0 10px 25px rgba(20, 73, 46, .28);
                font-size: .75rem;
                font-weight: 700;
            }
            #globalRequestModal .mobile-cart-jump span { display: flex; align-items: center; gap: .5rem; }
            #globalRequestModal .mobile-cart-jump svg { width: 17px; height: 17px; }
            #globalRequestModal .mobile-cart-jump strong { padding: .2rem .55rem; border-radius: 999px; background: rgba(255,255,255,.18); }
        }

        @media (max-width: 575.98px) {
            #globalRequestModal .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100%;
                min-height: 100%;
                margin: 0;
            }
            #globalRequestModal .requisition-shell {
                width: 100%;
                height: 100dvh;
                max-height: 100dvh;
                min-height: 100dvh;
                border-radius: 0 !important;
            }
            #globalRequestModal .requisition-header {
                position: sticky;
                top: 0;
                z-index: 6;
                gap: .75rem;
                padding: .85rem 1rem;
                background: #fff;
            }
            #globalRequestModal .requisition-header h4 { font-size: 1.05rem; }
            #globalRequestModal .requisition-header p { display: none; }
            #globalRequestModal .requisition-progress { gap: .25rem; padding: .65rem .8rem !important; overflow: hidden; }
            #globalRequestModal .requisition-progress div { gap: .3rem; font-size: .53rem; letter-spacing: 0; white-space: nowrap; }
            #globalRequestModal .requisition-progress div::after { min-width: 8px; }
            #globalRequestModal .requisition-progress span { width: 21px; height: 21px; flex: 0 0 21px; }
            #globalRequestModal .tier-stage { min-height: 0; padding: 1.1rem !important; }
            #globalRequestModal .tier-stage .text-center { margin-bottom: 1rem !important; }
            #globalRequestModal .tier-stage .row { --bs-gutter-y: .8rem; }
            #globalRequestModal .tier-choice { min-height: 0; padding: 1.15rem; border-radius: 16px; }
            #globalRequestModal .tier-choice .tier-icon { width: 42px; height: 42px; margin-bottom: .75rem; border-radius: 12px; }
            #globalRequestModal .tier-choice .tier-icon svg { width: 21px; height: 21px; }
            #globalRequestModal .tier-choice strong { font-size: 1.05rem; margin: .2rem 0 .35rem; }
            #globalRequestModal .tier-choice > span:not(.tier-icon):not(.tier-kicker) { line-height: 1.4; }
            #globalRequestModal .tier-choice small { padding-top: .65rem; margin-top: .75rem; }
            #globalRequestModal .catalog-pane { padding: .85rem; }
            #globalRequestModal .catalog-toolbar { gap: .5rem; }
            #globalRequestModal .catalog-search { height: 44px; }
            #globalRequestModal .category-chips { padding: .8rem 0; scrollbar-width: none; }
            #globalRequestModal .category-chips::-webkit-scrollbar { display: none; }
            #globalRequestModal .category-chips button { min-height: 38px; }
            #globalRequestModal .catalog-heading { align-items: flex-start; gap: .5rem; }
            #globalRequestModal .catalog-heading p { display: none; }
            #globalRequestModal .catalog-grid { grid-template-columns: 1fr; gap: .7rem; }
            #globalRequestModal .product-card { display: grid; grid-template-columns: 88px minmax(0, 1fr); min-height: 112px; }
            #globalRequestModal .product-visual { min-height: 112px; }
            #globalRequestModal .product-visual svg { width: 29px; height: 29px; }
            #globalRequestModal .product-visual em { right: .35rem; left: .35rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            #globalRequestModal .product-body { min-width: 0; padding: .8rem; }
            #globalRequestModal .product-meta { margin-top: .6rem; gap: .4rem; }
            #globalRequestModal .product-meta em { text-align: right; }
            #globalRequestModal .product-configurator > .btn { margin-bottom: 1rem !important; min-height: 40px; }
            #globalRequestModal .product-hero { height: 125px; border-radius: 14px; }
            #globalRequestModal .product-hero svg { width: 45px; height: 45px; }
            #globalRequestModal .product-configurator .row { --bs-gutter-y: 1rem; }
            #globalRequestModal .product-configurator h3 { font-size: 1.35rem; }
            #globalRequestModal .option-group button { min-height: 42px; padding: .55rem .75rem; }
            #globalRequestModal .product-configurator .d-flex.align-items-end { align-items: stretch !important; flex-direction: column; }
            #globalRequestModal .quantity-picker { width: 100%; }
            #globalRequestModal .quantity-picker button { width: 46px; }
            #globalRequestModal .quantity-picker input { flex: 1; width: auto; }
            #globalRequestModal .cart-title,
            #globalRequestModal .cart-items,
            #globalRequestModal .cart-summary { padding: 1rem; }
            #globalRequestModal .cart-empty { min-height: 150px; }
            #globalRequestModal .cart-summary { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
            #globalRequestModal .reason-field textarea { min-height: 92px; font-size: 16px; }
            #globalRequestModal .cart-summary > button[type="submit"] { min-height: 48px; }
        }

        /* Responsive request lists, details, and approval workflow */
        .workflow-stepper-scroll { max-width: 100%; }
        .min-w-0 { min-width: 0; }

        @media (max-width: 767.98px) {
            .workflow-page-heading { align-items: flex-start !important; flex-direction: column; }
            .workflow-heading-action { width: 100%; min-height: 42px; }
            .archive-date-range { display: grid; grid-template-columns: auto minmax(0, 1fr) auto minmax(0, 1fr); }
            .archive-date-range > * { min-width: 0; }
            .workflow-stepper-scroll { margin-right: -.5rem; margin-left: -.5rem; overflow-x: auto; overscroll-behavior-x: contain; scrollbar-width: thin; }
            .workflow-stepper-scroll .tracking-stepper { min-width: 520px; margin-bottom: .5rem; scroll-snap-type: x proximity; }
            .workflow-stepper-scroll .step-item { scroll-snap-align: center; }

            .table-responsive:has(> .workflow-responsive-table) { overflow: visible; }
            .workflow-responsive-table { display: block; min-width: 0 !important; background: #f5f8f6; }
            .workflow-responsive-table thead { display: none; }
            .workflow-responsive-table tbody { display: grid; gap: .75rem; padding: .75rem; }
            .workflow-responsive-table tbody tr { display: block; overflow: hidden; border: 1px solid #e0e8e3; border-radius: 14px; background: #fff; box-shadow: 0 3px 12px rgba(24, 58, 37, .05); }
            .workflow-responsive-table tbody tr:hover { background: #fff; }
            .workflow-responsive-table tbody td {
                display: grid;
                grid-template-columns: minmax(82px, 34%) minmax(0, 1fr);
                align-items: center;
                gap: .75rem;
                width: 100%;
                padding: .7rem .85rem !important;
                border-color: #edf1ee;
                text-align: right !important;
                overflow-wrap: anywhere;
            }
            .workflow-responsive-table tbody td::before {
                content: attr(data-label);
                color: #7b8981;
                font-size: .64rem;
                font-weight: 800;
                letter-spacing: .04em;
                text-align: left;
                text-transform: uppercase;
            }
            .workflow-responsive-table tbody td > * { justify-self: end; max-width: 100%; }
            .workflow-responsive-table tbody td[colspan] { display: block; text-align: center !important; }
            .workflow-responsive-table tbody td[colspan]::before { display: none; }
            .workflow-responsive-table tfoot { display: block; border-top: 1px solid #dce7e0; }
            .workflow-responsive-table tfoot tr { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; }
            .workflow-responsive-table tfoot td { display: block; padding: .85rem !important; }
            .workflow-actions { justify-content: flex-end; flex-wrap: wrap; }
            .workflow-actions .btn { min-height: 36px; }

            .request-detail-actions { align-items: stretch !important; flex-direction: column; }
            .request-pdf-action { width: 100%; min-height: 44px; }
            .request-summary-head { flex-direction: column; }
            .request-summary-head > .text-end { width: 100%; padding-top: .75rem; border-top: 1px solid #edf1ef; text-align: left !important; }
            .request-flow-heading { align-items: flex-start !important; flex-direction: column; }
            .request-details-page .card.p-4 { padding: 1rem !important; }
            .request-details-page .card-header { padding: 1rem !important; }
            .request-reason { padding: .85rem; }

            #approvalsTabs { margin-right: -1rem; margin-left: -1rem; padding: 0 1rem .65rem; overflow-x: auto; scrollbar-width: none; }
            #approvalsTabs::-webkit-scrollbar { display: none; }
            #approvalsTabs .nav-item { flex: 0 0 auto; }
            #approvalsTabs .nav-link { min-height: 42px; white-space: nowrap; }
            .approval-workspace > .card > .card-header { padding: 1rem !important; }
            #reviewModal .modal-header,
            #reviewModal .modal-body { padding: 1rem !important; }
            #reviewModal .modal-title { padding-right: .5rem; font-size: 1rem; }
            #reviewModal textarea { min-height: 84px; font-size: 16px; }
            #reviewModal form .btn { min-height: 44px; }
        }

        @media (max-width: 575.98px) {
            #reviewModal .modal-dialog { width: 100%; max-width: none; height: 100%; min-height: 100%; margin: 0; }
            #reviewModal .modal-content { height: 100dvh; max-height: 100dvh; border-radius: 0 !important; }
            #reviewModal .modal-body { overscroll-behavior: contain; }
            .request-summary-head > div:first-child { align-items: flex-start !important; }
            .request-summary-head .rounded-circle { padding: .7rem !important; }
            .request-details-page .activity-log-item { padding-left: 1.25rem !important; }
        }

        /* Inventory create/edit forms */
        @media (max-width: 767.98px) {
            .inventory-form-meta { align-items: flex-start !important; flex-direction: column; }
            .inventory-form-meta .text-end { text-align: left !important; }
            .inventory-status-card { align-items: flex-start !important; flex-direction: column; }
            .inventory-status-card > .text-end { text-align: left !important; }
            .inventory-options-header { align-items: flex-start !important; flex-direction: column; }
            .inventory-options-header > button { min-height: 40px; }
            .inventory-option-row { margin-bottom: 1rem !important; padding: .75rem; border: 1px solid #e3eae6; border-radius: 12px; background: #f8faf9; }
            .inventory-option-row input { min-height: 42px; }
            .inventory-option-remove { display: flex !important; justify-content: flex-end; }
            .inventory-option-remove .btn { width: 42px; height: 42px; }
            .inventory-form-actions { flex-direction: column-reverse; }
            .inventory-form-actions .btn { width: 100%; min-height: 46px; padding: .8rem 1rem !important; }
        }

        @media (max-width: 575.98px) {
            .inventory-form-page { padding-top: .5rem !important; }
            .inventory-form-page .card-body { padding: 1rem !important; }
            .inventory-form-page input,
            .inventory-form-page select,
            .inventory-form-page textarea { min-height: 44px; font-size: 16px; }
            .inventory-form-page textarea { min-height: 100px; }
        }
    </style>
</head>
<body>
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
                    <i data-lucide="layout-dashboard"></i> Dashboard
                </a>
                <a href="{{ route('notifications') }}" class="nav-link-custom {{ request()->routeIs('notifications') ? 'active' : '' }}">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                    @if(isset($unreadCount) && $unreadCount > 0)
                        <span class="sidebar-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
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
                        <i data-lucide="package-check"></i> Release Queue
                    </a>
                    <a href="{{ route('inventory.index') }}" class="nav-link-custom {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i data-lucide="boxes"></i> Manage Inventory
                    </a>

                    <div class="nav-section-label">Insights</div>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link-custom {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i data-lucide="chart-no-axes-combined"></i> Reports & Analytics
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

    @if(false)
    <!-- LEGACY REQUISITION ENGINE -->
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
                                            'specs' => collect([$s->brand, $s->specifications])->filter()->join(' — '),
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

    @endif

    @include('layouts.partials.requisition-modal')

    <!-- LIVE PRIORITY NOTIFICATION -->
    <div x-data="notificationCenter()" x-show="show" x-transition.opacity.duration.250ms class="notification-toast-wrap" x-cloak>
        <article class="notification-toast" :class="'type-' + type" role="alert" aria-live="assertive">
            <div class="notification-toast-accent"></div>
            <div class="notification-toast-body">
                <div class="notification-toast-icon">
                    <template x-if="type === 'danger'"><i data-lucide="siren"></i></template>
                    <template x-if="type === 'warning'"><i data-lucide="triangle-alert"></i></template>
                    <template x-if="type === 'success'"><i data-lucide="circle-check"></i></template>
                    <template x-if="!['danger', 'warning', 'success'].includes(type)"><i data-lucide="bell-ring"></i></template>
                </div>
                <div class="notification-toast-content">
                    <div class="notification-toast-meta"><span x-text="label"></span><time>Just now</time></div>
                    <h6 x-text="title"></h6>
                    <p x-text="message"></p>
                    <div class="notification-toast-actions">
                        <a :href="actionUrl" x-text="actionLabel"></a>
                        <button type="button" @click="toggleSound()" :title="soundEnabled ? 'Mute notification sounds' : 'Enable notification sounds'">
                            <template x-if="soundEnabled"><i data-lucide="volume-2"></i></template>
                            <template x-if="!soundEnabled"><i data-lucide="volume-x"></i></template>
                        </button>
                        <button type="button" @click="dismiss()">Dismiss</button>
                    </div>
                </div>
                <button type="button" class="notification-toast-close" @click="dismiss()" aria-label="Dismiss notification"><i data-lucide="x"></i></button>
            </div>
            <div class="notification-toast-timer" :key="timerKey"></div>
        </article>
    </div>

    <!-- LIVE DATA REFRESH STATUS -->
    <div id="liveRefreshNotice" class="live-refresh-notice" role="status" aria-live="polite" hidden>
        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        <span id="liveRefreshMessage">New activity detected. Refreshing…</span>
    </div>

    @include('layouts.partials.runtime-guard')

    <!-- Scripts -->
    <script>
        // Initialize Icons
        lucide.createIcons();

        (() => {
            const sidebar = document.getElementById('appSidebar');
            const toggle = document.getElementById('sidebarToggle');
            const closeButton = document.getElementById('sidebarClose');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobileQuery = window.matchMedia('(max-width: 991.98px)');

            if (!sidebar || !toggle || !backdrop) return;

            const setSidebar = open => {
                const shouldOpen = Boolean(open && mobileQuery.matches);
                sidebar.classList.toggle('is-open', shouldOpen);
                backdrop.classList.toggle('is-open', shouldOpen);
                document.body.classList.toggle('sidebar-open', shouldOpen);
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
            const storageKey = 'htc-last-notification-{{ Auth::id() }}';
            const soundKey = 'htc-notification-sound';

            return {
                show: false,
                title: '',
                message: '',
                type: 'info',
                icon: 'bell-ring',
                label: 'System update',
                actionUrl: @json(route('notifications')),
                actionLabel: 'View notification center',
                soundEnabled: localStorage.getItem(soundKey) !== 'off',
                lastId: Number(localStorage.getItem(storageKey) || 0),
                initialized: false,
                timer: null,
                timerKey: 0,
                audioContext: null,

                init() {
                    const unlock = () => this.unlockAudio();
                    document.addEventListener('pointerdown', unlock, {once: true});
                    document.addEventListener('keydown', unlock, {once: true});
                    document.addEventListener('htc:runtime-state', event => this.consumeRuntimeState(event.detail));
                },
                consumeRuntimeState(data) {
                    if (!data) return;

                    if (!this.initialized && this.lastId === 0) {
                        this.lastId = Number(data.latest_id || 0);
                        localStorage.setItem(storageKey, this.lastId);
                        this.initialized = true;
                        return;
                    }

                    this.initialized = true;
                    if (data.notification && Number(data.notification.id) > this.lastId) {
                        this.lastId = Number(data.notification.id);
                        localStorage.setItem(storageKey, this.lastId);
                        this.present(data.notification);
                    } else if (Number(data.latest_id || 0) > this.lastId) {
                        this.lastId = Number(data.latest_id);
                        localStorage.setItem(storageKey, this.lastId);
                    }
                },
                present(notification) {
                    this.title = notification.title;
                    this.message = notification.message;
                    this.type = ['success', 'warning', 'danger'].includes(notification.type) ? notification.type : 'info';
                    this.icon = notification.icon || 'bell-ring';
                    const inventoryAlert = ['triangle-alert', 'package-x'].includes(notification.icon) || notification.title.includes('Stock');
                    this.label = inventoryAlert ? (this.type === 'danger' ? 'Critical inventory alert' : 'Inventory warning') : this.type === 'success' ? 'Action completed' : this.type === 'danger' ? 'Action required' : 'System update';
                    this.actionUrl = inventoryAlert ? @json(route('inventory.index')) : @json(route('notifications'));
                    this.actionLabel = inventoryAlert ? 'Review stock & forecast' : 'View notification center';
                    this.show = true;
                    this.timerKey++;
                    window.clearTimeout(this.timer);
                    this.timer = window.setTimeout(() => this.dismiss(), 9000);
                    this.playSound(this.type);
                    this.$nextTick(() => lucide.createIcons());
                },
                dismiss() {
                    this.show = false;
                    window.clearTimeout(this.timer);
                },
                toggleSound() {
                    this.soundEnabled = !this.soundEnabled;
                    localStorage.setItem(soundKey, this.soundEnabled ? 'on' : 'off');
                    if (this.soundEnabled) {
                        this.unlockAudio();
                        this.playSound('info');
                    }
                    this.$nextTick(() => lucide.createIcons());
                },
                unlockAudio() {
                    if (!this.soundEnabled) return;
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    this.audioContext ??= new AudioContext();
                    if (this.audioContext.state === 'suspended') this.audioContext.resume();
                },
                playSound(type) {
                    if (!this.soundEnabled || !this.audioContext || this.audioContext.state !== 'running') return;
                    const patterns = {
                        danger: [[220, 0, .13], [170, .18, .2]],
                        warning: [[520, 0, .1], [410, .14, .14]],
                        success: [[440, 0, .09], [660, .12, .15]],
                        info: [[540, 0, .12]],
                    };
                    const start = this.audioContext.currentTime;
                    (patterns[type] || patterns.info).forEach(([frequency, offset, duration]) => {
                        const oscillator = this.audioContext.createOscillator();
                        const gain = this.audioContext.createGain();
                        oscillator.type = 'sine';
                        oscillator.frequency.value = frequency;
                        gain.gain.setValueAtTime(.0001, start + offset);
                        gain.gain.exponentialRampToValueAtTime(.035, start + offset + .015);
                        gain.gain.exponentialRampToValueAtTime(.0001, start + offset + duration);
                        oscillator.connect(gain).connect(this.audioContext.destination);
                        oscillator.start(start + offset);
                        oscillator.stop(start + offset + duration + .02);
                    });
                }
            };
        }

        // Cart Engine
        function cartSystem() {
            const catalog = {{ Illuminate\Support\Js::from($availableSupplies->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->item_name,
                'brand' => $s->brand,
                'category' => $s->category,
                'tier' => $s->requisition_tier ?? 'minor',
                'specs' => $s->specifications,
                'unit' => $s->unit,
                'price' => (float) $s->unit_price,
                'stock' => $s->quantity,
                'options' => $s->requestOptions(),
            ])->values()) }};
            const previousRequest = {{ Illuminate\Support\Js::from([
                'tier' => old('request_type'),
                'purpose' => old('purpose', ''),
                'items' => old('items', []),
            ]) }};

            return {
                catalog,
                tier: null,
                view: 'selection',
                search: '',
                category: 'All',
                selectedItem: null,
                selectedOptions: {},
                productQty: 1,
                cart: [],
                purpose: previousRequest.purpose || '',
                notice: '',
                noticeTimer: null,

                init() {
                    if (!['minor', 'major'].includes(previousRequest.tier)) return;

                    this.tier = previousRequest.tier;
                    this.view = 'catalog';
                    this.cart = (previousRequest.items || []).map((submitted) => {
                        const item = this.catalog.find(catalogItem => catalogItem.id === Number(submitted.supply_id));
                        if (!item || item.tier !== this.tier) return null;
                        const options = submitted.options || {};
                        return {...item, options, qty: Math.min(item.stock, Math.max(1, Number(submitted.qty) || 1)), key: item.id + ':' + JSON.stringify(options)};
                    }).filter(Boolean);
                    this.refreshIcons();
                },

                setTier(tier) {
                    this.tier = tier;
                    this.view = 'catalog';
                    this.search = '';
                    this.category = 'All';
                    this.cart = [];
                    this.refreshIcons();
                },
                backToTiers() {
                    if (this.cart.length && !window.confirm('Changing the tier will clear the items in your cart. Continue?')) return;
                    this.view = 'selection';
                    this.cart = [];
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
                    this.view = 'catalog';
                    this.notice = `${this.selectedItem.name} was added to your requisition.`;
                    window.clearTimeout(this.noticeTimer);
                    this.noticeTimer = window.setTimeout(() => this.notice = '', 3500);
                    this.refreshIcons();
                },
                removeItem(index) { this.cart.splice(index, 1); },
                optionSummary(options) { return Object.entries(options || {}).map(([label, value]) => `${label}: ${value}`).join(' · '); },
                brandCaption(item) {
                    const brands = item?.options?.Brand || [];
                    return brands.length > 1 ? `${brands.length} brands available` : (brands[0] || item?.brand || 'HTC catalog');
                },
                formatMoney(value) { return Number(value || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                refreshIcons() { setTimeout(() => lucide.createIcons(), 30); },
                resetModal() {
                    this.tier = null;
                    this.view = 'selection';
                    this.cart = [];
                    this.selectedItem = null;
                    this.purpose = '';
                    this.notice = '';
                },
                get tierLabel() { return this.tier === 'major' ? 'Major requisition' : 'Minor requisition'; },
                get tierItems() { return this.catalog.filter(item => item.tier === this.tier); },
                get categories() { return [...new Set(this.tierItems.map(item => item.category))].sort(); },
                get filteredItems() {
                    const term = this.search.trim().toLowerCase();
                    return this.tierItems.filter(item => (this.category === 'All' || item.category === this.category)
                        && (!term || `${item.name} ${item.brand || ''} ${item.specs || ''}`.toLowerCase().includes(term)));
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

        document.getElementById('globalRequestModal')?.addEventListener('hidden.bs.modal', event => {
            window.Alpine.$data(event.currentTarget).resetModal();
        });

        @if($errors->any() && old('request_type'))
            window.setTimeout(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('globalRequestModal')).show(), 100);
        @endif

        // Keep every signed-in workspace in sync with requisition and inventory actions.
        (() => {
            const endpoint = @json(route('api.runtime-state'));
            const notificationKey = 'htc-last-notification-{{ Auth::id() }}';
            const cacheKey = 'htc-runtime-state:{{ Auth::id() }}';
            const notice = document.getElementById('liveRefreshNotice');
            const message = document.getElementById('liveRefreshMessage');
            let currentVersion = null;
            let refreshPending = false;
            let checking = false;
            let timer = null;
            let failures = 0;
            let lastCheckedAt = 0;

            const userIsWorking = () => {
                const active = document.activeElement;
                const editing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
                return editing || Boolean(document.querySelector('.modal.show'));
            };

            const showNotice = (deferred = false) => {
                message.textContent = deferred
                    ? 'New activity is available. This page will refresh when you finish.'
                    : 'New activity detected. Refreshing…';
                notice.hidden = false;
            };

            const refreshWhenSafe = () => {
                if (!refreshPending) return;
                if (userIsWorking()) {
                    showNotice(true);
                    return;
                }
                showNotice(false);
                window.setTimeout(() => window.location.reload(), 500);
            };

            const scheduleCheck = delay => {
                window.clearTimeout(timer);
                timer = window.setTimeout(checkForUpdates, delay);
            };

            const cacheMetadata = data => {
                try {
                    sessionStorage.setItem(cacheKey, JSON.stringify({
                        version: data.version,
                        latest_id: Number(data.latest_id || 0),
                        cached_at: Date.now(),
                    }));
                } catch (error) {
                    // Runtime caching is an optional optimization.
                }
            };

            const initialDelay = () => {
                try {
                    const cached = JSON.parse(sessionStorage.getItem(cacheKey) || 'null');
                    const age = Date.now() - Number(cached?.cached_at || 0);
                    const mutationAge = Date.now() - Number(sessionStorage.getItem('htc-runtime-mutated-at') || 0);
                    const cacheDelay = age >= 0 && age < 12000 ? 12000 - age : 0;
                    const mutationDelay = mutationAge >= 0 && mutationAge < 6000 ? 6000 - mutationAge : 0;
                    return Math.max(cacheDelay, mutationDelay);
                } catch (error) {
                    return 0;
                }
            };

            const checkForUpdates = async () => {
                if (checking) return;
                if (document.hidden) {
                    scheduleCheck(60000);
                    return;
                }
                checking = true;
                const controller = new AbortController();
                const timeout = window.setTimeout(() => controller.abort(), 8000);

                try {
                    const afterId = Math.max(0, Number(localStorage.getItem(notificationKey) || 0));
                    const response = await fetch(`${endpoint}?after_id=${afterId}`, {
                        headers: {'Accept': 'application/json'},
                        cache: 'no-store',
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });
                    if (!response.ok) return;

                    const data = await response.json();
                    failures = 0;
                    lastCheckedAt = Date.now();
                    cacheMetadata(data);
                    document.dispatchEvent(new CustomEvent('htc:runtime-state', {detail: data}));

                    if (currentVersion === null) {
                        currentVersion = data.version;
                        return;
                    }
                    if (data.version !== currentVersion) {
                        refreshPending = true;
                        refreshWhenSafe();
                    }
                } catch (error) {
                    failures++;
                } finally {
                    window.clearTimeout(timeout);
                    checking = false;
                    const retryDelay = failures ? Math.min(60000, 15000 * (2 ** failures)) : 15000;
                    scheduleCheck(retryDelay);
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && Date.now() - lastCheckedAt > 5000) checkForUpdates();
            });
            document.addEventListener('focusout', () => window.setTimeout(refreshWhenSafe, 150));
            document.addEventListener('hidden.bs.modal', refreshWhenSafe);
            scheduleCheck(initialDelay());
        })();

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
