<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_code,
            'requestor' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'department' => $this->user->department,
            ]),
            'request_type' => $this->request_type,
            'status' => $this->status,
            'status_label' => $this->requestorStatus()['label'],
            'next_step' => $this->requestorStatus()['next_step'],
            'next_reviewer_role' => $this->nextReviewerRole(),
            'purpose' => $this->purpose,
            'remarks' => $this->remarks,
            'grand_total' => number_format((float) $this->grand_total, 2, '.', ''),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'due_at' => $this->due_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'supply_id' => $item->supply_id,
                'item_name' => $item->item_name,
                'specifications' => $item->specifications,
                'quantity' => (int) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'subtotal' => number_format((float) $item->subtotal, 2, '.', ''),
            ])),
            'history' => $this->whenLoaded('logs', fn () => $this->logs->sortBy('id')->values()->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'role' => $log->role,
                'actor_name' => $log->actor?->name,
                'remarks' => $log->remarks,
                'created_at' => $log->created_at?->toISOString(),
            ])),
        ];
    }
}
