<x-app-layout>
    <x-slot name="header">{{ Auth::user()->role === 'smo' ? 'SMO Command Center' : (Auth::user()->role === 'employee' ? 'My requisitions' : 'Institutional Dashboard') }}</x-slot>

    @php
        $isSmo = Auth::user()->role === 'smo';
        $isApprover = in_array(Auth::user()->role, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president']);
        $firstName = explode(' ', trim(Auth::user()->name))[0] ?: Auth::user()->name;
    @endphp

    <div class="dashboard-page{{ Auth::user()->role === 'employee' ? ' employee-dashboard' : ' staff-dashboard'.($isSmo ? ' smo-dashboard' : '') }}">
        @unless($isSmo)
            @include('dashboard.partials.hero')
        @endunless

        @if($isApprover)
            @include('requests.partials.approval-deadline-watch', ['approvalDeadlineRequests' => $approvalDeadlineRequests])
        @endif

        @if($isSmo)
            @include('dashboard.smo')
        @elseif(Auth::user()->role === 'employee')
            @include('dashboard.employee')
        @else
            <section class="dashboard-metric-grid two">
                <article class="modern-metric amber">
                    <div class="metric-top"><span class="metric-icon"><i data-lucide="clock-3"></i></span><span class="metric-trend">In progress</span></div>
                    <strong x-data="countUp({{ $activeCount }})" x-text="current">{{ $activeCount }}</strong>
                    <h3>My active requisitions</h3>
                    <p>Requests moving through the approval workflow.</p>
                </article>
                <article class="modern-metric green">
                    <div class="metric-top"><span class="metric-icon"><i data-lucide="badge-check"></i></span><span class="metric-trend">Last 24 hours</span></div>
                    <strong x-data="countUp({{ $approvedLast24h }})" x-text="current">{{ $approvedLast24h }}</strong>
                    <h3>Approved and ready</h3>
                    <p>Requests recently approved or available for pickup.</p>
                </article>
            </section>

            @if($isApprover)
                <section class="dashboard-panel approval-panel">
                    <div class="panel-heading">
                        <div>
                            <span class="panel-eyebrow">Decision queue</span>
                            <h3>Waiting for your approval <em>{{ $approvalRequests->count() }}</em></h3>
                            <p>Your most recent requests at the current approval stage.</p>
                        </div>
                        <a href="{{ route('admin.approvals') }}" class="panel-action"><i data-lucide="clipboard-check"></i> Review all</a>
                    </div>
                    <div class="table-responsive dashboard-table-wrap">
                        <table class="table dashboard-table responsive-dashboard-table">
                            <thead><tr><th>Request</th><th>Requestor</th><th>Requested item</th><th>Submitted</th><th></th></tr></thead>
                            <tbody>
                                @forelse($approvalRequests as $approvalRequest)
                                    <tr>
                                        <td data-label="Request"><div class="request-code">{{ $approvalRequest->tracking_code }}</div><span class="tier-pill {{ $approvalRequest->request_type }}">{{ $approvalRequest->request_type }}</span></td>
                                        <td data-label="Requestor"><div class="person-cell"><span>{{ strtoupper(substr($approvalRequest->user->name, 0, 1)) }}</span><div><b>{{ $approvalRequest->user->name }}</b><small>{{ $approvalRequest->user->department ?: 'General' }}</small></div></div></td>
                                        <td data-label="Requested item"><b>{{ $approvalRequest->items->first()->item_name ?? 'Supplies' }}</b>@if($approvalRequest->items->count() > 1)<small>+{{ $approvalRequest->items->count() - 1 }} more item(s)</small>@endif</td>
                                        <td data-label="Submitted"><span class="table-date">{{ $approvalRequest->created_at->format('M d, Y') }}</span></td>
                                        <td data-label="Action"><a href="{{ route('admin.approvals', ['request' => $approvalRequest->id]) }}" class="row-action" aria-label="Review {{ $approvalRequest->tracking_code }}"><i data-lucide="arrow-right"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="dashboard-empty"><i data-lucide="circle-check-big"></i><strong>You are all caught up</strong><span>No requests are waiting for your approval.</span></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section class="dashboard-panel">
                <div class="panel-heading">
                    <div><span class="panel-eyebrow">Request tracking</span><h3>My procurement activity</h3><p>Follow every active request through its approval journey.</p></div>
                    <a href="{{ route('requests.index') }}" class="panel-action subtle">View all requests <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="table-responsive dashboard-table-wrap">
                    <table class="table dashboard-table responsive-dashboard-table">
                        <thead><tr><th>Request</th><th>Primary item</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                                @php
                                    $statusTone = match($request->status) {
                                        'for_clarification' => 'warning',
                                        'approved_vp', 'approved_president', 'ready_for_pickup' => 'ready',
                                        default => 'pending',
                                    };
                                @endphp
                                <tr>
                                    <td data-label="Request"><div class="request-code">{{ $request->tracking_code }}</div><span class="tier-pill {{ $request->request_type }}">{{ $request->request_type }}</span></td>
                                    <td data-label="Primary item"><b>{{ $request->items->first()->item_name ?? 'Supplies' }}</b>@if($request->items->count() > 1)<small>+{{ $request->items->count() - 1 }} more item(s)</small>@endif</td>
                                    <td data-label="Submitted"><span class="table-date">{{ $request->created_at->format('M d, Y') }}</span></td>
                                    <td data-label="Status"><span class="status-pill {{ $statusTone }}">{{ str_replace('_', ' ', $request->status) }}</span></td>
                                    <td data-label="Action"><a href="{{ route('requests.show', $request->id) }}" class="row-action" aria-label="Track {{ $request->tracking_code }}"><i data-lucide="arrow-right"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="dashboard-empty"><i data-lucide="package-open"></i><strong>No active requisitions</strong><span>Start a new requisition when your office needs supplies.</span><button type="button" data-bs-toggle="modal" data-bs-target="#globalRequestModal">Create requisition</button></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    @pushOnce('styles', 'page-styles:dashboard.blade.php')
    <style>
        .dashboard-page {
            padding: var(--space-4);
            color: #26372d;
        }

        .dashboard-hero {
            position: relative;
            isolation: isolate;
            min-height: 210px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-32);
            margin-bottom: var(--space-16);
            padding: var(--space-32) calc(var(--space-32) + var(--space-4));
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 22px;
            color: #fff;
            background: radial-gradient(circle at 12% -60%, rgba(74,222,128,.28), transparent 24rem),
                radial-gradient(circle at 95% 130%, rgba(255,255,255,.13), transparent 18rem),
                linear-gradient(125deg, #052e16, #14532d 55%, #17713d);
            box-shadow: 0 18px 42px rgba(12,68,35,.2);
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: 220px;
            height: 220px;
            right: 24%;
            bottom: -170px;
            border: 34px solid rgba(255,255,255,.045);
            border-radius: 50%;
        }

        .dashboard-hero-copy {
            max-width: 650px;
        }

        .dashboard-kicker,
        .panel-eyebrow {
            display: block;
            margin-bottom: var(--space-8);
            color: #8ee5aa;
            font-size: var(--text-caption);
            font-weight: 800;
            letter-spacing: var(--tracking-label);
            text-transform: uppercase;
        }

        .dashboard-hero h2 {
            margin: 0 0 var(--space-8);
            color: #fff;
            font-size: var(--text-heading-md);
            font-weight: 800;
            letter-spacing: var(--tracking-normal);
        }

        .dashboard-hero p {
            max-width: 620px;
            margin: 0;
            color: rgba(239,251,243,.72);
            font-size: var(--text-secondary);
            line-height: var(--line-prose);
        }

        .dashboard-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-16);
            margin-top: var(--space-24);
        }

        .dashboard-hero-meta span {
            display: flex;
            align-items: center;
            gap: var(--space-8);
            color: rgba(239,251,243,.68);
            font-size: var(--text-caption);
            font-weight: 600;
        }

        .dashboard-hero-meta svg {
            width: 14px;
            height: 14px;
            color: #86dca2;
        }

        .dashboard-hero-actions {
            min-width: 170px;
            display: flex;
            flex-direction: column;
            gap: var(--space-12);
        }

        .hero-btn {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-8);
            padding: var(--space-12) var(--space-16);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 11px;
            font-size: var(--text-caption);
            font-weight: 800;
            text-decoration: none;
            transition: transform .18s ease, background-color .18s ease;
        }

        .hero-btn svg {
            width: 16px;
            height: 16px;
        }

        .hero-btn.primary {
            background: #fff;
            color: #14532d;
            box-shadow: 0 9px 22px rgba(1,25,10,.18);
        }

        .hero-btn.secondary {
            background: rgba(255,255,255,.09);
            color: #fff;
        }

        .hero-btn:hover {
            transform: translateY(-2px);
        }

        .hero-btn.primary:hover {
            background: #edfaf1;
            color: #10452a;
        }

        .hero-btn.secondary:hover {
            background: rgba(255,255,255,.16);
            color: #fff;
        }

        .priority-watch {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-12);
            margin-bottom: var(--space-16);
        }

        .priority-panel {
            --alert: #287249;
            --alert-soft: #e8f5ed;
            overflow: hidden;
            border: 1px solid #cfe3d6;
            border-top: 4px solid var(--alert);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(23,65,40,.07);
        }

        .priority-panel.deadline.has-alerts {
            --alert: #c23841;
            --alert-soft: #fde9ea;
            border-color: #efc3c6;
            background: linear-gradient(145deg, #fff 55%, #fff8f8);
            box-shadow: 0 10px 30px rgba(194,56,65,.1);
        }

        .priority-panel.stock.has-alerts {
            --alert: #c1790a;
            --alert-soft: #fff1d1;
            border-color: #edd49d;
            background: linear-gradient(145deg, #fff 55%, #fffbf2);
            box-shadow: 0 10px 30px rgba(193,121,10,.1);
        }

        .priority-heading {
            min-height: 76px;
            display: grid;
            grid-template-columns: 42px minmax(0,1fr) auto;
            align-items: center;
            gap: var(--space-12);
            padding: var(--space-12) var(--space-16);
            border-bottom: 1px solid color-mix(in srgb, var(--alert) 18%, #e7ede9);
        }

        .priority-symbol {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--alert-soft);
            color: var(--alert);
        }

        .priority-symbol svg {
            width: 20px;
            height: 20px;
        }

        .priority-panel.has-alerts .priority-symbol {
            animation: alertBeat 2.2s ease-in-out infinite;
        }

        .priority-heading div > span {
            display: block;
            margin-bottom: .15rem;
            color: var(--alert);
            font-size: var(--text-caption);
            font-weight: 900;
            letter-spacing: var(--tracking-label);
            text-transform: uppercase;
        }

        .priority-heading h3 {
            margin: 0;
            color: #33463a;
            font-size: var(--text-heading-sm);
            font-weight: 800;
        }

        .priority-heading > a {
            display: inline-flex;
            align-items: center;
            gap: var(--space-4);
            color: var(--alert);
            font-size: var(--text-caption);
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .priority-heading > a svg {
            width: 13px;
        }

        .priority-list {
            padding: var(--space-4) var(--space-12) var(--space-12);
        }

        .priority-row {
            display: grid;
            grid-template-columns: auto minmax(0,1fr) auto;
            align-items: center;
            gap: var(--space-12);
            padding: var(--space-12) .15rem;
            border-bottom: 1px solid #ebf0ec;
            color: inherit;
            text-decoration: none;
        }

        .priority-row:last-child {
            border-bottom: 0;
        }

        .priority-row:hover .priority-detail b {
            color: var(--alert);
        }

        .priority-state {
            min-width: 67px;
            padding: var(--space-4) var(--space-8);
            border-radius: 999px;
            font-size: var(--text-caption);
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
        }

        .priority-state.overdue {
            background: #fde3e5;
            color: #b52d36;
        }

        .priority-state.near {
            background: #fff0ca;
            color: #966008;
        }

        .priority-detail {
            min-width: 0;
        }

        .priority-detail b,
        .priority-detail small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .priority-detail b {
            color: #394b40;
            font-size: var(--text-caption);
            transition: color .18s ease;
        }

        .priority-detail small {
            margin-top: .12rem;
            color: #8a968e;
            font-size: var(--text-caption);
        }

        .priority-value {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            color: var(--alert);
            font-size: var(--text-caption);
            font-weight: 900;
            white-space: nowrap;
        }

        .priority-value svg {
            width: 13px;
        }

        .priority-clear {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-12);
            padding: var(--space-16);
        }

        .priority-clear > svg {
            width: 25px;
            color: #328457;
        }

        .priority-clear b,
        .priority-clear small {
            display: block;
        }

        .priority-clear b {
            color: #3a5042;
            font-size: var(--text-caption);
        }

        .priority-clear small {
            margin-top: .15rem;
            color: #89968e;
            font-size: var(--text-caption);
        }

        .dashboard-metric-grid {
            display: grid;
            gap: var(--space-12);
            margin-bottom: var(--space-16);
        }

        .dashboard-metric-grid.four {
            grid-template-columns: repeat(4, minmax(0,1fr));
        }

        .dashboard-metric-grid.two {
            grid-template-columns: repeat(2, minmax(0,1fr));
        }

        .modern-metric {
            --tone: #53665a;
            --soft: #eef3f0;
            position: relative;
            min-width: 0;
            padding: var(--space-16);
            overflow: hidden;
            border: 1px solid #dde7e1;
            border-radius: 17px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 7px 22px rgba(22,65,39,.055);
        }

        .modern-metric::after {
            content: '';
            position: absolute;
            width: 90px;
            height: 90px;
            top: -47px;
            right: -35px;
            border-radius: 50%;
            background: var(--soft);
            opacity: .75;
        }

        .modern-metric.amber {
            --tone: #a66809;
            --soft: #fff0cb;
        }

        .modern-metric.green {
            --tone: #167044;
            --soft: #dcf3e5;
        }

        .modern-metric.red {
            --tone: #b72f38;
            --soft: #fce0e2;
        }

        .metric-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-8);
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--soft);
            color: var(--tone);
        }

        .metric-icon svg {
            width: 18px;
        }

        .metric-trend {
            padding: var(--space-4) var(--space-8);
            border-radius: 999px;
            background: #f3f6f4;
            color: #7a8980;
            font-size: var(--text-caption);
            font-weight: 800;
            text-transform: uppercase;
        }

        .modern-metric > strong {
            display: block;
            margin-top: var(--space-16);
            color: var(--tone);
            font-size: var(--text-heading-lg);
            line-height: var(--line-heading);
            letter-spacing: var(--tracking-normal);
        }

        .modern-metric > strong.money {
            font-size: var(--text-heading-md);
            overflow-wrap: anywhere;
            line-height: var(--line-heading);
        }

        .modern-metric h3 {
            margin: var(--space-8) 0 .15rem;
            color: #26382e;
            font-size: var(--text-heading-sm);
            font-weight: 800;
        }

        .modern-metric p {
            margin: 0;
            color: #839088;
            font-size: var(--text-secondary);
            line-height: var(--line-body);
        }

        .modern-metric.urgent {
            animation: metricPulse 2.8s ease-in-out infinite;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: minmax(0,2.1fr) minmax(280px,.9fr);
            gap: var(--space-16);
            align-items: start;
        }

        .dashboard-panel {
            overflow: hidden;
            margin-bottom: var(--space-16);
            border: 1px solid #dfe8e2;
            border-radius: 18px;
            background: rgba(255,255,255,.97);
            box-shadow: 0 8px 26px rgba(23,65,40,.06);
        }

        .panel-heading {
            min-height: 88px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-16);
            padding: var(--space-16) var(--space-24);
            border-bottom: 1px solid #e5ece7;
        }

        .panel-heading.compact {
            min-height: 76px;
        }

        .panel-heading .panel-eyebrow {
            margin-bottom: var(--space-4);
            color: #37845a;
        }

        .panel-heading h3 {
            margin: 0;
            color: #24372c;
            font-size: var(--text-heading-sm);
            font-weight: 800;
        }

        .panel-heading h3 em {
            display: inline-grid;
            min-width: 23px;
            height: 23px;
            margin-left: var(--space-4);
            place-items: center;
            border-radius: 999px;
            background: #fce4e5;
            color: #b72f38;
            font-size: var(--text-caption);
            font-style: normal;
        }

        .panel-heading p {
            margin: var(--space-4) 0 0;
            color: #86928b;
            font-size: var(--text-secondary);
        }

        .panel-action {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-8);
            padding: var(--space-8) var(--space-12);
            border: 1px solid #1b7548;
            border-radius: 9px;
            background: #1b7548;
            color: #fff;
            font-size: var(--text-caption);
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .panel-action.subtle {
            border-color: #d4e2d9;
            background: #f6faf7;
            color: #2d6e49;
        }

        .panel-action:hover {
            color: #fff;
            background: #125a36;
        }

        .panel-action.subtle:hover {
            color: #1b5d3b;
            background: #eaf5ed;
        }

        .panel-action svg {
            width: 14px;
        }

        .icon-link,
        .row-action {
            display: inline-grid;
            place-items: center;
            border: 1px solid #dce6df;
            border-radius: 9px;
            color: #376c4d;
            background: #f8fbf9;
        }

        .icon-link {
            width: 34px;
            height: 34px;
        }

        .icon-link svg,
        .row-action svg {
            width: 14px;
        }

        .dashboard-table-wrap {
            border: 0 !important;
            border-radius: 0 !important;
        }

        .dashboard-table {
            margin: 0;
            font-size: var(--text-secondary);
        }

        .dashboard-table thead th {
            padding: var(--space-12) var(--space-16) !important;
            background: #f5f8f6 !important;
            color: #859189 !important;
            font-size: var(--text-caption) !important;
        }

        .dashboard-table tbody td {
            padding: var(--space-12) var(--space-16) !important;
        }

        .dashboard-table td > b {
            display: block;
            color: #35483d;
            font-size: var(--text-secondary);
        }

        .dashboard-table td > small {
            display: block;
            margin-top: var(--space-4);
            color: #8b978f;
            font-size: var(--text-caption);
        }

        .request-code {
            color: #294c37;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: var(--text-caption);
            font-weight: 800;
        }

        .person-cell {
            display: flex;
            align-items: center;
            gap: var(--space-8);
        }

        .person-cell > span {
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: #e9f4ed;
            color: #216b42;
            font-size: var(--text-secondary);
            font-weight: 800;
        }

        .person-cell b,
        .person-cell small {
            display: block;
        }

        .person-cell b {
            color: #35473c;
            font-size: var(--text-secondary);
        }

        .person-cell small {
            margin-top: .1rem;
            color: #8b978f;
            font-size: var(--text-caption);
        }

        .status-pill,
        .tier-pill {
            display: inline-block;
            padding: var(--space-4) var(--space-8);
            border-radius: 999px;
            font-size: var(--text-caption);
            font-weight: 800;
            letter-spacing: var(--tracking-label);
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-pill.pending {
            background: #eef2ef;
            color: #5e6d64;
        }

        .status-pill.ready {
            background: #def3e6;
            color: #17683f;
        }

        .status-pill.warning {
            background: #fff0c9;
            color: #8b5a08;
        }

        .tier-pill {
            margin-top: var(--space-4);
        }

        .tier-pill.minor {
            background: #e5f4ea;
            color: #236d45;
        }

        .tier-pill.major {
            background: #fff0cf;
            color: #8c5a09;
        }

        .deadline-text {
            font-weight: 800;
        }

        .deadline-text.overdue {
            color: #ba3038 !important;
        }

        .deadline-text.near {
            color: #a86a0a !important;
        }

        .row-overdue {
            box-shadow: inset 3px 0 #d3434b;
        }

        .row-near-deadline {
            box-shadow: inset 3px 0 #e5a120;
        }

        .row-action {
            width: 32px;
            height: 32px;
            margin-left: auto;
        }

        .row-action:hover,
        .icon-link:hover {
            border-color: #207547;
            color: #fff;
            background: #207547;
        }

        .table-date {
            color: #718078;
            font-size: var(--text-secondary);
        }

        .activity-panel {
            margin-bottom: 0;
        }

        .activity-list {
            padding: var(--space-4) var(--space-16);
        }

        .activity-item {
            display: grid;
            grid-template-columns: 38px minmax(0,1fr);
            gap: var(--space-12);
            padding: var(--space-16) .15rem;
            border-bottom: 1px solid #e9efeb;
        }

        .activity-item:last-child {
            border-bottom: 0;
        }

        .activity-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: #edf4ef;
            color: #397250;
        }

        .activity-icon.warning {
            background: #fff2d3;
            color: #a3690c;
        }

        .activity-icon.danger {
            background: #fde6e8;
            color: #b53038;
        }

        .activity-icon.success {
            background: #e1f3e7;
            color: #1d7145;
        }

        .activity-icon svg {
            width: 17px;
        }

        .activity-item h4 {
            margin: .05rem 0 var(--space-4);
            color: #34473c;
            font-size: var(--text-heading-sm);
            font-weight: 800;
        }

        .activity-item h4 a {
            color: inherit;
            text-decoration: none;
        }

        .activity-item h4 a:hover {
            text-decoration: underline;
        }

        .dashboard-page :is(a, button):focus-visible {
            outline: 3px solid #b47b16;
            outline-offset: 3px;
        }

        .activity-item p {
            margin: 0;
            color: #7f8c84;
            font-size: var(--text-secondary);
            line-height: var(--line-body);
        }

        .activity-item time {
            display: block;
            margin-top: var(--space-4);
            color: #a0aaa4;
            font-size: var(--text-caption);
        }

        .activity-footer {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-8);
            border-top: 1px solid #e6ede8;
            color: #2e754c;
            background: #f8fbf9;
            font-size: var(--text-caption);
            font-weight: 800;
            text-decoration: none;
        }

        .activity-footer svg {
            width: 13px;
        }

        .approval-panel {
            margin-bottom: var(--space-16);
        }

        .dashboard-empty {
            min-height: 190px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: var(--space-24);
            text-align: center;
        }

        .dashboard-empty.small {
            min-height: 280px;
        }

        .dashboard-empty svg {
            width: 34px;
            height: 34px;
            margin-bottom: var(--space-8);
            color: #9caf9f;
        }

        .dashboard-empty strong {
            color: #43564a;
            font-size: var(--text-caption);
        }

        .dashboard-empty span {
            margin-top: var(--space-4);
            color: #8a968e;
            font-size: var(--text-caption);
        }

        .dashboard-empty button {
            margin-top: var(--space-12);
            padding: var(--space-8) var(--space-12);
            border: 0;
            border-radius: 8px;
            background: #1b7045;
            color: #fff;
            font-size: var(--text-caption);
            font-weight: 800;
        }

        @keyframes metricPulse {
            0%, 100% { box-shadow: 0 7px 22px rgba(183,47,56,.06); }
            50% { box-shadow: 0 8px 26px rgba(183,47,56,.18); }
        }

        @keyframes alertBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.06); }
        }

        @media (max-width: 1199.98px) {
            .dashboard-metric-grid.four {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .activity-panel {
                margin-bottom: var(--space-16);
            }

        }

        @media (max-width: 767.98px) {
            .dashboard-page {
                padding: 0;
            }

            .dashboard-hero {
                min-height: auto;
                align-items: stretch;
                flex-direction: column;
                padding: var(--space-24);
                border-radius: 17px;
            }

            .dashboard-hero-actions {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .priority-watch {
                grid-template-columns: 1fr;
            }

            .priority-heading {
                grid-template-columns: 42px minmax(0,1fr);
            }

            .priority-heading > a {
                grid-column: 1 / -1;
                min-height: 34px;
                justify-content: center;
                border-radius: 8px;
                background: var(--alert-soft);
            }

            .dashboard-metric-grid.four,
            .dashboard-metric-grid.two {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .modern-metric {
                padding: var(--space-16);
            }

            .modern-metric > strong {
                font-size: var(--text-heading-md);
            }

            .panel-heading {
                align-items: flex-start;
                flex-direction: column;
                padding: var(--space-16);
            }

            .panel-action {
                width: 100%;
            }

            .dashboard-page .table-responsive {
                overflow: visible;
            }

            .dashboard-page .responsive-dashboard-table {
                display: block;
                min-width: 0;
                background: #f5f8f6;
            }

            .responsive-dashboard-table thead {
                display: none;
            }

            .responsive-dashboard-table tbody {
                display: grid;
                gap: var(--space-12);
                padding: var(--space-12);
            }

            .responsive-dashboard-table tr {
                display: block;
                overflow: hidden;
                border: 1px solid #e0e9e3;
                border-radius: 13px;
                background: #fff;
                box-shadow: 0 3px 12px rgba(24,58,37,.05);
            }

            .responsive-dashboard-table td {
                display: grid;
                grid-template-columns: minmax(85px,32%) minmax(0,1fr);
                align-items: center;
                gap: var(--space-12);
                width: 100%;
                padding: var(--space-12) var(--space-12) !important;
                border-color: #edf1ee;
                text-align: right;
                overflow-wrap: anywhere;
            }

            .responsive-dashboard-table td::before {
                content: attr(data-label);
                color: #819087;
                font-size: var(--text-caption);
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
            }

            .responsive-dashboard-table td > * {
                justify-self: end;
            }

            .responsive-dashboard-table td[colspan] {
                display: block;
                text-align: center;
            }

            .responsive-dashboard-table td[colspan]::before {
                display: none;
            }

            .person-cell {
                text-align: left;
            }

            .dashboard-table td > small {
                text-align: right;
            }

            .row-action {
                margin-left: auto;
            }

            .dashboard-empty {
                min-height: 150px;
            }

            .activity-list {
                padding-inline: var(--space-12);
            }
        }

        @media (max-width: 480px) {
            .dashboard-hero-actions,
            .dashboard-metric-grid.four,
            .dashboard-metric-grid.two {
                grid-template-columns: 1fr;
            }

            .dashboard-hero-meta {
                flex-direction: column;
                gap: var(--space-8);
            }

            .responsive-dashboard-table td {
                grid-template-columns: 78px minmax(0,1fr);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .modern-metric.urgent,
            .priority-panel.has-alerts .priority-symbol {
                animation: none;
            }
        }
    </style>
    @endPushOnce
    @if(Auth::user()->role !== 'employee')
        @include('dashboard.partials.employee-styles')
        @include('dashboard.partials.staff-styles')
        @if($isSmo)@include('dashboard.partials.smo-styles')@endif
    @endif
</x-app-layout>
