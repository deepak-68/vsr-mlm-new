<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SponsorCccEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $sponsor,
        public MlmUser $recipient,
        public Order $order,
        public float $ccPoints,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'CC Points Credited – Order #' . $this->order->id);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sponsor-cc', with: [
            'sponsor' => $this->sponsor,
            'recipient' => $this->recipient,
            'order' => $this->order,
            'ccPoints' => $this->ccPoints,
        ]);
    }
}
