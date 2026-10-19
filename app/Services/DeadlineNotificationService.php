<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Collection;

class DeadlineNotificationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @return array{created: int, delivered: int}
     */
    public function notify(Requisition $requisition): array
    {
        $requisition->loadMissing(['user', 'items']);

        if (! $requisition->isDeadlineNearing() && ! $requisition->isDeadlineOverdue()) {
            return ['created' => 0, 'delivered' => 0];
        }

        $overdue = $requisition->isDeadlineOverdue();
        $state = $overdue ? 'overdue' : 'nearing';
        $reviewerRole = $requisition->currentReviewerRole();
        $stage = $this->stageLabel($reviewerRole, $requisition->status);
        $due = $requisition->due_at?->format('M j, Y g:i A') ?? 'the recorded deadline';
        $title = $overdue ? 'Overdue: 3-Working-Day SLA Exceeded' : 'Near Deadline: 3-Working-Day SLA';
        $message = $overdue
            ? "Requisition {$requisition->tracking_code} is overdue while at {$stage}. It was due {$due}."
            : "Requisition {$requisition->tracking_code} is nearing its {$due} deadline and is currently at {$stage}.";

        $recipients = $this->recipients($requisition, $reviewerRole);
        $created = 0;
        $delivered = 0;

        foreach ($recipients as $recipient) {
            $isOwner = $recipient->id === $requisition->user_id;
            $canOpenRequest = $isOwner || $recipient->role === 'smo';
            $notification = $this->notifications->send(
                $recipient,
                "deadline.{$state}",
                $title,
                $message,
                [
                    'icon' => $overdue ? 'alert-triangle' : 'clock',
                    'type' => $overdue ? 'danger' : 'warning',
                    'action_type' => $canOpenRequest ? 'requisition' : 'approval_queue',
                    'action_id' => $requisition->id,
                    'dedupe_key' => "deadline:{$requisition->id}:{$state}:{$recipient->id}:".now()->toDateString(),
                ]
            );

            $delivered += (int) ($notification !== null);
            $created += (int) ($notification?->wasRecentlyCreated === true);
        }

        return ['created' => $created, 'delivered' => $delivered];
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(Requisition $requisition, ?string $reviewerRole): Collection
    {
        $recipients = User::query()
            ->where('role', 'smo')
            ->where('account_status', User::ACCOUNT_ACTIVE)
            ->get();

        if ($requisition->user) {
            $recipients->push($requisition->user);
        }

        if ($reviewerRole && $reviewerRole !== 'smo') {
            $reviewers = User::query()
                ->where('role', $reviewerRole)
                ->where('account_status', User::ACCOUNT_ACTIVE)
                ->when(
                    $reviewerRole === 'dept_head',
                    fn ($users) => $users->where('department_id', $requisition->user?->department_id)
                )
                ->get();
            $recipients = $recipients->merge($reviewers);
        }

        return $recipients->unique('id')->values();
    }

    private function stageLabel(?string $reviewerRole, string $status): string
    {
        if (in_array($status, ['processing', 'ready_for_pickup'], true)) {
            return $status === 'processing' ? 'SMO processing' : 'SMO pickup preparation';
        }

        return match ($reviewerRole) {
            'dept_head' => 'Department Head, Dean, or Principal approval',
            'vp_finance' => 'VP Finance approval',
            'vp_admin' => 'VP Administration approval',
            'provost' => 'Provost approval',
            'president' => 'President approval',
            'smo' => 'SMO processing',
            default => 'the current workflow stage',
        };
    }
}
