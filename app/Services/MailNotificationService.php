<?php

namespace App\Services;

use App\Mail\AccountActivationEmail;
use App\Mail\BinaryPositionEmail;
use App\Mail\InvoiceEmail;
use App\Mail\RankAchievedEmail;
use App\Mail\RewardAchievedEmail;
use App\Mail\SponsorCccEmail;
use App\Mail\SponsorNotificationEmail;
use App\Mail\TicketUpdateEmail;
use App\Mail\WelcomeEmail;
use App\Mail\WithdrawalApprovedEmail;
use App\Models\Invoice;
use App\Models\MlmUser;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailNotificationService
{
    public function sendWelcome(MlmUser $user): void
    {
        Mail::to($user->email)->queue(new WelcomeEmail($user));
    }

    public function sendSponsorNotification(MlmUser $sponsor, MlmUser $newUser): void
    {
        Mail::to($sponsor->email)->queue(new SponsorNotificationEmail($sponsor, $newUser));
    }

    public function sendSponsorActivation(MlmUser $sponsor, MlmUser $activatedUser): void
    {
        Mail::to($sponsor->email)->queue(new SponsorActivationEmail($sponsor, $activatedUser));
    }

    public function sendBinaryPosition(MlmUser $user, string $position, ?string $parentName = null): void
    {
        Mail::to($user->email)->queue(new BinaryPositionEmail($user, $position, $parentName));
    }

    public function sendInvoice(MlmUser $user, Invoice $invoice): void
    {
        Mail::to($user->email)->queue(new InvoiceEmail($user, $invoice));
    }

    public function sendAccountActivation(MlmUser $user): void
    {
        Mail::to($user->email)->queue(new AccountActivationEmail($user));
    }

    public function sendRewardAchieved(MlmUser $user, string $rewardName, string $rankName): void
    {
        Mail::to($user->email)->queue(new RewardAchievedEmail($user, $rewardName, $rankName));
    }

    public function sendRankAchieved(MlmUser $user, string $rankName): void
    {
        Mail::to($user->email)->queue(new RankAchievedEmail($user, $rankName));
    }

    public function sendWithdrawalUpdate(MlmUser $user, float $amount, string $status): void
    {
        Mail::to($user->email)->queue(new WithdrawalApprovedEmail($user, $amount, $status));
    }

    public function sendTicketUpdate(MlmUser $user, string $ticketNo, string $update): void
    {
        Mail::to($user->email)->queue(new TicketUpdateEmail($user, $ticketNo, $update));
    }

    public function sendSponsorCc(MlmUser $sponsor, MlmUser $recipient, Order $order, float $ccPoints): void
    {
        Mail::to($sponsor->email)->queue(new SponsorCccEmail($sponsor, $recipient, $order, $ccPoints));
    }

    public function sendInvoiceToUser(MlmUser $user, Invoice $invoice): void
    {
        if ($user->email) {
            Mail::to($user->email)->queue(new InvoiceEmail($user, $invoice));
        }
    }
}
