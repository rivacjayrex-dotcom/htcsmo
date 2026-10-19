<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveIndexRequest;
use App\Models\Department;
use App\Models\Requisition;

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
        $departments = Department::query()->whereHas('users')
            ->orderBy('dept_code')
            ->pluck('dept_code');

        return view('admin.archive', compact('archives', 'departments'));
    }
}
