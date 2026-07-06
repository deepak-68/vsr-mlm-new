<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MlmUser $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Account Has Been Activated');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-activation', with: ['user' => $this->user]);
    }
}
