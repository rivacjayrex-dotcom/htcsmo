<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SupplyResource;
use App\Models\Supply;
use App\Models\SupplyInventoryLog;
use App\Models\UserActivityLog;
use App\Services\InventoryAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupplyController extends Controller
{
    private const RELATIONS = ['supplyCategory', 'supplyUnit', 'options.values'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'requisition_tier' => ['nullable', 'in:minor,major'],
            'in_stock' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);

        $supplies = Supply::query()->with(self::RELATIONS)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('item_name', 'like', '%'.$search.'%'))
            ->when($filters['requisition_tier'] ?? null, fn ($query, $tier) => $query->where('requisition_tier', $tier))
            ->when($request->boolean('in_stock'), fn ($query) => $query->where('quantity', '>', 0))
            ->orderBy('item_name')->orderBy('id')->paginate($filters['per_page'] ?? 20)->withQueryString();

        return SupplyResource::collection($supplies);
    }

    public function show(Supply $supply): SupplyResource
    {
        return new SupplyResource($supply->load(self::RELATIONS));
    }

    public function update(Request $request, Supply $supply, InventoryAlertService $alerts): SupplyResource
    {
        abort_unless($request->user()->role === 'smo', 403, 'Only SMO users may update inventory.');

        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'min_stock_level' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
        ]);
        if ($validated === []) {
            throw ValidationException::withMessages(['supply' => 'Provide quantity, unit_price, or min_stock_level to update.']);
        }

        [$updated, $wasLow, $wasOut] = DB::transaction(function () use ($request, $supply, $validated) {
            $locked = Supply::query()->lockForUpdate()->findOrFail($supply->id);
            $before = (int) $locked->quantity;
            $wasLow = $before <= $locked->min_stock_level;
            $wasOut = $before <= 0;
            $locked->update($validated);
            $change = (int) $locked->quantity - $before;

            if ($change !== 0) {
                SupplyInventoryLog::create([
                    'supply_id' => $locked->id,
                    'user_id' => $request->user()->id,
                    'transaction_type' => 'manual_adjustment',
                    'quantity_change' => $change,
                    'balance_after' => $locked->quantity,
                    'remarks' => 'Inventory adjusted through REST API v1.',
                ]);
            }
            UserActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'API inventory update (supply '.$locked->id.')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return [$locked, $wasLow, $wasOut];
        });

        $warning = null;
        try {
            $alerts->notifyIfThresholdCrossed($updated, $wasLow, $wasOut);
        } catch (Throwable $exception) {
            $warning = 'Inventory was saved, but stock notifications could not be sent. Please contact the SMO.';
            Log::error('API inventory update notification failed.', ['supply_id' => $updated->id, 'exception' => $exception]);
        }

        return (new SupplyResource($updated->load(self::RELATIONS)))->additional(['notification_warning' => $warning]);
    }
}
