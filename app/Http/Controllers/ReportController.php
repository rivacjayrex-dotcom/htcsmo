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
            'account_status' => ['nullable', 'in:pending,active,suspended'],
        ]);

        [$semesterStart, $semesterEnd, $semesterLabel] = $this->currentSemester();

        $releasedThisSemester = Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)
            ->whereBetween('updated_at', [$semesterStart, $semesterEnd]);

        $stats = [
            'week' => Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)->where('updated_at', '>=', now()->startOfWeek())->count(),
            'month' => Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)->where('updated_at', '>=', now()->startOfMonth())->count(),
            'year' => Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)->where('updated_at', '>=', now()->startOfYear())->count(),
            'semester' => (clone $releasedThisSemester)->count(),
            'users' => User::where('account_status', User::ACCOUNT_ACTIVE)->count(),
            'inventory_items' => Supply::count(),
        ];

        $costs = [
            'total_spent' => Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)->sum('grand_total'),
            'semester_spent' => (clone $releasedThisSemester)->sum('grand_total'),
            'avg_per_req' => Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)->avg('grand_total') ?? 0,
            'inventory_value' => Supply::selectRaw('COALESCE(SUM(quantity * unit_price), 0) as value')->value('value') ?? 0,
        ];

        $monthlyData = Requisition::whereIn('status', Requisition::FULFILLED_STATUSES)
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
            ->whereIn('requisitions.status', Requisition::FULFILLED_STATUSES)
            ->groupBy('users.department')
            ->get();

        $topItems = RequisitionItem::select('item_name', DB::raw('SUM(quantity) as total_qty'))
            ->join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->whereIn('requisitions.status', Requisition::FULFILLED_STATUSES)
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
                ...$this->pdfContext($report, $request),
                'generatedBy' => Auth::user()->name,
                'generatedDate' => now()->format('F d, Y - h:i A'),
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
        // One row per line item prevents specifications, quantities, and prices
        // from being hidden inside a comma-separated requisition summary.
        $rows = $records->flatMap(function (Requisition $requisition) {
            $items = $requisition->items->isEmpty() ? collect([null]) : $requisition->items;

            return $items->map(fn ($item) => [
                'tracking' => $requisition->tracking_code,
                'submitted' => $requisition->created_at->format('Y-m-d H:i'),
                'requestor' => $requisition->user?->name ?? 'Unknown',
                'department' => $requisition->user?->department ?? 'Unassigned',
                'tier' => strtoupper($requisition->request_type),
                'purpose' => $requisition->purpose ?: 'Not specified',
                'item' => $item?->item_name ?? 'No line item',
                'specifications' => $item?->specifications ?: 'Not specified',
                'quantity' => (int) ($item?->quantity ?? 0),
                'unit' => $item?->unit ?: '-',
                'unit_price' => (float) ($item?->unit_price ?? 0),
                'line_total' => (float) ($item?->subtotal ?? 0),
                'status' => ucwords(str_replace('_', ' ', $requisition->status)),
            ]);
        })->values();

        return $this->reportResult(
            'Requisition Transactions',
            $rows,
            [
                $this->column('tracking', 'Tracking No.'),
                $this->column('submitted', 'Submitted'),
                $this->column('requestor', 'Requestor'),
                $this->column('department', 'Department'),
                $this->column('tier', 'Tier'),
                $this->column('purpose', 'Purpose'),
                $this->column('item', 'Item'),
                $this->column('specifications', 'Specifications'),
                $this->column('quantity', 'Qty', 'number'),
                $this->column('unit', 'Unit'),
                $this->column('unit_price', 'Unit Price', 'money'),
                $this->column('line_total', 'Line Total', 'money'),
                $this->column('status', 'Status'),
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
            'brand' => $supply->brand ?: '-',
            'model' => $supply->model_number ?: '-',
            'category' => $supply->category,
            'tier' => strtoupper($supply->requisition_tier ?: 'minor'),
            'specifications' => $supply->specifications ?: 'Not specified',
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
                $this->column('model', 'Model No.'),
                $this->column('category', 'Category'),
                $this->column('tier', 'Tier'),
                $this->column('specifications', 'Specifications'),
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
            ->whereIn('requisitions.status', Requisition::FULFILLED_STATUSES);

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
            $query->where('account_status', $request->account_status);
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
            'status' => ucfirst($user->account_status),
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
            ->leftJoin('supplies', 'requisition_items.supply_id', '=', 'supplies.id')
            ->select(
                'requisition_items.item_name',
                'requisition_items.unit',
                DB::raw("COALESCE(supplies.category, 'Uncategorized') as category"),
                DB::raw('COUNT(DISTINCT requisitions.id) as request_count'),
                DB::raw('SUM(requisition_items.quantity) as total_quantity'),
                DB::raw('SUM(requisition_items.subtotal) as total_cost'),
                DB::raw('AVG(requisition_items.unit_price) as average_unit_price'),
            )
            ->whereIn('requisitions.status', Requisition::FULFILLED_STATUSES);

        if ($request->filled('dept')) {
            $query->where('users.department', $request->dept);
        }

        $this->applyDateRange($query, 'requisitions.updated_at', $dateFrom, $dateTo);

        $records = $query
            ->groupBy('requisition_items.item_name', 'requisition_items.unit', 'supplies.category')
            ->orderByDesc('total_quantity')
            ->get();

        $rows = $records->map(fn ($item) => [
            'item' => $item->item_name,
            'category' => $item->category,
            'requests' => (int) $item->request_count,
            'quantity' => (int) $item->total_quantity,
            'unit' => $item->unit,
            'average_price' => (float) $item->average_unit_price,
            'cost' => (float) $item->total_cost,
        ]);

        return $this->reportResult(
            'Item Consumption and Distribution',
            $rows,
            [
                $this->column('item', 'Item'),
                $this->column('category', 'Category'),
                $this->column('requests', 'Requisitions', 'number'),
                $this->column('quantity', 'Quantity Distributed', 'number'),
                $this->column('unit', 'Unit'),
                $this->column('average_price', 'Average Unit Price', 'money'),
                $this->column('cost', 'Distributed Value', 'money'),
            ],
            'Total Distributed Value',
            $rows->sum('cost'),
            'money',
        );
    }

    private function approvalAuditReport(Request $request, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = ApprovalLog::with(['requisition.user', 'digitalSignature']);

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
            'tracking' => $log->requisition?->tracking_code ?? '-',
            'requestor' => $log->requisition?->user?->name ?? 'Unknown',
            'department' => $log->requisition?->user?->department ?? 'Unassigned',
            'role' => strtoupper(str_replace('_', ' ', $log->role)),
            'action' => $log->action,
            'remarks' => $log->remarks ?: '-',
            'signer' => $log->digitalSignature?->signer_name ?? 'Not digitally signed',
            'signed_at' => $log->digitalSignature?->signed_at
                ? Carbon::parse($log->digitalSignature->signed_at)->format('Y-m-d H:i')
                : '-',
            'verification' => $log->digitalSignature?->signature_token
                ? strtoupper(substr($log->digitalSignature->signature_token, 0, 12))
                : '-',
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
                $this->column('signer', 'Digital Signer'),
                $this->column('signed_at', 'Signed At'),
                $this->column('verification', 'Verification Ref.'),
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
            'tracking' => $log->requisition?->tracking_code ?? '-',
            'remarks' => $log->remarks ?: '-',
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
            'school_id' => $log->user?->school_id ?? '-',
            'department' => $log->user?->department ?? 'Unassigned',
            'role' => strtoupper(str_replace('_', ' ', $log->user?->role ?? 'unknown')),
            'action' => $log->action,
            'ip' => $log->ip_address,
            'device' => $log->user_agent ?: '-',
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
                $this->column('device', 'Browser / Device'),
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

    /**
     * Add the document-control, categorization, and interpretation data used by
     * the PDF. Keeping this separate from the tabular result also keeps CSV
     * exports clean and machine-readable.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function pdfContext(array $report, Request $request): array
    {
        $type = $report['reportType'];
        $rows = $report['rows'];

        $definitions = [
            'requisitions' => [
                'purpose' => 'Provides a line-by-line register of submitted requisitions, including requestor, purpose, item specifications, quantities, pricing, and current workflow status.',
                'basis' => 'Requisition submission date',
                'source' => 'Requisitions, requisition line items, and requestor account records. Amounts represent submitted values and may include requests that were not released.',
                'group' => ['status', 'Workflow Status', 'line_total', 'Line Value', 'money'],
            ],
            'inventory' => [
                'purpose' => 'Documents the current catalog, stock position, reorder threshold, pricing, and recorded asset value for every matching supply item.',
                'basis' => 'Current inventory snapshot',
                'source' => 'Live supply catalog balances at generation time. Historical stock levels are available in the Inventory Movement Ledger.',
                'group' => ['category', 'Supply Category', 'asset_value', 'Asset Value', 'money'],
            ],
            'dept_spending' => [
                'purpose' => 'Summarizes released requisition value, transaction volume, and participation by requesting department.',
                'basis' => 'Requisition release date',
                'source' => 'Released requisitions joined to the requestor department. Rejected and unreleased requests are excluded.',
                'group' => ['department', 'Department', 'total', 'Spending', 'money'],
            ],
            'users' => [
                'purpose' => 'Lists personnel accounts and their institutional identifiers, departments, roles, approval state, and registration dates.',
                'basis' => 'Account registration date',
                'source' => 'User account registry. Passwords, reset tokens, and other authentication secrets are never included.',
                'group' => ['role', 'Personnel Role', null, null, 'number'],
            ],
            'item_consumption' => [
                'purpose' => 'Aggregates released quantities and costs by item and category to support consumption review and purchasing decisions.',
                'basis' => 'Requisition release date',
                'source' => 'Line items from released requisitions. Catalog category is shown when the line item remains linked to a supply record.',
                'group' => ['category', 'Supply Category', 'cost', 'Distributed Value', 'money'],
            ],
            'approval_audit' => [
                'purpose' => 'Presents the complete decision trail for requisitions, including acting role, remarks, signer identity, signing time, and verification reference.',
                'basis' => 'Approval action date',
                'source' => 'Approval logs and linked digital-signature records. The displayed verification reference is the first 12 characters of the stored SHA-256 token.',
                'group' => ['role', 'Acting Role', null, null, 'number'],
            ],
            'inventory_movements' => [
                'purpose' => 'Provides an auditable ledger of releases, restocks, and manual adjustments with resulting balances and responsible officers.',
                'basis' => 'Inventory transaction date',
                'source' => 'Immutable inventory transaction logs. Quantity change is signed: positive adds stock and negative removes stock.',
                'group' => ['type', 'Transaction Type', 'change', 'Net Quantity Change', 'number'],
            ],
            'user_activity' => [
                'purpose' => 'Records user security and application activity with account, department, role, network address, and client information.',
                'basis' => 'Activity timestamp',
                'source' => 'User activity audit logs. This confidential report may contain IP addresses and browser/device identifiers.',
                'group' => ['role', 'Personnel Role', null, null, 'number'],
            ],
        ];

        $definition = $definitions[$type];
        [$groupKey, $groupLabel, $valueKey, $valueLabel, $valueType] = $definition['group'];

        $breakdown = $rows
            ->groupBy(fn (array $row) => filled($row[$groupKey] ?? null) ? (string) $row[$groupKey] : 'Unassigned')
            ->map(function (Collection $groupRows, string $label) use ($valueKey, $valueLabel, $valueType) {
                return [
                    'label' => $label,
                    'records' => $groupRows->count(),
                    'valueLabel' => $valueLabel,
                    'value' => $valueKey ? $groupRows->sum($valueKey) : null,
                    'type' => $valueType,
                ];
            })
            ->sortByDesc(fn (array $group) => $group['value'] ?? $group['records'])
            ->values();

        return [
            'purpose' => $definition['purpose'],
            'reportBasis' => $definition['basis'],
            'sourceNote' => $definition['source'],
            'classification' => $type === 'user_activity' ? 'Confidential - Internal Use' : 'Internal Administrative Use',
            'reportReference' => 'HTC-SMO-'.strtoupper(str_replace('_', '-', $type)).'-'.now()->format('Ymd-His'),
            'dataAsOf' => now()->format('F d, Y - h:i A'),
            'appliedFilters' => $this->appliedFilterSummary($request),
            'metrics' => $this->reportMetrics($type, $rows, $report),
            'breakdownTitle' => $groupLabel,
            'breakdown' => $breakdown,
            'notes' => $this->reportNotes($type, $rows, $report),
        ];
    }

    /** @return array<int, array{label: string, value: mixed, type: string}> */
    private function reportMetrics(string $type, Collection $rows, array $report): array
    {
        $metrics = match ($type) {
            'requisitions' => [
                ['label' => 'Requisitions', 'value' => $rows->pluck('tracking')->unique()->count(), 'type' => 'number'],
                ['label' => 'Line Items', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Units Requested', 'value' => $rows->sum('quantity'), 'type' => 'number'],
            ],
            'inventory' => [
                ['label' => 'Catalog Items', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Units on Hand', 'value' => $rows->sum('stock'), 'type' => 'number'],
                ['label' => 'Low / Out of Stock', 'value' => $rows->whereIn('health', ['Low stock', 'Out of stock'])->count(), 'type' => 'number'],
            ],
            'dept_spending' => [
                ['label' => 'Departments', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Released Requests', 'value' => $rows->sum('requests'), 'type' => 'number'],
                ['label' => 'Active Requestors', 'value' => $rows->sum('requestors'), 'type' => 'number'],
            ],
            'users' => [
                ['label' => 'Accounts', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Approved', 'value' => $rows->where('status', 'Approved')->count(), 'type' => 'number'],
                ['label' => 'Pending', 'value' => $rows->where('status', 'Pending')->count(), 'type' => 'number'],
            ],
            'item_consumption' => [
                ['label' => 'Distinct Item Groups', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Units Distributed', 'value' => $rows->sum('quantity'), 'type' => 'number'],
                ['label' => 'Requisition Links', 'value' => $rows->sum('requests'), 'type' => 'number'],
            ],
            'approval_audit' => [
                ['label' => 'Approval Actions', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Digitally Signed', 'value' => $rows->where('verification', '!=', '-')->count(), 'type' => 'number'],
                ['label' => 'Unsigned Actions', 'value' => $rows->where('verification', '-')->count(), 'type' => 'number'],
            ],
            'inventory_movements' => [
                ['label' => 'Transactions', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Stock Added', 'value' => $rows->where('change', '>', 0)->sum('change'), 'type' => 'number'],
                ['label' => 'Stock Removed', 'value' => abs($rows->where('change', '<', 0)->sum('change')), 'type' => 'number'],
            ],
            default => [
                ['label' => 'Activities', 'value' => $rows->count(), 'type' => 'number'],
                ['label' => 'Distinct Users', 'value' => $rows->pluck('user')->unique()->count(), 'type' => 'number'],
                ['label' => 'Distinct IP Addresses', 'value' => $rows->pluck('ip')->filter()->unique()->count(), 'type' => 'number'],
            ],
        };

        $metrics[] = [
            'label' => $report['summaryLabel'],
            'value' => $report['summaryValue'],
            'type' => $report['summaryType'],
        ];

        return $metrics;
    }

    /** @return array<int, string> */
    private function reportNotes(string $type, Collection $rows, array $report): array
    {
        $notes = [
            'The detailed table contains '.number_format($rows->count()).' matching row(s) after the selected period and filters were applied.',
            $report['summaryLabel'].' is '.($report['summaryType'] === 'money'
                ? 'PHP '.number_format((float) $report['summaryValue'], 2)
                : number_format((float) $report['summaryValue']).'.'),
        ];

        $notes[] = match ($type) {
            'requisitions' => 'Each item is printed on its own row; the same tracking number may therefore appear more than once.',
            'inventory' => 'Stock health is based on the current quantity compared with the configured minimum stock level.',
            'dept_spending', 'item_consumption' => 'Only released requisitions contribute to expenditure and distribution figures.',
            'approval_audit' => 'Actions without a digital signature remain visible and are explicitly identified as unsigned.',
            'inventory_movements' => 'Review the signed quantity change together with the balance after each transaction.',
            'user_activity' => 'Handle this report according to institutional privacy and access-control policies.',
            default => 'Account status reflects the approval state recorded when this report was generated.',
        };

        return $notes;
    }

    private function appliedFilterSummary(Request $request): string
    {
        $labels = [
            'dept' => 'Department', 'tier' => 'Tier', 'status' => 'Status',
            'role' => 'Role', 'account_status' => 'Account Status',
            'category' => 'Category', 'stock_filter' => 'Stock Health',
            'transaction_type' => 'Transaction Type',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $key) => $request->filled($key))
            ->map(fn (string $label, string $key) => $label.': '.ucwords(str_replace('_', ' ', (string) $request->input($key))))
            ->values()
            ->join('; ');
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
