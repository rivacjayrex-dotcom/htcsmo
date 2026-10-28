<?php

namespace App\Console\Commands;

use App\Support\MailFailure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMail extends Command
{
    protected $signature = 'mail:test {recipient? : Email address to receive one test email}
        {--check : Check local configuration without sending email}';

    protected $description = 'Check outgoing mail configuration or send one delivery test';

    public function handle(): int
    {
        $mailer = config('mail.default');
        if (! in_array($mailer, ['brevo', 'smtp'], true)) {
            $this->error('Set MAIL_MAILER=brevo for Render or smtp for an SMTP-capable host.');

            return self::FAILURE;
        }

        if ($mailer === 'brevo' && blank(config('mail.mailers.brevo.key'))) {
            $this->error('BREVO_API_KEY is missing. Add a Brevo API key privately to your environment.');

            return self::FAILURE;
        }

        if ($mailer === 'smtp' && (blank(config('mail.mailers.smtp.username')) || blank(config('mail.mailers.smtp.password')))) {
            $this->error('SMTP login or password is missing.');

            return self::FAILURE;
        }

        $sender = config('mail.from.address');
        if (! filter_var($sender, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($sender), '@example.com')) {
            $this->error('Set MAIL_FROM_ADDRESS to a sender verified in Brevo.');

            return self::FAILURE;
        }

        if ($this->option('check')) {
            $this->info('Local mail configuration is complete. No email was sent; Brevo sender verification and inbox delivery still need testing.');

            return self::SUCCESS;
        }

        $recipient = $this->argument('recipient');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid recipient: php artisan mail:test you@example.com');

            return self::FAILURE;
        }

        try {
            Mail::raw('This is a delivery test from the HTC Supply Management Office. Application: '.config('app.url'),
                fn ($message) => $message->to($recipient)->subject('HTC SMO Email Delivery Test'));
        } catch (Throwable $exception) {
            $context = MailFailure::context($exception);
            Log::error('Mail delivery test failed.', $context);
            $this->error('Mail failure reason: '.$context['reason'].(isset($context['http_status']) ? ' (HTTP '.$context['http_status'].')' : ''));
            $this->error('Email could not be sent. Check the key, verified sender, Brevo transactional access, daily quota, and network connection.');

            return self::FAILURE;
        }

        $this->info('The email provider accepted the test email. Confirm receipt in the inbox/spam folder and check Brevo transactional logs.');

        return self::SUCCESS;
    }
}
