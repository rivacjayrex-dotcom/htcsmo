<?php

namespace App\Models;

use App\Services\BusinessDaySlaService;
use Illuminate\Database\Eloquent\Model;

class InstitutionalHoliday extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => app(BusinessDaySlaService::class)->clearHolidayCache());
        static::deleted(fn () => app(BusinessDaySlaService::class)->clearHolidayCache());
    }

    protected $fillable = [
        'holiday_date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
        ];
    }
}
