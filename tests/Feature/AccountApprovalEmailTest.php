<?php

namespace Tests\Feature;

use App\Jobs\SendAccountApprovedEmail;
use App\Mail\AccountApprovedMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class AccountApprovalEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
    }

    public function test_smo_approval_queues_email_and_creates_an_actionable_in_app_notification(): void
    {
        Queue::fake();
        Mail::fake();
        $smo = User::factory()->create(['role' => 'smo']);
        $user = $this->pendingUser();

        $this->actingAs($smo)->post(route('admin.users.approve', $user), ['version' => 0])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(User::ACCOUNT_ACTIVE, $user->fresh()->account_status);
        Queue::assertPushedOn('mail', SendAccountApprovedEmail::class,
            fn ($job) => $job->userId === $user->id && $job->afterCommit === true);
        Mail::assertNothingSent();
        $notification = Notification::where('user_id', $user->id)->sole();
        $this->assertSame('Account Approved', $notification->title);
        $this->assertSame('account.activated', $notification->event_type);
        $this->assertSame(route('profile.edit'), $notification->actionFor($user)['url']);
    }

    public function test_approval_email_renders_sign_in_and_password_reset_links_and_logs_the_recipient(): void
    {
        $user = User::factory()->create(['name' => 'Approved Faculty']);
        (new SendAccountApprovedEmail($user->id))->handle();

        $message = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame($user->email, $message->getTo()[0]->getAddress());
        $this->assertSame(AccountApprovedMail::SUBJECT, $message->getSubject());
        $this->assertStringContainsString('Approved Faculty', $message->getHtmlBody());
        $this->assertStringContainsString(route('login'), $message->getHtmlBody());
        $this->assertStringContainsString(route('password.request'), $message->getHtmlBody());
        $this->assertStringContainsString('Sign in to your account', $message->getTextBody());
        $this->assertDatabaseHas('email_logs', [
            'user_id' => $user->id, 'recipient_email' => $user->email,
            'subject' => AccountApprovedMail::SUBJECT, 'status' => 'sent',
        ]);
        $this->get(route('login'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }

    public function test_stale_or_repeated_approvals_do_not_queue_duplicate_emails(): void
    {
        Queue::fake();
        $smo = User::factory()->create(['role' => 'smo']);
        $user = $this->pendingUser();
        $this->actingAs($smo)->post(route('admin.users.approve', $user), ['version' => 99])->assertSessionHasErrors('account');
        Queue::assertNothingPushed();

        $this->post(route('admin.users.approve', $user), ['version' => 0])->assertSessionHasNoErrors();
        $this->post(route('admin.users.approve', $user), ['version' => 0])->assertSessionHasErrors('account');
        Queue::assertPushed(SendAccountApprovedEmail::class, 1);
        $this->assertSame(1, Notification::where('user_id', $user->id)->count());
    }

    public function test_other_roles_cannot_approve_an_account_or_send_approval_email(): void
    {
        Queue::fake();
        $head = User::factory()->create(['role' => 'dept_head']);
        $user = $this->pendingUser();
        $this->actingAs($head)->post(route('admin.users.approve', $user), ['version' => 0])->assertRedirect('/dashboard');
        $this->assertSame(User::ACCOUNT_PENDING, $user->fresh()->account_status);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_queue_failure_preserves_approval_and_the_in_app_notification(): void
    {
        $smo = User::factory()->create(['role' => 'smo']);
        $user = $this->pendingUser();
        Bus::partialMock()->shouldReceive('dispatch')->once()->with(\Mockery::type(SendAccountApprovedEmail::class))
            ->andThrow(new \RuntimeException('Queue unavailable'));

        $this->actingAs($smo)->post(route('admin.users.approve', $user), ['version' => 0])
            ->assertSessionHasNoErrors()->assertSessionHas('success', fn ($message) => str_contains($message, 'Some approval notifications could not be created'));
        $this->assertSame(User::ACCOUNT_ACTIVE, $user->fresh()->account_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'event_type' => 'account.activated']);
    }

    public function test_in_app_failure_still_queues_the_approval_email(): void
    {
        Queue::fake();
        $smo = User::factory()->create(['role' => 'smo']);
        $user = $this->pendingUser();
        $this->mock(NotificationService::class)->shouldReceive('send')->once()->andThrow(new \RuntimeException('Notification storage unavailable'));

        $this->actingAs($smo)->post(route('admin.users.approve', $user), ['version' => 0])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(User::ACCOUNT_ACTIVE, $user->fresh()->account_status);
        Queue::assertPushed(SendAccountApprovedEmail::class, 1);
    }

    public function test_suspended_or_deleted_accounts_do_not_receive_a_stale_approval_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['account_status' => User::ACCOUNT_SUSPENDED]);
        $job = new SendAccountApprovedEmail($user->id);
        $job->handle();
        $user->delete();
        $job->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_provider_failure_does_not_log_success_and_exhausted_job_records_safe_failure(): void
    {
        $user = User::factory()->create();
        $job = new SendAccountApprovedEmail($user->id);
        Mail::shouldReceive('to')->once()->with($user->email)->andThrow(new TransportException('Provider unavailable private-secret'));

        try {
            $job->handle();
            $this->fail('A failed delivery must be eligible for retry.');
        } catch (TransportException $exception) {
            $this->assertDatabaseCount('email_logs', 0);
            $job->failed($exception);
        }

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $user->id, 'status' => 'failed', 'error_message' => 'mail_transport_failed',
        ]);
        $this->assertSame(User::ACCOUNT_ACTIVE, $user->fresh()->account_status);
    }

    private function pendingUser(): User
    {
        return User::factory()->create(['department' => 'CETE', 'account_status' => User::ACCOUNT_PENDING, 'is_approved' => false]);
    }
}
