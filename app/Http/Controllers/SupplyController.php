<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInventoryProductRequest;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Services\InventoryAlertService;
use App\Services\InventoryForecastService;
use App\Services\InventoryProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplyController extends Controller
{
    public function requisitionCatalog()
    {
        $supplies = Supply::query()
            ->with(['supplyCategory', 'supplyUnit', 'options.values'])
            ->select([
                'id', 'supply_category_id', 'supply_unit_id', 'item_name', 'requisition_tier',
                'model_number', 'specifications', 'unit_price', 'quantity', 'size', 'supplier', 'variant_key',
            ])
            ->where('quantity', '>', 0)
            ->orderBy('requisition_tier')
            ->orderBy('item_name')
            ->get();

        return response()->json($supplies->map(fn (Supply $supply) => [
            'id' => $supply->id,
            'name' => $supply->item_name,
            'size' => $supply->size,
            'supplier' => $supply->supplier,
            'attributes' => (object) $supply->pricedAttributes(),
            'brand' => $supply->brand,
            'category' => $supply->category,
            'model' => $supply->model_number,
            'tier' => $supply->requisition_tier ?? 'minor',
            'specs' => $supply->specifications,
            'unit' => $supply->unit,
            'price' => (float) $supply->unit_price,
            'stock' => $supply->quantity,
            'options' => $supply->requestOptions(),
        ])->values());
    }

    // List all inventory items
    public function index(Request $request, InventoryForecastService $forecastService)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', 'in:all,attention,out,low,healthy'],
            'forecast_period' => ['nullable', 'in:monthly,quarterly'],
        ]);
        $period = in_array($request->query('forecast_period'), ['monthly', 'quarterly'], true)
            ? $request->query('forecast_period')
            : 'monthly';

        // Operational priority: out of stock, low stock, then healthy stock.
        $supplies = $this->orderedSupplies();

        $forecast = $forecastService->build($supplies, period: $period);
        $search = mb_strtolower(trim($filters['search'] ?? ''));
        $stock = $filters['stock'] ?? 'all';
        $catalogSupplies = $supplies->filter(function (Supply $supply) use ($search, $stock) {
            $text = mb_strtolower(implode(' ', [$supply->display_name, $supply->brand, $supply->model_number, $supply->category, $supply->specifications]));
            $state = $supply->quantity <= 0 ? 'out' : ($supply->quantity <= $supply->min_stock_level ? 'low' : 'healthy');

            return ($search === '' || str_contains($text, $search))
                && ($stock === 'all' || $stock === $state || ($stock === 'attention' && in_array($state, ['out', 'low'], true)));
        })->values();

        $catalogGroups = $catalogSupplies
            ->groupBy(fn (Supply $supply) => json_encode([mb_strtolower(trim($supply->item_name)), $supply->supply_category_id]))
            ->map(fn ($group) => [
                'name' => $group->first()->item_name,
                'category' => $group->first()->category,
                'edit_url' => route('inventory.products.edit', $group->first()->id),
                'variants' => $group->map(fn (Supply $supply) => $this->inventoryVariant($supply))->values()->all(),
            ])->values();

        return view('inventory.index', compact('supplies', 'forecast', 'catalogSupplies', 'catalogGroups'));
    }

    private function inventoryVariant(Supply $supply): array
    {
        $choices = $supply->requestOptions();
        $details = collect($supply->pricedAttributes())->map(fn ($value, $label) => "{$label}: {$value}");
        if ($details->isEmpty() && filled($supply->brand)) {
            $details->push('Brand: '.$supply->brand);
        }
        if (filled($supply->size)) {
            $details->push('Size: '.$supply->size);
        }
        if (filled($supply->supplier)) {
            $details->push('Supplier: '.$supply->supplier);
        }

        return [
            'id' => $supply->id,
            'label' => $details->implode(' · ') ?: 'Standard',
            'name' => $supply->display_name,
            'model' => $supply->model_number,
            'specifications' => $supply->specifications,
            'options_summary' => collect($choices)->map(fn ($values, $label) => $label.': '.implode(', ', $values))->implode(' · '),
            'quantity' => (int) $supply->quantity,
            'unit' => $supply->unit,
            'price' => (float) $supply->unit_price,
            'min_stock_level' => (int) $supply->min_stock_level,
            'status' => $supply->quantity <= 0 ? 'out' : ($supply->quantity <= $supply->min_stock_level ? 'low' : 'healthy'),
            'text' => mb_strtolower(implode(' ', [$supply->display_name, $supply->brand, $supply->model_number, $supply->category, $supply->specifications])),
            'edit_url' => route('inventory.edit', $supply->id),
            'delete_url' => route('inventory.destroy', $supply->id),
        ];
    }

    /**
     * Generate the SMO purchasing forecast as a printable PDF report.
     */
    public function forecastReport(Request $request, InventoryForecastService $forecastService)
    {
        $validated = $request->validate([
            'period' => ['required', 'in:monthly,quarterly'],
        ]);
        $forecast = $forecastService->build($this->orderedSupplies(), period: $validated['period']);

        $pdf = Pdf::loadView('inventory.forecast-report', [
            'forecast' => $forecast,
            'generatedBy' => $request->user()->name,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'HTC_Inventory_Forecast_'.ucfirst($validated['period']).'_'.now()->format('Ymd_His').'.pdf'
        );
    }

    // Show the form to add a new item
    public function create()
    {
        return view('inventory.create');
    }

    // Save a new item to the database
    public function store(Request $request, InventoryAlertService $alertService)
    {
        $batch = $request->has('variants');
        $stockRequired = $batch ? 'nullable' : 'required';
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'size' => 'nullable|string|max:100',
            'supplier' => 'nullable|string|max:255',
            'category' => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
            'specifications' => 'required|string|min:3',
            'quantity' => $stockRequired.'|integer|min:0',
            'unit' => 'required|string|max:50',
            'unit_price' => $stockRequired.'|numeric|min:0.01',
            'min_stock_level' => $stockRequired.'|integer|min:0',
            'model_number' => 'nullable|string|max:100',
            'option_names' => 'nullable|array|max:5',
            'option_names.*' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'option_values' => 'nullable|array|max:5',
            'option_values.*' => 'nullable|string|max:500',
            'variant_names' => ($batch ? 'required' : 'prohibited').'|array|min:1|max:5',
            'variant_names.*' => ['required', 'string', 'max:50', 'distinct:ignore_case', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'variants' => 'sometimes|required|array|min:1|max:100',
            'variants.*' => 'required|array:values,size,supplier,quantity,unit_price,min_stock_level',
            'variants.*.values' => 'required|array|min:1|max:5',
            'variants.*.values.*' => ['required', 'string', 'max:100', 'regex:/^[^,]+$/'],
            'variants.*.size' => 'nullable|string|max:100',
            'variants.*.supplier' => 'nullable|string|max:255',
            'variants.*.quantity' => 'required|integer|min:0|max:2147483647',
            'variants.*.unit_price' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'variants.*.min_stock_level' => 'required|integer|min:0|max:2147483647',
        ]);

        if ($batch) {
            return $this->storeCombinations($validated, $alertService);
        }

        $validated['configurable_options'] = $this->normalizeOptions($validated);
        $validated = $this->validateVariant($validated);
        $validated['brand'] = $this->brandSummary($validated['configurable_options']);
        unset($validated['option_names'], $validated['option_values']);

        $validated['supply_category_id'] = SupplyCategory::where('category_name', $validated['category'])->value('id');
        unset($validated['category']);

        $supply = DB::transaction(fn () => Supply::create($validated));
        $alertService->notifyIfThresholdCrossed($supply, false, false);

        return redirect()->route('inventory.index')->with('success', "Item '{$validated['item_name']}' has been registered into inventory.");
    }

    /**
     * NEW: Show the form to edit an existing item
     */
    public function edit(int $id)
    {
        $item = Supply::findOrFail($id);

        return view('inventory.edit', compact('item'));
    }

    public function editProduct(int $id, InventoryProductService $products)
    {
        $product = Supply::findOrFail($id);
        $variants = $products->query($product)->get();
        $rows = $products->editRows($variants);
        $categories = SupplyCategory::orderBy('category_name')->pluck('category_name');

        return view('inventory.edit-product', compact('product', 'variants', 'rows', 'categories'));
    }

    public function updateProduct(UpdateInventoryProductRequest $request, int $id, InventoryProductService $products, InventoryAlertService $alerts)
    {
        $result = $products->update($request->user(), Supply::findOrFail($id), $request->validated());
        $warning = false;
        foreach ($result['variants'] as $supply) {
            try {
                $state = $result['before'][$supply->id];
                $alerts->notifyIfThresholdCrossed($supply, $state['wasLow'], $state['wasOut']);
            } catch (\Throwable $exception) {
                $warning = true;
                Log::error('Product saved but inventory alert failed.', ['supply_id' => $supply->id, 'exception' => $exception]);
            }
        }

        return redirect()->route('inventory.index')->with('success', 'Product and all '.$result['variants']->count().' variants saved.'.($warning ? ' Some stock alerts could not be sent.' : ''));
    }

    /**
     * Save changes to an existing item
     */
    public function update(Request $request, int $id, InventoryAlertService $alertService)
    {
        $item = Supply::findOrFail($id);
        $wasOut = $item->quantity <= 0;
        $wasLow = $item->quantity <= $item->min_stock_level;

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'size' => 'nullable|string|max:100',
            'supplier' => 'nullable|string|max:255',
            'category' => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
            'specifications' => 'required|string|min:3',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0.01',
            'min_stock_level' => 'required|integer|min:0',
            'model_number' => 'nullable|string|max:100',
            'option_names' => 'nullable|array|max:5',
            'option_names.*' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'option_values' => 'nullable|array|max:5',
            'option_values.*' => 'nullable|string|max:500',
        ]);

        $validated['configurable_options'] = $this->normalizeOptions($validated);
        $validated = $this->validateVariant($validated, $item);
        $validated['brand'] = $this->brandSummary($validated['configurable_options']);
        unset($validated['option_names'], $validated['option_values']);

        $validated['supply_category_id'] = SupplyCategory::where('category_name', $validated['category'])->value('id');
        unset($validated['category']);

        DB::transaction(fn () => $item->update($validated));
        $alertService->notifyIfThresholdCrossed($item->fresh(), $wasLow, $wasOut);

        return redirect()->route('inventory.index')->with('success', "Record for '{$item->item_name}' has been updated.");
    }

    /**
     * NEW: Remove an item from inventory
     */
    public function destroy(int $id)
    {
        $item = Supply::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Item has been removed from inventory.');
    }

    /**
     * Remove multiple selected catalog items in one inventory action.
     */
    public function destroyMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:supplies,id'],
        ]);

        $deleted = Supply::destroy($validated['ids']);

        return redirect()
            ->route('inventory.index')
            ->with('success', $deleted.' inventory '.($deleted === 1 ? 'item has' : 'items have').' been removed.');
    }

    private function normalizeOptions(array $validated, bool $requireBrand = true): array
    {
        $groups = [];
        $names = $validated['option_names'] ?? [];
        $values = $validated['option_values'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if (strcasecmp($name, 'brand') === 0) {
                $name = 'Brand';
            }
            $choices = collect(explode(',', (string) ($values[$index] ?? '')))
                ->map(fn ($choice) => trim($choice))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($name !== '' && $choices !== []) {
                $groups[$name] = $choices;
            }
        }

        if ($requireBrand && empty($groups['Brand'])) {
            throw ValidationException::withMessages([
                'option_values' => 'Add at least one Brand under Requestor Choices.',
            ]);
        }

        $brand = isset($groups['Brand']) ? ['Brand' => $groups['Brand']] : [];
        unset($groups['Brand']);

        return $brand + $groups;
    }

    private function storeCombinations(array $validated, InventoryAlertService $alerts)
    {
        $names = collect($validated['variant_names'])->map(fn ($name) => Str::title(preg_replace('/\s+/', ' ', trim($name))))->values();
        if ($names->unique()->count() !== $names->count() || $names->intersect(['Size', 'Supplier'])->isNotEmpty()) {
            throw ValidationException::withMessages(['variant_names' => 'Use different attribute names. Size and Supplier already have their own columns.']);
        }
        $commonOptions = $this->normalizeOptions($validated, false);
        $base = Arr::except($validated, ['variants', 'variant_names', 'option_names', 'option_values']);
        $base['supply_category_id'] = SupplyCategory::where('category_name', $base['category'])->value('id');
        unset($base['category']);
        $records = [];
        $seen = [];
        foreach ($validated['variants'] as $index => $row) {
            if (count($row['values']) !== $names->count() || ! array_is_list($row['values'])) {
                throw ValidationException::withMessages(["variants.{$index}.values" => 'Enter one value for each attribute column.']);
            }
            $options = $commonOptions;
            foreach ($names as $column => $name) {
                foreach (array_keys($options) as $commonName) {
                    if (strcasecmp($commonName, $name) === 0) {
                        unset($options[$commonName]);
                    }
                }
                $options[$name] = [trim($row['values'][$column])];
            }
            if (count($options) > 5 || collect($options)->contains(fn ($choices) => count($choices) !== 1)) {
                throw ValidationException::withMessages(['option_values' => 'Priced combinations need one value per attribute, with at most five attributes. Put different choices in separate rows.']);
            }
            if (empty($options['Brand'])) {
                throw ValidationException::withMessages(['variant_names' => 'Add a Brand column, or enter one shared Brand under Requestor Choices.']);
            }
            $record = $this->validateVariant([
                ...$base, ...Arr::except($row, ['values']), 'configurable_options' => $options, 'priced_variant' => true,
            ]);
            $key = mb_strtolower(json_encode([$record['size'], $record['supplier'], $record['variant_key']], JSON_THROW_ON_ERROR));
            if (isset($seen[$key])) {
                throw ValidationException::withMessages(["variants.{$index}" => 'This combination appears more than once. Combine its stock into one row.']);
            }
            $seen[$key] = true;
            $record['brand'] = $this->brandSummary($record['configurable_options']);
            $records[] = $record;
        }
        $supplies = DB::transaction(fn () => collect($records)->map(fn ($record) => Supply::create($record)));
        foreach ($supplies as $supply) {
            $alerts->notifyIfThresholdCrossed($supply, false, false);
        }

        return redirect()->route('inventory.index')->with('success', $supplies->count().' priced combinations registered for '.$validated['item_name'].'.');
    }

    private function validateVariant(array $validated, ?Supply $item = null): array
    {
        foreach (['size' => 'Size', 'supplier' => 'Supplier'] as $field => $label) {
            $validated[$field] = trim((string) (array_key_exists($field, $validated) ? $validated[$field] : ($item?->{$field} ?? '')));
            foreach ($validated['configurable_options'] as $name => $choices) {
                if (strcasecmp($name, $label) !== 0) {
                    continue;
                }
                if (count($choices) > 1 || ($validated[$field] !== '' && $validated[$field] !== $choices[0])) {
                    throw ValidationException::withMessages([
                        $field => "Register each {$label} separately with its own supplier, price and stock. Remove multiple or conflicting {$label} choices first.",
                    ]);
                }
                $validated[$field] = $choices[0];
                unset($validated['configurable_options'][$name]);
            }
        }

        $priced = ($validated['priced_variant'] ?? false) || filled($item?->variant_key);
        unset($validated['priced_variant']);
        if ($priced && collect($validated['configurable_options'])->contains(fn ($choices) => count($choices) !== 1 || mb_strlen($choices[0]) > 100)) {
            throw ValidationException::withMessages(['option_values' => 'Each priced combination must have exactly one value per attribute, up to 100 characters. Register another combination for other choices.']);
        }
        $validated['variant_key'] = $priced ? Supply::combinationKey($validated['configurable_options']) : '';

        Validator::make($validated, [
            'size' => ['string', 'max:100'],
            'supplier' => ['string', 'max:255'],
            'item_name' => [Rule::unique('supplies', 'item_name')
                ->where('size', $validated['size'])->where('supplier', $validated['supplier'])
                ->where('variant_key', $validated['variant_key'])->ignore($item?->id)],
        ], ['item_name.unique' => 'This item and selection combination already exists. Update its stock instead.'])->validate();

        if ($item && ($item->size !== $validated['size'] || $item->supplier !== $validated['supplier'] || $item->variant_key !== $validated['variant_key'])
            && RequisitionItem::where('supply_id', $item->id)->exists()) {
            throw ValidationException::withMessages([
                'size' => 'This stock record is linked to requisitions. Register a new combination separately to keep fulfillment linked to the original selections.',
            ]);
        }

        return $validated;
    }

    private function brandSummary(array $options): string
    {
        $summary = collect($options['Brand'] ?? [])->implode(', ');

        if (mb_strlen($summary) > 255) {
            throw ValidationException::withMessages([
                'option_values' => 'The combined Brand choices must not exceed 255 characters.',
            ]);
        }

        return $summary;
    }

    private function orderedSupplies()
    {
        return Supply::with(['supplyCategory', 'supplyUnit', 'options.values'])
            ->orderByRaw('CASE WHEN quantity <= 0 THEN 0 WHEN quantity <= min_stock_level THEN 1 ELSE 2 END')
            ->orderBy('item_name')
            ->get();
    }
}
