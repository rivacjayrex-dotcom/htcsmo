<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRequisitionRequest;
use App\Models\ApprovalLog;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Services\RequisitionSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequisitionController extends Controller
{
    public function store(SubmitRequisitionRequest $request, RequisitionSubmissionService $submissions)
    {
        $result = $submissions->submit($request->user(), $request->validated());
        $requisition = $result['requisition'];

        return redirect()->route('dashboard')->with('success', $result['notification_failed']
            ? "Requisition {$requisition->tracking_code} was submitted, but some notifications could not be sent. Please contact the SMO."
            : 'Requisition processed successfully.');
    }

    private function catalogPayload(Supply $supply): array
    {
        return [
            'id' => $supply->id,
            'name' => $supply->item_name,
            'brand' => $supply->brand,
            'category' => $supply->category,
            'tier' => $supply->requisition_tier ?? 'minor',
            'specs' => $supply->specifications,
            'unit' => $supply->unit,
            'price' => (float) $supply->unit_price,
            'stock' => (int) $supply->quantity,
            'options' => $supply->requestOptions(),
        ];
    }

    private function selectedOptionsFromSpecifications(Supply $supply, ?string $specifications): array
    {
        return collect($supply->requestOptions())
            ->mapWithKeys(function (array $choices, string $label) use ($specifications) {
                $selected = collect($choices)->first(
                    fn (string $choice) => str_contains((string) $specifications, "{$label}: {$choice}"),
                );
                $selected ??= count($choices) === 1 ? $choices[0] : null;

                return $selected === null ? [] : [$label => $selected];
            })
            ->all();
    }

    // 1. Show Edit Form
    public function edit(int $id)
    {
        $requisition = Requisition::with('items.supply')->where('user_id', Auth::id())->findOrFail($id);

        // Security Guardrail: Only allow editing if still pending
        if ($requisition->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Cannot edit: Requisition is already in the approval process.');
        }

        $catalog = Supply::query()
            ->with(['supplyCategory', 'supplyUnit', 'options.values'])
            ->where('requisition_tier', $requisition->request_type)
            ->orderBy(SupplyCategory::select('category_name')
                ->whereColumn('supply_categories.id', 'supplies.supply_category_id'))
            ->orderBy('item_name')
            ->get()
            ->toBase()
            ->map(fn (Supply $supply) => $this->catalogPayload($supply))
            ->values();

        $catalogById = $catalog->keyBy('id');
        $initialItems = $requisition->items->map(function (RequisitionItem $item) use ($catalogById) {
            $catalogItem = $catalogById->get($item->supply_id);

            return [
                ...($catalogItem ?? [
                    'id' => $item->supply_id,
                    'name' => $item->item_name,
                    'brand' => null,
                    'category' => 'Unavailable catalog item',
                    'tier' => null,
                    'specs' => $item->specifications,
                    'unit' => $item->unit,
                    'price' => (float) $item->unit_price,
                    'stock' => 0,
                    'options' => [],
                ]),
                'qty' => (int) $item->quantity,
                'selected_options' => $item->supply
                    ? $this->selectedOptionsFromSpecifications($item->supply, $item->specifications)
                    : [],
            ];
        })->values();

        return view('requests.edit', compact('requisition', 'catalog', 'initialItems'));
    }

    // 2. Save Changes
    public function update(Request $request, int $id, RequisitionSubmissionService $submissions)
    {
        $requisition = Requisition::where('user_id', Auth::id())->findOrFail($id);

        if ($requisition->status !== 'pending') {
            return back()->with('error', 'Access denied: Approval has already started.');
        }

        $validated = $request->validate([
            'purpose' => 'required|string|min:10|max:500',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|integer|exists:supplies,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.options' => 'nullable|array',
            'items.*.options.*' => 'nullable|string|max:100',
        ]);

        $preparedItems = $submissions->prepareItems($validated['items'], $requisition->request_type);
        $grandTotal = $preparedItems->sum('subtotal');

        if ($requisition->request_type === 'minor' && $grandTotal > 1000) {
            throw ValidationException::withMessages(['items' => 'Minor requisitions cannot exceed ₱1,000.']);
        }

        if ($requisition->request_type === 'major' && $grandTotal <= 1000) {
            throw ValidationException::withMessages(['items' => 'Major requisitions must exceed PHP 1,000.']);
        }

        DB::transaction(function () use ($preparedItems, $requisition, $grandTotal, $validated) {
            $lockedRequisition = Requisition::query()
                ->whereKey($requisition->id)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequisition->status !== 'pending') {
                throw ValidationException::withMessages([
                    'items' => 'Approval has already started. This requisition can no longer be changed.',
                ]);
            }

            $lockedRequisition->items()->delete();
            foreach ($preparedItems as $item) {
                $lockedRequisition->items()->create([
                    'supply_id' => $item['supply']->id,
                    'item_name' => $item['supply']->item_name,
                    'specifications' => $item['specifications'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['supply']->unit,
                    'unit_price' => $item['supply']->unit_price,
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $lockedRequisition->update([
                'purpose' => $validated['purpose'],
                'grand_total' => $grandTotal,
            ]);
        });

        return redirect()->route('requests.index')->with('success', 'Requisition updated successfully.');
    }

    // Keep the existing DELETE URL compatible, but retain the submitted record.
    public function destroy(Request $request, int $id)
    {
        $validated = $request->validate(['withdrawal_reason' => ['nullable', 'string', 'max:500']]);
        $withdrawn = DB::transaction(function () use ($id, $validated, $request) {
            $requisition = Requisition::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
            if ($requisition->status !== 'pending') {
                return false;
            }
            $closedAt = now();
            $requisition->update(['status' => 'withdrawn', 'closed_at' => $closedAt]);
            $log = new ApprovalLog([
                'requisition_id' => $requisition->id, 'acted_by_user_id' => $request->user()->id,
                'action' => 'Withdrawn by requestor', 'role' => $request->user()->role,
                'remarks' => filled($validated['withdrawal_reason'] ?? null)
                    ? trim($validated['withdrawal_reason']) : 'Withdrawn by the requestor before approval.',
            ]);
            $log->created_at = $closedAt;
            $log->save();

            return true;
        });

        if (! $withdrawn) {
            return back()->with('error', 'Only your pending requests can be withdrawn. This request has already moved on or is closed.');
        }

        return redirect()->route('requests.archive', ['category' => 'withdrawn'])
            ->with('success', 'Request withdrawn. Its items and history have been kept in your archive.');
    }
}
