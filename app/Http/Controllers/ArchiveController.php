<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all'); // Default to show all
        $query = Requisition::where('status', 'released')->with(['user', 'items']);

        // Filter Logic
        switch ($filter) {
            case 'today':
                $query->whereDate('updated_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('updated_at', Carbon::now()->month)
                      ->whereYear('updated_at', Carbon::now()->year);
                break;
            case 'year':
                $query->whereYear('updated_at', Carbon::now()->year);
                break;
        }

        $archives = $query->latest('updated_at')->paginate(15)->withQueryString();

        // Statistics for the top cards
        $stats = [
            'today_count' => Requisition::where('status', 'released')->whereDate('updated_at', Carbon::today())->count(),
            'week_val' => Requisition::where('status', 'released')->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('grand_total'),
            'month_count' => Requisition::where('status', 'released')->whereMonth('updated_at', Carbon::now()->month)->count(),
        ];

        return view('admin.archive', compact('archives', 'filter', 'stats'));
    }
}
