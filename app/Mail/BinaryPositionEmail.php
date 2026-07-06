<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BinaryPositionEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public string $position,
        public ?string $parentName = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Binary Tree Position Assigned');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.binary-position', with: [
            'user' => $this->user,
            'position' => $this->position,
            'parentName' => $this->parentName,
        ]);
    }
}
