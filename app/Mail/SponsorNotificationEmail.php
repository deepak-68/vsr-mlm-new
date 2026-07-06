<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SponsorNotificationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MlmUser $sponsor, public MlmUser $newUser) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Team Member Joined Under You');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sponsor-notification', with: [
            'sponsor' => $this->sponsor,
            'newUser' => $this->newUser,
        ]);
    }
}
