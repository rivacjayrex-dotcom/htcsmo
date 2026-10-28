<?php

namespace Tests\Feature;

use App\Jobs\SendRequisitionEmail;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\TestCase;

class BrevoMailTest extends TestCase
{
    use RefreshDatabase;

    private array $requests = [];

    private function configureBrevo(int $status = 201, ?array $body = null): void
    {
        config([
            'mail.default' => 'brevo',
            'mail.mailers.brevo.key' => 'test-private-api-key',
            'mail.from.address' => 'smo@school.test',
            'app.url' => 'https://panel.example.test',
        ]);
        $this->app->instance(HttpClientInterface::class, new MockHttpClient(function ($method, $url, $options) use ($status, $body) {
            $this->assertSame('POST', $method);
            $this->assertSame('https://api.brevo.com/v3/smtp/email', $url);
            $this->assertContains('api-key: test-private-api-key', $options['headers']);
            $this->requests[] = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);

            return new MockResponse(json_encode($body ?? ['messageId' => '<brevo-test@example.test>']), [
                'http_code' => $status, 'response_headers' => ['content-type: application/json'],
            ]);
        }));
        Mail::purge();
    }

    public function test_password_reset_email_uses_brevo_and_the_received_link_resets_the_password(): void
    {
        $this->configureBrevo();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

        $this->assertCount(1, $this->requests);
        $payload = $this->requests[0];
        $this->assertSame($user->email, $payload['to'][0]['email']);
        $this->assertSame('smo@school.test', $payload['sender']['email']);
        $this->assertSame('Reset Password Notification', $payload['subject']);
        preg_match('~/reset-password/([a-zA-Z0-9]+)~', $payload['htmlContent'], $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->post('/reset-password', [
            'token' => $matches[1], 'email' => $user->email,
            'password' => 'Panel-demo-password-123!', 'password_confirmation' => 'Panel-demo-password-123!',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Panel-demo-password-123!', $user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user, $matches[1]));
    }

    public function test_unknown_account_does_not_send_email_and_receives_generic_confirmation(): void
    {
        $this->configureBrevo();
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertCount(0, $this->requests);
    }

    public function test_reset_delivery_failure_is_reported_without_exposing_credentials_and_allows_retry(): void
    {
        $this->configureBrevo(401, ['message' => 'Invalid API key']);
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors(['email' => 'Password reset email is temporarily unavailable. Please try again later or contact the SMO.']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->configureBrevo();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertCount(2, $this->requests);
    }

    public function test_requisition_job_sends_rendered_email_through_brevo_and_logs_acceptance(): void
    {
        $this->configureBrevo();
        $owner = User::factory()->create();
        $requisition = Requisition::create(['user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'pending', 'grand_total' => 50]);

        (new SendRequisitionEmail($owner->email, $owner->id, $requisition->id,
            'Submitted', 'Requisition submitted', 'Your request was saved.', '#185b3b'))->handle();

        $this->assertCount(1, $this->requests);
        $this->assertStringContainsString($requisition->tracking_code, $this->requests[0]['subject']);
        $this->assertStringContainsString(route('requests.show', $requisition->id), $this->requests[0]['htmlContent']);
        $this->assertDatabaseHas('email_logs', ['user_id' => $owner->id, 'status' => 'sent']);
    }

    public function test_brevo_rejection_does_not_record_a_successful_requisition_email(): void
    {
        $this->configureBrevo(429, ['message' => 'Daily quota exceeded']);
        $owner = User::factory()->create();
        $requisition = Requisition::create(['user_id' => $owner->id, 'request_type' => 'minor', 'status' => 'pending', 'grand_total' => 50]);
        $job = new SendRequisitionEmail($owner->email, $owner->id, $requisition->id,
            'Submitted', 'Requisition submitted', 'Your request was saved.', '#185b3b');

        try {
            $job->handle();
            $this->fail('Provider rejection must fail the queued job so it can retry.');
        } catch (TransportExceptionInterface $exception) {
            $this->assertDatabaseCount('email_logs', 0);
            $job->failed($exception);
            $this->assertDatabaseHas('email_logs', ['user_id' => $owner->id, 'status' => 'failed']);
        }
    }

    public function test_check_requires_credentials_and_does_not_send_email(): void
    {
        $this->configureBrevo();
        config(['mail.mailers.brevo.key' => '']);
        $this->artisan('mail:test --check')->assertFailed();
        config(['mail.mailers.brevo.key' => 'test-private-api-key']);
        $this->artisan('mail:test --check')->assertSuccessful();
        $this->assertCount(0, $this->requests);
    }

    public function test_mail_test_command_uses_brevo_for_one_real_delivery_request(): void
    {
        $this->configureBrevo();
        $this->artisan('mail:test panelist@example.test')->assertSuccessful();
        $this->assertCount(1, $this->requests);
        $this->assertSame('panelist@example.test', $this->requests[0]['to'][0]['email']);
    }
}
