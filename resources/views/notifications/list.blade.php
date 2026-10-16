<div class="notification-feed-list">
    @forelse($notes as $note)
        @php
            $action = isset($resolvedActions) && array_key_exists($note->id, $resolvedActions)
                ? $resolvedActions[$note->id]
                : $note->actionFor(Auth::user());
            $eventGroup = match (true) {
                str_starts_with((string) $note->event_type, 'requisition.') => 'Requisition',
                str_starts_with((string) $note->event_type, 'deadline.') => 'Deadline',
                str_starts_with((string) $note->event_type, 'inventory.') => 'Inventory',
                str_starts_with((string) $note->event_type, 'account.') => 'Account',
                default => 'System',
            };
        @endphp
        <article class="notification-feed-item type-{{ $note->safeType() }} {{ $note->is_read ? 'is-read' : 'is-unread' }}" data-notification-id="{{ $note->id }}">
            <div class="notification-feed-icon" aria-hidden="true"><i data-lucide="{{ $note->safeIcon() }}"></i></div>
            <div class="notification-feed-content">
                <div class="notification-feed-meta">
                    <span>{{ $eventGroup }}</span>
                    <time datetime="{{ $note->created_at?->toIso8601String() }}" title="{{ $note->created_at?->format('F d, Y · g:i A') }}">{{ $note->created_at?->diffForHumans() }}</time>
                </div>
                <div class="notification-feed-title-row">
                    <h3>{{ $note->title }}</h3>
                    @unless($note->is_read)<span class="notification-unread-indicator" aria-label="Unread notification"></span>@endunless
                </div>
                <p>{{ $note->message }}</p>
                <div class="notification-feed-actions">
                    @if($action)
                        <form method="POST" action="{{ route('notifications.open', $note) }}">
                            @csrf
                            <button type="submit" class="notification-primary-action">{{ $action['label'] }} <i data-lucide="arrow-up-right"></i></button>
                        </form>
                    @elseif($note->action_type)
                        <span class="notification-action-unavailable"><i data-lucide="link-2-off"></i> Related record unavailable</span>
                    @endif
                    @unless($note->is_read)
                        <form method="POST" action="{{ route('notifications.read', $note) }}" data-notification-read-form>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="notification-read-action"><i data-lucide="check"></i> Mark as read</button>
                        </form>
                    @endunless
                </div>
            </div>
        </article>
    @empty
        <div class="notification-empty-state">
            <span><i data-lucide="inbox"></i></span>
            <h3>No notifications here</h3>
            <p>This category is clear. New activity will appear here when it happens.</p>
        </div>
    @endforelse
</div>
