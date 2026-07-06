<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RankAchievedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public string $rankName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Congratulations on Your New Rank!');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rank-achieved', with: [
            'user' => $this->user,
            'rankName' => $this->rankName,
        ]);
    }
}
