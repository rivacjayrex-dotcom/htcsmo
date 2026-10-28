<?php

namespace App\Jobs;

use App\Mail\AccountApprovedMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Support\MailFailure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAccountApprovedEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $userId)
    {
        $this->afterCommit();
        $this->onQueue('mail');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        // The account may have been removed or suspended while mail waited.
        if (! $user || ! $user->hasActiveAccess()) {
            return;
        }

        Mail::to($user->email)->send(new AccountApprovedMail($user));
        $this->log($user, 'sent');
    }

    public function failed(?Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $this->log($user, 'failed', $exception ? MailFailure::context($exception)['reason'] : 'mail_delivery_failed');
        }
    }

    private function log(User $user, string $status, ?string $error = null): void
    {
        EmailLog::create([
            'user_id' => $user->id,
            'recipient_email' => $user->email,
            'subject' => AccountApprovedMail::SUBJECT,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }
}
