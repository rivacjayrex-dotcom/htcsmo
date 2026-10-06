<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Services\InventoryForecastService;
use App\Services\InventoryAlertService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    public function requisitionCatalog()
    {
        $supplies = Supply::query()
            ->select([
                'id', 'item_name', 'brand', 'category', 'requisition_tier',
                'specifications', 'unit', 'unit_price', 'quantity', 'configurable_options',
            ])
            ->where('quantity', '>', 0)
            ->orderBy('requisition_tier')
            ->orderBy('item_name')
            ->get();

        return response()->json($supplies->map(fn (Supply $supply) => [
            'id' => $supply->id,
            'name' => $supply->item_name,
            'brand' => $supply->brand,
            'category' => $supply->category,
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
        $period = in_array($request->query('forecast_period'), ['monthly', 'quarterly'], true)
            ? $request->query('forecast_period')
            : 'monthly';

        // Operational priority: out of stock, low stock, then healthy stock.
        $supplies = $this->orderedSupplies();

        $forecast = $forecastService->build($supplies, period: $period);

        return view('inventory.index', compact('supplies', 'forecast'));
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
        $validated = $request->validate([
            'item_name'       => 'required|string|max:255|unique:supplies,item_name',
            'category'        => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
            'requisition_tier'=> 'required|in:minor,major',
            'specifications'  => 'required|string|min:3',
            'quantity'        => 'required|integer|min:0',
            'unit'            => 'required|string|max:50',
            'unit_price'      => 'required|numeric|min:0.01',
            'min_stock_level' => 'required|integer|min:0',
            'model_number'    => 'nullable|string|max:100',
            'option_names'    => 'nullable|array|max:5',
            'option_names.*'  => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'option_values'   => 'nullable|array|max:5',
            'option_values.*' => 'nullable|string|max:500',
        ]);

        $validated['configurable_options'] = $this->normalizeOptions($validated);
        $validated['brand'] = $this->brandSummary($validated['configurable_options']);
        unset($validated['option_names'], $validated['option_values']);

        // Link the foreign key supply_category_id automatically
        $categoryRecord = \App\Models\SupplyCategory::where('category_name', $validated['category'])->first();
        if ($categoryRecord) {
            $validated['supply_category_id'] = $categoryRecord->id;
        }

        $supply = Supply::create($validated);
        $alertService->notifyIfThresholdCrossed($supply, false, false);

        return redirect()->route('inventory.index')->with('success', "Item '{$validated['item_name']}' has been registered into inventory.");
    }

    /**
     * NEW: Show the form to edit an existing item
     */
    public function edit($id)
    {
        $item = Supply::findOrFail($id);
        return view('inventory.edit', compact('item'));
    }

    /**
     * Save changes to an existing item
     */
    public function update(Request $request, $id, InventoryAlertService $alertService)
    {
        $item = Supply::findOrFail($id);
        $wasOut = $item->quantity <= 0;
        $wasLow = $item->quantity <= $item->min_stock_level;

        $validated = $request->validate([
            'item_name'       => 'required|string|max:255|unique:supplies,item_name,' . $id,
            'category'        => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
            'requisition_tier'=> 'required|in:minor,major',
            'specifications'  => 'required|string|min:3',
            'quantity'        => 'required|integer|min:0',
            'unit'            => 'required|string|max:50',
            'unit_price'      => 'required|numeric|min:0.01',
            'min_stock_level' => 'required|integer|min:0',
            'model_number'    => 'nullable|string|max:100',
            'option_names'    => 'nullable|array|max:5',
            'option_names.*'  => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'option_values'   => 'nullable|array|max:5',
            'option_values.*' => 'nullable|string|max:500',
        ]);

        $validated['configurable_options'] = $this->normalizeOptions($validated);
        $validated['brand'] = $this->brandSummary($validated['configurable_options']);
        unset($validated['option_names'], $validated['option_values']);

        // Keep category foreign key synced
        $categoryRecord = \App\Models\SupplyCategory::where('category_name', $validated['category'])->first();
        if ($categoryRecord) {
            $validated['supply_category_id'] = $categoryRecord->id;
        }

        $item->update($validated);
        $alertService->notifyIfThresholdCrossed($item->fresh(), $wasLow, $wasOut);

        return redirect()->route('inventory.index')->with('success', "Record for '{$item->item_name}' has been updated.");
    }

    /**
     * NEW: Remove an item from inventory
     */
    public function destroy($id)
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

    private function normalizeOptions(array $validated): array
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

        if (empty($groups['Brand'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'option_values' => 'Add at least one Brand under Requestor Choices.',
            ]);
        }

        $brand = ['Brand' => $groups['Brand']];
        unset($groups['Brand']);

        return $brand + $groups;
    }

    private function brandSummary(array $options): string
    {
        $summary = collect($options['Brand'] ?? [])->implode(', ');

        if (mb_strlen($summary) > 255) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'option_values' => 'The combined Brand choices must not exceed 255 characters.',
            ]);
        }

        return $summary;
    }

    private function orderedSupplies()
    {
        return Supply::orderByRaw('CASE WHEN quantity <= 0 THEN 0 WHEN quantity <= min_stock_level THEN 1 ELSE 2 END')
            ->orderBy('item_name')
            ->get();
    }
}
