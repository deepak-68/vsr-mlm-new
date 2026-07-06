<?php

namespace App\Mail;

use App\Models\MlmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RewardAchievedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MlmUser $user,
        public string $rewardName,
        public string $rankName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Congratulations! You Earned a Reward');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reward-achieved', with: [
            'user' => $this->user,
            'rewardName' => $this->rewardName,
            'rankName' => $this->rankName,
        ]);
    }
}
