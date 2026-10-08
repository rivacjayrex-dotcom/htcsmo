<?php
namespace App\Http\Controllers;

use App\Http\Requests\ArchiveIndexRequest;
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
use App\Services\InventoryAlertService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class RequestController extends Controller
{
    /**
     * Lightweight fingerprint used by open pages to detect requisition activity.
     */
    public function systemVersion()
    {
        return response()->json(['version' => $this->runtimeVersion()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * One compact response replaces the former notification and refresh pollers.
     */
    public function runtimeState(Request $request)
    {
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $user = $request->user();
        $afterId = (int) ($validated['after_id'] ?? 0);
        $notificationState = Notification::query()->forUser($user)
            ->selectRaw(
                'COALESCE(MAX(id), 0) as latest_id, COALESCE(SUM(CASE WHEN is_read = ? THEN 1 ELSE 0 END), 0) as unread_count',
                [false]
            )
            ->first();
        $notifications = Notification::query()->forUser($user)->unread()
            ->where('id', '>', $afterId)->oldest('id')->limit(10)->get();
        $payloads = $notifications->map(fn (Notification $notification) => $notification->pollPayload($user))->values();
        $latestId = (int) $notificationState->latest_id;

        return response()->json([
            'version' => $this->runtimeVersion(),
            'account_id' => $user->id,
            'latest_id' => $latestId,
            'cursor' => (int) ($notifications->last()?->id ?? $latestId),
            'unread_count' => (int) $notificationState->unread_count,
            'notifications' => $payloads,
            'notification' => $payloads->first(),
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    private function runtimeVersion(): string
    {
        $state = DB::query()->selectRaw('(
                SELECT COUNT(*) FROM requisitions
            ) as requisition_total, (
                SELECT COALESCE(MAX(id), 0) FROM requisitions
            ) as requisition_latest_id, (
                SELECT MAX(updated_at) FROM requisitions
            ) as requisition_latest_update, (
                SELECT COUNT(*) FROM approval_logs
            ) as approval_total, (
                SELECT COALESCE(MAX(id), 0) FROM approval_logs
            ) as approval_latest_id, (
                SELECT MAX(created_at) FROM approval_logs
            ) as approval_latest_update, (
                SELECT COUNT(*) FROM supplies
            ) as supply_total, (
                SELECT COALESCE(MAX(id), 0) FROM supplies
            ) as supply_latest_id, (
                SELECT MAX(updated_at) FROM supplies
            ) as supply_latest_update')
            ->first();

        return hash('sha256', json_encode($state));
    }

    /**
     * Active Requisitions (Pending, In-Progress, Approved)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $scopeQuery = Requisition::query()
            ->whereNotIn('status', ['released', 'rejected']);

        if ($user->role !== 'smo') {
            $scopeQuery->where('user_id', $user->id);
        }

        $requestStats = [
            'total' => (clone $scopeQuery)->count(),
            'pending' => (clone $scopeQuery)->where('status', 'pending')->count(),
            'clarification' => (clone $scopeQuery)->where('status', 'for_clarification')->count(),
            'ready' => (clone $scopeQuery)->where(function ($ready) {
                $ready->where(function ($minor) {
                    $minor->where('request_type', 'minor')->where('status', 'approved_vp');
                })->orWhere(function ($major) {
                    $major->where('request_type', 'major')->where('status', 'approved_president');
                });
            })->count(),
            'deadline' => (clone $scopeQuery)->where(function ($ready) {
                $ready->where(function ($minor) {
                    $minor->where('request_type', 'minor')->where('status', 'approved_vp');
                })->orWhere(function ($major) {
                    $major->where('request_type', 'major')->where('status', 'approved_president');
                });
            })->where('stage_started_at', '<=', now()->subDays(2))->count(),
        ];

        $query = (clone $scopeQuery)->with(['items', 'user']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($matches) use ($search) {
                $matches->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($items) => $items->where('item_name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%"));
            });
        }
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

        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $activeRequests = $query->orderBy('created_at', $order)->paginate(15)->withQueryString();
        $departments = $user->role === 'smo'
            ? User::whereNotNull('department')->where('department', '<>', '')->distinct()->orderBy('department')->pluck('department')
            : collect();

        $approvalDeadlineRequests = collect();
        if ($user->role === 'smo' || in_array($user->role, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president'], true)) {
            $deadlineQuery = Requisition::query()
                ->with(['items', 'user'])
                ->approvalDeadlineRisks();

            if ($user->role !== 'smo') {
                $deadlineQuery->assignedToReviewer($user);
            }

            $approvalDeadlineRequests = $deadlineQuery
                ->oldest('stage_started_at')
                ->take(8)
                ->get();
        }

        return view('requests.index', compact('activeRequests', 'requestStats', 'departments', 'approvalDeadlineRequests'));
    }

    public function sendApprovalDeadlineReminder(Request $request, Requisition $requisition, NotificationService $notifications)
    {
        abort_unless($request->user()->role === 'smo', 403);

        $requisition->loadMissing(['user', 'items']);
        if (! $requisition->isApprovalDeadlineNearing() && ! $requisition->isApprovalDeadlineOverdue()) {
            return back()->with('error', 'This requisition is no longer at an approval deadline risk.');
        }

        $reviewerRole = $requisition->currentReviewerRole();
        if (! in_array($reviewerRole, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president'], true)) {
            return back()->with('error', 'This requisition no longer has an administrator awaiting action.');
        }

        $recipients = User::query()
            ->where('role', $reviewerRole)
            ->where('account_status', User::ACCOUNT_ACTIVE)
            ->when(
                $reviewerRole === 'dept_head',
                fn ($users) => $users->where('department', $requisition->user?->department)
            )
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No active approver is assigned to this requisition stage.');
        }

        $state = $requisition->isApprovalDeadlineOverdue() ? 'at or past its deadline' : 'nearing its deadline';
        $created = 0;
        $delivered = 0;
        foreach ($recipients as $recipient) {
            $notification = $notifications->send(
                $recipient,
                'deadline.approval_reminder',
                'SMO Approval Deadline Reminder',
                "Requisition {$requisition->tracking_code} is {$state} and needs your review. — SMO",
                [
                    'icon' => 'clock',
                    'type' => $requisition->isApprovalDeadlineOverdue() ? 'danger' : 'warning',
                    'action_type' => 'approval_queue',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "deadline-reminder:{$requisition->id}:{$reviewerRole}:".now()->toDateString(),
                ]
            );
            $delivered += (int) ($notification !== null);
            $created += (int) ($notification?->wasRecentlyCreated === true);
        }

        if ($delivered === 0) {
            return back()->with('error', 'The reminder could not be delivered. Please try again.');
        }

        return back()->with(
            'success',
            $created > 0
                ? 'The current approver has been notified about the deadline.'
                : 'The current approver was already notified about this requisition today.'
        );
    }

    /**
     * Archived Requisitions (Released & Rejected)
     */
    public function archive(ArchiveIndexRequest $request)
    {
        $user = Auth::user();
        $filters = $request->validated();
        $order = $filters['order'] ?? 'desc';
        $query = Requisition::query()
            ->archived()
            ->visibleTo($user)
            ->withArchiveFilters($filters, allowDepartment: $user->role === 'smo')
            ->with(['items', 'user'])
            ->orderBy('closed_at', $order)
            ->orderBy('id', $order);
        $archivedRequests = $request->boolean('print')
            ? $query->get()
            : $query->paginate(15)->withQueryString();
        $departments = $user->role === 'smo'
            ? User::whereNotNull('department')->where('department', '<>', '')->distinct()->orderBy('department')->pluck('department')
            : collect();

        return view('requests.archive', compact('archivedRequests', 'departments'));
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

        if ($user->role === 'smo') {
            $query->prioritizeExecutiveRequestors();
        }

        $pendingRequests = $query->latest()->get();

        // A notification can deep-link to one request, but only when that
        // request is already visible in the signed-in user's authorized queue.
        $focusedRequestId = request()->integer('request');
        $focusedRequest = $focusedRequestId > 0
            ? $pendingRequests->firstWhere('id', $focusedRequestId)
            : null;

        // Dedicated list for requests placed on hold / for clarification
        $clarificationRequests = Requisition::with([
                'items',
                'user',
                'logs' => fn ($query) => $query->orderBy('id'),
            ])
            ->where('status', 'for_clarification')
            ->latest()
            ->get();

        // SMO coordinates every inquiry; signatories only handle inquiries they initiated.
        if ($user->role !== 'smo') {
            $clarificationRequests = $clarificationRequests
                ->filter(function ($requisition) use ($user) {
                    $clarificationLog = $requisition->logs
                        ->filter(fn ($log) => str_contains($log->action, 'Clarification'))
                        ->sortByDesc('id')
                        ->first();

                    return $clarificationLog?->role === $user->role;
                })
                ->values();
        }

        if ($user->role === 'smo') {
            $pendingMinor = $pendingRequests->where('request_type', 'minor');
            $pendingMajor = $pendingRequests->where('request_type', 'major');

            return view('admin.approvals', compact('pendingMinor', 'pendingMajor', 'clarificationRequests', 'focusedRequest'));
        }

        return view('admin.approvals', compact('pendingRequests', 'clarificationRequests', 'focusedRequest'));
    }

    /**
     * Update Approval Status (Signatories)
     */
    public function updateStatus(Request $request, $id, NotificationService $notifications)
    {
        $request->validate([
            'status'  => 'required|in:approved,rejected,clarification',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $sr = Requisition::with(['items', 'user'])->findOrFail($id);
        $role = Auth::user()->role;

        abort_unless(
            $this->canReviewRequisition($sr, Auth::user()),
            403,
            'This requisition is not assigned to your approval stage.'
        );

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
                'danger',
                'requisition.rejected',
                'requisition',
                $sr->id,
                "requisition:{$sr->id}:rejected:{$role}"
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
                'warning',
                'requisition.clarification_requested',
                'requisition',
                $sr->id,
                "requisition:{$sr->id}:clarification:{$role}"
            );

            // Notify SMO In-Charge on Screen
            $smoUsers = User::where('role', 'smo')->get();
            foreach ($smoUsers as $smo) {
                $this->sendAlert(
                    $smo->id,
                    "⚠️ Inquiry on Req {$sr->tracking_code}",
                    "{$position} requested a meeting regarding {$sr->tracking_code} ({$sr->user->name}). Please coordinate with their office.",
                    'users',
                    'warning',
                    'requisition.clarification_coordination',
                    'requisition',
                    $sr->id,
                    "requisition:{$sr->id}:clarification-smo:{$role}"
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
                'success',
                'requisition.endorsed',
                'requisition',
                $sr->id,
                "requisition:{$sr->id}:endorsed:{$role}"
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

        if ($request->status === 'approved') {
            $nextRole = $sr->nextReviewerRole();

            if ($nextRole) {
                $notifications->sendToRole($nextRole, 'requisition.awaiting_approval', 'Requisition Ready for Action',
                    "Requisition {$sr->tracking_code} is ready for your review.", [
                        'icon' => 'file-text',
                        'type' => $nextRole === 'smo' ? 'warning' : 'info',
                        'action_type' => 'approval_queue',
                        'action_id' => $sr->id,
                        'dedupe_key' => "requisition:{$sr->id}:log:{$log->id}:next:{$nextRole}",
                    ]);
            }
        }

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
     * Confirm that the signed-in signatory owns the requisition's current stage.
     */
    private function canReviewRequisition(Requisition $requisition, User $user): bool
    {
        return $user->role !== 'smo' && $requisition->canBeReviewedBy($user);
    }

    /**
     * SMO Confirmation & Release (Fulfillment)
     */
    public function releaseRequest($id, InventoryAlertService $alertService)
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

        return DB::transaction(function () use ($sr, $alertService) {
            $deductedItems = [];

            // Check and deduct items that exist in our inventory catalog
         foreach ($sr->items()->get() as $item) {
             // Prefer the immutable catalog link; keep the name fallback for legacy requests.
             $inventoryItem = $item->supply_id
                 ? Supply::find($item->supply_id)
                 : Supply::where('item_name', 'LIKE', trim($item->item_name))->first();

             if ($inventoryItem) {
                 if ($inventoryItem->quantity < $item->quantity) {
                     return back()->with('error', "Insufficient stock for '{$item->item_name}'. Available: {$inventoryItem->quantity}, Requested: {$item->quantity}.");
                 }
                 $wasOut = $inventoryItem->quantity <= 0;
                 $wasLow = $inventoryItem->quantity <= $inventoryItem->min_stock_level;
                 $inventoryItem->decrement('quantity', $item->quantity);
                 $inventoryItem->refresh();
                 $alertService->notifyIfThresholdCrossed($inventoryItem, $wasLow, $wasOut);
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
                'success',
                'requisition.released',
                'requisition',
                $sr->id,
                "requisition:{$sr->id}:released"
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

    public function show(Request $httpRequest, $id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature'])->findOrFail($id);
        abort_unless($request->canBeViewedBy($httpRequest->user()), 403);

        return view('requests.show', compact('request'));
    }

    public function downloadPDF(Request $httpRequest, $id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature'])->findOrFail($id);
        abort_unless($request->canBeViewedBy($httpRequest->user()), 403);

        $pdf = Pdf::loadView('requests.pdf', compact('request'))->setPaper('a4', 'portrait');

        return $pdf->download('HTC-Requisition-'.$request->tracking_code.'.pdf');
    }

    private function sendAlert(
        $userId,
        $title,
        $message,
        $icon = 'bell',
        $type = 'info',
        $eventType = 'system.notice',
        $actionType = null,
        $actionId = null,
        $dedupeKey = null,
    )
    {
        app(NotificationService::class)->send($userId, $eventType, $title, $message, [
            'icon' => $icon,
            'type' => $type,
            'action_type' => $actionType,
            'action_id' => $actionId,
            'dedupe_key' => $dedupeKey,
        ]);
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
