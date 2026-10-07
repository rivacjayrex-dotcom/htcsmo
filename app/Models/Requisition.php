<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisition extends Model
{
    public const ARCHIVED_STATUSES = ['released', 'rejected'];

    protected $fillable = [
        'tracking_number', // e.g. MIN-CETE-20000001
        'user_id',
        'request_type',
        'status',
        'closed_at',
        'purpose',
        'remarks',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Boot function to automatically generate the institutional tracking code upon creation
     */
    protected static function booted()
    {
        static::saving(function (Requisition $requisition) {
            if (! $requisition->isDirty('status')) {
                return;
            }

            if (in_array($requisition->status, self::ARCHIVED_STATUSES, true)) {
                $requisition->closed_at ??= now();
            } else {
                $requisition->closed_at = null;
            }
        });

        static::created(function ($requisition) {
            if (empty($requisition->tracking_number)) {
                $prefix = strtoupper($requisition->request_type) === 'MAJOR' ? 'MAJ' : 'MIN';
                $tierCode = strtoupper($requisition->request_type) === 'MAJOR' ? '10' : '20';
                $dept = strtoupper($requisition->user->department ?? 'HTC');
                $uniqueNumber = $tierCode.str_pad($requisition->id, 6, '0', STR_PAD_LEFT);

                $requisition->tracking_number = "{$prefix}-{$dept}-{$uniqueNumber}";
                $requisition->saveQuietly();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereIn('status', self::ARCHIVED_STATUSES);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === 'smo' ? $query : $query->where('user_id', $user->id);
    }

    public function scopeWithArchiveFilters(Builder $query, array $filters, bool $allowDepartment = false): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(
                $allowDepartment && ($filters['dept'] ?? null),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $users) => $users->where('department', $filters['dept']))
            )
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('closed_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('closed_at', '<=', $date));
    }

    public function canBeViewedBy(User $user): bool
    {
        return $user->role === 'smo' || $this->user_id === $user->id;
    }

    public function canBeReviewedBy(User $user): bool
    {
        if ($this->status === 'for_clarification') {
            $questioningRole = $this->logs()
                ->where('action', 'like', '%Clarification%')
                ->latest('id')
                ->value('role');

            return $questioningRole !== null && $user->role === $questioningRole;
        }

        return match ($user->role) {
            'dept_head' => $this->status === 'pending'
                && $this->user?->department === $user->department,
            'vp_finance' => $this->status === 'approved_dept' && $this->request_type === 'minor',
            'vp_admin' => $this->status === 'approved_dept' && $this->request_type === 'major',
            'provost' => $this->status === 'approved_vp' && $this->request_type === 'major',
            'president' => $this->status === 'approved_provost' && $this->request_type === 'major',
            'smo' => ($this->request_type === 'minor' && $this->status === 'approved_vp')
                || ($this->request_type === 'major' && $this->status === 'approved_president'),
            default => false,
        };
    }

    /**
     * Accessor for tracking_code compatibility across Blade views
     */
    public function getTrackingCodeAttribute(): string
    {
        return $this->tracking_number ?? $this->getTrackingCodeFallback();
    }

    public function getTrackingCodeFallback(): string
    {
        $prefix = strtoupper($this->request_type) === 'MAJOR' ? 'MAJ' : 'MIN';
        $tierCode = strtoupper($this->request_type) === 'MAJOR' ? '10' : '20';
        $dept = strtoupper($this->user->department ?? 'HTC');
        $uniqueNumber = $tierCode.str_pad($this->id, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$dept}-{$uniqueNumber}";
    }

    /**
     * Check if nearing the 3-day SLA (2 days elapsed since final approval)
     */
    public function isNearingDeadline(): bool
    {
        $isFullyApproved = ($this->status === 'approved_president') ||
                           ($this->request_type === 'minor' && $this->status === 'approved_vp');

        if ($isFullyApproved) {
            return $this->updated_at->diffInDays(Carbon::now()) >= 2;
        }

        return false;
    }

    /**
     * Check if exceeded the 3-day processing deadline
     */
    public function isOverdue(): bool
    {
        $isFullyApproved = ($this->status === 'approved_president') ||
                           ($this->request_type === 'minor' && $this->status === 'approved_vp');

        if ($isFullyApproved) {
            return $this->updated_at->diffInDays(Carbon::now()) >= 3;
        }

        return false;
    }
}
