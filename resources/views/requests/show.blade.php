<x-app-layout>
    <x-slot name="header">
        <span style="color: white; font-weight: 800;">REQUISITION DETAILS</span>
    </x-slot>

    @php
        $lastLog = $request->logs->sortByDesc('created_at')->first();
        $questioningRole = $request->status === 'for_clarification' ? $lastLog?->role : null;
        $vpTitle = $request->request_type === 'minor' ? 'VP Finance' : 'VP Admin';

        $statusMeta = match ($request->status) {
            'pending' => ['label' => 'Awaiting department head', 'tone' => 'warning', 'icon' => 'clock-3'],
            'approved_dept' => ['label' => "Awaiting {$vpTitle}", 'tone' => 'progress', 'icon' => 'hourglass'],
            'approved_vp' => $request->request_type === 'minor'
                ? ['label' => 'Awaiting SMO processing', 'tone' => 'ready', 'icon' => 'package-check']
                : ['label' => 'Awaiting Provost', 'tone' => 'progress', 'icon' => 'hourglass'],
            'approved_provost' => ['label' => 'Awaiting President', 'tone' => 'progress', 'icon' => 'hourglass'],
            'approved_president' => ['label' => 'Awaiting SMO processing', 'tone' => 'ready', 'icon' => 'package-check'],
            'processing' => ['label' => 'Processing', 'tone' => 'progress', 'icon' => 'package-open'],
            'ready_for_pickup' => ['label' => 'Ready for pickup', 'tone' => 'ready', 'icon' => 'package-check'],
            'completed' => ['label' => 'Completed', 'tone' => 'success', 'icon' => 'badge-check'],
            'released' => ['label' => 'Released', 'tone' => 'success', 'icon' => 'badge-check'],
            'rejected' => ['label' => 'Rejected', 'tone' => 'danger', 'icon' => 'circle-x'],
            'for_clarification' => ['label' => 'Clarification requested', 'tone' => 'clarification', 'icon' => 'message-circle-question'],
            default => ['label' => ucwords(str_replace('_', ' ', $request->status)), 'tone' => 'neutral', 'icon' => 'circle-dot'],
        };

        $fulfillmentStatuses = ['processing', 'ready_for_pickup', 'completed', 'released'];
        $isDeptDone = in_array($request->status, ['approved_dept', 'approved_vp', 'approved_provost', 'approved_president', ...$fulfillmentStatuses], true)
            || ($request->status === 'for_clarification' && in_array($questioningRole, ['vp_finance', 'vp_admin', 'provost', 'president'], true));
        $isDeptQuestioning = $request->status === 'for_clarification' && ($questioningRole === 'dept_head' || is_null($questioningRole));
        $isVPDone = in_array($request->status, ['approved_vp', 'approved_provost', 'approved_president', ...$fulfillmentStatuses], true)
            || ($request->status === 'for_clarification' && in_array($questioningRole, ['provost', 'president'], true));
        $isVPQuestioning = $request->status === 'for_clarification' && in_array($questioningRole, ['vp_finance', 'vp_admin'], true);

        $steps = [
            ['label' => 'Submitted', 'icon' => 'send', 'state' => 'completed'],
            [
                'label' => $isDeptQuestioning ? 'Dept Head inquiry' : 'Dept Head',
                'icon' => $isDeptQuestioning ? 'help-circle' : 'user-check',
                'state' => $isDeptDone ? 'completed' : ($isDeptQuestioning ? 'questioning' : ($request->status === 'pending' ? 'active' : 'pending')),
            ],
            [
                'label' => $isVPQuestioning ? "{$vpTitle} inquiry" : $vpTitle,
                'icon' => $isVPQuestioning ? 'help-circle' : 'shield-check',
                'state' => $isVPDone ? 'completed' : ($isVPQuestioning ? 'questioning' : ($request->status === 'approved_dept' ? 'active' : 'pending')),
            ],
        ];

        if ($request->request_type === 'major') {
            $isProvostDone = in_array($request->status, ['approved_provost', 'approved_president', ...$fulfillmentStatuses], true)
                || ($request->status === 'for_clarification' && $questioningRole === 'president');
            $isProvostQuestioning = $request->status === 'for_clarification' && $questioningRole === 'provost';
            $steps[] = [
                'label' => $isProvostQuestioning ? 'Provost inquiry' : 'Provost',
                'icon' => $isProvostQuestioning ? 'help-circle' : 'graduation-cap',
                'state' => $isProvostDone ? 'completed' : ($isProvostQuestioning ? 'questioning' : ($request->status === 'approved_vp' ? 'active' : 'pending')),
            ];

            $isPresidentQuestioning = $request->status === 'for_clarification' && $questioningRole === 'president';
            $steps[] = [
                'label' => $isPresidentQuestioning ? 'President inquiry' : 'President',
                'icon' => $isPresidentQuestioning ? 'help-circle' : 'award',
                'state' => in_array($request->status, ['approved_president', ...$fulfillmentStatuses], true)
                    ? 'completed'
                    : ($isPresidentQuestioning ? 'questioning' : ($request->status === 'approved_provost' ? 'active' : 'pending')),
            ];
        }

        $readyForProcessing = ($request->request_type === 'minor' && $request->status === 'approved_vp')
            || ($request->request_type === 'major' && $request->status === 'approved_president');
        $steps[] = [
            'label' => 'SMO Processing',
            'icon' => 'package-open',
            'state' => in_array($request->status, ['ready_for_pickup', 'completed', 'released'], true) ? 'completed' : ($request->status === 'processing' || $readyForProcessing ? 'active' : 'pending'),
        ];
        $steps[] = [
            'label' => 'Ready for Pickup',
            'icon' => 'package-check',
            'state' => in_array($request->status, ['completed', 'released'], true) ? 'completed' : ($request->status === 'ready_for_pickup' ? 'active' : 'pending'),
        ];
        $steps[] = [
            'label' => 'Completed',
            'icon' => 'circle-check',
            'state' => in_array($request->status, ['completed', 'released'], true) ? 'completed' : 'pending',
        ];
    @endphp

    <div class="container-fluid request-details-page">
        <nav class="request-detail-nav" aria-label="Requisition navigation">
            <a href="{{ route('dashboard') }}" class="request-back-link"><i data-lucide="arrow-left"></i><span>Back to dashboard</span></a>
            <a href="{{ route('requests.download', $request->id) }}" class="request-pdf-action btn btn-htc"><i data-lucide="file-down"></i><span>Export official PDF</span></a>
        </nav>

        <header class="request-hero">
            <div class="request-hero-main">
                <div class="request-hero-icon"><i data-lucide="clipboard-list"></i></div>
                <div class="min-w-0">
                    <div class="request-kicker">{{ strtoupper($request->request_type) }} REQUISITION</div>
                    <h1>{{ $request->tracking_code }}</h1>
                    <div class="request-status request-status--{{ $statusMeta['tone'] }}"><i data-lucide="{{ $statusMeta['icon'] }}"></i><span>{{ $statusMeta['label'] }}</span></div>
                </div>
            </div>
            <div class="request-hero-stats">
                <div><span>Total value</span><strong>₱{{ number_format($request->grand_total, 2) }}</strong></div>
                <div><span>Items</span><strong>{{ $request->items->sum('quantity') }}</strong></div>
                <div><span>Submitted</span><strong>{{ $request->created_at->format('M d, Y') }}</strong></div>
                <div><span>SLA deadline</span><strong>{{ $request->due_at?->format('M d, Y') ?? 'Not set' }}</strong></div>
            </div>
        </header>

        <div class="request-detail-grid">
            <main class="request-detail-main">
                <section class="request-panel request-overview-panel">
                    <div class="request-section-heading">
                        <div><span>Request overview</span><h2>Requester and purpose</h2></div>
                        <span class="request-tier-pill">{{ ucfirst($request->request_type) }} tier</span>
                    </div>
                    <div class="request-overview-grid">
                        <div class="requester-block">
                            <div class="requester-avatar"><i data-lucide="user-round"></i></div>
                            <div class="min-w-0">
                                <span class="request-field-label">Official requester</span>
                                <strong>{{ $request->user?->name ?? 'Former or unavailable user' }}</strong>
                                <small>{{ $request->user?->department ?: 'Unassigned department' }} · {{ $request->user?->school_id ?? 'No ID available' }}</small>
                            </div>
                        </div>
                        <div class="request-date-block"><span class="request-field-label">Last updated</span><strong>{{ $request->updated_at->format('M d, Y') }}</strong><small>{{ $request->updated_at->format('h:i A') }}</small></div>
                    </div>
                    <div class="request-purpose-block">
                        <div class="request-purpose-icon"><i data-lucide="message-square-text"></i></div>
                        <div class="request-purpose-copy"><span class="request-field-label">Purpose and justification</span><p>{{ $request->purpose ?: 'No reason was recorded for this requisition.' }}</p></div>
                    </div>
                </section>

                <section class="request-panel request-approval-panel">
                    <div class="request-section-heading"><div><span>Approval route</span><h2>Requisition progress</h2></div><small>Live workflow status</small></div>
                    <div class="request-progress-scroll" tabindex="0" aria-label="Approval route; scroll horizontally to see every step">
                        <ol class="request-progress-list" style="--request-step-count: {{ count($steps) }}">
                            @foreach($steps as $index => $step)
                                <li class="request-progress-step is-{{ $step['state'] }}">
                                    <div class="request-step-marker"><i data-lucide="{{ $step['icon'] }}"></i></div>
                                    <span>Step {{ $index + 1 }}</span><strong>{{ $step['label'] }}</strong>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </section>

                <section class="request-panel request-items-panel">
                    <div class="request-section-heading request-items-heading"><div><span>Order contents</span><h2>Requested items</h2></div><span class="request-item-count">{{ $request->items->count() }} {{ Str::plural('line item', $request->items->count()) }}</span></div>
                    <div class="table-responsive request-items-wrap">
                        <table class="table align-middle mb-0 workflow-responsive-table request-items-table">
                            <thead><tr><th>Item and specifications</th><th class="text-center">Quantity</th><th class="text-end">Unit price</th><th class="text-end">Subtotal</th></tr></thead>
                            <tbody>
                                @forelse($request->items as $item)
                                    <tr>
                                        <td data-label="Item"><div class="request-item-name">{{ $item->item_name }}</div><small>{{ $item->specifications ?: 'No additional specifications' }}</small></td>
                                        <td class="text-center" data-label="Quantity"><span class="request-qty-pill">{{ $item->quantity }} {{ $item->unit }}</span></td>
                                        <td class="text-end" data-label="Unit price">₱{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end request-line-total" data-label="Subtotal">₱{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">No line items were recorded.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot><tr><td colspan="3">Total requisition value</td><td class="text-end">₱{{ number_format($request->grand_total, 2) }}</td></tr></tfoot>
                        </table>
                    </div>
                </section>
            </main>

            <aside class="request-audit-column">
                <section class="request-panel request-audit-panel">
                    <div class="request-section-heading"><div><span>Accountability</span><h2>Activity audit trail</h2></div><i data-lucide="history"></i></div>
                    <div class="request-audit-stream">
                        <article class="request-audit-entry is-created">
                            <div class="request-audit-dot"><i data-lucide="plus"></i></div>
                            <div><time>{{ $request->created_at->format('M d, Y · h:i A') }}</time><h3>Requisition created</h3><p>Submitted by {{ $request->user?->name ?? 'Former or unavailable user' }}</p></div>
                        </article>
                        @foreach($request->logs ?? [] as $log)
                            <article class="request-audit-entry">
                                <div class="request-audit-dot"><i data-lucide="check"></i></div>
                                <div>
                                    <time>{{ $log->created_at->format('M d, Y · h:i A') }}</time><h3>{{ $log->action }}</h3>
                                    @if($log->remarks)<blockquote>{{ $log->remarks }}</blockquote>@endif
                                    @if($log->digitalSignature)<span class="request-signature-badge"><i data-lucide="badge-check"></i> Digitally signed by {{ $log->digitalSignature->signer_name }}</span>@endif
                                </div>
                            </article>
                        @endforeach
                        @if(in_array($request->status, ['completed', 'released'], true))
                            <article class="request-audit-entry is-released">
                                <div class="request-audit-dot"><i data-lucide="package-check"></i></div>
                                <div><time>{{ ($request->closed_at ?? $request->updated_at)->format('M d, Y · h:i A') }}</time><h3>Fulfillment completed</h3><p>The requestor received the prepared supplies.</p></div>
                            </article>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <style>
        .request-details-page { --request-ink: #173226; --request-muted: #6f8177; --request-line: #dfe8e2; padding: .75rem 0 1.25rem; }
        .request-detail-nav { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .8rem; }
        .request-back-link { display: inline-flex; align-items: center; gap: .45rem; color: #607168; font-size: .7rem; font-weight: 800; text-decoration: none; text-transform: uppercase; letter-spacing: .05em; }
        .request-back-link:hover { color: var(--htc-green); }
        .request-back-link svg, .request-pdf-action svg { width: 16px; height: 16px; }
        .request-pdf-action { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: .6rem 1rem; border-radius: 11px; }
        .request-hero { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 2rem; overflow: hidden; margin-bottom: 1rem; padding: 1.35rem 1.5rem; border: 1px solid rgba(255,255,255,.14); border-radius: 18px; background: radial-gradient(circle at 84% -40%, rgba(110,231,155,.25), transparent 20rem), linear-gradient(125deg, #0b3d25, #17633e 60%, #1c7549); color: #fff; box-shadow: 0 14px 30px rgba(14, 67, 39, .16); }
        .request-hero::after { content: ''; position: absolute; right: -4rem; bottom: -7rem; width: 17rem; height: 17rem; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; }
        .request-hero-main, .request-hero-stats { position: relative; z-index: 1; display: flex; align-items: center; }
        .request-hero-main { min-width: 0; gap: 1rem; }
        .request-hero-icon { width: 52px; height: 52px; flex: 0 0 52px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.17); border-radius: 15px; background: rgba(255,255,255,.12); }
        .request-hero-icon svg { width: 25px; height: 25px; }
        .request-kicker { margin-bottom: .2rem; color: #a9e8bf; font-size: .62rem; font-weight: 800; letter-spacing: .12em; }
        .request-hero h1 { margin: 0 0 .5rem; overflow-wrap: anywhere; color: #fff; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.35rem; font-weight: 800; }
        .request-status { width: fit-content; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .55rem; border-radius: 999px; font-size: .64rem; font-weight: 800; }
        .request-status svg { width: 13px; height: 13px; }
        .request-status--warning, .request-status--progress { background: #fff3cd; color: #785500; }
        .request-status--ready, .request-status--success { background: #dff7e8; color: #11623a; }
        .request-status--danger { background: #ffe1e3; color: #9d2930; }
        .request-status--clarification { background: #e6edff; color: #3159a8; }
        .request-status--neutral { background: rgba(255,255,255,.15); color: #fff; }
        .request-hero-stats { flex: 0 0 auto; gap: .45rem; }
        .request-hero-stats > div { min-width: 112px; padding: .7rem .85rem; border: 1px solid rgba(255,255,255,.13); border-radius: 12px; background: rgba(5,38,21,.18); }
        .request-hero-stats span { display: block; margin-bottom: .18rem; color: #bfe6cc; font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .request-hero-stats strong { color: #fff; font-size: .86rem; }
        .request-detail-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, .8fr); gap: 1rem; align-items: start; }
        .request-detail-main { min-width: 0; display: grid; gap: 1rem; }
        .request-panel { overflow: hidden; border: 1px solid var(--request-line); border-radius: 16px; background: #fff; box-shadow: 0 7px 20px rgba(24, 70, 43, .05); }
        .request-section-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; border-bottom: 1px solid #e7eee9; background: linear-gradient(135deg, #fbfdfc, #f4f8f5); }
        .request-section-heading > div > span, .request-field-label { display: block; color: #85958c; font-size: .58rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; }
        .request-section-heading h2 { margin: .15rem 0 0; color: var(--request-ink); font-size: .95rem; font-weight: 800; }
        .request-section-heading > small { color: var(--request-muted); font-size: .65rem; }
        .request-tier-pill, .request-item-count { padding: .32rem .6rem; border: 1px solid #cfe1d6; border-radius: 999px; background: #edf7f1; color: #246443; font-size: .62rem; font-weight: 800; white-space: nowrap; }
        .request-overview-grid { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 1.25rem; padding: 1.15rem; }
        .requester-block { min-width: 0; display: flex; align-items: center; gap: .8rem; }
        .requester-avatar, .request-purpose-icon { display: grid; place-items: center; color: var(--htc-green); background: #e4f3e9; }
        .requester-avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; }
        .requester-avatar svg { width: 20px; height: 20px; }
        .requester-block strong, .request-date-block strong { display: block; margin: .15rem 0; color: var(--request-ink); font-size: .82rem; }
        .requester-block small, .request-date-block small { display: block; color: var(--request-muted); font-size: .66rem; }
        .request-date-block { min-width: 120px; padding-left: 1.25rem; border-left: 1px solid #e7eee9; }
        .request-purpose-block { position: relative; display: flex; align-items: flex-start; gap: 1rem; margin: 0 1.15rem 1.15rem; padding: 1.1rem 1.15rem; overflow: hidden; border: 1px solid #b9d9c5; border-left: 5px solid var(--htc-green); border-radius: 13px; background: linear-gradient(135deg, #edf8f1, #f9fcfa); box-shadow: 0 7px 18px rgba(24, 91, 59, .08); }
        .request-purpose-block::after { content: '“'; position: absolute; right: 1rem; top: -.55rem; color: rgba(24, 91, 59, .08); font-family: Georgia, serif; font-size: 5.5rem; line-height: 1; pointer-events: none; }
        .request-purpose-icon { width: 42px; height: 42px; flex: 0 0 42px; border: 1px solid #b9ddc6; border-radius: 12px; background: #d9f0e1; }
        .request-purpose-icon svg { width: 20px; height: 20px; }
        .request-purpose-copy { position: relative; z-index: 1; min-width: 0; }
        .request-purpose-copy .request-field-label { color: #267048; font-size: .62rem; }
        .request-purpose-block p { margin: .35rem 0 0; color: #1f3e2e; font-size: .84rem; font-weight: 600; line-height: 1.65; white-space: pre-line; overflow-wrap: anywhere; }
        .request-progress-scroll { overflow-x: auto; padding: 1.2rem 1.15rem 1.3rem; scrollbar-width: thin; }
        .request-progress-list { min-width: calc(var(--request-step-count) * 116px); display: grid; grid-template-columns: repeat(var(--request-step-count), minmax(100px, 1fr)); margin: 0; padding: 0; list-style: none; }
        .request-progress-step { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .request-progress-step::after { content: ''; position: absolute; z-index: 0; top: 18px; left: 50%; width: 100%; height: 2px; background: #dfe7e2; }
        .request-progress-step:last-child::after { display: none; }
        .request-progress-step.is-completed::after { background: var(--htc-green); }
        .request-step-marker { position: relative; z-index: 1; width: 38px; height: 38px; display: grid; place-items: center; margin-bottom: .55rem; border: 2px solid #d9e3dd; border-radius: 50%; background: #fff; color: #91a097; }
        .request-step-marker svg { width: 16px; height: 16px; }
        .request-progress-step > span { color: #9aa8a0; font-size: .52rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; }
        .request-progress-step > strong { max-width: 110px; margin-top: .15rem; color: #68796f; font-size: .63rem; line-height: 1.3; }
        .request-progress-step.is-completed .request-step-marker { border-color: var(--htc-green); background: var(--htc-green); color: #fff; }
        .request-progress-step.is-completed > strong { color: var(--htc-green); }
        .request-progress-step.is-active .request-step-marker { border-color: #e2a900; color: #a87900; box-shadow: 0 0 0 4px #fff5cf; }
        .request-progress-step.is-active > strong { color: #8a6500; }
        .request-progress-step.is-questioning .request-step-marker { border-color: #4571c5; color: #3159a8; box-shadow: 0 0 0 4px #eaf0ff; }
        .request-progress-step.is-questioning > strong { color: #3159a8; }
        .request-items-wrap { border: 0 !important; border-radius: 0 !important; }
        .request-items-table thead th { padding: .72rem 1rem !important; background: #f7faf8 !important; color: #718178 !important; font-size: .57rem !important; }
        .request-items-table tbody td { padding: .9rem 1rem !important; }
        .request-item-name { margin-bottom: .2rem; color: var(--request-ink); font-size: .78rem; font-weight: 800; }
        .request-items-table tbody small { color: var(--request-muted); font-size: .63rem; }
        .request-qty-pill { display: inline-flex; padding: .25rem .5rem; border-radius: 7px; background: #f0f4f2; color: #40554a; font-size: .65rem; font-weight: 700; }
        .request-line-total { color: var(--htc-green) !important; font-weight: 800; }
        .request-items-table tfoot td { padding: .85rem 1rem !important; border: 0; background: #e8f4ec; color: #315341; font-size: .65rem; font-weight: 800; text-transform: uppercase; }
        .request-items-table tfoot td:last-child { color: var(--htc-green); font-size: 1.05rem; }
        .request-audit-column { min-width: 0; }
        .request-audit-panel { position: sticky; top: 88px; }
        .request-audit-panel > .request-section-heading > svg { width: 18px; height: 18px; color: #7c8e84; }
        .request-audit-stream { max-height: calc(100dvh - 260px); overflow-y: auto; padding: 1.1rem 1.15rem; overscroll-behavior: contain; }
        .request-audit-entry { position: relative; display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: .65rem; padding-bottom: 1.25rem; }
        .request-audit-entry:not(:last-child)::after { content: ''; position: absolute; top: 26px; bottom: 0; left: 13px; border-left: 1px dashed #cfdcd4; }
        .request-audit-dot { position: relative; z-index: 1; width: 28px; height: 28px; display: grid; place-items: center; border: 1px solid #cbdad1; border-radius: 50%; background: #f2f7f4; color: #4c725d; }
        .request-audit-dot svg { width: 12px; height: 12px; }
        .request-audit-entry.is-created .request-audit-dot, .request-audit-entry.is-released .request-audit-dot { border-color: #7eb996; background: #e5f5eb; color: #17633e; }
        .request-audit-entry time { display: block; margin-bottom: .15rem; color: #91a097; font-size: .56rem; font-weight: 700; text-transform: uppercase; }
        .request-audit-entry h3 { margin: 0; color: var(--request-ink); font-size: .72rem; font-weight: 800; line-height: 1.4; }
        .request-audit-entry p { margin: .22rem 0 0; color: var(--request-muted); font-size: .62rem; line-height: 1.45; }
        .request-audit-entry blockquote { margin: .5rem 0 0; padding: .55rem .65rem; border-left: 3px solid #9cc4aa; border-radius: 0 8px 8px 0; background: #f4f8f5; color: #586b60; font-size: .63rem; font-style: italic; }
        .request-signature-badge { display: inline-flex; align-items: center; gap: .3rem; margin-top: .45rem; color: #27724a; font-size: .56rem; font-weight: 800; }
        .request-signature-badge svg { width: 12px; height: 12px; }
        @media (max-width: 991.98px) {
            .request-hero { align-items: flex-start; flex-direction: column; }
            .request-hero-stats { width: 100%; }
            .request-hero-stats > div { flex: 1; min-width: 0; }
            .request-detail-grid { grid-template-columns: 1fr; }
            .request-audit-panel { position: static; }
            .request-audit-stream { max-height: none; }
        }
        @media (max-width: 575.98px) {
            .request-details-page { padding-top: .25rem; }
            .request-detail-nav { align-items: stretch; flex-direction: column; }
            .request-pdf-action { width: 100%; }
            .request-hero { padding: 1rem; border-radius: 15px; }
            .request-hero-icon { width: 44px; height: 44px; flex-basis: 44px; }
            .request-hero h1 { font-size: 1.05rem; }
            .request-hero-stats { display: grid; grid-template-columns: 1fr 1fr; }
            .request-hero-stats > div:first-child { grid-column: 1 / -1; }
            .request-overview-grid { grid-template-columns: 1fr; }
            .request-date-block { padding: .8rem 0 0; border-top: 1px solid #e7eee9; border-left: 0; }
            .request-section-heading { align-items: flex-start; padding: .85rem; }
            .request-purpose-block { margin: 0 .85rem .85rem; }
            .request-progress-scroll { padding-right: .85rem; padding-left: .85rem; }
            .request-items-heading { align-items: center; }
        }
    </style>
</x-app-layout>
