<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketUpdateEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public string $ticketNo,
        public string $update
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ticket #' . $this->ticketNo . ' Updated');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket-update', with: [
            'user' => $this->user,
            'ticketNo' => $this->ticketNo,
            'update' => $this->update,
        ]);
    }
}
