<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    // List all inventory items
    public function index()
    {
        // We order by item_name so the list is alphabetical and easier to read
        $supplies = Supply::orderBy('item_name', 'asc')->get();
        return view('inventory.index', compact('supplies'));
    }

    // Show the form to add a new item
    public function create()
    {
        return view('inventory.create');
    }

    // Save a new item to the database
// Save a new item to the database
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'       => 'required|string|max:255|unique:supplies,item_name',
            'brand'           => 'required|string|max:100',
            'category'        => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,HDMI,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
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
        unset($validated['option_names'], $validated['option_values']);

        // Link the foreign key supply_category_id automatically
        $categoryRecord = \App\Models\SupplyCategory::where('category_name', $validated['category'])->first();
        if ($categoryRecord) {
            $validated['supply_category_id'] = $categoryRecord->id;
        }

        Supply::create($validated);

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
    public function update(Request $request, $id)
    {
        $item = Supply::findOrFail($id);

        $validated = $request->validate([
            'item_name'       => 'required|string|max:255|unique:supplies,item_name,' . $id,
            'brand'           => 'required|string|max:100',
            'category'        => 'required|in:Cleaning Supplies,Construction Supplies,Drugs and Medicines,HDMI,Maintenance Supplies,Medical Supplies,Non-Medical Supplies,Office Supplies',
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
        unset($validated['option_names'], $validated['option_values']);

        // Keep category foreign key synced
        $categoryRecord = \App\Models\SupplyCategory::where('category_name', $validated['category'])->first();
        if ($categoryRecord) {
            $validated['supply_category_id'] = $categoryRecord->id;
        }

        $item->update($validated);

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

    private function normalizeOptions(array $validated): array
    {
        $groups = [];
        $names = $validated['option_names'] ?? [];
        $values = $validated['option_values'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
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

        return $groups;
    }
}
