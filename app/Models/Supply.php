<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supply extends Model
{
    protected $fillable = [
        'supply_category_id', // <--- ADD THIS
        'item_name',
        'brand',
        'model_number',
        'category',           // Keep for backwards-compatibility with views
        'requisition_tier',
        'specifications',
        'configurable_options',
        'physical_description',
        'quantity',
        'unit',
        'unit_price',
        'min_stock_level',
    ];

    protected $casts = [
        'configurable_options' => 'array',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Category that classifies this supply item.
     */
    public function supplyCategory(): BelongsTo
    {
        return $this->belongsTo(SupplyCategory::class, 'supply_category_id');
    }

    public function inventoryLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplyInventoryLog::class);
    }
}
