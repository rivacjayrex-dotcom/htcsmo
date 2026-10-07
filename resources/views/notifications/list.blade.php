<div class="list-group list-group-flush">
    @forelse($notes as $note)
        @php($action = $note->actionFor(Auth::user()))
        <article class="notification-feed-item type-{{ $note->safeType() }} {{ $note->is_read ? '' : 'unread' }}">
            <div class="d-flex align-items-start">
                <div class="notification-feed-icon" aria-hidden="true"><i data-lucide="{{ $note->safeIcon() }}"></i></div>
                <div class="notification-feed-content flex-grow-1">
                    <div class="notification-feed-heading d-flex justify-content-between align-items-center mb-1 gap-2">
                        <h6 class="fw-bold mb-0 text-dark">@unless($note->is_read)<span class="unread-dot" aria-label="Unread"></span>@endunless{{ $note->title }}</h6>
                        <time class="text-muted" style="font-size:11px" datetime="{{ $note->created_at?->toIso8601String() }}">{{ $note->created_at?->format('M d, g:i A') }}</time>
                    </div>
                    <p class="text-muted small mb-2">{{ $note->message }}</p>
                    <div class="mt-2 d-flex flex-wrap align-items-center gap-3">
                        @if($action)
                            <a href="{{ route('notifications.open', $note) }}" class="notification-action">{{ $action['label'] }} →</a>
                        @elseif($note->action_type)
                            <span class="text-muted small">Related record unavailable or access changed.</span>
                        @endif
                        @unless($note->is_read)
                            <form method="POST" action="{{ route('notifications.read', $note) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-link btn-sm p-0 text-secondary text-decoration-none">Mark as read</button>
                            </form>
                        @endunless
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="p-5 text-center text-muted">
            <i data-lucide="inbox" class="mb-3 opacity-25" style="width:48px;height:48px"></i>
            <p class="small mb-0">No notifications found in this category.</p>
        </div>
    @endforelse
</div>

<style>
    .notification-feed-item{position:relative;padding:1.25rem 1.5rem;border-bottom:1px solid #edf1ef;background:#fff;transition:.2s}
    .notification-feed-item:hover{background:#fafcfb}.notification-feed-item.unread{background:#f7fbf8}
    .notification-feed-item.type-warning{border-left:3px solid #e4a128}.notification-feed-item.type-danger{border-left:3px solid #d4434c}.notification-feed-item.type-success{border-left:3px solid #27804f}
    .notification-feed-icon{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;margin-right:.85rem;border-radius:12px;color:#397253;background:#e8f4ec}.notification-feed-icon svg{width:19px}
    .type-warning .notification-feed-icon{color:#9a650c;background:#fff0cf}.type-danger .notification-feed-icon{color:#b52d36;background:#fde9ea}.type-success .notification-feed-icon{color:#17643d;background:#e4f4ea}
    .unread-dot{display:inline-block;width:7px;height:7px;margin-right:.45rem;border-radius:50%;background:#dc3545;vertical-align:1px}.notification-action{color:var(--htc-green);font-size:.72rem;font-weight:800;text-decoration:none}
    @media(max-width:575.98px){.notification-feed-item{padding:1rem}.notification-feed-icon{width:36px;height:36px;flex-basis:36px;margin-right:.65rem}.notification-feed-content{min-width:0}.notification-feed-heading{align-items:flex-start!important;flex-direction:column}.notification-feed-heading h6{overflow-wrap:anywhere}.notification-action{display:inline-flex;min-height:38px;align-items:center}}
</style>
