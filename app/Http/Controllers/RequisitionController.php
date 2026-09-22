<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequisitionController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. VALIDATION
        $request->validate([
            'request_type' => 'required',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
        ]);

        // 2. AUTO-APPROVAL LOGIC (Advisor's Rule)
        // If the user is President or Provost, the status is pre-approved.
        // Otherwise, it starts as 'pending' for the Dept Head.
        $initialStatus = in_array($user->role, ['president', 'provost'])
            ? 'approved_president'
            : 'pending';

        // 3. CREATE THE HEADER
        $requisition = Requisition::create([
            'user_id' => $user->id,
            'request_type' => $request->request_type,
            'status' => $initialStatus,
            'grand_total' => 0,
        ]);

        $calculatedGrandTotal = 0;

        // 4. THE CART LOOP
        foreach ($request->items as $itemData) {
            $subtotal = $itemData['qty'] * $itemData['price'];
            $calculatedGrandTotal += $subtotal;

            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'item_name' => $itemData['name'],
                'specifications' => $itemData['specs'] ?? null,
                'quantity' => $itemData['qty'],
                'unit' => $itemData['unit'] ?? 'pc',
                'unit_price' => $itemData['price'],
                'subtotal' => $subtotal,
            ]);
        }

        // 5. UPDATE THE GRAND TOTAL
        $requisition->update(['grand_total' => $calculatedGrandTotal]);

        // 6. SMART NOTIFICATIONS
        if ($initialStatus === 'approved_president') {
            // NOTIFY SMO: High Priority Alert
            $smo = User::where('role', 'smo')->first();
            if ($smo) {
                Notification::create([
                    'user_id' => $smo->id,
                    'title' => '⚡ URGENT: Priority Requisition',
                    'message' => "A direct requisition from the {$user->role} ({$user->name}) is ready for immediate release.",
                    'icon' => 'zap',
                    'type' => 'danger' // Red alert style
                ]);
            }
        } else {
            // NOTIFY USER: Standard submission
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Requisition Submitted',
                'message' => "Your request for " . count($request->items) . " item(s) has been sent to your Department Head.",
                'icon' => 'send',
                'type' => 'info'
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Requisition processed successfully.');
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

        $request->validate([
            'request_type' => 'required|in:minor,major',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.specs' => 'nullable|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Update the Requisition Header
        $requisition->update(['request_type' => $request->request_type]);

        // Refresh the items (Delete old ones and add new ones)
        $requisition->items()->delete();

        $grandTotal = 0;
        foreach ($request->items as $itemData) {
            $subtotal = $itemData['qty'] * $itemData['price'];
            $grandTotal += $subtotal;

            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'item_name' => $itemData['name'],
                'specifications' => $itemData['specs'] ?? null,
                'quantity' => $itemData['qty'],
                'unit' => $itemData['unit'] ?? 'pc',
                'unit_price' => $itemData['price'],
                'subtotal' => $subtotal,
            ]);
        }

        $requisition->update(['grand_total' => $grandTotal]);

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
