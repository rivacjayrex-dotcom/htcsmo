<?php

namespace App\Observers;

use App\Models\ApprovalLog;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyInventoryLog;
use App\Models\SupplyOption;
use App\Models\SupplyOptionValue;
use App\Models\SupplyUnit;
use App\Services\WorkspaceVersionService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkspaceObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->changed($model);
    }

    public function updated(Model $model): void
    {
        $this->changed($model);
    }

    public function deleted(Model $model): void
    {
        $this->changed($model);
    }

    public function restored(Model $model): void
    {
        $this->changed($model);
    }

    private function changed(Model $model): void
    {
        $domain = match (true) {
            $model instanceof Requisition, $model instanceof RequisitionItem, $model instanceof ApprovalLog => 'requisitions',
            $model instanceof Supply, $model instanceof SupplyInventoryLog,
            $model instanceof SupplyOption, $model instanceof SupplyOptionValue,
            $model instanceof SupplyCategory, $model instanceof SupplyUnit => 'inventory',
            default => 'accounts',
        };
        // The data is already committed. A refresh-cache failure must not turn
        // a successful write into a 500 or stop the remaining commit callbacks.
        try {
            app(WorkspaceVersionService::class)->touch($domain);
        } catch (Throwable $exception) {
            Log::warning('Workspace refresh marker failed after a committed change.', [
                'domain' => $domain,
                'model' => $model::class,
                'model_id' => $model->getKey(),
                'exception' => $exception,
            ]);
        }
    }
}
