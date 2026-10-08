@php
    $isSmoDeadlineView = Auth::user()->role === 'smo';
    $reviewerLabels = [
        'dept_head' => 'Department Head / Dean',
        'vp_finance' => 'VP for Finance',
        'vp_admin' => 'VP for Administration',
        'provost' => 'Provost',
        'president' => 'President',
    ];
@endphp

<section class="approval-deadline-watch" aria-labelledby="approvalDeadlineHeading">
    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-3" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-3" role="alert">{{ session('error') }}</div>
    @endif

    <div class="approval-deadline-heading">
        <div>
            <span class="approval-deadline-eyebrow">Approval deadline watch</span>
            <h3 id="approvalDeadlineHeading">
                {{ $isSmoDeadlineView ? 'Approvals needing follow-up' : 'Requests requiring your attention' }}
                <em>{{ $approvalDeadlineRequests->count() }}</em>
            </h3>
            <p>
                {{ $isSmoDeadlineView
                    ? 'Notify only the administrator currently responsible for a request that has reached Day 2 or later.'
                    : 'These requisitions are nearing or have passed the three-day approval-stage target.' }}
            </p>
        </div>
        <span class="approval-deadline-heading__icon"><i data-lucide="alarm-clock"></i></span>
    </div>

    <div class="approval-deadline-list">
        @forelse($approvalDeadlineRequests as $deadlineRequest)
            @php
                $daysElapsed = $deadlineRequest->approvalDeadlineDaysElapsed();
                $isOverdue = $deadlineRequest->isApprovalDeadlineOverdue();
                $reviewerRole = $deadlineRequest->currentReviewerRole();
            @endphp
            <article class="approval-deadline-item {{ $isOverdue ? 'overdue' : 'near' }}">
                <span class="approval-deadline-state">
                    <i data-lucide="{{ $isOverdue ? 'siren' : 'clock-alert' }}"></i>
                    {{ $isOverdue ? 'At deadline' : 'Nearing deadline' }}
                </span>
                <div class="approval-deadline-request">
                    <strong>{{ $deadlineRequest->tracking_code }}</strong>
                    <span>{{ $deadlineRequest->user?->name ?? 'Unavailable requestor' }} · {{ $deadlineRequest->user?->department ?: 'General' }}</span>
                </div>
                <div class="approval-deadline-owner">
                    <small>Current approver</small>
                    <strong>{{ $reviewerLabels[$reviewerRole] ?? 'Assigned administrator' }}</strong>
                </div>
                <div class="approval-deadline-age">
                    <strong>Day {{ $daysElapsed }}</strong>
                    <span>in this stage</span>
                </div>
                <div class="approval-deadline-action">
                    @if($isSmoDeadlineView)
                        <form method="POST" action="{{ route('admin.requests.deadline-reminder', $deadlineRequest) }}">
                            @csrf
                            <button type="submit" data-loading-text="Notifying…">
                                <i data-lucide="send"></i> Notify approver
                            </button>
                        </form>
                    @else
                        <a href="{{ route('admin.approvals', ['request' => $deadlineRequest->id]) }}">
                            Review now <i data-lucide="arrow-right"></i>
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="approval-deadline-empty">
                <span><i data-lucide="circle-check-big"></i></span>
                <div>
                    <strong>No approval deadlines need attention</strong>
                    <p>{{ $isSmoDeadlineView ? 'No administrator currently needs an SMO reminder.' : 'Your assigned approval queue is within its target.' }}</p>
                </div>
            </div>
        @endforelse
    </div>
</section>

<style>
    .approval-deadline-watch { margin-bottom: 1rem; overflow: hidden; border: 1px solid #e7d8b4; border-radius: 18px; background: #fff; box-shadow: 0 8px 26px rgba(70,52,20,.06); }
    .approval-deadline-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid #eee4cc; background: linear-gradient(135deg,#fffdf7,#fff8e8); }
    .approval-deadline-eyebrow { display: block; margin-bottom: .24rem; color: #9a6811; font-size: .54rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
    .approval-deadline-heading h3 { margin: 0; color: #493b24; font-size: .92rem; font-weight: 850; }
    .approval-deadline-heading h3 em { display: inline-grid; min-width: 24px; height: 24px; margin-left: .3rem; place-items: center; border-radius: 999px; color: #8a5c0b; background: #f7e4b8; font-size: .6rem; font-style: normal; }
    .approval-deadline-heading p { margin: .25rem 0 0; color: #87775d; font-size: .62rem; }
    .approval-deadline-heading__icon { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 12px; color: #9a6811; background: #f8e7bd; }
    .approval-deadline-heading__icon svg { width: 19px; }
    .approval-deadline-list { padding: .35rem 1rem; }
    .approval-deadline-item { display: grid; grid-template-columns: 130px minmax(190px,1.3fr) minmax(145px,.8fr) 80px auto; align-items: center; gap: .8rem; padding: .8rem .25rem; border-bottom: 1px solid #edf0ed; }
    .approval-deadline-item:last-child { border-bottom: 0; }
    .approval-deadline-state { display: inline-flex; width: fit-content; align-items: center; gap: .35rem; padding: .35rem .5rem; border-radius: 999px; font-size: .52rem; font-weight: 900; text-transform: uppercase; }
    .approval-deadline-state svg { width: 12px; height: 12px; }
    .approval-deadline-item.near .approval-deadline-state { color: #8b5a08; background: #fff0c9; }
    .approval-deadline-item.overdue .approval-deadline-state { color: #b32c36; background: #fde3e5; }
    .approval-deadline-request strong, .approval-deadline-request span, .approval-deadline-owner small, .approval-deadline-owner strong, .approval-deadline-age strong, .approval-deadline-age span { display: block; }
    .approval-deadline-request strong { color: #2f4938; font-family: ui-monospace,SFMono-Regular,Menlo,monospace; font-size: .68rem; }
    .approval-deadline-request span, .approval-deadline-owner small, .approval-deadline-age span { margin-top: .12rem; color: #8a958e; font-size: .54rem; }
    .approval-deadline-owner strong, .approval-deadline-age strong { color: #526359; font-size: .62rem; }
    .approval-deadline-action { text-align: right; }
    .approval-deadline-action form { margin: 0; }
    .approval-deadline-action :is(button,a) { min-height: 34px; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .45rem .65rem; border: 1px solid #d6a84e; border-radius: 9px; color: #805207; background: #fff9ea; font-size: .56rem; font-weight: 850; text-decoration: none; white-space: nowrap; }
    .approval-deadline-action :is(button,a):hover { color: #fff; background: #9a6811; }
    .approval-deadline-action svg { width: 13px; height: 13px; }
    .approval-deadline-empty { min-height: 92px; display: flex; align-items: center; justify-content: center; gap: .75rem; padding: 1rem; text-align: left; }
    .approval-deadline-empty > span { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: #277048; background: #e6f3ea; }
    .approval-deadline-empty svg { width: 18px; }
    .approval-deadline-empty strong { color: #46594d; font-size: .66rem; }
    .approval-deadline-empty p { margin: .15rem 0 0; color: #8b978f; font-size: .56rem; }
    @media(max-width: 991.98px) { .approval-deadline-item { grid-template-columns: 120px minmax(180px,1fr) minmax(140px,.8fr) auto; }.approval-deadline-age { display: none; } }
    @media(max-width: 767.98px) { .approval-deadline-list { display: grid; gap: .65rem; padding: .75rem; }.approval-deadline-item { display: grid; grid-template-columns: 1fr auto; gap: .65rem; padding: .85rem; border: 1px solid #e8e2d3!important; border-radius: 13px; }.approval-deadline-state { grid-column: 1/-1; }.approval-deadline-owner { grid-column: 1/2; }.approval-deadline-action { grid-column: 2/3; grid-row: 2/4; align-self: end; }.approval-deadline-action :is(button,a) { white-space: normal; }.approval-deadline-heading { padding: 1rem; } }
    @media(max-width: 479.98px) { .approval-deadline-item { grid-template-columns: 1fr; }.approval-deadline-action { grid-column: auto; grid-row: auto; text-align: left; }.approval-deadline-action :is(button,a) { width: 100%; }.approval-deadline-heading__icon { display: none; } }
</style>
