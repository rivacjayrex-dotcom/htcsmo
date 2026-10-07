<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveIndexRequest;
use App\Models\Requisition;
use App\Models\User;

class ArchiveController extends Controller
{
    public function index(ArchiveIndexRequest $request)
    {
        $filters = $request->validated();
        $order = $filters['order'] ?? 'desc';
        $query = Requisition::query()
            ->archived()
            ->withArchiveFilters($filters, allowDepartment: true)
            ->with(['user', 'items'])
            ->orderBy('closed_at', $order)
            ->orderBy('id', $order);
        $archives = $request->boolean('print')
            ? $query->get()
            : $query->paginate(15)->withQueryString();
        $departments = User::whereNotNull('department')
            ->where('department', '<>', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('admin.archive', compact('archives', 'departments'));
    }
}
