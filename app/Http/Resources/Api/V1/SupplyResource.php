<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'brand' => $this->brand,
            'category' => $this->category,
            'requisition_tier' => $this->requisition_tier,
            'specifications' => $this->specifications,
            'model_number' => $this->model_number,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'quantity' => (int) $this->quantity,
            'min_stock_level' => (int) $this->min_stock_level,
            'options' => (object) $this->requestOptions(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
