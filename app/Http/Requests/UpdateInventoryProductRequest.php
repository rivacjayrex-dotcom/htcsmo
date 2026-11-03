<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'smo';
    }

    public function rules(): array
    {
        $rules = [
            'item_name' => 'required|string|max:255',
            'category' => 'required|exists:supply_categories,category_name',
            'apply_description' => 'sometimes|boolean',
            'shared_specifications' => 'exclude_unless:apply_description,1|required|string|min:3|max:10000',
            'variants' => 'required|array|min:1|max:100',
            'variants.*.id' => 'required_if:variants.*.remove,1|nullable|integer|min:1|distinct',
            'variants.*.version' => 'nullable|string|size:64',
            'variants.*.remove' => 'sometimes|boolean',
            'variants.*.size' => 'nullable|string|max:100',
            'variants.*.supplier' => 'nullable|string|max:255',
            'variants.*.unit' => 'required|string|max:50',
            'variants.*.unit_price' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'variants.*.quantity' => 'required|integer|min:0|max:2147483647',
            'variants.*.min_stock_level' => 'required|integer|min:0|max:2147483647',
            'variants.*.specifications' => 'nullable|string|max:10000',
            'variants.*.model_number' => 'nullable|string|max:100',
            'variants.*.options' => 'nullable|array|max:5',
            'variants.*.options.*.name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'variants.*.options.*.values' => 'required|string|max:500',
        ];

        // Removed rows retain their identity and version, but need no editable fields.
        foreach ($rules as $field => $rule) {
            if (str_starts_with($field, 'variants.*.') && ! in_array($field, ['variants.*.id', 'variants.*.version', 'variants.*.remove'], true)) {
                $rules[$field] = ['exclude_if:variants.*.remove,1', ...(is_array($rule) ? $rule : explode('|', $rule))];
            }
        }

        return $rules;
    }
}
