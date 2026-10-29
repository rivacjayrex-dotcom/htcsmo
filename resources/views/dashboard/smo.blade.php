@php
    $selectedTab = in_array(request('tab'), ['recent', 'deadlines', 'inventory'], true) ? request('tab') : 'recent';
    $tabs = [
        'recent' => ['label' => 'Recent requisitions', 'icon' => 'files', 'count' => $stats['total'], 'description' => 'Latest requests across all stages', 'hint' => 'View requisitions'],
        'deadlines' => ['label' => 'Deadline requisitions', 'icon' => 'alarm-clock', 'count' => $stats['urgent'], 'description' => 'Near-deadline and overdue requests', 'hint' => 'Review deadlines'],
        'inventory' => ['label' => 'Inventory warnings', 'icon' => 'package-minus', 'count' => $stats['low_stock'], 'description' => 'Items at or below minimum stock', 'hint' => 'Check inventory'],
    ];
@endphp
<div class="smo-workspace-scroll">
<div class="smo-workspace" data-smo-dashboard>
    @include('dashboard.partials.hero')
    <nav class="smo-card-tabs" role="tablist" aria-label="SMO dashboard sections">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('dashboard', ['tab' => $key]) }}" id="smo-tab-{{ $key }}" class="smo-card-tab {{ $key }}" role="tab" data-smo-tab="{{ $key }}" data-no-loader aria-selected="{{ $selectedTab === $key ? 'true' : 'false' }}" aria-controls="smo-panel-{{ $key }}" tabindex="{{ $selectedTab === $key ? '0' : '-1' }}">
                <span class="smo-tab-top"><span class="smo-tab-icon"><i data-lucide="{{ $tab['icon'] }}" aria-hidden="true"></i></span><strong>{{ number_format($tab['count']) }}</strong></span>
                <span class="smo-tab-label">{{ $tab['label'] }}</span>
                <span class="smo-tab-description">{{ $tab['description'] }}</span>
                <span class="smo-tab-hint">{{ $tab['hint'] }}<i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
        @endforeach
    </nav>
    <div class="smo-tab-content">
        <section id="smo-panel-recent" role="tabpanel" aria-labelledby="smo-tab-recent" tabindex="0" @if($selectedTab !== 'recent') hidden @endif>
            <section class="dashboard-panel queue-panel">c
                <div class="panel-heading">
                    <div>
                        <h3>Recent requisitions</h3>
                        <p>Latest {{ $allStaffRequests->count() }} of {{ $stats['total'] }} requisitions, newest first.</p>
                    </div>
                    <a href="{{ route('requests.index') }}" class="panel-action"><i data-lucide="arrow-up-right"></i> View All</a>
                </div>

                <div class="table-responsive dashboard-table-wrap">
                    <table class="table dashboard-table responsive-dashboard-table">
                        <thead>
                            <tr><th scope="col">Request</th><th scope="col">Requestor</th><th scope="col">Primary item</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Action</span></th></tr>
                        </thead>
                        <tbody>
                            @forelse($allStaffRequests as $req)
                                @php
                                    $isOverdue = $req->isDeadlineOverdue();
                                    $isNearDeadline = $req->isDeadlineNearing();
                                    $statusLabel = match($req->status) {
                                        'pending' => 'Department review',
                                        'approved_dept' => $req->request_type === 'minor' ? 'VP Finance review' : 'VP Administration review',
                                        'approved_vp' => $req->request_type === 'minor' ? 'Awaiting SMO processing' : 'Provost review',
                                        'approved_provost' => 'President review',
                                        'approved_president' => 'Awaiting SMO processing',
                                        'processing' => 'SMO processing',
                                        'ready_for_pickup' => 'Ready for pickup',
                                        'for_clarification' => 'For clarification',
                                        default => ucfirst(str_replace('_', ' ', $req->status)),
                                    };
                                    $statusTone = match($req->status) {
                                        'for_clarification', 'rejected', 'cancelled' => 'warning',
                                        'completed', 'released' => 'ready',
                                        'approved_vp' => $req->request_type === 'minor' ? 'ready' : 'pending',
                                        'approved_president', 'processing', 'ready_for_pickup' => 'ready',
                                        default => 'pending',
                                    };
                                @endphp
                                <tr class="{{ $isOverdue ? 'row-overdue' : ($isNearDeadline ? 'row-near-deadline' : '') }}">
                                    <td data-label="Request">
                                        <div class="request-code">{{ $req->tracking_code }}</div>
                                        <small>{{ $req->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td data-label="Requestor">
                                        <div class="person-cell"><div><b>{{ $req->user?->name ?? 'Unavailable requestor' }}</b><small>{{ $req->user?->department ?: 'General' }}</small></div></div>
                                    </td>
                                    <td data-label="Primary item"><b>{{ $req->items->first()->item_name ?? 'Supplies' }}</b>@if($req->items->count() > 1)<small>+{{ $req->items->count() - 1 }} more item(s)</small>@endif</td>
                                    <td data-label="Status">
                                        <span class="status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
                                        @if($isOverdue)<small class="deadline-text overdue">Overdue by SLA</small>@elseif($isNearDeadline)<small class="deadline-text near">Near SLA deadline</small>@endif
                                    </td>
                                    <td data-label="Action"><a href="{{ route('requests.show', $req->id) }}" class="row-action" aria-label="View {{ $req->tracking_code }}"><i data-lucide="arrow-right"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="dashboard-empty"><i data-lucide="inbox"></i><strong>No recent requisitions</strong><span>New requisitions will appear here as they are submitted.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="smo-workspace-summary"><span><b>{{ $stats['pending'] }}</b> active in the pipeline</span><span>Released this month <b>&#8369;<span>{{ number_format((float) $stats['monthly_value'], 2) }}</span></b></span></div>
        </section>
        <section id="smo-panel-deadlines" role="tabpanel" aria-labelledby="smo-tab-deadlines" tabindex="0" @if($selectedTab !== 'deadlines') hidden @endif>
            <article class="priority-panel deadline {{ $stats['urgent'] > 0 ? 'has-alerts' : 'is-clear' }}">
                <div class="priority-heading">
                    <span class="priority-symbol"><i data-lucide="{{ $stats['urgent'] > 0 ? 'siren' : 'shield-check' }}"></i></span>
                    <div>
                        <span>Deadline monitor</span>
                        <h3>{{ $stats['urgent'] > 0 ? ($stats['urgent'] === 1 ? '1 request needs attention' : $stats['urgent'].' requests need attention') : 'All request deadlines are healthy' }}</h3>
                    </div>
                    <a href="{{ route('requests.index', ['status' => 'deadline']) }}">View deadline requests <i data-lucide="arrow-up-right"></i></a>
                </div>
                <div class="priority-list">
                    @forelse($deadlineRequests as $deadlineRequest)
                        @php
                            $waitingDays = $deadlineRequest->approvalDeadlineDaysElapsed();
                            $deadlineState = $deadlineRequest->isDeadlineOverdue() ? 'overdue' : 'near';
                        @endphp
                        <a href="{{ route('requests.show', $deadlineRequest) }}" class="priority-row">
                            <span class="priority-state {{ $deadlineState }}">{{ $deadlineRequest->isDeadlineOverdue() ? 'Overdue' : 'Near deadline' }}</span>
                            <span class="priority-detail"><b>{{ $deadlineRequest->tracking_code }}</b><small>{{ $deadlineRequest->items->first()->item_name ?? 'Supply requisition' }} · {{ $deadlineRequest->user?->department ?: 'General' }}</small></span>
                            <span class="priority-value">Day {{ $waitingDays }}<i data-lucide="chevron-right"></i></span>
                        </a>
                    @empty
                        <div class="priority-clear"><i data-lucide="circle-check-big"></i><span><b>No near or overdue requests</b><small>The institutional workflow is currently within the three-working-day target.</small></span></div>
                    @endforelse
                </div>
                @if($stats['urgent'] > $deadlineRequests->count())
                    <a href="{{ route('requests.index', ['status' => 'deadline']) }}" class="activity-footer">Showing {{ $deadlineRequests->count() }} of {{ $stats['urgent'] }} deadline risks <i data-lucide="arrow-right"></i></a>
                @endif
            </article>
            @include('requests.partials.approval-deadline-watch', ['approvalDeadlineRequests' => $approvalDeadlineRequests])
        </section>
        <section id="smo-panel-inventory" role="tabpanel" aria-labelledby="smo-tab-inventory" tabindex="0" @if($selectedTab !== 'inventory') hidden @endif>
            <article class="priority-panel stock {{ $stats['low_stock'] > 0 ? 'has-alerts' : 'is-clear' }}">
                <div class="priority-heading">
                    <span class="priority-symbol"><i data-lucide="{{ $stats['low_stock'] > 0 ? 'package-minus' : 'package-check' }}"></i></span>
                    <div>
                        <span>Inventory warning</span>
                        <h3>{{ $stats['low_stock'] > 0 ? ($stats['low_stock'] === 1 ? '1 item requires restocking' : $stats['low_stock'].' items require restocking') : 'Inventory thresholds are healthy' }}</h3>
                        @if($stats['out_of_stock'] > 0)<small>{{ $stats['out_of_stock'] }} out of stock</small>@endif
                    </div>
                    <a href="{{ route('inventory.index') }}">Manage inventory <i data-lucide="arrow-up-right"></i></a>
                </div>
                <div class="priority-list">
                    @forelse($lowStockSupplies as $stockItem)
                        <a href="{{ route('inventory.edit', $stockItem->id) }}" class="priority-row">
                            <span class="priority-state {{ $stockItem->quantity <= 0 ? 'overdue' : 'near' }}">{{ $stockItem->quantity <= 0 ? 'Out of stock' : 'Low stock' }}</span>
                            <span class="priority-detail"><b>{{ $stockItem->item_name }}</b><small>{{ strtoupper($stockItem->requisition_tier ?? 'minor') }} · Minimum {{ $stockItem->min_stock_level }} {{ $stockItem->unit }}</small></span>
                            <span class="priority-value">{{ $stockItem->quantity }} left<i data-lucide="chevron-right"></i></span>
                        </a>
                    @empty
                        <div class="priority-clear"><i data-lucide="circle-check-big"></i><span><b>No low-stock items</b><small>Every inventory item is currently above its minimum level.</small></span></div>
                    @endforelse
                </div>
                @if($stats['low_stock'] > $lowStockSupplies->count())
                    <a href="{{ route('inventory.index') }}" class="activity-footer">Showing {{ $lowStockSupplies->count() }} of {{ $stats['low_stock'] }} stock warnings <i data-lucide="arrow-right"></i></a>
                @endif
            </article>
        </section>
    </div>
    <aside class="dashboard-panel activity-panel" aria-labelledby="smoActivityHeading" id="smo-activity">
        <div class="panel-heading compact">
            <div><h3 id="smoActivityHeading">Alerts and activity</h3></div>
            <a href="{{ route('notifications') }}" class="icon-link" aria-label="View all notifications"><i data-lucide="arrow-up-right"></i></a>
        </div>
        <div class="activity-list">
            @forelse($recentActivity as $activity)
                <article class="activity-item {{ $activity->is_read ? 'is-read' : 'is-unread' }}">
                    <span class="activity-icon {{ $activity->safeType() }}" aria-hidden="true"><i data-lucide="{{ $activity->safeIcon() }}"></i></span>
                    <div><h4>{{ $activity->title }}</h4><p>{{ Str::limit($activity->message, 120) }}</p><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time></div>
                    <form method="POST" action="{{ route('notifications.open', $activity) }}">
                        @csrf
                        <button type="submit" class="smo-notification-action" aria-label="Open notification: {{ $activity->title }}"></button>
                    </form>
                    @unless($activity->is_read)<span class="smo-unread-dot" aria-label="Unread notification"></span>@endunless
                </article>
            @empty
                <div class="dashboard-empty small"><i data-lucide="bell-off"></i><strong>No recent alerts</strong><span>New system activity will appear here.</span></div>
            @endforelse
        </div>
        <a href="{{ route('notifications') }}" class="activity-footer">View notification center <i data-lucide="arrow-right"></i></a>
    </aside>
</div>
</div>
@include('dashboard.partials.smo-tabs')
