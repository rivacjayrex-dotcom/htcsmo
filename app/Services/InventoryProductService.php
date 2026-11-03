<?php

namespace App\Services;

use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryProductService
{
    public function query(Supply $anchor): Builder
    {
        return Supply::query()->where('supply_category_id', $anchor->supply_category_id)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [mb_strtolower(trim($anchor->item_name))])
            ->with(['supplyCategory', 'supplyUnit', 'options.values'])->orderBy('id');
    }

    public function editRows(Collection $variants): array
    {
        $linked = RequisitionItem::whereIn('supply_id', $variants->pluck('id'))->pluck('supply_id')->flip();

        return $variants->map(fn (Supply $supply) => [
            'id' => $supply->id,
            'version' => $this->version($supply),
            'locked' => $linked->has($supply->id),
            'size' => $supply->size,
            'supplier' => $supply->supplier,
            'unit' => $supply->unit,
            'unit_price' => $supply->unit_price,
            'quantity' => $supply->quantity,
            'min_stock_level' => $supply->min_stock_level,
            'specifications' => $supply->specifications,
            'model_number' => $supply->model_number,
            'options' => collect($supply->requestOptions())->map(fn ($values, $name) => ['name' => $name, 'values' => implode(', ', $values)])->values()->all(),
        ])->all();
    }

    private function version(Supply $supply): string
    {
        return hash('sha256', json_encode([
            $supply->only(['id', 'item_name', 'supply_category_id', 'size', 'supplier', 'supply_unit_id', 'unit_price', 'quantity', 'min_stock_level', 'specifications', 'model_number', 'variant_key']),
            $supply->requestOptions(),
        ], JSON_THROW_ON_ERROR));
    }

    /** @return array{variants: Collection, before: array, removed: int} */
    public function update(User $user, Supply $anchor, array $input): array
    {
        return DB::transaction(function () use ($user, $anchor, $input) {
            $existing = $this->query($anchor)->lockForUpdate()->get()->keyBy('id');
            $submittedIds = collect($input['variants'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($submittedIds !== $existing->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages(['variants' => 'The product variants have changed. Reload this page before saving the whole product.']);
            }
            $categoryId = SupplyCategory::where('category_name', $input['category'])->value('id');
            if (Supply::whereNotIn('id', $existing->keys())->where('supply_category_id', $categoryId)
                ->whereRaw('LOWER(TRIM(item_name)) = ?', [mb_strtolower(trim($input['item_name']))])->exists()) {
                throw ValidationException::withMessages(['item_name' => 'Another product already uses this name and category. Choose a different name.']);
            }
            $linked = RequisitionItem::whereIn('supply_id', $existing->keys())->pluck('supply_id')->flip();
            $prepared = $seen = $before = $removed = [];
            foreach ($input['variants'] as $index => $row) {
                $supply = filled($row['id'] ?? null) ? $existing->get((int) $row['id']) : null;
                if ($supply && ! hash_equals($this->version($supply), $row['version'] ?? '')) {
                    throw ValidationException::withMessages(["variants.{$index}.quantity" => 'Variant '.($index + 1).' changed while this form was open. Reload to use the latest stock and prices.']);
                }
                if ($row['remove'] ?? false) {
                    if (! $supply) {
                        throw ValidationException::withMessages(["variants.{$index}.id" => 'Only saved variants can be marked for removal.']);
                    }
                    $removed[] = $supply;

                    continue;
                }
                try {
                    $data = $this->prepare($row, $supply, $input, $categoryId, $supply && $linked->has($supply->id));
                } catch (ValidationException $exception) {
                    $errors = [];
                    foreach ($exception->errors() as $field => $messages) {
                        $errors["variants.{$index}.{$field}"] = array_map(fn ($message) => 'Variant '.($index + 1).': '.$message, $messages);
                    }
                    throw ValidationException::withMessages($errors);
                }
                $key = mb_strtolower(json_encode([$data['size'], $data['supplier'], $data['variant_key']], JSON_THROW_ON_ERROR));
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(["variants.{$index}.options" => 'Variant '.($index + 1).' duplicates another combination. Keep each combination in one row.']);
                }
                $seen[$key] = true;
                $prepared[] = compact('supply', 'data');
            }
            foreach ($removed as $supply) {
                $supply->delete();
            }
            $updated = collect();
            foreach ($prepared as ['supply' => $supply, 'data' => $data]) {
                $previousQuantity = $supply ? (int) $supply->quantity : 0;
                $wasOut = $supply ? $previousQuantity <= 0 : false;
                $wasLow = $supply ? $previousQuantity <= $supply->min_stock_level : false;
                if ($supply) {
                    $supply->update($data);
                } else {
                    $supply = Supply::create($data);
                }
                $change = (int) $supply->quantity - $previousQuantity;
                if ($change !== 0) {
                    SupplyInventoryLog::create([
                        'supply_id' => $supply->id, 'user_id' => $user->id,
                        'transaction_type' => 'manual_adjustment', 'quantity_change' => $change,
                        'balance_after' => $supply->quantity, 'remarks' => 'Stock updated while editing the whole product.',
                    ]);
                }
                $before[$supply->id] = compact('wasLow', 'wasOut');
                $updated->push($supply->fresh(['supplyUnit', 'options.values']));
            }

            return ['variants' => $updated, 'before' => $before, 'removed' => count($removed)];
        });
    }

    private function prepare(array $row, ?Supply $supply, array $shared, int $categoryId, bool $linked): array
    {
        $options = [];
        $names = [];
        foreach ($row['options'] ?? [] as $option) {
            $name = trim($option['name']);
            if (isset($names[mb_strtolower($name)])) {
                throw ValidationException::withMessages(['options' => 'Use each attribute name only once.']);
            }
            $names[mb_strtolower($name)] = true;
            $name = strcasecmp($name, 'Brand') === 0 ? 'Brand' : $name;
            $choices = collect(explode(',', $option['values']))->map(fn ($value) => trim($value))->filter(fn ($value) => $value !== '')->unique()->values()->all();
            if ($choices === []) {
                throw ValidationException::withMessages(['options' => 'Enter at least one value for each attribute.']);
            }
            $options[$name] = $choices;
        }
        $originalOptions = $supply?->requestOptions() ?? [];
        $size = trim((string) ($row['size'] ?? ''));
        $supplier = trim((string) ($row['supplier'] ?? ''));
        $unchanged = $supply && $options == $originalOptions && $size === $supply->size && $supplier === $supply->supplier;
        if ($linked && ! $unchanged) {
            throw ValidationException::withMessages(['options' => 'This variant is linked to requisitions. Keep its selections and supplier, or add a new variant.']);
        }
        if ($linked && $row['unit'] !== $supply->unit) {
            throw ValidationException::withMessages(['unit' => 'This variant is linked to requisitions. Keep its stock unit, or add a new variant with the different unit.']);
        }
        if (! $unchanged) {
            if (empty($options['Brand'])) {
                throw ValidationException::withMessages(['options' => 'Add a Brand attribute for this variant.']);
            }
            foreach (array_keys($options) as $name) {
                if (in_array(mb_strtolower($name), ['size', 'supplier'], true)) {
                    throw ValidationException::withMessages(['options' => 'Enter Size and Supplier in their own columns. Preserve unchanged shared choices or register new separate variants.']);
                }
            }
        }
        $priced = ! $supply || filled($supply->variant_key);
        if ($priced && collect($options)->contains(fn ($values) => count($values) !== 1 || mb_strlen($values[0]) > 100)) {
            throw ValidationException::withMessages(['options' => 'Each priced variant needs one value per attribute, up to 100 characters.']);
        }
        $variantKey = $priced ? Supply::combinationKey($options) : '';
        $data = [
            'item_name' => trim($shared['item_name']), 'supply_category_id' => $categoryId,
            'size' => $size, 'supplier' => $supplier, 'variant_key' => $variantKey,
            'unit' => $row['unit'], 'unit_price' => $row['unit_price'], 'quantity' => $row['quantity'],
            'min_stock_level' => $row['min_stock_level'], 'model_number' => $row['model_number'] ?? null,
            'specifications' => ($shared['apply_description'] ?? false) ? $shared['shared_specifications'] : ($row['specifications'] ?? null),
        ];
        if (! $unchanged) {
            $data['configurable_options'] = $options;
        }
        Validator::make($data, ['item_name' => [Rule::unique('supplies', 'item_name')
            ->where('size', $size)->where('supplier', $supplier)->where('variant_key', $variantKey)->ignore($supply?->id)]],
            ['item_name.unique' => 'This combination already exists in inventory.'])->validate();

        return $data;
    }
}
