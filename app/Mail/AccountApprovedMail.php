<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccountApprovedMail extends Mailable
{
    public const SUBJECT = 'Your HTC SMO Account Has Been Approved';

    public string $recipientName;

    public string $loginUrl;

    public string $resetPasswordUrl;

    public function __construct(User $user)
    {
        $this->recipientName = $user->name;
        $this->loginUrl = route('login');
        $this->resetPasswordUrl = route('password.request');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECT);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.account-approved');
    }
}
