<?php

namespace Tests\Feature;

use App\Jobs\SendRequisitionEmail;
use App\Models\Requisition;
use App\Models\Supply;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WorkspaceVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequisitionSubmissionEmailTest extends TestCase
{
    use RefreshDatabase;

    public static function submissionRolesAndTiers(): array
    {
        $cases = [];
        foreach (User::ROLES as $role) {
            foreach (['minor', 'major'] as $tier) {
                $cases[$role.' '.$tier] = [$role, $tier];
            }
        }

        return $cases;
    }

    #[DataProvider('submissionRolesAndTiers')]
    public function test_submission_queues_email_for_requestor_active_smo_and_assigned_reviewers(string $role, string $tier): void
    {
        Queue::fake();
        $requestor = User::factory()->create(['role' => $role]);
        $smo = User::factory()->count(2)->create(['role' => 'smo']);
        User::factory()->create(['role' => 'smo', 'account_status' => User::ACCOUNT_PENDING]);
        User::factory()->create(['role' => 'smo', 'account_status' => User::ACCOUNT_SUSPENDED]);
        $head = User::factory()->create(['role' => 'dept_head', 'department_id' => $requestor->department_id]);
        User::factory()->create(['role' => 'dept_head', 'department_id' => $requestor->department_id, 'account_status' => User::ACCOUNT_SUSPENDED]);
        User::factory()->create(['role' => 'dept_head', 'department' => $requestor->department === 'CETE' ? 'CTE' : 'CETE']);
        User::factory()->create(['role' => 'employee']);

        $this->submit($requestor, $this->supply($tier))->assertRedirect(route('dashboard'));

        $requisition = Requisition::firstOrFail();
        $expectedRecipients = $smo->prepend($requestor)->pluck('email')->sort()->values()->all();
        if ($requisition->status === 'pending') {
            $expectedRecipients[] = $head->email;
            sort($expectedRecipients);
        }
        $jobs = Queue::pushed(SendRequisitionEmail::class);
        Queue::assertPushed(SendRequisitionEmail::class, count($expectedRecipients));
        $this->assertSame($expectedRecipients, $jobs->pluck('recipientEmail')->sort()->values()->all());
        $this->assertTrue($jobs->every(fn ($job) => $job->queue === 'mail'
            && $job->afterCommit === true && $job->requisitionId === $requisition->id));
        $ownerJob = $jobs->firstWhere('userId', $requestor->id);
        $this->assertSame('Requisition Submitted', $ownerJob->subject);
        $this->assertStringContainsString($requisition->tracking_code, $ownerJob->message);
        if ($requisition->status === 'pending') {
            $this->assertSame(route('admin.approvals', ['request' => $requisition->id]), $jobs->firstWhere('userId', $head->id)->actionUrl);
        }
        $this->assertTrue($jobs->where('userId', '!=', $requestor->id)->every(
            fn ($job) => $job->subject === 'New Requisition Submitted'
                && str_contains($job->message, $requestor->name)
        ));
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_requestor_receives_confirmation_when_no_smo_account_is_active(): void
    {
        Queue::fake();
        $requestor = User::factory()->create();
        User::factory()->create(['role' => 'smo', 'account_status' => User::ACCOUNT_SUSPENDED]);

        $this->submit($requestor, $this->supply())->assertRedirect(route('dashboard'));

        Queue::assertPushed(SendRequisitionEmail::class, 1);
        Queue::assertPushedOn('mail', SendRequisitionEmail::class, fn ($job) => $job->userId === $requestor->id);
    }

    public function test_invalid_submission_does_not_queue_email(): void
    {
        Queue::fake();
        $requestor = User::factory()->create();
        User::factory()->create(['role' => 'smo']);
        $supply = $this->supply();

        $this->actingAs($requestor)->post(route('requisitions.store'), [
            'request_type' => 'minor', 'purpose' => 'Too short',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ])->assertSessionHasErrors('purpose');

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('requisitions', 0);
    }

    public function test_submission_jobs_render_email_and_log_each_recipient(): void
    {
        Queue::fake();
        $requestor = User::factory()->create();
        $smo = User::factory()->create(['role' => 'smo']);
        $supply = $this->supply();
        $this->submit($requestor, $supply)->assertRedirect(route('dashboard'));
        $requisition = Requisition::firstOrFail();

        foreach (Queue::pushed(SendRequisitionEmail::class) as $job) {
            $job->handle();
        }

        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);
        foreach ([$requestor, $smo] as $recipient) {
            $message = $messages->first(fn ($sent) => $sent->getOriginalMessage()->getTo()[0]->getAddress() === $recipient->email)
                ->getOriginalMessage();
            $this->assertStringContainsString($requisition->tracking_code, $message->getSubject());
            $this->assertStringContainsString($supply->item_name, $message->getHtmlBody());
            $this->assertStringContainsString(url('/requests/'.$requisition->id), $message->getHtmlBody());
            $this->assertDatabaseHas('email_logs', [
                'user_id' => $recipient->id, 'recipient_email' => $recipient->email, 'status' => 'sent',
            ]);
        }
        $this->assertDatabaseCount('email_logs', 2);
    }

    public function test_queue_failure_does_not_undo_submission_or_skip_other_recipients(): void
    {
        $requestor = User::factory()->create();
        $smo = User::factory()->create(['role' => 'smo']);
        $attemptedRecipients = [];
        Log::spy();
        Bus::partialMock()->shouldReceive('dispatch')->twice()->andReturnUsing(
            function (SendRequisitionEmail $job) use ($requestor, &$attemptedRecipients) {
                $attemptedRecipients[] = $job->userId;
                if ($job->userId === $requestor->id) {
                    throw new \RuntimeException('Queue unavailable for this recipient');
                }
            }
        );

        $this->submit($requestor, $this->supply())->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'was submitted, but some notifications could not be sent'));

        $this->assertDatabaseCount('requisitions', 1);
        $this->assertSame([$requestor->id, $smo->id], $attemptedRecipients);
        Log::shouldHaveReceived('error')->once()->with('Could not queue requisition submission email.', \Mockery::on(
            fn ($context) => $context['user_id'] === $requestor->id
                && $context['requisition_id'] === Requisition::firstOrFail()->id
        ));
    }

    public function test_smo_recipient_lookup_failure_does_not_turn_a_saved_request_into_a_500(): void
    {
        Queue::fake();
        Log::spy();
        $requestor = User::factory()->create();
        $supply = $this->supply();
        DB::connection()->beforeExecuting(function (string $query, array $bindings): void {
            if (str_contains($query, 'users') && in_array('smo', $bindings, true)) {
                throw new \RuntimeException('SMO recipient lookup unavailable');
            }
        });

        $this->submit($requestor, $supply)->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'was submitted, but some notifications could not be sent'));

        $this->assertDatabaseCount('requisitions', 1);
        $this->assertDatabaseCount('requisition_items', 1);
        $this->assertDatabaseHas('notifications', ['user_id' => $requestor->id, 'event_type' => 'requisition.submitted']);
        Log::shouldHaveReceived('error')->once()->with('Requisition saved but submission notification failed.', \Mockery::on(
            fn ($context) => $context['channel'] === 'email'
                && $context['requisition_id'] === Requisition::firstOrFail()->id
                && $context['exception'] instanceof \RuntimeException
        ));
    }

    public function test_in_app_notification_failure_does_not_undo_submission_or_prevent_email(): void
    {
        Queue::fake();
        Log::spy();
        $requestor = User::factory()->create();
        User::factory()->create(['role' => 'smo']);
        $this->mock(NotificationService::class)->shouldReceive('send')->once()
            ->andThrow(new \RuntimeException('In-app notification unavailable'));

        $this->submit($requestor, $this->supply())->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'was submitted, but some notifications could not be sent'));

        $this->assertDatabaseCount('requisitions', 1);
        $this->assertDatabaseCount('requisition_items', 1);
        Queue::assertPushed(SendRequisitionEmail::class, 2);
        Log::shouldHaveReceived('error')->once()->with('Requisition saved but submission notification failed.', \Mockery::on(
            fn ($context) => $context['channel'] === 'in_app'
                && $context['requisition_id'] === Requisition::firstOrFail()->id
                && $context['exception'] instanceof \RuntimeException
        ));
    }

    public function test_refresh_marker_failure_after_commit_does_not_fail_a_saved_submission_or_skip_notifications(): void
    {
        Queue::fake();
        Log::spy();
        $requestor = User::factory()->create();
        User::factory()->create(['role' => 'smo']);
        $supply = $this->supply();
        $this->partialMock(WorkspaceVersionService::class)->shouldReceive('touch')
            ->with('requisitions')->twice()
            ->andThrow(new \RuntimeException('Refresh cache is unavailable'));

        $response = $this->submit($requestor, $supply);
        $this->assertDatabaseCount('requisitions', 1);
        $this->assertDatabaseCount('requisition_items', 1);
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('notifications', ['user_id' => $requestor->id, 'event_type' => 'requisition.submitted']);
        Queue::assertPushed(SendRequisitionEmail::class, 2);
        Log::shouldHaveReceived('warning')->twice()->with(
            'Workspace refresh marker failed after a committed change.',
            \Mockery::on(fn ($context) => $context['domain'] === 'requisitions'
                && $context['exception'] instanceof \RuntimeException),
        );
    }

    private function submit(User $requestor, Supply $supply): TestResponse
    {
        return $this->actingAs($requestor)->post(route('requisitions.store'), [
            'request_type' => $supply->requisition_tier,
            'purpose' => 'Supplies required for official institutional operations.',
            'items' => [['supply_id' => $supply->id, 'qty' => 1]],
        ]);
    }

    private function supply(string $tier = 'minor'): Supply
    {
        return Supply::create([
            'item_name' => 'Submission Email Test Supply', 'category' => 'Office Supplies',
            'requisition_tier' => $tier, 'quantity' => 20, 'unit' => 'Unit(s)',
            'unit_price' => $tier === 'major' ? 1500 : 50, 'min_stock_level' => 1,
        ]);
    }
}
