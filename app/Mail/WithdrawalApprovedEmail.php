<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalApprovedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public float $amount,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Withdrawal Request ' . ucfirst($this->status));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.withdrawal-approved', with: [
            'user' => $this->user,
            'amount' => $this->amount,
            'status' => $this->status,
        ]);
    }
}
