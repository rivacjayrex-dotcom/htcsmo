<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ACCOUNT_PENDING = 'pending';

    public const ACCOUNT_ACTIVE = 'active';

    public const ACCOUNT_SUSPENDED = 'suspended';

    public const ACCOUNT_STATUSES = [self::ACCOUNT_PENDING, self::ACCOUNT_ACTIVE, self::ACCOUNT_SUSPENDED];

    public const ROLES = ['employee', 'dept_head', 'vp_finance', 'vp_admin', 'provost', 'president', 'smo'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'school_id',
        'department',    // Keep temporarily so old views don't crash
        'department_id', // <--- ADD THIS
        'email',
        'password',
        'role',
        'is_approved',
        'account_status',
        'account_version',
    ];

    /**
     * The department/college the user belongs to.
     */
    public function academicDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'account_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            $user->account_version ??= 0;

            if ($user->isDirty('account_status')) {
                $user->is_approved = $user->account_status === self::ACCOUNT_ACTIVE;
            } elseif ($user->isDirty('is_approved')) {
                $user->account_status = $user->is_approved
                    ? self::ACCOUNT_ACTIVE
                    : ($user->getOriginal('account_status') === self::ACCOUNT_ACTIVE ? self::ACCOUNT_SUSPENDED : self::ACCOUNT_PENDING);
            }
        });
    }

    public function scopeWithAccountStatus(Builder $query, string $status): Builder
    {
        return $query->where('account_status', $status);
    }

    public function hasActiveAccess(): bool
    {
        return $this->account_status === self::ACCOUNT_ACTIVE;
    }

    // Check if user is an Admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Check if user is SMO In-charge
    public function isSMO()
    {
        return $this->role === 'smo';
    }

    // Check if user is an Employee
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function isDeptHead()
    {
        return $this->role === 'dept_head';
    }

    public function isVP()
    {
        return $this->role === 'vp';
    }

    public function isProvost()
    {
        return $this->role === 'provost';
    }

    public function isPresident()
    {
        return $this->role === 'president';
    }
}
