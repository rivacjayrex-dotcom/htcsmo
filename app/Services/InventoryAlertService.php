<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Supply;
use App\Models\User;

class InventoryAlertService
{
    public function notifyIfThresholdCrossed(Supply $supply, bool $wasLow, bool $wasOut): void
    {
        $isOut = $supply->quantity <= 0;
        $isLow = $supply->quantity <= $supply->min_stock_level;

        if ((! $isOut || $wasOut) && (! $isLow || $wasLow)) {
            return;
        }

        $alert = $isOut
            ? [
                'title' => "Out of Stock: {$supply->item_name}",
                'message' => "{$supply->item_name} has reached zero stock. Review its demand forecast and arrange replenishment before the next request.",
                'icon' => 'package-x',
                'type' => 'danger',
            ]
            : [
                'title' => "Low Stock Warning: {$supply->item_name}",
                'message' => "Only {$supply->quantity} {$supply->unit} remain; the restock threshold is {$supply->min_stock_level}. Review the forecast and replenish soon.",
                'icon' => 'triangle-alert',
                'type' => 'warning',
            ];

        User::where('role', 'smo')->pluck('id')->each(function ($userId) use ($alert) {
            Notification::create(['user_id' => $userId, ...$alert]);
        });
    }
}
