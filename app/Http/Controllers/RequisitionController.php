<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequisitionController extends Controller
{
    public function store(Request $request, NotificationService $notifications)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:minor,major',
            'purpose' => 'required|string|min:10|max:500',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|integer|exists:supplies,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.options' => 'nullable|array',
            'items.*.options.*' => 'nullable|string|max:100',
        ]);

        $catalogItems = Supply::whereIn('id', collect($validated['items'])->pluck('supply_id'))
            ->get()
            ->keyBy('id');

        $preparedItems = collect($validated['items'])->map(function (array $itemData) use ($catalogItems, $validated) {
            $supply = $catalogItems->get($itemData['supply_id']);

            if (! $supply || $supply->requisition_tier !== $validated['request_type']) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected items do not belong to the chosen requisition tier.',
                ]);
            }

            if ($itemData['qty'] > $supply->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Only {$supply->quantity} {$supply->unit} of {$supply->item_name} are currently available.",
                ]);
            }

            $selectedOptions = $this->validateSelectedOptions($supply, $itemData['options'] ?? []);
            $optionSummary = collect($selectedOptions)
                ->map(fn ($value, $label) => "{$label}: {$value}")
                ->implode('; ');
            $specifications = collect([$supply->specifications, $optionSummary])->filter()->implode(' | ');

            return [
                'supply' => $supply,
                'specifications' => $specifications ?: null,
                'quantity' => $itemData['qty'],
                'subtotal' => $itemData['qty'] * (float) $supply->unit_price,
            ];
        });

        foreach ($preparedItems->groupBy(fn ($item) => $item['supply']->id) as $itemsForSupply) {
            $supply = $itemsForSupply->first()['supply'];
            $totalRequested = $itemsForSupply->sum('quantity');
            if ($totalRequested > $supply->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Only {$supply->quantity} {$supply->unit} of {$supply->item_name} are currently available across all selected options.",
                ]);
            }
        }

        $calculatedGrandTotal = $preparedItems->sum('subtotal');
        if ($validated['request_type'] === 'minor' && $calculatedGrandTotal > 1000) {
            throw ValidationException::withMessages([
                'items' => 'Minor requisitions cannot exceed ₱1,000. Please choose the Major tier instead.',
            ]);
        }

        // Start after the requestor's own approval office so nobody approves
        // their own requisition or sends it backward to a lower office.
        $initialStatus = Requisition::initialStatusFor($user);

        // 3. CREATE THE HEADER
        $requisition = DB::transaction(function () use ($user, $validated, $initialStatus, $preparedItems, $calculatedGrandTotal) {
            $requisition = Requisition::create([
                'user_id' => $user->id,
                'request_type' => $validated['request_type'],
                'status' => $initialStatus,
                'purpose' => $validated['purpose'],
                'grand_total' => $calculatedGrandTotal,
            ]);

            foreach ($preparedItems as $item) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'supply_id' => $item['supply']->id,
                    'item_name' => $item['supply']->item_name,
                    'specifications' => $item['specifications'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['supply']->unit,
                    'unit_price' => $item['supply']->unit_price,
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $requisition;
        });

        $notifications->send($user, 'requisition.submitted', 'Requisition Submitted',
            'Your request for '.count($validated['items']).' item(s) has been submitted.', [
                'icon' => 'send', 'type' => 'info', 'action_type' => 'requisition',
                'action_id' => $requisition->id,
                'dedupe_key' => "requisition:{$requisition->id}:submitted:owner",
            ]);

        if ($initialStatus === 'pending') {
            User::query()->where('role', 'dept_head')->where('department', $user->department)
                ->where('account_status', User::ACCOUNT_ACTIVE)->eachById(fn (User $head) => $notifications->send(
                    $head, 'requisition.awaiting_approval', 'Requisition Awaiting Review',
                    "{$user->name}'s requisition {$requisition->tracking_code} is ready for department review.", [
                        'icon' => 'file-text', 'type' => 'info', 'action_type' => 'approval_queue',
                        'action_id' => $requisition->id,
                        'dedupe_key' => "requisition:{$requisition->id}:awaiting:dept-head",
                    ]
                ));
        } elseif ($nextRole = $requisition->nextReviewerRole()) {
            $isExecutivePriority = in_array($user->role, ['president', 'provost'], true);
            $notifications->sendToRole(
                $nextRole,
                $nextRole === 'smo' ? 'requisition.ready_for_release' : 'requisition.awaiting_approval',
                $isExecutivePriority ? 'Priority Requisition Ready' : 'Requisition Ready for Action',
                $isExecutivePriority
                    ? "A direct requisition from the {$user->role} ({$user->name}) is ready for release."
                    : "Requisition {$requisition->tracking_code} from {$user->name} is ready for your review.",
                [
                    'icon' => $isExecutivePriority ? 'zap' : 'file-text',
                    'type' => $isExecutivePriority ? 'danger' : ($nextRole === 'smo' ? 'warning' : 'info'),
                    'action_type' => 'approval_queue',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "requisition:{$requisition->id}:initial-route:{$nextRole}",
                ]
            );
        }

        return redirect()->route('dashboard')->with('success', 'Requisition processed successfully.');
    }

    private function validateSelectedOptions(Supply $supply, array $selected): array
    {
        $configured = $supply->requestOptions();
        $clean = [];

        foreach ($configured as $label => $choices) {
            $value = $selected[$label] ?? (count($choices) === 1 ? $choices[0] : null);
            if (! is_string($value) || ! in_array($value, $choices, true)) {
                throw ValidationException::withMessages([
                    'items' => "Please select a valid {$label} for {$supply->item_name}.",
                ]);
            }
            $clean[$label] = $value;
        }

        return $clean;
    }

    // 1. Show Edit Form
    public function edit($id)
    {
        $requisition = Requisition::with('items')->where('user_id', Auth::id())->findOrFail($id);

        // Security Guardrail: Only allow editing if still pending
        if ($requisition->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'Cannot edit: Requisition is already in the approval process.');
        }

        return view('requests.edit', compact('requisition'));
    }

    // 2. Save Changes
    public function update(Request $request, $id)
    {
        $requisition = Requisition::where('user_id', Auth::id())->findOrFail($id);

        if ($requisition->status !== 'pending') {
            return back()->with('error', 'Access denied: Approval has already started.');
        }

        $validated = $request->validate([
            'purpose' => 'required|string|min:10|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $grandTotal = 0;
        $itemUpdates = [];
        $requisitionItems = $requisition->items()->with('supply')->get()->keyBy('id');
        $submittedIds = collect($validated['items'])->pluck('id');
        if ($submittedIds->unique()->count() !== $requisitionItems->count()
            || $submittedIds->unique()->diff($requisitionItems->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'All requisition items must be included exactly once.']);
        }

        foreach ($validated['items'] as $itemData) {
            $item = $requisitionItems->get($itemData['id']);
            if (! $item || ! $item->supply || $item->supply->requisition_tier !== $requisition->request_type) {
                throw ValidationException::withMessages(['items' => 'This requisition contains an unavailable catalog item.']);
            }
            if ($itemData['qty'] > $item->supply->quantity) {
                throw ValidationException::withMessages(['items' => "Only {$item->supply->quantity} {$item->supply->unit} of {$item->item_name} are available."]);
            }

            $subtotal = $itemData['qty'] * (float) $item->supply->unit_price;
            $grandTotal += $subtotal;
            $itemUpdates[] = [$item, [
                'quantity' => $itemData['qty'],
                'unit_price' => $item->supply->unit_price,
                'subtotal' => $subtotal,
            ]];
        }

        if ($requisition->request_type === 'minor' && $grandTotal > 1000) {
            throw ValidationException::withMessages(['items' => 'Minor requisitions cannot exceed ₱1,000.']);
        }

        DB::transaction(function () use ($itemUpdates, $requisition, $grandTotal, $validated) {
            foreach ($itemUpdates as [$item, $values]) {
                $item->update($values);
            }
            $requisition->update([
                'purpose' => $validated['purpose'],
                'grand_total' => $grandTotal,
            ]);
        });

        return redirect()->route('requests.index')->with('success', 'Requisition updated successfully.');
    }

    // 3. Delete Requisition
    public function destroy($id)
    {
        $requisition = Requisition::where('user_id', Auth::id())->findOrFail($id);

        if ($requisition->status !== 'pending') {
            return back()->with('error', 'Cannot delete: The Department Head has already acted on this requisition.');
        }

        $requisition->delete(); // This also deletes items because of onDelete('cascade') in migration

        return redirect()->route('requests.index')->with('success', 'Requisition deleted successfully.');
    }
}
