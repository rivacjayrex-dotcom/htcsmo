<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\SupplyController;
use App\Models\Notification;
use App\Models\Requisition;
use App\Models\Supply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Welcome Page
Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/terms-of-service', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');

// DASHBOARD LOGIC (Updated for Multi-item Requisitions)
Route::get('/dashboard', function () {
    $user = Auth::user();

    $activeCount = 0;
    $approvedLast24h = 0;
    $recentRequests = collect();

    if ($user->role !== 'smo') {
        // 1. BASE COUNTERS (Keep these to show overall health)
        $activeCount = Requisition::where('user_id', $user->id)
            ->whereNotIn('status', ['released', 'rejected'])
            ->count();

        $approvedLast24h = Requisition::where('user_id', $user->id)
            ->whereIn('status', ['approved_president', 'released'])
            ->where('updated_at', '>=', now()->subDay())
            ->count();

        // 2. EMPLOYEE TABLE: Filter to show ONLY Pending/In-Progress
        $recentRequests = Requisition::with('items')
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['released', 'rejected']) // <--- THE FILTER
            ->latest()
            ->get();
    }

    // SCHOOL ADMIN DASHBOARD: Show only requests currently assigned to this approver.
    $approvalRequests = collect();
    $approvalDeadlineRequests = collect();
    if (in_array($user->role, ['dept_head', 'vp_finance', 'vp_admin', 'provost', 'president'])) {
        $approvalQuery = Requisition::with(['items', 'user']);

        switch ($user->role) {
            case 'dept_head':
                $approvalQuery->where('status', 'pending')
                    ->whereHas('user', fn ($query) => $query->where('department', $user->department));
                break;
            case 'vp_finance':
                $approvalQuery->where('status', 'approved_dept')->where('request_type', 'minor');
                break;
            case 'vp_admin':
                $approvalQuery->where('status', 'approved_dept')->where('request_type', 'major');
                break;
            case 'provost':
                $approvalQuery->where('status', 'approved_vp')->where('request_type', 'major');
                break;
            case 'president':
                $approvalQuery->where('status', 'approved_provost')->where('request_type', 'major');
                break;
        }

        $approvalRequests = $approvalQuery->latest()->take(5)->get();
        $approvalDeadlineRequests = Requisition::with(['items', 'user'])
            ->approvalDeadlineRisks()
            ->assignedToReviewer($user)
            ->oldest('stage_started_at')
            ->take(6)
            ->get();
    }

    // 3. SMO SPECIFIC LOGIC
    $stats = ['total' => 0, 'pending' => 0, 'urgent' => 0, 'monthly_value' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
    $recentActivity = [];
    $allStaffRequests = [];
    $deadlineRequests = collect();
    $lowStockSupplies = collect();

    if ($user->role == 'smo') {
        $approvalDeadlineRequests = Requisition::with(['items', 'user'])
            ->approvalDeadlineRisks()
            ->oldest('stage_started_at')
            ->take(6)
            ->get();
        $requisitionStats = Requisition::query()->selectRaw(
            'COUNT(*) as total,
             COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN 1 ELSE 0 END), 0) as pending,
             COALESCE(SUM(CASE WHEN ((request_type = ? AND status = ?) OR (request_type = ? AND status = ?)) AND stage_started_at <= ? THEN 1 ELSE 0 END), 0) as urgent',
            ['released', 'rejected', 'minor', 'approved_vp', 'major', 'approved_president', now()->subDays(2)]
        )->first();
        $stats['total'] = (int) $requisitionStats->total;
        $stats['pending'] = (int) $requisitionStats->pending;
        $stats['urgent'] = (int) $requisitionStats->urgent;
        $deadlineRequests = Requisition::with(['items', 'user'])
            ->where(function ($query) {
                $query->where(function ($minor) {
                    $minor->where('request_type', 'minor')->where('status', 'approved_vp');
                })->orWhere(function ($major) {
                    $major->where('request_type', 'major')->where('status', 'approved_president');
                });
            })
            ->where('stage_started_at', '<=', now()->subDays(2))
            ->oldest('stage_started_at')
            ->take(4)
            ->get();
        $inventoryStats = Supply::query()->selectRaw(
            'COALESCE(SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END), 0) as low_stock,
             COALESCE(SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock'
        )->first();
        $stats['low_stock'] = (int) $inventoryStats->low_stock;
        $stats['out_of_stock'] = (int) $inventoryStats->out_of_stock;
        $lowStockSupplies = Supply::whereColumn('quantity', '<=', 'min_stock_level')
            ->orderByRaw('CASE WHEN quantity <= 0 THEN 0 ELSE 1 END')
            ->orderByRaw('(min_stock_level - quantity) DESC')
            ->take(4)
            ->get();
        $stats['monthly_value'] = Requisition::where('status', 'released')
            ->whereMonth('updated_at', now()->month)->sum('grand_total');

        // SMO TABLE: Only pull active items that still need to be processed or released
        $query = Requisition::with(['items', 'user'])
            ->whereNotIn('status', ['released', 'rejected']); // Exclude historical/released records

        // Filter by Status (Within Active Lifecycle)
        if (request('status')) {
            if (request('status') === 'near_deadline') {
                $query->where(function ($deadline) {
                    $deadline->where(function ($minor) {
                        $minor->where('request_type', 'minor')->where('status', 'approved_vp');
                    })->orWhere(function ($major) {
                        $major->where('request_type', 'major')->where('status', 'approved_president');
                    });
                })->where('stage_started_at', '<=', now()->subDays(2));
            } else {
                $query->where('status', request('status'));
            }
        }

        // Filter by Department
        if (request('dept')) {
            $query->whereHas('user', function ($q) {
                $q->where('department', request('dept'));
            });
        }

        // Filter by Date Range
        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        // Sorting
        $sort = request('sort', 'created_at');
        $order = request('order', 'desc');
        $allStaffRequests = $query->orderBy($sort, $order)->paginate(20)->withQueryString();

        $recentActivity = Notification::where('user_id', $user->id)->latest()->take(5)->get();
    }

    return view('dashboard', compact(
        'activeCount', 'approvedLast24h', 'recentRequests',
        'stats', 'allStaffRequests', 'recentActivity', 'approvalRequests',
        'deadlineRequests', 'approvalDeadlineRequests', 'lowStockSupplies'
    ));
})->middleware(['auth'])->name('dashboard');

// EMPLOYEE REQUEST & ARCHIVE ROUTES
Route::middleware(['auth'])->group(function () {
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/archive', [RequestController::class, 'archive'])->name('requests.archive');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}/download', [RequestController::class, 'downloadPDF'])->name('requests.download');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
});

// SMO INVENTORY & USER MANAGEMENT ROUTES
Route::middleware(['auth', 'role:smo'])->group(function () {
    // Inventory Management
    Route::get('/inventory', [SupplyController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/forecast/report', [SupplyController::class, 'forecastReport'])->name('inventory.forecast.report');
    Route::get('/inventory/create', [SupplyController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [SupplyController::class, 'store'])->name('inventory.store');

    // --- ADDED THESE INVENTORY ROUTES ---
    Route::get('/inventory/{id}/edit', [SupplyController::class, 'edit'])->name('inventory.edit');
    Route::patch('/inventory/{id}', [SupplyController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/bulk', [SupplyController::class, 'destroyMany'])->name('inventory.bulk-destroy');
    Route::delete('/inventory/{id}', [SupplyController::class, 'destroy'])->name('inventory.destroy');
    // -------------------------------------

    // USER ACCESS MANAGEMENT
    Route::get('/admin/users/pending', [ProfileController::class, 'pendingUsers'])->name('admin.users.pending');
    Route::post('/admin/users/{id}/approve', [ProfileController::class, 'approveUser'])->whereNumber('id')->name('admin.users.approve');
});

// PROFILE ROUTES (Standard Laravel)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';

// ADMIN APPROVAL ROUTES
Route::middleware(['auth', 'role:dept_head,vp_finance,vp_admin,provost,president,smo'])->group(function () {
    Route::get('/admin/approvals', [RequestController::class, 'adminIndex'])->name('admin.approvals');
    Route::post('/admin/requests/{id}/status', [RequestController::class, 'updateStatus'])->name('admin.status.update');
    Route::post('/admin/requests/{id}/release', [RequestController::class, 'releaseRequest'])->name('admin.requests.release');
    Route::post('/admin/requests/{requisition}/deadline-reminder', [RequestController::class, 'sendApprovalDeadlineReminder'])
        ->middleware('role:smo')->whereNumber('requisition')->name('admin.requests.deadline-reminder');
});

Route::middleware(['auth', 'role:smo,president,provost,vp_admin,dept_head'])->group(function () {
    Route::get('/admin/users/manage', [ProfileController::class, 'manageUsers'])->name('admin.users.manage');
    Route::patch('/admin/users/{user}', [ProfileController::class, 'updateManagedUser'])->whereNumber('user')->name('admin.users.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
});

// Change the submission route to point to the new Requisition Controller
Route::post('/requisitions', [RequisitionController::class, 'store'])->middleware('auth')->name('requisitions.store');
Route::get('/api/requisition-catalog', [SupplyController::class, 'requisitionCatalog'])
    ->middleware('auth')
    ->name('api.requisition-catalog');

Route::get('/api/latest-notification', [NotificationController::class, 'poll'])
    ->middleware(['auth', 'throttle:20,1']);
Route::get('/api/system-version', [RequestController::class, 'systemVersion'])
    ->middleware('auth')
    ->name('api.system-version');
Route::get('/api/runtime-state', [RequestController::class, 'runtimeState'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('api.runtime-state');

// Requestors may manage only their own pending requisitions.
Route::middleware('auth')->group(function () {
    Route::get('/requisitions/{id}/edit', [RequisitionController::class, 'edit'])->name('requisitions.edit');
    Route::patch('/requisitions/{id}', [RequisitionController::class, 'update'])->name('requisitions.update');
    Route::delete('/requisitions/{id}', [RequisitionController::class, 'destroy'])->name('requisitions.destroy');
});

// Statistics and Reports Page
// SMO REPORTING & ANALYTICS
Route::middleware(['auth', 'role:smo'])->group(function () {
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/generate', [ReportController::class, 'generate'])->name('admin.reports.generate');

    // Preserve existing bookmarks while directing users to the unified workspace.
    Route::redirect('/admin/reports/analytics', '/admin/reports')->name('admin.reports.analytics');
    Route::redirect('/admin/reports/builder', '/admin/reports')->name('admin.reports.builder');
});

Route::get('/admin/archive', [ArchiveController::class, 'index'])
    ->middleware(['auth', 'role:smo'])
    ->name('admin.archive');
