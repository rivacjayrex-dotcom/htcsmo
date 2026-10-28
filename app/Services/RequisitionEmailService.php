<?php

namespace App\Services;

use App\Jobs\SendRequisitionEmail;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequisitionEmailService
{
    /** @return Collection<int, User> */
    public function reviewers(Requisition $requisition): Collection
    {
        $role = $requisition->nextReviewerRole();
        if (! $role) {
            return collect();
        }

        return User::query()->where('role', $role)
            ->where('account_status', User::ACCOUNT_ACTIVE)
            ->whereKeyNot($requisition->user_id)
            ->when($role === 'dept_head', fn ($query) => $query->where('department_id', $requisition->user->department_id))
            ->get();
    }

    public function notifyReviewers(Requisition $requisition): void
    {
        try {
            $recipients = $this->reviewers($requisition);
        } catch (Throwable $exception) {
            Log::error('Could not find requisition email reviewers.', ['requisition_id' => $requisition->id]);

            return;
        }

        foreach ($recipients as $recipient) {
            try {
                $readyForProcessing = $recipient->role === 'smo';
                SendRequisitionEmail::dispatch(
                    $recipient->email, $recipient->id, $requisition->id,
                    $readyForProcessing ? 'Requisition Approved - Ready for SMO' : 'Requisition Awaiting Your Approval',
                    $readyForProcessing ? 'Approved Requisition Ready for Processing' : 'A Requisition Needs Your Review',
                    "Requisition {$requisition->tracking_code} from {$requisition->user->name} is ready for your action. Sign in to review the details.",
                    '#185b3b',
                    route('admin.approvals', ['request' => $requisition->id]),
                );
            } catch (Throwable $exception) {
                Log::error('Could not queue requisition reviewer email.', [
                    'requisition_id' => $requisition->id, 'user_id' => $recipient->id,
                ]);
            }
        }
    }
}
