<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SponsorActivationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MlmUser $sponsor, public MlmUser $activatedUser) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Team Member Has Activated Their Account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sponsor-activation', with: [
            'sponsor' => $this->sponsor,
            'activatedUser' => $this->activatedUser,
        ]);
    }
}
