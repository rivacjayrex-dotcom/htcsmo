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

        /* Requisition marketplace */
        .requisition-shell { border-radius: 22px !important; min-height: 680px; background: #fff; }
        .requisition-header { padding: 1.4rem 2rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e8eeeb; }
        .requisition-eyebrow { color: var(--htc-green); font-size: .68rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
        .requisition-progress { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; padding-top: 1rem; padding-bottom: 1rem; background: #fbfcfb; border-bottom: 1px solid #edf1ef; }
        .requisition-progress div { display: flex; align-items: center; gap: .55rem; color: #94a3b8; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .requisition-progress div::after { content: ''; height: 2px; flex: 1; background: #e2e8f0; }
        .requisition-progress div:last-child::after { display: none; }
        .requisition-progress span { width: 24px; height: 24px; border-radius: 50%; display: inline-grid; place-items: center; background: #e2e8f0; color: #64748b; }
        .requisition-progress .active { color: var(--htc-green); }
        .requisition-progress .active span, .requisition-progress .done span { background: var(--htc-green); color: #fff; }
        .requisition-progress .done::after { background: var(--htc-green); }
        .tier-stage { min-height: 510px; background: radial-gradient(circle at 50% 0, #f0f8f3, #fff 52%); }
        .tier-choice { min-height: 330px; border: 1px solid #dfe8e3; border-radius: 22px; background: #fff; padding: 2rem; display: flex; flex-direction: column; align-items: flex-start; text-align: left; transition: .2s ease; box-shadow: 0 12px 30px rgba(15, 45, 29, .05); }
        .tier-choice:hover { transform: translateY(-5px); border-color: var(--htc-green); box-shadow: 0 20px 44px rgba(24, 91, 59, .12); }
        .tier-choice .tier-icon { width: 58px; height: 58px; border-radius: 17px; display: grid; place-items: center; margin-bottom: 1.5rem; }
        .tier-choice .tier-icon svg { width: 27px; height: 27px; }
        .tier-minor .tier-icon { background: #e9f7ef; color: #137044; }
        .tier-major .tier-icon { background: #fff5dd; color: #a16207; }
        .tier-choice .tier-kicker { font-size: .68rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: #94a3b8; }
        .tier-choice strong { display: block; color: #14251c; font-size: 1.35rem; margin: .35rem 0 .65rem; }
        .tier-choice > span:not(.tier-icon):not(.tier-kicker) { color: #64748b; line-height: 1.65; }
        .tier-choice small { margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #edf1ef; width: 100%; color: #64748b; }
        .requisition-workspace { min-height: 540px; }
        .catalog-pane { padding: 1.5rem 1.75rem; background: #f7f9f8; max-height: 610px; overflow-y: auto; }
        .catalog-toolbar { display: flex; align-items: center; gap: .75rem; }
        .catalog-toolbar svg, .product-configurator .btn svg, .btn-htc svg { width: 15px; height: 15px; }
        .tier-pill { padding: .45rem .8rem; border-radius: 999px; font-size: .68rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
        .tier-pill.minor { color: #137044; background: #e7f6ed; }
        .tier-pill.major { color: #946200; background: #fff3d1; }
        .catalog-search { margin-left: auto; flex: 1; max-width: 320px; height: 40px; display: flex; align-items: center; gap: .6rem; padding: 0 .85rem; border: 1px solid #dfe7e2; border-radius: 12px; background: #fff; }
        .catalog-search svg { width: 16px; color: #94a3b8; }
        .catalog-search input { width: 100%; border: 0; outline: 0; font-size: .82rem; background: transparent; }
        .category-chips { display: flex; gap: .5rem; padding: 1.25rem 0; overflow-x: auto; }
        .category-chips button { border: 1px solid #dfe7e2; background: #fff; color: #64748b; border-radius: 999px; padding: .45rem .8rem; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .category-chips button.active { background: var(--htc-green); color: #fff; border-color: var(--htc-green); }
        .catalog-heading { display: flex; align-items: end; justify-content: space-between; margin: .4rem 0 1rem; }
        .catalog-notice { display: flex; align-items: center; gap: .55rem; margin: 1rem 0 0; padding: .7rem .85rem; border: 1px solid #bfe2ca; border-radius: 10px; background: #edf8f1; color: #17613b; font-size: .75rem; font-weight: 700; }
        .catalog-notice svg { width: 16px; height: 16px; }
        .catalog-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .product-card { padding: 0; overflow: hidden; border: 1px solid #e1e8e4; border-radius: 16px; background: #fff; text-align: left; transition: .2s ease; }
        .product-card:hover { border-color: var(--htc-green); transform: translateY(-2px); box-shadow: 0 10px 24px rgba(24, 91, 59, .1); }
        .product-visual { min-height: 105px; background: linear-gradient(135deg, #e7f2eb, #f9fbfa); display: grid; place-items: center; position: relative; color: var(--htc-green); }
        .product-visual svg { width: 36px; height: 36px; }
        .product-visual em { position: absolute; left: .75rem; bottom: .6rem; font-size: .62rem; font-style: normal; font-weight: 700; color: #5c7567; }
        .product-body { display: flex; flex-direction: column; padding: 1rem; }
        .product-body > small { color: #8b9b92; font-size: .65rem; text-transform: uppercase; letter-spacing: .06em; }
        .product-body > strong { color: #15251c; margin: .2rem 0; font-size: .9rem; }
        .product-spec { color: #718078; font-size: .72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-meta { display: flex; justify-content: space-between; align-items: end; margin-top: 1rem; }
        .product-meta b { color: var(--htc-green); font-size: 1rem; }
        .product-meta em { color: #87958d; font-size: .62rem; font-style: normal; }
        .empty-catalog, .cart-empty { min-height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #94a3b8; gap: .5rem; }
        .empty-catalog svg, .cart-empty svg { width: 40px; height: 40px; opacity: .45; }
        .empty-catalog strong, .cart-empty strong { color: #526158; }
        .product-configurator { padding: .5rem 0; }
        .product-hero { height: 330px; border-radius: 20px; background: linear-gradient(145deg, #dceee3, #f5f9f6); color: var(--htc-green); display: flex; flex-direction: column; gap: 1rem; align-items: center; justify-content: center; }
        .product-hero svg { width: 75px; height: 75px; }
        .product-hero span { font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .product-description { color: #6b7c72; font-size: .8rem; line-height: 1.6; }
        .option-group { margin-top: 1.15rem; }
        .option-group label { display: block; font-size: .72rem; font-weight: 800; margin-bottom: .55rem; color: #34473c; }
        .option-group > div { display: flex; flex-wrap: wrap; gap: .5rem; }
        .option-group button { padding: .5rem .8rem; border: 1px solid #d9e2dd; border-radius: 8px; background: #fff; color: #5a6c62; font-size: .74rem; }
        .option-group button.active { border-color: var(--htc-green); color: var(--htc-green); background: #eff8f2; box-shadow: inset 0 0 0 1px var(--htc-green); }
        .quantity-picker, .mini-quantity { display: flex; align-items: center; border: 1px solid #dce4df; border-radius: 9px; overflow: hidden; background: #fff; }
        .quantity-picker button, .mini-quantity button { border: 0; background: #f5f7f6; color: #43544a; }
        .quantity-picker button { width: 34px; height: 42px; }
        .quantity-picker input { width: 48px; height: 42px; border: 0; text-align: center; font-weight: 700; }
        .cart-pane { display: flex; flex-direction: column; max-height: 610px; background: #fff; border-left: 1px solid #e4ebe7; }
        .cart-title { padding: 1.4rem; border-bottom: 1px solid #edf1ef; display: flex; align-items: center; justify-content: space-between; }
        .cart-title > div { display: flex; align-items: center; gap: .65rem; }
        .cart-title > span { color: #839088; font-size: .7rem; }
        .cart-icon { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; color: var(--htc-green); background: #eaf5ee; }
        .cart-icon svg { width: 16px; }
        .cart-items { padding: 1rem 1.35rem; flex: 1; overflow-y: auto; }
        .cart-item { border-bottom: 1px solid #edf1ef; padding: .9rem 0; }
        .cart-item strong { color: #26382e; font-size: .82rem; }
        .cart-item small { display: block; color: #87958d; font-size: .67rem; margin-top: .2rem; }
        .cart-item > div button { border: 0; background: transparent; color: #94a3b8; }
        .cart-item > div button svg { width: 14px; height: 14px; }
        .mini-quantity button { width: 25px; height: 25px; }
        .mini-quantity span { min-width: 28px; text-align: center; font-size: .7rem; font-weight: 700; }
        .cart-summary { padding: 1.25rem 1.4rem; background: #f8faf9; border-top: 1px solid #e4ebe7; }
        .reason-field { display: block !important; margin-bottom: 1rem !important; color: inherit !important; }
        .reason-field label { display: block; margin-bottom: .4rem; color: #34473c; font-size: .72rem; font-weight: 800; }
        .reason-field textarea { display: block; width: 100%; resize: vertical; min-height: 74px; padding: .7rem .8rem; border: 1px solid #d7e1db; border-radius: 10px; background: #fff; color: #283a30; font: inherit; font-size: .75rem; line-height: 1.45; outline: none; transition: .2s ease; }
        .reason-field textarea:focus { border-color: var(--htc-green); box-shadow: 0 0 0 3px rgba(24, 91, 59, .1); }
        .reason-field textarea::placeholder { color: #a0aaa4; }
        .reason-field > span { display: flex; justify-content: space-between; margin-top: .3rem; color: #94a3b8; font-size: .62rem; }
        .reason-field em { font-style: normal; }
        .cart-summary > div { display: flex; justify-content: space-between; align-items: center; margin-bottom: .65rem; color: #738178; font-size: .75rem; }
        .cart-summary > div strong { color: #173d27; font-size: 1.25rem; }
        .cart-summary p { font-size: .68rem; font-weight: 700; }
        .cart-summary > small { display: flex; align-items: center; justify-content: center; gap: .35rem; color: #829087; font-size: .62rem; margin-top: .7rem; }
        .cart-summary > small svg { width: 13px; }
        [x-cloak] { display: none !important; }
        @media (max-width: 991.98px) { .requisition-shell { min-height: auto; } .catalog-pane, .cart-pane { max-height: none; } .cart-pane { border-left: 0; border-top: 1px solid #e4ebe7; } }
        @media (max-width: 575.98px) { .catalog-grid { grid-template-columns: 1fr; } .catalog-toolbar { flex-wrap: wrap; } .catalog-search { order: 3; max-width: none; flex-basis: 100%; } .requisition-progress { gap: .3rem; } .requisition-progress div { font-size: .58rem; } }
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

    @endif

    @include('layouts.partials.requisition-modal')

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
                'options' => $s->configurable_options ?? [],
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
