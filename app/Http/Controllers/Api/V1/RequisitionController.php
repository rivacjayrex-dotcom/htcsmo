<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitRequisitionRequest;
use App\Http\Resources\Api\V1\RequisitionResource;
use App\Models\Requisition;
use App\Services\RequisitionSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class RequisitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'request_type' => ['nullable', 'in:minor,major'],
            'status' => ['nullable', Rule::in([
                'pending', 'approved_dept', 'approved_vp', 'approved_provost', 'approved_president',
                'processing', 'ready_for_pickup', 'for_clarification', 'completed', 'released', 'rejected', 'withdrawn',
            ])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);

        $requisitions = Requisition::query()->visibleTo($request->user())
            ->with('user.academicDepartment')->search($filters['search'] ?? null)
            ->when($filters['request_type'] ?? null, fn ($query, $type) => $query->where('request_type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();

        return RequisitionResource::collection($requisitions);
    }

    public function show(Request $request, int $id): RequisitionResource
    {
        $requisition = Requisition::query()->visibleTo($request->user())
            ->with(['user.academicDepartment', 'items', 'logs.actor'])->findOrFail($id);

        return new RequisitionResource($requisition);
    }

    public function store(SubmitRequisitionRequest $request, RequisitionSubmissionService $submissions): JsonResponse
    {
        $result = $submissions->submit($request->user(), $request->validated());
        $requisition = $result['requisition']->load(['user.academicDepartment', 'items', 'logs.actor']);

        return (new RequisitionResource($requisition))->additional([
            'message' => 'Requisition submitted successfully.',
            'notification_warning' => $result['notification_failed']
                ? 'The requisition was saved, but some notifications could not be sent. Please contact the SMO.' : null,
        ])->response()->setStatusCode(201)
            ->header('Location', route('api.v1.requisitions.show', $requisition->id));
    }
}
