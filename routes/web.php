<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequisitionController;
use App\Models\SupplyRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Requisition;
use App\Http\Controllers\ReportController;


// Welcome Page
Route::get('/', function () {
    return redirect()->route('login');
});

// DASHBOARD LOGIC (Updated for Multi-item Requisitions)
Route::get('/dashboard', function () {
    $user = Auth::user();

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

    // SCHOOL ADMIN DASHBOARD: Show only requests currently assigned to this approver.
    $approvalRequests = collect();
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
    }

    // 3. SMO SPECIFIC LOGIC
    $stats = ['total' => 0, 'pending' => 0, 'urgent' => 0, 'monthly_value' => 0];
    $recentActivity = [];
    $allStaffRequests = [];

    if ($user->role == 'smo') {
        $stats['total'] = Requisition::count();
        $stats['pending'] = Requisition::whereNotIn('status', ['released', 'rejected'])->count();
        $stats['urgent'] = Requisition::whereIn('status', ['approved_president', 'approved_vp'])
            ->where('updated_at', '<=', now()->subDays(2))->count();
        $stats['monthly_value'] = Requisition::where('status', 'released')
            ->whereMonth('updated_at', now()->month)->sum('grand_total');

        // SMO TABLE: Only pull active items that still need to be processed or released
        $query = Requisition::with(['items', 'user'])
            ->whereNotIn('status', ['released', 'rejected']); // Exclude historical/released records

        // Filter by Status (Within Active Lifecycle)
        if (request('status')) {
            if (request('status') === 'near_deadline') {
                $query->whereIn('status', ['approved_president', 'approved_vp'])
                      ->where('updated_at', '<=', now()->subDays(2));
            } else {
                $query->where('status', request('status'));
            }
        }

        // Filter by Department
        if (request('dept')) {
            $query->whereHas('user', function($q) {
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

        $recentActivity = \App\Models\Notification::where('user_id', $user->id)->latest()->take(5)->get();
    }

    return view('dashboard', compact(
        'activeCount', 'approvedLast24h', 'recentRequests',
        'stats', 'allStaffRequests', 'recentActivity', 'approvalRequests'
    ));
})->middleware(['auth'])->name('dashboard');

// EMPLOYEE REQUEST & ARCHIVE ROUTES
    Route::middleware(['auth'])->group(function () {
        Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/archive', [RequestController::class, 'archive'])->name('requests.archive'); // <--- ADD THIS LINE
        Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    });

    // SMO INVENTORY & USER MANAGEMENT ROUTES
    Route::middleware(['auth', 'role:smo'])->group(function () {
        // Inventory Management
        Route::get('/inventory', [SupplyController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/create', [SupplyController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [SupplyController::class, 'store'])->name('inventory.store');

        // --- ADDED THESE INVENTORY ROUTES ---
        Route::get('/inventory/{id}/edit', [SupplyController::class, 'edit'])->name('inventory.edit');
        Route::patch('/inventory/{id}', [SupplyController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{id}', [SupplyController::class, 'destroy'])->name('inventory.destroy');
        // -------------------------------------

        // USER ACCESS MANAGEMENT
        Route::get('/admin/users/pending', [ProfileController::class, 'pendingUsers'])->name('admin.users.pending');
        Route::post('/admin/users/{id}/approve', [ProfileController::class, 'approveUser'])->name('admin.users.approve');
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
    });

    Route::middleware(['auth', 'role:smo,president,provost,vp_admin,dept_head'])->group(function () {
        Route::get('/admin/users/manage', [ProfileController::class, 'manageUsers'])->name('admin.users.manage');
        Route::patch('/admin/users/{user}', [ProfileController::class, 'updateManagedUser'])->name('admin.users.update');
    });

    Route::get('/notifications', [RequestController::class, 'notifications'])->name('notifications');

    // Change the submission route to point to the new Requisition Controller
    Route::post('/requisitions', [RequisitionController::class, 'store'])->middleware('auth')->name('requisitions.store');


    Route::get('/requests/{id}/download', [RequestController::class, 'downloadPDF'])->name('requests.download');

    // Route for the "Details" / Shopee-style tracker page
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');


    Route::get('/api/latest-notification', [RequestController::class, 'getLatestNotification']);
    Route::get('/api/system-version', [RequestController::class, 'systemVersion'])
        ->middleware('auth')
        ->name('api.system-version');


    // Requestors may manage only their own pending requisitions.
    Route::middleware('auth')->group(function () {
        Route::get('/requisitions/{id}/edit', [RequisitionController::class, 'edit'])->name('requisitions.edit');
        Route::patch('/requisitions/{id}', [RequisitionController::class, 'update'])->name('requisitions.update');
        Route::delete('/requisitions/{id}', [RequisitionController::class, 'destroy'])->name('requisitions.destroy');
    });

    //Statistics and Reports Page
    // SMO REPORTING & ANALYTICS
    Route::middleware(['auth', 'role:smo'])->group(function () {
        Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/admin/reports/generate', [ReportController::class, 'generate'])->name('admin.reports.generate');

        // Preserve existing bookmarks while directing users to the unified workspace.
        Route::redirect('/admin/reports/analytics', '/admin/reports')->name('admin.reports.analytics');
        Route::redirect('/admin/reports/builder', '/admin/reports')->name('admin.reports.builder');
    });

    //Archive Page route
    Route::get('/admin/archive', [App\Http\Controllers\ArchiveController::class, 'index'])->name('admin.archive');
