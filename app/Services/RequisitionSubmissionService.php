<?php

namespace App\Services;

use App\Jobs\SendRequisitionEmail;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RequisitionSubmissionService
{
    public function __construct(private NotificationService $notifications) {}

    /** @return array{requisition: Requisition, notification_failed: bool} */
    public function submit(User $user, array $validated): array
    {
        $preparedItems = $this->prepareItems($validated['items'], $validated['request_type']);

        $calculatedGrandTotal = $preparedItems->sum('subtotal');
        if ($validated['request_type'] === 'minor' && $calculatedGrandTotal > 1000) {
            throw ValidationException::withMessages([
                'items' => 'Minor requisitions cannot exceed ₱1,000. Please choose the Major tier instead.',
            ]);
        }

        if ($validated['request_type'] === 'major' && $calculatedGrandTotal <= 1000) {
            throw ValidationException::withMessages([
                'items' => 'Major requisitions must exceed PHP 1,000. Requests totaling PHP 1,000 or less are Minor.',
            ]);
        }

        // Start after the requestor's own approval office so nobody approves
        // their own requisition or sends it backward to a lower office.
        $initialStatus = Requisition::initialStatusFor($user);

        // 3. CREATE THE HEADER
        $requisition = DB::transaction(function () use ($user, $validated, $initialStatus, $preparedItems, $calculatedGrandTotal) {
            $requisition = Requisition::create([
                'user_id' => $user->id,
                'request_type' => $validated['request_type'],
                'status' => $initialStatus,
                'purpose' => $validated['purpose'],
                'grand_total' => $calculatedGrandTotal,
            ]);

            foreach ($preparedItems as $item) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'supply_id' => $item['supply']->id,
                    'item_name' => $item['supply']->item_name,
                    'specifications' => $item['specifications'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['supply']->unit,
                    'unit_price' => $item['supply']->unit_price,
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $requisition;
        });

        $notificationFailed = false;
        foreach ([
            'email' => fn () => $this->sendSubmissionEmails($requisition, $user),
            'in_app' => fn () => $this->sendSubmissionNotifications($requisition, $user, $this->notifications, count($validated['items'])),
        ] as $channel => $send) {
            try {
                if ($send() === false) {
                    $notificationFailed = true;
                }
            } catch (\Throwable $exception) {
                $notificationFailed = true;
                Log::error('Requisition saved but submission notification failed.', [
                    'requisition_id' => $requisition->id,
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'exception' => $exception,
                ]);
            }
        }

        return ['requisition' => $requisition, 'notification_failed' => $notificationFailed];
    }

    private function sendSubmissionNotifications(Requisition $requisition, User $user, NotificationService $notifications, int $itemCount): void
    {
        $notifications->send($user, 'requisition.submitted', 'Requisition Submitted',
            'Your request for '.$itemCount.' item(s) has been submitted.', [
                'icon' => 'send', 'type' => 'info', 'action_type' => 'requisition',
                'action_id' => $requisition->id,
                'dedupe_key' => "requisition:{$requisition->id}:submitted:owner",
            ]);

        if ($requisition->status === 'pending') {
            User::query()->where('role', 'dept_head')->where('department_id', $user->department_id)
                ->where('account_status', User::ACCOUNT_ACTIVE)->eachById(fn (User $head) => $notifications->send(
                    $head, 'requisition.awaiting_approval', 'Requisition Awaiting Review',
                    "{$user->name}'s requisition {$requisition->tracking_code} is ready for department review.", [
                        'icon' => 'file-text', 'type' => 'info', 'action_type' => 'approval_queue',
                        'action_id' => $requisition->id,
                        'dedupe_key' => "requisition:{$requisition->id}:awaiting:dept-head",
                    ]
                ));
        } elseif ($nextRole = $requisition->nextReviewerRole()) {
            $isExecutivePriority = in_array($user->role, ['president', 'provost'], true);
            $notifications->sendToRole(
                $nextRole,
                $nextRole === 'smo' ? 'requisition.ready_for_release' : 'requisition.awaiting_approval',
                $isExecutivePriority ? 'Priority Requisition Ready' : 'Requisition Ready for Action',
                $isExecutivePriority
                    ? "A direct requisition from the {$user->role} ({$user->name}) is ready for release."
                    : "Requisition {$requisition->tracking_code} from {$user->name} is ready for your review.",
                [
                    'icon' => $isExecutivePriority ? 'zap' : 'file-text',
                    'type' => $isExecutivePriority ? 'danger' : ($nextRole === 'smo' ? 'warning' : 'info'),
                    'action_type' => 'approval_queue',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "requisition:{$requisition->id}:initial-route:{$nextRole}",
                ]
            );
        }
    }

    private function sendSubmissionEmails(Requisition $requisition, User $requestor): bool
    {
        $recipients = User::query()
            ->where('role', 'smo')
            ->where('account_status', User::ACCOUNT_ACTIVE)
            ->where('id', '!=', $requestor->id)
            ->get()
            ->prepend($requestor)
            ->merge(app(RequisitionEmailService::class)->reviewers($requisition))
            ->unique('id');

        $queued = true;
        foreach ($recipients as $recipient) {
            $isRequestor = $recipient->is($requestor);

            try {
                SendRequisitionEmail::dispatch(
                    $recipient->email,
                    $recipient->id,
                    $requisition->id,
                    $isRequestor ? 'Requisition Submitted' : 'New Requisition Submitted',
                    $isRequestor ? 'Your Requisition Has Been Submitted' : 'New Requisition Received',
                    $isRequestor
                        ? "Your requisition {$requisition->tracking_code} has been submitted successfully. You can view its current status in the system."
                        : "{$requestor->name} has submitted requisition {$requisition->tracking_code}. You can view its details and current approval status in the system.",
                    '#185b3b',
                    ! $isRequestor && $recipient->role !== 'smo'
                        ? route('admin.approvals', ['request' => $requisition->id]) : null,
                );
            } catch (\Throwable $exception) {
                $queued = false;
                Log::error('Could not queue requisition submission email.', [
                    'requisition_id' => $requisition->id,
                    'user_id' => $recipient->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $queued;
    }

    private function validateSelectedOptions(Supply $supply, array $selected): array
    {
        $configured = $supply->requestOptions();
        $clean = [];

        foreach ($configured as $label => $choices) {
            $value = $selected[$label] ?? (count($choices) === 1 ? $choices[0] : null);
            if (! is_string($value) || ! in_array($value, $choices, true)) {
                throw ValidationException::withMessages([
                    'items' => "Please select a valid {$label} for {$supply->item_name}.",
                ]);
            }
            $clean[$label] = $value;
        }

        return $clean;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function prepareItems(array $submittedItems, string $requestType): Collection
    {
        $catalogItems = Supply::whereIn('id', collect($submittedItems)->pluck('supply_id'))
            ->get()
            ->keyBy('id');

        $preparedItems = collect($submittedItems)->map(function (array $itemData) use ($catalogItems, $requestType) {
            $supply = $catalogItems->get($itemData['supply_id']);

            if (! $supply || $supply->requisition_tier !== $requestType) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected items do not belong to this requisition tier.',
                ]);
            }
            if ($itemData['qty'] > $supply->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Only {$supply->quantity} {$supply->unit} of {$supply->item_name} are currently available.",
                ]);
            }

            // Item snapshots store prices in DECIMAL(10, 2).
            if ((float) $supply->unit_price > 99999999.99) {
                throw ValidationException::withMessages([
                    'items' => "The price of {$supply->item_name} exceeds the supported requisition price. Please contact the SMO.",
                ]);
            }

            $selectedOptions = $this->validateSelectedOptions($supply, $itemData['options'] ?? []);
            $optionSummary = collect($selectedOptions)
                ->map(fn ($value, $label) => "{$label}: {$value}")
                ->implode('; ');
            $specifications = collect([$supply->specifications, $optionSummary])->filter()->implode(' | ');

            return [
                'supply' => $supply,
                'specifications' => $specifications ?: null,
                'quantity' => (int) $itemData['qty'],
                'subtotal' => (int) $itemData['qty'] * (float) $supply->unit_price,
            ];
        });

        foreach ($preparedItems->groupBy(fn ($item) => $item['supply']->id) as $itemsForSupply) {
            $supply = $itemsForSupply->first()['supply'];
            if ($itemsForSupply->sum('quantity') > $supply->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Only {$supply->quantity} {$supply->unit} of {$supply->item_name} are available across all selected options.",
                ]);
            }
        }

        // Requisition totals and item subtotals use DECIMAL(12, 2).
        if ($preparedItems->sum('subtotal') > 9999999999.99) {
            throw ValidationException::withMessages([
                'items' => 'The requisition total cannot exceed PHP 9,999,999,999.99.',
            ]);
        }

        return $preparedItems;
    }
}
