<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActiveRequestIndexRequest;
use App\Http\Requests\ArchiveIndexRequest;
use App\Jobs\SendRequisitionEmail;
use App\Models\ApprovalLog;
use App\Models\Department;
use App\Models\DigitalSignature;
use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\DeadlineNotificationService;
use App\Services\InventoryAlertService;
use App\Services\NotificationActionResolver;
use App\Services\NotificationService;
use App\Services\WorkspaceVersionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
    public function runtimeState(Request $request, NotificationActionResolver $actionResolver)
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
            'scope' => ['nullable', 'in:all,dashboard,inventory,requests,approvals,users,notifications,none'],
        ]);
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
        $resolvedActions = $actionResolver->resolve($notifications, $user);
        $payloads = $notifications->map(
            fn (Notification $notification) => $notification->pollPayload(
                $user,
                $resolvedActions[$notification->id] ?? null,
                true,
            )
        )->values();
        $latestId = (int) $notificationState->latest_id;
        $versions = app(WorkspaceVersionService::class)->versions();
        $versions['dashboard'] = app(WorkspaceVersionService::class)->dashboardVersion($versions['all'], $latestId);

        return response()->json([
            'version' => $versions[$validated['scope'] ?? 'all'],
            'versions' => $versions,
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
        return app(WorkspaceVersionService::class)->version();
    }

    /**
     * Active Requisitions (Pending, In-Progress, Approved)
     */
    public function index(ActiveRequestIndexRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $scopeQuery = Requisition::query()
            ->whereNotIn('status', Requisition::ARCHIVED_STATUSES);

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
            'deadline' => (clone $scopeQuery)->deadlineRisks()->count(),
        ];

        $query = (clone $scopeQuery)->with(['items', 'user.academicDepartment']);
        if ($user->role === 'employee') {
            $query->with('latestClarificationLog');
            $requestStats['pickup'] = (clone $scopeQuery)->where('status', 'ready_for_pickup')->count();
        }

        $query->search($validated['search'] ?? null);
        if ($request->filled('status')) {
            if ($user->role === 'employee' && $request->status === 'action_required') {
                $query->needingRequestorAction();
            } else {
                $request->status === 'deadline'
                    ? $query->deadlineRisks()
                    : $query->where('status', $request->status);
            }
        }
        if ($user->role == 'smo' && $request->filled('dept')) {
            $query->whereHas('user', fn ($q) => $q->inDepartment($request->dept));
        }
        if ($request->filled('date_from')) {
            $user->role === 'employee'
                ? $query->whereRaw('COALESCE(submitted_at, created_at) >= ?', [Carbon::parse($request->date_from)->startOfDay()])
                : $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $user->role === 'employee'
                ? $query->whereRaw('COALESCE(submitted_at, created_at) < ?', [Carbon::parse($request->date_to)->startOfDay()->addDay()])
                : $query->whereDate('created_at', '<=', $request->date_to);
        }

        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $user->role === 'employee'
            ? $query->orderByRaw('COALESCE(submitted_at, created_at) '.$order)->orderBy('id', $order)
            : $query->orderBy('created_at', $order);
        $activeRequests = $query->paginate(15)->withQueryString();
        $departments = $user->role === 'smo'
            ? Department::query()->whereHas('users')->orderBy('dept_code')->pluck('dept_code')
            : collect();

        $approvalDeadlineRequests = collect();
        if ($user->role === 'smo' || in_array($user->role, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president'], true)) {
            $deadlineQuery = Requisition::query()
                ->with(['items', 'user.academicDepartment'])
                ->approvalDeadlineRisks();

            if ($user->role !== 'smo') {
                $deadlineQuery->assignedToReviewer($user);
            }

            $approvalDeadlineRequests = $deadlineQuery
                ->oldest('due_at')
                ->take(8)
                ->get();
        }

        return view('requests.index', compact('activeRequests', 'requestStats', 'departments', 'approvalDeadlineRequests'));
    }

    public function sendApprovalDeadlineReminder(Request $request, Requisition $requisition, DeadlineNotificationService $deadlineNotifications)
    {
        abort_unless($request->user()->role === 'smo', 403);

        $requisition->loadMissing(['user', 'items']);
        if (! $requisition->isDeadlineNearing() && ! $requisition->isDeadlineOverdue()) {
            return back()->with('error', 'This requisition is no longer at a deadline risk.');
        }

        $result = $deadlineNotifications->notify($requisition);

        if ($result['delivered'] === 0) {
            return back()->with('error', 'The reminder could not be delivered. Please try again.');
        }

        return back()->with(
            'success',
            $result['created'] > 0
                ? 'The requestor, SMO, and current approver have been notified about the deadline.'
                : 'All deadline recipients were already notified about this requisition today.'
        );
    }

    /**
     * Archived requisitions, scoped to their owner unless the viewer is SMO.
     */
    public function archive(ArchiveIndexRequest $request, ArchiveService $archives)
    {
        return $archives->index($request);
    }

    /**
     * Admin/Signatory Approval Queue
     */
    public function adminIndex()
    {
        $user = Auth::user();

        // Standard pending query
        $query = Requisition::with(['items', 'user.academicDepartment']);

        switch ($user->role) {
            case 'dept_head':
                $query->where('status', 'pending')
                    ->whereHas('user', fn ($u) => $u->where('department_id', $user->department_id));
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
                    })->orWhereIn('status', ['processing', 'ready_for_pickup']);
                });
                break;
            default:
                $query->whereRaw('1 = 0');
                break;
        }

        if ($user->role === 'smo') {
            $query->prioritizeExecutiveRequestors();
        }

        // Dedicated list for requests placed on hold / for clarification
        $clarificationRequests = Requisition::with([
            'items',
            'user.academicDepartment',
            'logs' => fn ($query) => $query->orderBy('id'),
        ])
            ->where('status', 'for_clarification')
            ->latest();

        // SMO coordinates every inquiry; signatories only handle inquiries they initiated.
        if ($user->role !== 'smo') {
            $clarificationRequests->whereRaw(
                '(SELECT role FROM approval_logs WHERE requisition_id = requisitions.id AND action LIKE ? ORDER BY id DESC LIMIT 1) = ?',
                ['%Clarification%', $user->role],
            );
        }

        $focusedRequestId = request()->integer('request');
        $focusedRequest = $focusedRequestId > 0
            ? ((clone $query)->find($focusedRequestId) ?? (clone $clarificationRequests)->find($focusedRequestId))
            : null;
        $clarificationRequests = $clarificationRequests->orderByDesc('id')->paginate(15, ['*'], 'inquiry_page')
            ->withQueryString()->appends(['tab' => 'clarification', 'request' => null]);

        if ($user->role === 'smo') {
            $pendingMinor = (clone $query)->where('request_type', 'minor')->latest()->orderByDesc('id')->paginate(15, ['*'], 'minor_page')
                ->withQueryString()->appends(['tab' => 'minor', 'request' => null]);
            $pendingMajor = (clone $query)->where('request_type', 'major')->latest()->orderByDesc('id')->paginate(15, ['*'], 'major_page')
                ->withQueryString()->appends(['tab' => 'major', 'request' => null]);

            return view('admin.approvals', compact('pendingMinor', 'pendingMajor', 'clarificationRequests', 'focusedRequest'));
        }

        $pendingRequests = $query->latest()->orderByDesc('id')->paginate(15)
            ->withQueryString()->appends(['tab' => 'pending', 'request' => null]);

        return view('admin.approvals', compact('pendingRequests', 'clarificationRequests', 'focusedRequest'));
    }

    /**
     * Update Approval Status (Signatories)
     */
    public function updateStatus(Request $request, int $id, NotificationService $notifications)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,clarification',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $role = Auth::user()->role;

        DB::beginTransaction();

        try {
            // Lock the workflow row so two reviewers cannot advance the same
            // stage concurrently and generate contradictory notifications.
            $sr = Requisition::with(['items', 'user.academicDepartment'])->lockForUpdate()->findOrFail($id);

            abort_unless(
                $this->canReviewRequisition($sr, Auth::user()),
                403,
                'This requisition is not assigned to your approval stage.'
            );

            $roleTitles = [
                'dept_head' => 'Department Head',
                'vp_finance' => 'VP for Finance',
                'vp_admin' => 'VP for Administration',
                'provost' => 'School Provost',
                'president' => 'School President',
                'smo' => 'SMO In-Charge',
            ];
            $position = $roleTitles[$role] ?? ucwords(str_replace('_', ' ', $role));

            $sr->remarks = $request->remarks;

            // 1. REJECTED
            if ($request->status === 'rejected') {
                $sr->status = 'rejected';
                $actionText = 'Rejected by '.$position;

                $this->sendAlert(
                    $sr->user_id,
                    'Requisition Disapproved',
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
                $actionText = 'Flagged for Office Clarification by '.$position;

                // Notify Requestor on Screen
                $this->sendAlert(
                    $sr->user_id,
                    '⚠️ Office Appearance Requested',
                    "Your request {$sr->tracking_code} requires clarification. Please proceed to the office of {$position} together with SMO. Note: {$request->remarks}",
                    'help-circle',
                    'warning',
                    'requisition.clarification_requested',
                    'requisition',
                    $sr->id,
                    "requisition:{$sr->id}:clarification:{$role}"
                );

                // Notify SMO In-Charge on Screen
                $smoUsers = User::where('role', 'smo')->where('account_status', User::ACCOUNT_ACTIVE)->get();
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
                $actionText = 'Approved by '.$position;

                $this->sendAlert(
                    $sr->user_id,
                    'Requisition Endorsed',
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
                'acted_by_user_id' => Auth::id(),
                'action' => $actionText,
                'role' => $role,
                'remarks' => $request->remarks,
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

            // 5. Generate an internal approval audit token.
            if ($request->status === 'approved') {
                $rawPayload = "REQ-{$sr->id}|USER-".Auth::id()."|ROLE-{$role}|TIME-".now()->timestamp.'|SECRET-'.config('app.key');
                $signatureToken = hash('sha256', $rawPayload);

                DigitalSignature::create([
                    'approval_log_id' => $log->id,
                    'signature_token' => $signatureToken,
                    'signer_name' => Auth::user()->name,
                    'signer_role' => $position,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'signed_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        // 6. TRIGGER OUTBOUND EMAIL NOTIFICATION FOR ALL 3 DECISIONS
        $accent = match ($request->status) {
            'approved' => '#185b3b', // HTC Green
            'clarification' => '#fd7e14', // Warning Amber
            default => '#dc3545'  // Danger Red
        };

        $headline = match ($request->status) {
            'approved' => "Requisition Endorsed by {$position}",
            'clarification' => "⚠️ Office Appearance Required: {$position}",
            default => "Requisition Disapproved by {$position}"
        };

        $emailMsg = match ($request->status) {
            'approved' => "Your requisition has been reviewed and endorsed by {$position}. It will now proceed to the next stage.",
            'clarification' => "The {$position} has requested a consultation regarding this request. Please report to their office together with the SMO In-Charge. Notes: ".($request->remarks ?? 'None'),
            default => "Your requisition was disapproved by {$position}. Reason: ".($request->remarks ?? 'Not specified')
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
     * Move a fully approved requisition into active SMO processing.
     */
    public function startProcessing(int $id, NotificationService $notifications)
    {
        if (Auth::user()->role !== 'smo') {
            return back()->with('error', 'Unauthorized. Only the SMO In-Charge can process supplies.');
        }

        $outcome = DB::transaction(function () use ($id, $notifications) {
            $requisition = Requisition::with(['items', 'user.academicDepartment'])->lockForUpdate()->findOrFail($id);
            $isFullyApproved = ($requisition->request_type === 'minor' && $requisition->status === 'approved_vp')
                || ($requisition->request_type === 'major' && $requisition->status === 'approved_president');

            if (! $isFullyApproved) {
                return ['error' => 'This requisition has not completed its required approval route.'];
            }

            $requisition->update(['status' => 'processing']);
            ApprovalLog::create([
                'requisition_id' => $requisition->id,
                'acted_by_user_id' => Auth::id(),
                'action' => 'Processing Started by SMO In-Charge',
                'role' => 'smo',
                'remarks' => 'The approved requisition entered SMO processing.',
            ]);

            $notifications->send($requisition->user, 'requisition.processing', 'SMO Processing Started',
                "Your requisition {$requisition->tracking_code} is now being processed by the SMO.", [
                    'icon' => 'package-open', 'type' => 'info', 'action_type' => 'requisition',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "requisition:{$requisition->id}:processing",
                ]);

            return ['requisition' => $requisition];
        });

        if (isset($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        return redirect()->route('admin.approvals')->with('success', "Requisition #{$outcome['requisition']->id} is now being processed.");
    }

    /**
     * Deduct prepared stock and notify the requestor that pickup is available.
     */
    public function markReadyForPickup(int $id, InventoryAlertService $alertService)
    {
        // Only SMO can release supplies.
        if (Auth::user()->role !== 'smo') {
            return back()->with('error', 'Unauthorized. Only the SMO In-Charge can release supplies.');
        }

        $outcome = DB::transaction(function () use ($id, $alertService) {
            $sr = Requisition::with(['items', 'user.academicDepartment'])->lockForUpdate()->findOrFail($id);

            if (in_array($sr->status, ['ready_for_pickup', 'completed', 'released'], true)) {
                return ['error' => 'This requisition has already been prepared for pickup.'];
            }

            if ($sr->status !== 'processing') {
                return ['error' => 'This requisition must enter SMO processing before it can be prepared for pickup.'];
            }

            $deductedItems = [];

            // Lock and aggregate every catalog item before changing stock. This
            // prevents concurrent releases and avoids partial deductions when
            // a later line is unavailable or duplicate cart lines share stock.
            $linkedSupplies = Supply::query()
                ->whereIn('id', $sr->items->pluck('supply_id')->filter()->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $legacySupplies = [];
            $inventoryPlans = [];

            foreach ($sr->items as $item) {
                if ($item->supply_id) {
                    $inventoryItem = $linkedSupplies->get($item->supply_id);
                } else {
                    $legacyKey = mb_strtolower(trim($item->item_name));
                    if (! array_key_exists($legacyKey, $legacySupplies)) {
                        $legacySupplies[$legacyKey] = Supply::query()
                            ->where('item_name', 'LIKE', trim($item->item_name))
                            ->lockForUpdate()
                            ->first();
                    }
                    $inventoryItem = $legacySupplies[$legacyKey];
                }

                if (! $inventoryItem) {
                    continue;
                }

                $inventoryPlans[$inventoryItem->id] ??= [
                    'supply' => $inventoryItem,
                    'quantity' => 0,
                ];
                $inventoryPlans[$inventoryItem->id]['quantity'] += (int) $item->quantity;
            }

            foreach ($inventoryPlans as $plan) {
                $inventoryItem = $plan['supply'];
                $requestedQuantity = $plan['quantity'];
                if ($inventoryItem->quantity < $requestedQuantity) {
                    return ['error' => "Insufficient stock for '{$inventoryItem->item_name}'. Available: {$inventoryItem->quantity}, Requested: {$requestedQuantity}."];
                }
            }

            foreach ($inventoryPlans as $plan) {
                $inventoryItem = $plan['supply'];
                $requestedQuantity = $plan['quantity'];
                $wasOut = $inventoryItem->quantity <= 0;
                $wasLow = $inventoryItem->quantity <= $inventoryItem->min_stock_level;
                $inventoryItem->decrement('quantity', $requestedQuantity);
                $inventoryItem->refresh();
                $alertService->notifyIfThresholdCrossed($inventoryItem, $wasLow, $wasOut);

                SupplyInventoryLog::create([
                    'supply_id' => $inventoryItem->id,
                    'user_id' => Auth::id(),
                    'requisition_id' => $sr->id,
                    'transaction_type' => 'release_deduction',
                    'quantity_change' => -$requestedQuantity,
                    'balance_after' => $inventoryItem->quantity,
                    'remarks' => "Fulfillment of {$sr->tracking_code} for {$sr->user->name}",
                ]);

                $deductedItems[] = "{$inventoryItem->item_name} (-{$requestedQuantity})";
            }

            // Keep pickup readiness distinct from final completion.
            $sr->status = 'ready_for_pickup';
            $sr->save();

            // Create detailed audit log
            $logRemarks = count($deductedItems) > 0
                ? 'Prepared for pickup by SMO. Stock deducted: '.implode(', ', $deductedItems)
                : 'Prepared for pickup by SMO. Special requisition processed.';

            $log = ApprovalLog::create([
                'requisition_id' => $sr->id,
                'acted_by_user_id' => Auth::id(),
                'action' => 'Marked Ready for Pickup by SMO In-Charge',
                'role' => 'smo',
                'remarks' => $logRemarks,
            ]);

            // Generate an internal fulfillment audit token.
            $rawPayload = "READY-{$sr->id}|USER-".Auth::id().'|TIME-'.now()->timestamp.'|SECRET-'.config('app.key');
            $signatureToken = hash('sha256', $rawPayload);

            DigitalSignature::create([
                'approval_log_id' => $log->id,
                'signature_token' => $signatureToken,
                'signer_name' => Auth::user()->name,
                'signer_role' => 'SMO In-Charge',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'signed_at' => now(),
            ]);

            // Notify Requester
            $this->sendAlert(
                $sr->user_id,
                'Supplies Ready for Pickup',
                "Your requisition #{$sr->id} has been fulfilled by the SMO and is ready for pickup.",
                'package-check',
                'success',
                'requisition.ready_for_pickup',
                'requisition',
                $sr->id,
                "requisition:{$sr->id}:ready-for-pickup"
            );

            return ['requisition' => $sr];
        });

        if (isset($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        $sr = $outcome['requisition'];
        $this->sendEmailNotification(
            $sr->user->email,
            $sr->user,
            $sr,
            'Supplies Fulfilled and Ready for Pickup',
            'Your Supplies Are Ready!',
            "All requested items under {$sr->tracking_code} have been verified, deducted from inventory, and prepared by the SMO.",
            '#185b3b'
        );

        return redirect()->route('admin.approvals')->with('success', "Requisition #{$sr->id} is ready for pickup.");
    }

    /**
     * Close the requisition after the requestor receives the prepared items.
     */
    public function completeRequest(int $id, NotificationService $notifications)
    {
        if (Auth::user()->role !== 'smo') {
            return back()->with('error', 'Unauthorized. Only the SMO In-Charge can complete requests.');
        }

        $outcome = DB::transaction(function () use ($id, $notifications) {
            $requisition = Requisition::with('user')->lockForUpdate()->findOrFail($id);

            if ($requisition->status !== 'ready_for_pickup') {
                return ['error' => 'Only a requisition that is ready for pickup can be completed.'];
            }

            $requisition->update(['status' => 'completed']);
            ApprovalLog::create([
                'requisition_id' => $requisition->id,
                'acted_by_user_id' => Auth::id(),
                'action' => 'Completed by SMO In-Charge',
                'role' => 'smo',
                'remarks' => 'The prepared supplies were released to the requestor.',
            ]);

            $notifications->send($requisition->user, 'requisition.completed', 'Requisition Completed',
                "Your requisition {$requisition->tracking_code} has been released and marked complete.", [
                    'icon' => 'circle-check', 'type' => 'success', 'action_type' => 'requisition',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "requisition:{$requisition->id}:completed",
                ]);

            return ['requisition' => $requisition];
        });

        if (isset($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        return redirect()->route('admin.approvals')->with('success', "Requisition #{$outcome['requisition']->id} has been completed.");
    }

    public function show(Request $httpRequest, int $id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature', 'logs.actor', 'latestClosureLog.actor'])->findOrFail($id);
        abort_unless($request->canBeViewedBy($httpRequest->user()), 403);
        $archiveReturnUrl = app(ArchiveService::class)->returnUrl($httpRequest);
        $activeReturnUrl = null;
        if ($httpRequest->boolean('from_list') && is_array($httpRequest->query('list_filters', []))) {
            $filters = Validator::make($httpRequest->query('list_filters', []), ActiveRequestIndexRequest::filterRules($httpRequest->user()));
            if ($filters->passes()) {
                $activeReturnUrl = route('requests.index', $filters->validated()).'#request-results';
            }
        }

        return view('requests.show', compact('request', 'archiveReturnUrl', 'activeReturnUrl'));
    }

    public function downloadPDF(Request $httpRequest, int $id)
    {
        $request = Requisition::with(['user', 'items', 'logs.digitalSignature'])->findOrFail($id);
        abort_unless($request->canBeViewedBy($httpRequest->user()), 403);

        $pdf = Pdf::loadView('requests.pdf', compact('request'))->setPaper('a4', 'portrait');

        return $pdf->download('HTC-Requisition-'.$request->tracking_code.'.pdf');
    }

    private function sendAlert(
        int $userId,
        string $title,
        string $message,
        string $icon = 'bell',
        string $type = 'info',
        string $eventType = 'system.notice',
        ?string $actionType = null,
        ?int $actionId = null,
        ?string $dedupeKey = null,
    ): void {
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
    private function sendEmailNotification(
        string $recipientEmail,
        ?User $user,
        Requisition $requisition,
        string $subject,
        string $headline,
        string $message,
        string $accentColor = '#185b3b',
    ): void {
        try {
            SendRequisitionEmail::dispatch(
                $recipientEmail, $user?->id, $requisition->id,
                $subject, $headline, $message, $accentColor,
            );
        } catch (\Exception $e) {
            Log::error('Could not queue requisition email.', [
                'requisition_id' => $requisition->id, 'exception' => $e->getMessage(),
            ]);
        }
    }
}
