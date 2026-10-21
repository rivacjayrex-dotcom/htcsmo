<div class="personal-workspace">
    <section class="personal-summary" aria-label="My current request totals">
        <a href="{{ route('requests.index') }}"><div class="personal-summary-top"><span class="personal-summary-icon"><i data-lucide="layers-3" aria-hidden="true"></i></span><span>Current total</span><i data-lucide="arrow-up-right" aria-hidden="true"></i></div><strong x-data="countUp({{ $activeCount }})" x-text="current">{{ $activeCount }}</strong><h3>My active requisitions</h3><p>Follow every request in progress.</p></a>
        <a href="{{ route('requests.index', ['status' => 'for_clarification']) }}" class="personal-summary-clarification"><div class="personal-summary-top"><span class="personal-summary-icon"><i data-lucide="message-circle-question" aria-hidden="true"></i></span><span>Needs your response</span><i data-lucide="arrow-up-right" aria-hidden="true"></i></div><strong>{{ $personalCounts->clarification }}</strong><h3>Needs clarification</h3><p>Review the remarks and next steps.</p></a>
        <a href="{{ route('requests.index', ['status' => 'ready_for_pickup']) }}" class="personal-summary-pickup"><div class="personal-summary-top"><span class="personal-summary-icon"><i data-lucide="package-check" aria-hidden="true"></i></span><span>Pickup status</span><i data-lucide="arrow-up-right" aria-hidden="true"></i></div><strong>{{ $personalCounts->pickup }}</strong><h3>Ready for pickup</h3><p>View requests marked ready by SMO.</p></a>
    </section>

    <div class="personal-columns">
    <div class="personal-main-stack">
    <section class="personal-panel personal-attention-panel" aria-labelledby="personalActionHeading">
        <header><div><span class="personal-eyebrow">Your next steps</span><h2 id="personalActionHeading">Needs your attention <span>{{ $actionCount }}</span></h2><p>A quick look at requests waiting on you.</p></div><a href="{{ route('requests.index', ['status' => 'action_required']) }}">View action requests <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></header>
        <div class="personal-action-grid">
            @forelse($actionRequests as $requisition)
                @php($stage = $requisition->requestorStatus())
                <article class="personal-action-card is-{{ $stage['tone'] }}">
                    <div class="personal-action-heading"><span class="personal-action-icon"><i data-lucide="{{ $stage['icon'] }}" aria-hidden="true"></i></span><span class="personal-status is-{{ $stage['tone'] }}">{{ $stage['label'] }}</span></div>
                    <h3>{{ $requisition->items->first()->item_name ?? 'Supply requisition' }}</h3>
                    <p class="personal-reference">{{ $requisition->tracking_code }} <span>· {{ ucfirst($requisition->request_type) }}</span></p>
                    @if($requisition->status === 'for_clarification')
                        <blockquote>{{ Str::limit($requisition->clarificationRemarks() ?? 'No clarification remarks were recorded. Open the request history for context.', 180) }}</blockquote>
                    @else
                        <p>{{ $stage['next_step'] }}</p>
                    @endif
                    <a class="personal-button" href="{{ route('requests.show', $requisition) }}#employee-next-step">{{ $requisition->status === 'for_clarification' ? 'Read clarification' : 'View pickup status' }} <span class="visually-hidden">for {{ $requisition->tracking_code }}</span><i data-lucide="arrow-right" aria-hidden="true"></i></a>
                </article>
            @empty
                <div class="personal-empty personal-all-clear"><span class="personal-empty-icon"><i data-lucide="circle-check" aria-hidden="true"></i></span><div><strong>No action required from you right now.</strong><p>Updates to your active requests appear below.</p></div></div>
            @endforelse
        </div>
        @if($actionCount > $actionRequests->count())<footer><a href="{{ route('requests.index', ['status' => 'action_required']) }}">Showing {{ $actionRequests->count() }} of {{ $actionCount }} action requests. View all.</a></footer>@endif
    </section>

        <section class="personal-panel" aria-labelledby="personalRequestsHeading">
            <header><div><span class="personal-eyebrow">Request tracking</span><h2 id="personalRequestsHeading">My procurement activity</h2><p>Showing {{ $recentRequests->count() }} of {{ $activeCount }} active requests. Action requests first, then deadline warnings, then recent submissions.</p></div><a href="{{ route('requests.index') }}">View all requests <i data-lucide="arrow-up-right" aria-hidden="true"></i></a></header>
            <ol class="personal-request-list">
                @forelse($recentRequests as $requisition)
                    @php($stage = $requisition->requestorStatus())
                    <li class="personal-request-row is-{{ $stage['tone'] }}">
                        <div class="personal-request-top"><div class="personal-request-identity"><span class="personal-request-icon"><i data-lucide="package" aria-hidden="true"></i></span><div><a href="{{ route('requests.show', $requisition) }}">{{ $requisition->items->first()->item_name ?? 'Supply requisition' }}</a><small class="personal-request-reference">{{ $requisition->tracking_code }} · {{ ucfirst($requisition->request_type) }}</small></div></div><span class="personal-status is-{{ $stage['tone'] }}">{{ $stage['label'] }}</span></div>
                        <small class="personal-submitted">Submitted {{ ($requisition->submitted_at ?? $requisition->created_at)->format('M d, Y') }}@if($requisition->items->count() > 1) · +{{ $requisition->items->count() - 1 }} more item lines @endif</small>
                        @if($requisition->isDeadlineOverdue())<p class="personal-deadline overdue"><i data-lucide="alarm-clock" aria-hidden="true"></i>At or past SLA deadline · {{ $requisition->due_at?->format('M d, Y · g:i A') }}</p>@elseif($requisition->isDeadlineNearing())<p class="personal-deadline near"><i data-lucide="clock-alert" aria-hidden="true"></i>Nearing SLA deadline · {{ $requisition->due_at?->format('M d, Y · g:i A') ?? 'Date unavailable' }}</p>@endif
                        <p class="personal-next-step">{{ $stage['next_step'] }}</p>
                        <div class="personal-row-actions"><a href="{{ route('requests.show', $requisition) }}">{{ $requisition->status === 'for_clarification' ? 'Read clarification' : ($requisition->status === 'ready_for_pickup' ? 'View pickup status' : 'Track request') }}<span class="visually-hidden"> {{ $requisition->tracking_code }}</span><i data-lucide="arrow-right" aria-hidden="true"></i></a>@if($requisition->status === 'pending')<a href="{{ route('requisitions.edit', $requisition) }}">Edit pending request<span class="visually-hidden"> {{ $requisition->tracking_code }}</span></a>@endif</div>
                    </li>
                @empty
                    <li class="personal-empty personal-start-state"><span class="personal-empty-icon"><i data-lucide="package-open" aria-hidden="true"></i></span><h3>No active requisitions</h3><p>Submit a requisition when you need supplies. Closed requests remain in your archive.</p><button type="button" class="personal-button" data-bs-toggle="modal" data-bs-target="#globalRequestModal">Create requisition</button><a href="{{ route('requests.archive') }}">View my archive</a></li>
                @endforelse
            </ol>
        </section>
    </div>

    <aside class="personal-side-stack">
        <section class="personal-panel personal-notifications" aria-labelledby="personalUpdatesHeading">
            <header><div><span class="personal-eyebrow">Stay in the loop</span><h2 id="personalUpdatesHeading">Request updates</h2><p>Your latest request-related notifications.</p></div><span class="personal-section-icon"><i data-lucide="bell" aria-hidden="true"></i></span></header>
            <ul class="personal-updates">
                @forelse($personalNotifications as $notification)
                    <li class="{{ $notification->is_read ? '' : 'is-unread' }}">
                        <div class="personal-update-heading"><i data-lucide="{{ $notification->safeIcon() }}" aria-hidden="true"></i><h3>{{ $notification->title }}</h3>@unless($notification->is_read)<span class="personal-unread">Unread</span>@endunless</div>
                        <p>{{ Str::limit($notification->message, 180) }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                        @if($personalNotificationActions[$notification->id] ?? null)
                            <form method="POST" action="{{ route('notifications.open', $notification) }}">@csrf<button type="submit" class="personal-link-button">Open related request<span class="visually-hidden">: {{ $notification->title }}</span><i data-lucide="arrow-up-right" aria-hidden="true"></i></button></form>
                        @else
                            <a href="{{ route('notifications') }}">View in notification center</a>
                        @endif
                    </li>
                @empty
                    <li class="personal-empty"><p>No request updates yet. Approval, clarification, and pickup notifications will appear here.</p></li>
                @endforelse
            </ul>
            <footer><a href="{{ route('notifications') }}">View all notifications <i data-lucide="arrow-right" aria-hidden="true"></i></a></footer>
        </section>
        <section class="personal-sla" aria-label="Request processing target">
            <div class="personal-sla-heading"><span class="personal-section-icon"><i data-lucide="clock-3" aria-hidden="true"></i></span><h3>Processing target</h3></div><strong>Three working days</strong><p>Measured from submission, excluding weekends and institutional holidays. This is a target, not a guaranteed pickup date.</p><a href="{{ route('requests.index', ['status' => 'deadline']) }}"><span>{{ $personalDeadlineCount }}</span> of your active requests near or past the target — review deadline warnings <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
        </section>
        <section class="personal-archive-card"><span class="personal-section-icon"><i data-lucide="archive" aria-hidden="true"></i></span><h3>Looking for a past request?</h3><p>Your completed, released, and disapproved requisitions stay in your archive.</p><a href="{{ route('requests.archive') }}">View my archive <i data-lucide="arrow-right" aria-hidden="true"></i></a></section>
    </aside>
    </div>
</div>
@include('requests.partials.personal-styles')
