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
     * Choices presented after a requestor opens this catalog item.
     * Brand is kept first so the experience reads Brand, Size, Color, etc.
     */
    public function requestOptions(): array
    {
        $options = $this->configurable_options ?? [];
        $configuredBrands = collect($options['Brand'] ?? [])
            ->map(fn ($brand) => trim((string) $brand))
            ->filter();
        $brands = $configuredBrands->isNotEmpty()
            ? $configuredBrands
            : collect(explode(',', (string) $this->brand))
                ->map(fn ($brand) => trim($brand))
                ->filter();

        unset($options['Brand']);

        return $brands->isEmpty()
            ? $options
            : ['Brand' => $brands->unique()->values()->all()] + $options;
    }

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
