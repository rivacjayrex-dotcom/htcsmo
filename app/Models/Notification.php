<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const TYPES = ['info', 'success', 'warning', 'danger'];

    public const ICONS = [
        'bell', 'send', 'check-circle', 'x-circle', 'help-circle', 'users',
        'user-plus', 'user-check', 'user-cog', 'shield-check', 'triangle-alert', 'alert-triangle', 'package-x',
        'package-check', 'package-open', 'circle-check', 'zap', 'clock', 'file-text',
    ];

    public const ACTION_TYPES = ['requisition', 'approval_queue', 'inventory', 'pending_users', 'profile'];

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'icon',
        'type',
        'event_type',
        'action_type',
        'action_id',
        'dedupe_key',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeWithUnreadSummary(Builder $query): Builder
    {
        // Mutually exclusive groups keep the popup breakdown equal to its total.
        return $query->selectRaw('COALESCE(MAX(id), 0) as latest_id')
            ->selectRaw('COUNT(CASE WHEN is_read = ? THEN 1 END) as unread_count', [false])
            ->selectRaw("COUNT(CASE WHEN is_read = ? AND (type IN ('warning', 'danger') OR event_type LIKE 'deadline.%') THEN 1 END) as needs_attention", [false])
            ->selectRaw("COUNT(CASE WHEN is_read = ? AND COALESCE(type, '') NOT IN ('warning', 'danger') AND event_type IN ('requisition.submitted', 'requisition.awaiting_approval') THEN 1 END) as new_requisitions", [false]);
    }

    public function unreadSummary(): array
    {
        return [
            'new_requisitions' => (int) $this->new_requisitions,
            'needs_attention' => (int) $this->needs_attention,
            'updates' => max(0, (int) $this->unread_count - (int) $this->new_requisitions - (int) $this->needs_attention),
        ];
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->forceFill(['is_read' => true, 'read_at' => now()])->save();
        }
    }

    public function safeType(): string
    {
        return in_array($this->type, self::TYPES, true) ? $this->type : 'info';
    }

    public function safeIcon(): string
    {
        return in_array($this->icon, self::ICONS, true) ? $this->icon : 'bell';
    }

    /**
     * Resolve the action on the server so a forged URL can never be stored or
     * exposed. The legacy checks keep old rows useful without driving new rows.
     */
    public function actionFor(User $user): ?array
    {
        $target = match ($this->action_type) {
            'requisition', 'approval_queue' => $this->action_id ? Requisition::with(['user', 'logs'])->find($this->action_id) : null,
            'inventory' => $this->action_id ? Supply::find($this->action_id) : null,
            default => null,
        };

        return $this->actionForResolvedTarget($user, $target);
    }

    public function actionForResolvedTarget(User $user, ?Model $target): ?array
    {
        if ($this->action_type === 'requisition' && $this->action_id) {
            $requisition = $target instanceof Requisition ? $target : null;

            return $requisition?->canBeViewedBy($user)
                ? ['url' => route('requests.show', $requisition), 'label' => 'View requisition']
                : null;
        }

        if ($this->action_type === 'approval_queue' && $this->action_id) {
            $requisition = $target instanceof Requisition ? $target : null;

            return $requisition?->canBeReviewedBy($user)
                ? ['url' => route('admin.approvals', ['request' => $requisition->id]), 'label' => 'Review requisition']
                : null;
        }

        if ($this->action_type === 'inventory' && $user->role === 'smo') {
            $supply = $target instanceof Supply ? $target : null;

            return ['url' => $supply ? route('inventory.edit', $supply) : route('inventory.index'), 'label' => 'View inventory'];
        }

        if ($this->action_type === 'pending_users' && $user->role === 'smo') {
            return ['url' => route('admin.users.pending'), 'label' => 'Review access requests'];
        }

        if ($this->action_type === 'profile' && (int) $this->action_id === (int) $user->id) {
            return ['url' => route('profile.edit'), 'label' => 'View profile'];
        }

        if ($this->action_type !== null) {
            return null;
        }

        // Compatibility for records created before explicit action metadata.
        if ($user->role === 'smo' && in_array($this->icon, ['triangle-alert', 'package-x'], true)) {
            return ['url' => route('inventory.index'), 'label' => 'View inventory'];
        }
        if ($user->role === 'smo' && $this->icon === 'user-plus') {
            return ['url' => route('admin.users.pending'), 'label' => 'Review access requests'];
        }

        return null;
    }

    public function pollPayload(User $user, ?array $resolvedAction = null, bool $wasResolved = false): array
    {
        $action = $wasResolved ? $resolvedAction : $this->actionFor($user);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->safeType(),
            'icon' => $this->safeIcon(),
            'event_type' => $this->event_type,
            'action_url' => $action ? route('notifications.open', $this) : null,
            'action_label' => $action['label'] ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
