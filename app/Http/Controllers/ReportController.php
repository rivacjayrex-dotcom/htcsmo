<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLog;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\User;
use App\Models\UserActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'requisitions',
        'inventory',
        'dept_spending',
        'users',
        'item_consumption',
        'approval_audit',
        'inventory_movements',
        'user_activity',
    ];

    /**
     * Display operational analytics and the report builder in one workspace.
     */
    public function index(Request $request)
    {
        $request->validate([
            'report_type' => ['nullable', 'in:'.implode(',', self::REPORT_TYPES)],
            'period' => ['nullable', 'in:current_semester,this_month,this_year,all,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$semesterStart, $semesterEnd, $semesterLabel] = $this->currentSemester();

        $releasedThisSemester = Requisition::where('status', 'released')
            ->whereBetween('updated_at', [$semesterStart, $semesterEnd]);

        $stats = [
            'week' => Requisition::where('status', 'released')->where('updated_at', '>=', now()->startOfWeek())->count(),
            'month' => Requisition::where('status', 'released')->where('updated_at', '>=', now()->startOfMonth())->count(),
            'year' => Requisition::where('status', 'released')->where('updated_at', '>=', now()->startOfYear())->count(),
            'semester' => (clone $releasedThisSemester)->count(),
            'users' => User::where('is_approved', true)->count(),
            'inventory_items' => Supply::count(),
        ];

        $costs = [
            'total_spent' => Requisition::where('status', 'released')->sum('grand_total'),
            'semester_spent' => (clone $releasedThisSemester)->sum('grand_total'),
            'avg_per_req' => Requisition::where('status', 'released')->avg('grand_total') ?? 0,
            'inventory_value' => Supply::selectRaw('COALESCE(SUM(quantity * unit_price), 0) as value')->value('value') ?? 0,
        ];

        $monthlyData = Requisition::where('status', 'released')
            ->whereYear('updated_at', now()->year)
            ->get(['updated_at'])
            ->groupBy(fn (Requisition $requisition) => $requisition->updated_at->month)
            ->map(fn ($requisitions, $month) => (object) [
                'month' => Carbon::create(now()->year, (int) $month, 1)->format('F'),
                'month_number' => (int) $month,
                'count' => $requisitions->count(),
            ])
            ->sortBy('month_number')
            ->values();

        $deptSpending = User::join('requisitions', 'users.id', '=', 'requisitions.user_id')
            ->select('users.department', DB::raw('SUM(requisitions.grand_total) as total'))
            ->where('requisitions.status', 'released')
            ->groupBy('users.department')
            ->get();

        $topItems = RequisitionItem::select('item_name', DB::raw('SUM(quantity) as total_qty'))
            ->join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.status', 'released')
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $report = $this->buildReport($request);

        if ($request->get('export') === 'csv') {
            return $this->downloadCsv($report);
        }

        if ($request->boolean('download_pdf')) {
            $pdf = Pdf::loadView('admin.reports.pdf_template', [
                ...$report,
                'filters' => $request->all(),
                'generatedBy' => Auth::user()->name,
                'generatedDate' => now()->format('F d, Y • h:i A'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download('HTC_SMO_'.$report['reportType'].'_'.date('Ymd_His').'.pdf');
        }

        return view('admin.reports', [
            'stats' => $stats,
            'costs' => $costs,
            'semesterLabel' => $semesterLabel,
            'monthlyData' => $monthlyData,
            'deptSpending' => $deptSpending,
            'topItems' => $topItems,
            ...$report,
        ]);
    }

    /**
     * Preserve the old report-generation endpoint.
     */
    public function generate(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Build a normalized tabular report for previews and exports.
     *
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $reportType = $request->get('report_type', 'requisitions');
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($request);

        $report = match ($reportType) {
            'inventory' => $this->inventoryReport($request, $dateFrom, $dateTo),
            'dept_spending' => $this->departmentSpendingReport($request, $dateFrom, $dateTo),
            'users' => $this->userReport($request, $dateFrom, $dateTo),
            'item_consumption' => $this->itemConsumptionReport($request, $dateFrom, $dateTo),
            'approval_audit' => $this->approvalAuditReport($request, $dateFrom, $dateTo),
            'inventory_movements' => $this->inventoryMovementReport($request, $dateFrom, $dateTo),
            'user_activity' => $this->userActivityReport($request, $dateFrom, $dateTo),
            default => $this->requisitionReport($request, $dateFrom, $dateTo),
        };

        if ($reportType === 'inventory') {
            $periodLabel = 'Current Inventory Snapshot';
        }

        return [
            'reportType' => $reportType,
            'periodLabel' => $periodLabel,
            ...$report,
        ];
    }

    private function requisitionReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = Requisition::with(['user', 'items']);

        if ($request->filled('dept')) {
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('department', $request->dept));
        }
        if ($request->filled('tier')) {
            $query->where('request_type', $request->tier);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->applyDateRange($query, 'created_at', $dateFrom, $dateTo);

        $records = $query->latest('created_at')->get();
        $rows = $records->map(fn (Requisition $requisition) => [
            'tracking' => $requisition->tracking_code,
            'date' => $requisition->created_at->format('Y-m-d'),
            'requestor' => $requisition->user?->name ?? 'Unknown',
            'department' => $requisition->user?->department ?? 'Unassigned',
            'tier' => strtoupper($requisition->request_type),
            'items' => $requisition->items->pluck('item_name')->join(', '),
            'status' => str_replace('_', ' ', $requisition->status),
            'total' => (float) $requisition->grand_total,
        ]);

        return $this->reportResult(
            'Requisition Transactions',
            $rows,
            [
                $this->column('tracking', 'Tracking No.'),
                $this->column('date', 'Date'),
                $this->column('requestor', 'Requestor'),
                $this->column('department', 'Department'),
                $this->column('tier', 'Tier'),
                $this->column('items', 'Items'),
                $this->column('status', 'Status'),
                $this->column('total', 'Total Amount', 'money'),
            ],
            'Total Requested Value',
            $records->sum('grand_total'),
            'money',
        );
    }

    private function inventoryReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = Supply::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->get('stock_filter') === 'low') {
            $query->whereRaw('quantity <= min_stock_level AND quantity > 0');
        } elseif ($request->get('stock_filter') === 'out') {
            $query->where('quantity', '<=', 0);
        }

        $records = $query->orderBy('category')->orderBy('item_name')->get();
        $rows = $records->map(fn (Supply $supply) => [
            'item' => $supply->item_name,
            'brand' => $supply->brand ?: '—',
            'category' => $supply->category,
            'stock' => $supply->quantity,
            'unit' => $supply->unit,
            'minimum' => $supply->min_stock_level,
            'unit_price' => (float) $supply->unit_price,
            'asset_value' => (float) ($supply->quantity * $supply->unit_price),
            'health' => $supply->quantity <= 0 ? 'Out of stock' : ($supply->quantity <= $supply->min_stock_level ? 'Low stock' : 'Healthy'),
        ]);

        return $this->reportResult(
            'Inventory and Asset Valuation',
            $rows,
            [
                $this->column('item', 'Item'),
                $this->column('brand', 'Brand'),
                $this->column('category', 'Category'),
                $this->column('stock', 'Stock', 'number'),
                $this->column('unit', 'Unit'),
                $this->column('minimum', 'Minimum', 'number'),
                $this->column('unit_price', 'Unit Price', 'money'),
                $this->column('asset_value', 'Asset Value', 'money'),
                $this->column('health', 'Stock Health'),
            ],
            'Total Inventory Value',
            $rows->sum('asset_value'),
            'money',
        );
    }

    private function departmentSpendingReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = Requisition::join('users', 'requisitions.user_id', '=', 'users.id')
            ->select(
                'users.department',
                DB::raw('COUNT(requisitions.id) as total_requests'),
                DB::raw('COUNT(DISTINCT requisitions.user_id) as active_requestors'),
                DB::raw('SUM(requisitions.grand_total) as total_spent'),
                DB::raw('AVG(requisitions.grand_total) as avg_cost'),
            )
            ->where('requisitions.status', 'released');

        if ($request->filled('dept')) {
            $query->where('users.department', $request->dept);
        }

        $this->applyDateRange($query, 'requisitions.updated_at', $dateFrom, $dateTo);

        $records = $query->groupBy('users.department')->orderByDesc('total_spent')->get();
        $rows = $records->map(fn ($department) => [
            'department' => $department->department ?: 'Unassigned',
            'requests' => (int) $department->total_requests,
            'requestors' => (int) $department->active_requestors,
            'average' => (float) $department->avg_cost,
            'total' => (float) $department->total_spent,
        ]);

        return $this->reportResult(
            'Departmental Expenditure',
            $rows,
            [
                $this->column('department', 'Department'),
                $this->column('requests', 'Released Requests', 'number'),
                $this->column('requestors', 'Active Requestors', 'number'),
                $this->column('average', 'Average Cost', 'money'),
                $this->column('total', 'Total Spending', 'money'),
            ],
            'Combined Department Spending',
            $rows->sum('total'),
            'money',
        );
    }

    private function userReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = User::query();

        if ($request->filled('dept')) {
            $query->where('department', $request->dept);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('account_status')) {
            $query->where('is_approved', $request->account_status === 'approved');
        }

        $this->applyDateRange($query, 'created_at', $dateFrom, $dateTo);

        $records = $query->orderBy('department')->orderBy('name')->get();
        $rows = $records->map(fn (User $user) => [
            'school_id' => $user->school_id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'department' => $user->department ?: 'Administrative Office',
            'role' => strtoupper(str_replace('_', ' ', $user->role)),
            'status' => $user->is_approved ? 'Approved' : 'Pending',
            'registered' => $user->created_at->format('Y-m-d'),
        ]);

        return $this->reportResult(
            'User Accounts and Personnel',
            $rows,
            [
                $this->column('school_id', 'Institution ID'),
                $this->column('name', 'Name'),
                $this->column('username', 'Username'),
                $this->column('email', 'Email'),
                $this->column('department', 'Department'),
                $this->column('role', 'Role'),
                $this->column('status', 'Account Status'),
                $this->column('registered', 'Registered'),
            ],
            'Matching User Accounts',
            $rows->count(),
            'number',
        );
    }

    private function itemConsumptionReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = RequisitionItem::join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->join('users', 'requisitions.user_id', '=', 'users.id')
            ->select(
                'requisition_items.item_name',
                'requisition_items.unit',
                DB::raw('COUNT(DISTINCT requisitions.id) as request_count'),
                DB::raw('SUM(requisition_items.quantity) as total_quantity'),
                DB::raw('SUM(requisition_items.subtotal) as total_cost'),
            )
            ->where('requisitions.status', 'released');

        if ($request->filled('dept')) {
            $query->where('users.department', $request->dept);
        }

        $this->applyDateRange($query, 'requisitions.updated_at', $dateFrom, $dateTo);

        $records = $query
            ->groupBy('requisition_items.item_name', 'requisition_items.unit')
            ->orderByDesc('total_quantity')
            ->get();

        $rows = $records->map(fn ($item) => [
            'item' => $item->item_name,
            'requests' => (int) $item->request_count,
            'quantity' => (int) $item->total_quantity,
            'unit' => $item->unit,
            'cost' => (float) $item->total_cost,
        ]);

        return $this->reportResult(
            'Item Consumption and Distribution',
            $rows,
            [
                $this->column('item', 'Item'),
                $this->column('requests', 'Requisitions', 'number'),
                $this->column('quantity', 'Quantity Distributed', 'number'),
                $this->column('unit', 'Unit'),
                $this->column('cost', 'Distributed Value', 'money'),
            ],
            'Total Distributed Value',
            $rows->sum('cost'),
            'money',
        );
    }

    private function approvalAuditReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = ApprovalLog::with(['requisition.user']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('dept')) {
            $query->whereHas('requisition.user', fn (Builder $userQuery) => $userQuery->where('department', $request->dept));
        }

        $this->applyDateRange($query, 'created_at', $dateFrom, $dateTo);

        $records = $query->latest()->get();
        $rows = $records->map(fn (ApprovalLog $log) => [
            'date' => $log->created_at->format('Y-m-d H:i'),
            'tracking' => $log->requisition?->tracking_code ?? '—',
            'requestor' => $log->requisition?->user?->name ?? 'Unknown',
            'department' => $log->requisition?->user?->department ?? 'Unassigned',
            'role' => strtoupper(str_replace('_', ' ', $log->role)),
            'action' => $log->action,
            'remarks' => $log->remarks ?: '—',
        ]);

        return $this->reportResult(
            'Approval and Signatory Audit Trail',
            $rows,
            [
                $this->column('date', 'Action Date'),
                $this->column('tracking', 'Tracking No.'),
                $this->column('requestor', 'Requestor'),
                $this->column('department', 'Department'),
                $this->column('role', 'Acting Role'),
                $this->column('action', 'Action'),
                $this->column('remarks', 'Remarks'),
            ],
            'Recorded Approval Actions',
            $rows->count(),
            'number',
        );
    }

    private function inventoryMovementReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = SupplyInventoryLog::with(['supply', 'user', 'requisition']);

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('category')) {
            $query->whereHas('supply', fn (Builder $supplyQuery) => $supplyQuery->where('category', $request->category));
        }

        $this->applyDateRange($query, 'created_at', $dateFrom, $dateTo);

        $records = $query->latest()->get();
        $rows = $records->map(fn (SupplyInventoryLog $log) => [
            'date' => $log->created_at->format('Y-m-d H:i'),
            'item' => $log->supply?->item_name ?? 'Deleted item',
            'type' => strtoupper(str_replace('_', ' ', $log->transaction_type)),
            'change' => (int) $log->quantity_change,
            'balance' => (int) $log->balance_after,
            'officer' => $log->user?->name ?? 'Unknown',
            'tracking' => $log->requisition?->tracking_code ?? '—',
            'remarks' => $log->remarks ?: '—',
        ]);

        return $this->reportResult(
            'Inventory Movement Ledger',
            $rows,
            [
                $this->column('date', 'Transaction Date'),
                $this->column('item', 'Supply Item'),
                $this->column('type', 'Transaction Type'),
                $this->column('change', 'Quantity Change', 'number'),
                $this->column('balance', 'Balance After', 'number'),
                $this->column('officer', 'SMO Officer'),
                $this->column('tracking', 'Related Requisition'),
                $this->column('remarks', 'Remarks'),
            ],
            'Inventory Transactions',
            $rows->count(),
            'number',
        );
    }

    private function userActivityReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = UserActivityLog::with('user');

        if ($request->filled('role')) {
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('role', $request->role));
        }
        if ($request->filled('dept')) {
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('department', $request->dept));
        }

        $this->applyDateRange($query, 'created_at', $dateFrom, $dateTo);

        $records = $query->latest('created_at')->get();
        $rows = $records->map(fn (UserActivityLog $log) => [
            'date' => Carbon::parse($log->created_at)->format('Y-m-d H:i'),
            'user' => $log->user?->name ?? 'Deleted user',
            'school_id' => $log->user?->school_id ?? '—',
            'department' => $log->user?->department ?? 'Unassigned',
            'role' => strtoupper(str_replace('_', ' ', $log->user?->role ?? 'unknown')),
            'action' => $log->action,
            'ip' => $log->ip_address,
        ]);

        return $this->reportResult(
            'User Activity and Security Log',
            $rows,
            [
                $this->column('date', 'Activity Date'),
                $this->column('user', 'User'),
                $this->column('school_id', 'Institution ID'),
                $this->column('department', 'Department'),
                $this->column('role', 'Role'),
                $this->column('action', 'Activity'),
                $this->column('ip', 'IP Address'),
            ],
            'Recorded User Activities',
            $rows->count(),
            'number',
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, array{key: string, label: string, type: string}>  $columns
     * @return array<string, mixed>
     */
    private function reportResult(
        string $title,
        Collection $rows,
        array $columns,
        string $summaryLabel,
        float|int $summaryValue,
        string $summaryType,
    ): array {
        return compact('title', 'rows', 'columns', 'summaryLabel', 'summaryValue', 'summaryType');
    }

    /**
     * @return array{key: string, label: string, type: string}
     */
    private function column(string $key, string $label, string $type = 'text'): array
    {
        return compact('key', 'label', 'type');
    }

    private function applyDateRange(Builder|QueryBuilder $query, string $column, ?Carbon $dateFrom, ?Carbon $dateTo): void
    {
        if ($dateFrom) {
            $query->where($column, '>=', $dateFrom->copy()->startOfDay());
        }
        if ($dateTo) {
            $query->where($column, '<=', $dateTo->copy()->endOfDay());
        }
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null, 2: string}
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'current_semester');

        return match ($period) {
            'all' => [null, null, 'All Available Records'],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth(), now()->format('F Y')],
            'this_year' => [now()->startOfYear(), now()->endOfYear(), 'Calendar Year '.now()->year],
            'custom' => [
                $request->filled('date_from') ? Carbon::parse($request->date_from) : null,
                $request->filled('date_to') ? Carbon::parse($request->date_to) : null,
                $this->customPeriodLabel($request),
            ],
            default => $this->currentSemester(),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function currentSemester(): array
    {
        $year = now()->year;
        $month = now()->month;

        if ($month <= 5) {
            return [
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 5, 31)->endOfDay(),
                'Second Semester AY '.($year - 1).'-'.$year,
            ];
        }

        if ($month <= 7) {
            return [
                Carbon::create($year, 6, 1)->startOfDay(),
                Carbon::create($year, 7, 31)->endOfDay(),
                'Summer Term '.$year,
            ];
        }

        return [
            Carbon::create($year, 8, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay(),
            'First Semester AY '.$year.'-'.($year + 1),
        ];
    }

    private function customPeriodLabel(Request $request): string
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->format('M d, Y') : 'Beginning';
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->format('M d, Y') : 'Present';

        return $from.' to '.$to;
    }

    /**
     * Download an Excel-compatible CSV without requiring an additional package.
     *
     * @param  array<string, mixed>  $report
     */
    private function downloadCsv(array $report)
    {
        $filename = 'HTC_SMO_'.$report['reportType'].'_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_column($report['columns'], 'label'));

            foreach ($report['rows'] as $row) {
                $values = collect($report['columns'])->map(function (array $column) use ($row) {
                    $value = (string) ($row[$column['key']] ?? '');

                    return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
                })->all();

                fputcsv($output, $values);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
