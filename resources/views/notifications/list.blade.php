<div class="list-group list-group-flush">
    @forelse($notes as $note)
        <div class="notification-feed-item type-{{ $note->type }} {{ $note->is_read ? '' : 'unread' }}">
            <div class="d-flex align-items-start">
                <!-- Icon -->
                <div class="notification-feed-icon">
                    <i data-lucide="{{ $note->icon ?? 'bell' }}"></i>
                </div>

                <!-- Text Content -->
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold mb-0 text-dark">@if(!$note->is_read)<span class="unread-dot"></span>@endif{{ $note->title }}</h6>
                        <small class="text-muted" style="font-size: 11px;">{{ $note->created_at->format('M d, g:i A') }}</small>
                    </div>
                    <p class="text-muted small mb-2">{{ $note->message }}</p>

                    <!-- REFINED ACTION LOGIC -->
                    <div class="mt-2">
                        @if($note->icon == 'user-plus' || $note->title == 'New User Access Request')
                            <!-- 1. Priority: User Access (Only for SMO) -->
                            <a href="{{ route('admin.users.pending') }}" class="btn btn-sm btn-htc px-3 rounded-3 shadow-sm">
                                <i data-lucide="shield-check" class="me-1" style="width: 14px; vertical-align: middle;"></i>
                                Review Access Request
                            </a>

                        @elseif(str_contains($note->title, 'Stock'))
                            <a href="{{ route('inventory.index') }}" class="notification-action">Review stock & forecast →</a>
                        @elseif(str_contains($note->title, 'Requisition') || str_contains($note->title, 'Supplies') || $note->title == 'Request Initiated' || $note->title == 'Request Approved')
                            <!-- 2. Requisition Actions (Separated by Role) -->
                            @if(Auth::user()->role == 'employee')
                                <a href="{{ route('requests.index') }}" class="text-decoration-none small fw-bold" style="color: var(--htc-green);">
                                    View My History →
                                </a>
                            @else
                                <a href="{{ route('admin.approvals') }}" class="text-decoration-none small fw-bold" style="color: var(--htc-green);">
                                    Open Approval Queue →
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="p-5 text-center text-muted">
            <i data-lucide="inbox" class="mb-3 opacity-25" style="width: 48px; height: 48px;"></i>
            <p class="small italic">No notifications found in this category.</p>
        </div>
    @endforelse
</div>

<style>
    .notification-feed-item { position: relative; padding: 1.25rem 1.5rem; border-bottom: 1px solid #edf1ef; background: #fff; transition: .2s; }
    .notification-feed-item:hover { background: #fafcfb; }.notification-feed-item.unread { background: #f7fbf8; }.notification-feed-item.type-warning { border-left: 3px solid #e4a128; }.notification-feed-item.type-danger { border-left: 3px solid #d4434c; }.notification-feed-item.type-success { border-left: 3px solid #27804f; }
    .notification-feed-icon { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; margin-right: .85rem; border-radius: 12px; color: #397253; background: #e8f4ec; }.notification-feed-icon svg { width: 19px; }.type-warning .notification-feed-icon { color: #9a650c; background: #fff0cf; }.type-danger .notification-feed-icon { color: #b52d36; background: #fde9ea; }.type-success .notification-feed-icon { color: #17643d; background: #e4f4ea; }
    .unread-dot { display: inline-block; width: 7px; height: 7px; margin-right: .45rem; border-radius: 50%; background: #dc3545; vertical-align: 1px; }.notification-action { color: var(--htc-green); font-size: .68rem; font-weight: 800; text-decoration: none; }
</style>
