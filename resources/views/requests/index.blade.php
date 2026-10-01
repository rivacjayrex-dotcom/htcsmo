<x-app-layout>
    <x-slot name="header">{{ __('Active Requisitions') }}</x-slot>

    <div class="request-workspace">
        <section class="request-hero">
            <div>
                <span class="request-eyebrow">Live procurement tracking</span>
                <h2>{{ Auth::user()->role === 'smo' ? 'Institutional request pipeline' : 'Your active requisitions' }}</h2>
                <p>{{ Auth::user()->role === 'smo' ? 'Monitor every request moving through institutional approval and fulfillment.' : 'Follow each request from submission through approval and SMO release.' }}</p>
            </div>
            <div class="request-hero-actions">
                <button type="button" data-bs-toggle="modal" data-bs-target="#globalRequestModal"><i data-lucide="plus"></i> New requisition</button>
                <a href="{{ route('requests.archive') }}"><i data-lucide="archive"></i> Open archive</a>
            </div>
        </section>

        <section class="request-metrics">
            <article class="request-metric total"><span><i data-lucide="layers-3"></i></span><div><strong>{{ $requestStats['total'] }}</strong><h3>All active</h3><p>Currently in the pipeline</p></div></article>
            <article class="request-metric pending"><span><i data-lucide="clock-3"></i></span><div><strong>{{ $requestStats['pending'] }}</strong><h3>New submissions</h3><p>Awaiting first review</p></div></article>
            <article class="request-metric clarification"><span><i data-lucide="message-circle-warning"></i></span><div><strong>{{ $requestStats['clarification'] }}</strong><h3>Needs clarification</h3><p>Requires requestor action</p></div></article>
            <article class="request-metric ready"><span><i data-lucide="package-check"></i></span><div><strong>{{ $requestStats['ready'] }}</strong><h3>Ready for release</h3><p>Fully approved requests</p></div></article>
            <article class="request-metric deadline {{ $requestStats['deadline'] > 0 ? 'attention' : '' }}"><span><i data-lucide="siren"></i></span><div><strong>{{ $requestStats['deadline'] }}</strong><h3>Deadline watch</h3><p>Day 2 or already overdue</p></div></article>
        </section>

        <section class="request-panel">
            <div class="request-panel-heading">
                <div>
                    <span class="request-eyebrow">Current workflow</span>
                    <h3>In-progress requests <em>{{ $activeRequests->total() }}</em></h3>
                    <p>Search, filter, and open any active requisition.</p>
                </div>
                @if(request()->anyFilled(['search', 'status', 'dept', 'date_from', 'date_to', 'order']))
                    <a href="{{ route('requests.index') }}" class="clear-request-filters"><i data-lucide="rotate-ccw"></i> Clear filters</a>
                @endif
            </div>

            <form method="GET" action="{{ route('requests.index') }}" class="request-filters">
                <label class="request-search">
                    <span>Search requests</span>
                    <div><i data-lucide="search"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Tracking code, item, purpose, or requestor"></div>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">All active stages</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending Department Head</option>
                        <option value="approved_dept" @selected(request('status') === 'approved_dept')>Department approved</option>
                        <option value="approved_vp" @selected(request('status') === 'approved_vp')>VP approved</option>
                        <option value="approved_provost" @selected(request('status') === 'approved_provost')>Provost approved</option>
                        <option value="approved_president" @selected(request('status') === 'approved_president')>President approved</option>
                        <option value="for_clarification" @selected(request('status') === 'for_clarification')>Needs clarification</option>
                    </select>
                </label>
                @if(Auth::user()->role === 'smo')
                    <label>
                        <span>Department</span>
                        <select name="dept">
                            <option value="">All departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" @selected(request('dept') === $department)>{{ $department }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label><span>From</span><input type="date" name="date_from" value="{{ request('date_from') }}"></label>
                <label><span>To</span><input type="date" name="date_to" value="{{ request('date_to') }}"></label>
                <label><span>Sort</span><select name="order"><option value="desc" @selected(request('order', 'desc') === 'desc')>Newest first</option><option value="asc" @selected(request('order') === 'asc')>Oldest first</option></select></label>
                <button type="submit"><i data-lucide="list-filter"></i> Apply</button>
            </form>

            @include('requests.partials.history-table', ['requisitions' => $activeRequests, 'type' => 'active'])

            @if($activeRequests->hasPages())
                <div class="request-pagination">{{ $activeRequests->links() }}</div>
            @endif
        </section>
    </div>

    <style>
        .request-workspace { padding: .25rem; color: #293a30; }.request-hero { min-height: 175px; display: flex; align-items: center; justify-content: space-between; gap: 2rem; margin-bottom: .9rem; padding: 1.7rem 1.9rem; overflow: hidden; border-radius: 20px; background: radial-gradient(circle at 10% -80%,rgba(74,222,128,.25),transparent 22rem),radial-gradient(circle at 95% 150%,rgba(255,255,255,.12),transparent 16rem),linear-gradient(125deg,#052e16,#14532d 58%,#17713d); color: #fff; box-shadow: 0 16px 38px rgba(12,68,35,.18); }.request-eyebrow { display: block; margin-bottom: .4rem; color: #8ee5aa; font-size: .59rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }.request-hero h2 { margin: 0 0 .4rem; color: #fff; font-size: clamp(1.45rem,2.5vw,2rem); font-weight: 800; letter-spacing: -.035em; }.request-hero p { max-width: 600px; margin: 0; color: rgba(239,251,243,.72); font-size: .72rem; line-height: 1.6; }.request-hero-actions { min-width: 160px; display: flex; flex-direction: column; gap: .55rem; }.request-hero-actions :is(button,a) { min-height: 41px; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .6rem .85rem; border: 1px solid rgba(255,255,255,.19); border-radius: 10px; font-size: .65rem; font-weight: 800; text-decoration: none; transition: transform .18s ease; }.request-hero-actions button { background: #fff; color: #14532d; }.request-hero-actions a { background: rgba(255,255,255,.09); color: #fff; }.request-hero-actions :is(button,a):hover { transform: translateY(-2px); }.request-hero-actions svg { width: 15px; }
        .request-metrics { display: grid; grid-template-columns: repeat(5,minmax(0,1fr)); gap: .7rem; margin-bottom: .9rem; }.request-metric { --tone:#51645a;--soft:#eef3f0; min-width: 0; display: flex; align-items: center; gap: .75rem; padding: .9rem; border: 1px solid #dfe8e2; border-radius: 15px; background: #fff; box-shadow: 0 6px 18px rgba(22,65,39,.05); }.request-metric > span { width: 38px; height: 38px; flex: 0 0 38px; display: grid; place-items: center; border-radius: 11px; color: var(--tone); background: var(--soft); }.request-metric svg { width: 18px; }.request-metric strong { display: block; color: var(--tone); font-size: 1.3rem; line-height: 1; }.request-metric h3 { margin: .22rem 0 .08rem; color: #35473c; font-size: .64rem; font-weight: 800; }.request-metric p { margin: 0; color: #8b978f; font-size: .5rem; white-space: nowrap; }.request-metric.pending { --tone:#956109;--soft:#fff1d2; }.request-metric.clarification { --tone:#a55b0a;--soft:#ffead2; }.request-metric.ready { --tone:#176c42;--soft:#e1f3e7; }.request-metric.deadline { --tone:#637169;--soft:#eef2ef; }.request-metric.deadline.attention { --tone:#b82f38;--soft:#fde4e6; border-color:#ecc3c6; background:linear-gradient(145deg,#fff,#fff7f7); animation:requestAlert 2.8s ease-in-out infinite; }
        .request-panel { overflow: hidden; border: 1px solid #dfe8e2; border-radius: 18px; background: #fff; box-shadow: 0 8px 26px rgba(23,65,40,.06); }.request-panel-heading { min-height: 86px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid #e5ece7; }.request-panel-heading .request-eyebrow { margin-bottom: .2rem; color: #368258; }.request-panel-heading h3 { margin: 0; color: #293d31; font-size: .92rem; font-weight: 800; }.request-panel-heading h3 em { display: inline-grid; min-width: 23px; height: 23px; margin-left: .3rem; place-items: center; border-radius: 999px; background: #e5f3e9; color: #226a43; font-size: .58rem; font-style: normal; }.request-panel-heading p { margin: .2rem 0 0; color: #87938b; font-size: .6rem; }.clear-request-filters { display: inline-flex; align-items: center; gap: .35rem; color: #a4383f; font-size: .58rem; font-weight: 800; text-decoration: none; }.clear-request-filters svg { width: 13px; }
        .request-filters { display: grid; grid-template-columns: minmax(220px,1.7fr) repeat(5,minmax(105px,.75fr)) auto; gap: .5rem; align-items: end; padding: .8rem 1rem; border-bottom: 1px solid #e7ede9; background: #fafcfb; }.request-filters label { min-width: 0; }.request-filters label > span { display: block; margin: 0 0 .27rem .12rem; color: #78867d; font-size: .5rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }.request-filters :is(input,select) { width: 100%; min-height: 37px; padding: .42rem .55rem; border: 1px solid #d8e4dc; border-radius: 8px; outline: 0; color: #46584d; background: #fff; font-size: .61rem; }.request-filters :is(input,select):focus { border-color: #2f8558; box-shadow: 0 0 0 3px rgba(34,126,76,.1); }.request-search > div { position: relative; }.request-search svg { position: absolute; top: 50%; left: .65rem; width: 14px; color: #87948c; transform: translateY(-50%); }.request-search input { padding-left: 2rem; }.request-filters > button { min-height: 37px; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .45rem .7rem; border: 0; border-radius: 8px; background: #1b7045; color: #fff; font-size: .6rem; font-weight: 800; }.request-filters > button svg { width: 13px; }
        .request-panel > .request-history-card { border: 0 !important; border-radius: 0 !important; box-shadow: none !important; }.request-panel .table-responsive { border: 0 !important; border-radius: 0 !important; }.request-pagination { padding: .75rem 1rem; border-top: 1px solid #e5ece7; }.request-empty { min-height: 240px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }.request-empty > span { width: 52px; height: 52px; display: grid; place-items: center; margin-bottom: .7rem; border-radius: 16px; background: #e8f4ec; color: #277048; }.request-empty svg { width: 24px; }.request-empty strong { color: #3e5145; font-size: .72rem; }.request-empty p { margin: .2rem 0 .8rem; color: #8b978f; font-size: .6rem; }.request-empty button { padding: .5rem .75rem; border: 0; border-radius: 8px; color: #fff; background: #1b7045; font-size: .6rem; font-weight: 800; }
        @keyframes requestAlert { 0%,100%{box-shadow:0 6px 18px rgba(184,47,56,.06)}50%{box-shadow:0 8px 24px rgba(184,47,56,.18)} }
        @media(max-width:1399.98px){.request-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.request-filters{grid-template-columns:repeat(4,minmax(0,1fr))}.request-search{grid-column:span 2}.request-filters > button{align-self:end}}
        @media(max-width:991.98px){.request-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.request-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.request-search{grid-column:1/-1}}
        @media(max-width:767.98px){.request-workspace{padding:0}.request-hero{min-height:auto;align-items:stretch;flex-direction:column;padding:1.3rem;border-radius:17px}.request-hero-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));width:100%}.request-panel-heading{align-items:flex-start;flex-direction:column;padding:1rem}.request-filters{padding:.75rem}.request-panel .table-responsive{overflow:visible!important}.workflow-responsive-table{display:block;min-width:0!important;background:#f5f8f6}.workflow-responsive-table thead{display:none}.workflow-responsive-table tbody{display:grid;gap:.75rem;padding:.75rem}.workflow-responsive-table tr{display:block;overflow:hidden;border:1px solid #e0e9e3;border-radius:13px;background:#fff;box-shadow:0 3px 12px rgba(24,58,37,.05)}.workflow-responsive-table td{display:grid;grid-template-columns:minmax(82px,32%) minmax(0,1fr);align-items:center;gap:.65rem;width:100%;padding:.68rem .8rem!important;border-color:#edf1ee;text-align:right!important;overflow-wrap:anywhere}.workflow-responsive-table td::before{content:attr(data-label);color:#819087;font-size:.54rem;font-weight:800;text-align:left;text-transform:uppercase}.workflow-responsive-table td > *{justify-self:end}.workflow-responsive-table td[colspan]{display:block;text-align:center!important}.workflow-responsive-table td[colspan]::before{display:none}.request-person{text-align:left}.request-actions{justify-content:flex-end}}
        @media(max-width:480px){.request-metrics,.request-hero-actions,.request-filters{grid-template-columns:1fr}.request-search{grid-column:auto}.request-metric p{white-space:normal}.workflow-responsive-table td{grid-template-columns:76px minmax(0,1fr)}}
        @media(prefers-reduced-motion:reduce){.request-metric.deadline.attention{animation:none}}
    </style>
</x-app-layout>
