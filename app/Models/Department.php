<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'dept_code',
        'dept_name',
        'description',
    ];

    /**
     * Users belonging to this academic department/college.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managementVersion(): string
    {
        return hash('sha256', implode("\0", [$this->dept_code, $this->dept_name, $this->updated_at?->format('Y-m-d H:i:s.u')]));
    }
}
