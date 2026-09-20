<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Models\Supply;
use App\Models\ApprovalLog;
use App\Models\DigitalSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Mail\RequisitionAlertMail;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;

class RequestController extends Controller
{
    /**
     * Active Requisitions (Pending, In-Progress, Approved)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Base Query for ACTIVE ONLY
        $query = Requisition::with(['items', 'user'])
            ->whereNotIn('status', ['released', 'rejected']);

        // 2. Privacy: Non-SMO only sees their own
        if ($user->role !== 'smo') {
            $query->where('user_id', $user->id);
        }

        // 3. Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($user->role == 'smo' && $request->filled('dept')) {
            $query->whereHas('user', fn($q) => $q->where('department', $request->dept));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 4. Sorting
        $order = $request->get('order', 'desc');
        $activeRequests = $query->orderBy('created_at', $order)->paginate(15);

        return view('requests.index', compact('activeRequests'));
    }

    /**
     * Archived Requisitions (Released & Rejected)
     */
    public function archive(Request $request)
    {
        $user = Auth::user();

        // 1. Base Query for COMPLETED ONLY
        $query = Requisition::with(['items', 'user'])
            ->whereIn('status', ['released', 'rejected']);

        // 2. Privacy: Non-SMO only sees their own
        if ($user->role !== 'smo') {
            $query->where('user_id', $user->id);
        }

        // 3. Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($user->role == 'smo' && $request->filled('dept')) {
            $query->whereHas('user', fn($q) => $q->where('department', $request->dept));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->date_to);
        }

        // 4. Sorting
        $order = $request->get('order', 'desc');
        $archivedRequests = $query->orderBy('updated_at', $order)->paginate(15);

        return view('requests.archive', compact('archivedRequests'));
    }

    /**
     * Admin/Signatory Approval Queue
     */
    public function adminIndex()
    {
        $user = Auth::user();

        // Standard pending query
        $query = Requisition::with(['items', 'user']);

        switch ($user->role) {
            case 'dept_head':
                $query->where('status', 'pending')
                    ->whereHas('user', fn($u) => $u->where('department', $user->department));
                break;
            case 'vp_finance':
                $query->where('status', 'approved_dept')->where('request_type', 'minor');
                break;
            case 'vp_admin':
                $query->where('status', 'approved_dept')->where('request_type', 'major');
                break;
            case 'provost':
                $query->where('status', 'approved_vp')->where('request_type', 'major');
                break;
            case 'president':
                $query->where('status', 'approved_provost')->where('request_type', 'major');
                break;
            case 'smo':
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('request_type', 'minor')->where('status', 'approved_vp');
                    })->orWhere(function ($sub) {
                        $sub->where('status', 'approved_president');
                    });
                });
                break;
            default:
                $query->whereRaw('1 = 0');
                break;
        }

        $pendingRequests = $query->latest()->get();

        // Dedicated list for requests placed on hold / for clarification
        $clarificationRequests = Requisition::with(['items', 'user'])
            ->where('status', 'for_clarification')
            ->latest()
            ->get();

        if ($user->role === 'smo') {
            $pendingMinor = $pendingRequests->where('request_type', 'minor');
            $pendingMajor = $pendingRequests->where('request_type', 'major');

            return view('admin.approvals', compact('pendingMinor', 'pendingMajor', 'clarificationRequests'));
        }

        return view('admin.approvals', compact('pendingRequests', 'clarificationRequests'));
    }

    /**
     * Update Approval Status (Signatories)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'  => 'required|in:approved,rejected,clarification',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $sr = Requisition::with(['items', 'user'])->findOrFail($id);
        $role = Auth::user()->role;

        $roleTitles = [
            'dept_head'  => 'Department Head',
            'vp_finance' => 'VP for Finance',
            'vp_admin'   => 'VP for Administration',
            'provost'    => 'School Provost',
            'president'  => 'School President',
            'smo'        => 'SMO In-Charge',
        ];
        $position = $roleTitles[$role] ?? ucwords(str_replace('_', ' ', $role));

        $sr->remarks = $request->remarks;

        // 1. REJECTED
        if ($request->status === 'rejected') {
            $sr->status = 'rejected';
            $actionText = 'Rejected by ' . $position;

            $this->sendAlert(
                $sr->user_id,
                "Requisition Disapproved",
                "Your request {$sr->tracking_code} was rejected by {$position}. Reason: {$request->remarks}",
                'x-circle',
                'danger'
            );
        }
        // 2. QUESTIONABLE / OFFICE CLARIFICATION
        elseif ($request->status === 'clarification') {
            $sr->status = 'for_clarification';
            $actionText = 'Flagged for Office Clarification by ' . $position;

            // Notify Requestor on Screen
            $this->sendAlert(
                $sr->user_id,
                "⚠️ Office Appearance Requested",
                "Your request {$sr->tracking_code} requires clarification. Please proceed to the office of {$position} together with SMO. Note: {$request->remarks}",
                'help-circle',
                'warning'
            );

            // Notify SMO In-Charge on Screen
            $smoUsers = User::where('role', 'smo')->get();
            foreach ($smoUsers as $smo) {
                $this->sendAlert(
                    $smo->id,
                    "⚠️ Inquiry on Req {$sr->tracking_code}",
                    "{$position} requested a meeting regarding {$sr->tracking_code} ({$sr->user->name}). Please coordinate with their office.",
                    'users',
                    'warning'
                );
            }
        }
        // 3. APPROVED (Normal Hierarchy)
        else {
            if ($role === 'dept_head') {
                $sr->status = 'approved_dept';
            } elseif ($role === 'vp_finance' || $role === 'vp_admin') {
                $sr->status = 'approved_vp';
            } elseif ($role === 'provost') {
                $sr->status = 'approved_provost';
            } elseif ($role === 'president') {
                $sr->status = 'approved_president';
            }
            $actionText = 'Approved by ' . $position;

            $this->sendAlert(
                $sr->user_id,
                "Requisition Endorsed",
                "Your request {$sr->tracking_code} has been endorsed by {$position}.",
                'check-circle',
                'success'
            );
        }

        $sr->save();

        // 4. Log the Decision
        $log = ApprovalLog::create([
            'requisition_id' => $sr->id,
            'action'         => $actionText,
            'role'           => $role,
            'remarks'        => $request->remarks,
        ]);

        // 5. Cryptographic Digital Signature Generation (RA 8792 E-Commerce Act Compliance)
        if ($request->status === 'approved') {
            $rawPayload = "REQ-{$sr->id}|USER-" . Auth::id() . "|ROLE-{$role}|TIME-" . now()->timestamp . "|SECRET-" . config('app.key');
            $signatureToken = hash('sha256', $rawPayload);

            DigitalSignature::create([
                'approval_log_id' => $log->id,
                'signature_token' => $signatureToken,
                'signer_name'     => Auth::user()->name,
                'signer_role'     => $position,
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'signed_at'       => now(),
            ]);
        }

        // 6. TRIGGER OUTBOUND EMAIL NOTIFICATION FOR ALL 3 DECISIONS
        $accent = match($request->status) {
            'approved'      => '#185b3b', // HTC Green
            'clarification' => '#fd7e14', // Warning Amber
            default         => '#dc3545'  // Danger Red
        };

        $headline = match($request->status) {
            'approved'      => "Requisition Endorsed by {$position}",
            'clarification' => "⚠️ Office Appearance Required: {$position}",
            default         => "Requisition Disapproved by {$position}"
        };

        $emailMsg = match($request->status) {
            'approved'      => "Your requisition has been reviewed and endorsed by {$position}. It will now proceed to the next stage.",
            'clarification' => "The {$position} has requested a consultation regarding this request. Please report to their office together with the SMO In-Charge. Notes: " . ($request->remarks ?? 'None'),
            default         => "Your requisition was disapproved by {$position}. Reason: " . ($request->remarks ?? 'Not specified')
        };

        $this->sendEmailNotification(
            $sr->user->email,
            $sr->user,
            $sr,
            $headline,
            $headline,
            $emailMsg,
            $accent
        );

        return redirect()->route('admin.approvals')->with('success', "Requisition #{$sr->id} status updated.");
    }

    /**
     * SMO Confirmation & Release (Fulfillment)
     */
    public function releaseRequest($id)
    {
        $sr = Requisition::with('items')->findOrFail($id);

        // 1. Guard against double-releasing
        if ($sr->status === 'released') {
            return back()->with('error', 'This requisition has already been released.');
        }

        // 2. Only SMO can release
        if (Auth::user()->role !== 'smo') {
            return back()->with('error', 'Unauthorized. Only the SMO In-Charge can release supplies.');
        }

        return DB::transaction(function () use ($sr) {
            $deductedItems = [];

            // Check and deduct items that exist in our inventory catalog
         foreach ($sr->items()->get() as $item) {
             // Look for matching item name (case-insensitive)
             $inventoryItem = Supply::where('item_name', 'LIKE', trim($item->item_name))->first();

             if ($inventoryItem) {
                 if ($inventoryItem->quantity < $item->quantity) {
                     return back()->with('error', "Insufficient stock for '{$item->item_name}'. Available: {$inventoryItem->quantity}, Requested: {$item->quantity}.");
                 }
                 $inventoryItem->decrement('quantity', $item->quantity);
                 // Record Immutable Inventory Audit Log (3NF Traceability)
                 \App\Models\SupplyInventoryLog::create([
                     'supply_id'        => $inventoryItem->id,
                     'user_id'          => Auth::id(),
                     'requisition_id'   => $sr->id,
                     'transaction_type' => 'release_deduction',
                     'quantity_change'  => -$item->quantity,
                     'balance_after'    => $inventoryItem->quantity,
                     'remarks'          => "Fulfillment of {$sr->tracking_code} for {$sr->user->name}",
                 ]);

                 $deductedItems[] = "{$item->item_name} (-{$item->quantity})";
                }
            }

            // Mark Requisition as Released
            $sr->status = 'released';
            $sr->save();

            // Create detailed audit log
            $logRemarks = count($deductedItems) > 0
                ? 'Released by SMO. Stock deducted: ' . implode(', ', $deductedItems)
                : 'Released by SMO. Special requisition processed.';

            $log = ApprovalLog::create([
                'requisition_id' => $sr->id,
                'action'         => 'Released and Fulfilled by SMO In-Charge',
                'role'           => 'smo',
                'remarks'        => $logRemarks,
            ]);

            // Cryptographic Digital Signature for Release
            $rawPayload = "RELEASE-{$sr->id}|USER-" . Auth::id() . "|TIME-" . now()->timestamp . "|SECRET-" . config('app.key');
            $signatureToken = hash('sha256', $rawPayload);

            DigitalSignature::create([
                'approval_log_id' => $log->id,
                'signature_token' => $signatureToken,
                'signer_name'     => Auth::user()->name,
                'signer_role'     => 'SMO In-Charge',
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent(),
                'signed_at'       => now(),
            ]);

            // Notify Requester
            $this->sendAlert(
                $sr->user_id,
                "Supplies Ready for Pickup",
                "Your requisition #{$sr->id} has been fulfilled by the SMO and is ready for pickup.",
                'package-check',
                'success'
            );

            $this->sendEmailNotification(
                $sr->user->email,
                $sr->user,
                $sr,
                "Supplies Fulfilled and Ready for Pickup",
                "Your Supplies Are Ready!",
                "All requested items under {$sr->tracking_code} have been verified, deducted from inventory, and prepared by the SMO.",
                '#185b3b'
            );

            return redirect()->route('admin.approvals')->with('success', "Requisition #{$sr->id} successfully confirmed and released!");
        });
    }

    /**
     * Notifications Center
     */
    public function notifications()
    {
        $user = Auth::user();
        $allNotes = Notification::where('user_id', $user->id)->latest()->get();

        // Categorized notes for tabs
        $approvalNotes = $allNotes->where('type', 'success');
        $deadlineNotes = $allNotes->where('type', 'danger');

        // 1. Data for SMO (Critical Deadlines)
        $criticalRequests = ($user->role == 'smo')
            ? Requisition::with('items')
                ->whereIn('status', ['approved_president', 'approved_vp'])
                ->where('updated_at', '<=', now()->subDays(2))->get()
            : [];

        // 2. Data for Employee/Staff (Personal Summary)
        $personalStats = [
            'total'    => Requisition::where('user_id', $user->id)->count(),
            'pending'  => Requisition::where('user_id', $user->id)->where('status', 'pending')->count(),
            'released' => Requisition::where('user_id', $user->id)->where('status', 'released')->count(),
        ];

        Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);

        return view('notifications', compact('allNotes', 'approvalNotes', 'deadlineNotes', 'criticalRequests', 'personalStats'));
    }

    public function show($id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature'])->findOrFail($id);
        return view('requests.show', compact('request'));
    }

    public function downloadPDF($id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature'])->findOrFail($id);
        $pdf = Pdf::loadView('requests.pdf', compact('request'));
        return $pdf->download('HTC-Requisition-'.$request->id.'.pdf');
    }

    private function sendAlert($userId, $title, $message, $icon = 'bell', $type = 'info')
    {
        Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'icon'    => $icon,
            'type'    => $type
        ]);
    }

    public function getLatestNotification()
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->latest()
            ->first();

        if ($notification) {
            return response()->json($notification);
        }

        return response()->json(null);
    }

    /**
     * Send Outbound Email and Record in email_logs Table (Thesis Notification Engine)
     */
    private function sendEmailNotification($recipientEmail, $user, $requisition, $subject, $headline, $message, $accentColor = '#185b3b')
    {
        try {
            // 1. Send the SMTP email
            Mail::to($recipientEmail)->send(
                new RequisitionAlertMail($requisition, $subject, $headline, $message, $accentColor)
            );

            // 2. Log successful transmission to database
            EmailLog::create([
                'user_id'         => $user?->id,
                'recipient_email' => $recipientEmail,
                'subject'         => $subject,
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);
        } catch (\Exception $e) {
            // Log failure without crashing the user request
            EmailLog::create([
                'user_id'         => $user?->id,
                'recipient_email' => $recipientEmail,
                'subject'         => $subject,
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
                'sent_at'         => now(),
            ]);
        }
    }
}
