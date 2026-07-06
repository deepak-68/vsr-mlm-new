<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\MlmUser;

class NotificationService
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'mlm_user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('mlm_user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(int $notificationId): void
    {
        Notification::where('id', $notificationId)->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('mlm_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function createRegistrationNotification(int $userId): void
    {
        $this->create($userId, 'registration', 'Registration Successful',
            'Welcome! Your account has been created successfully.');
    }

    public function createIncomeNotification(int $userId, float $amount, string $incomeType): void
    {
        $this->create($userId, 'income', "{$incomeType} Credited",
            "You have received ₹{$amount} as {$incomeType}.");
    }

    public function createRankNotification(int $userId, string $rankName): void
    {
        $this->create($userId, 'rank', 'Rank Upgraded',
            "Congratulations! You have achieved the {$rankName} rank.");
    }

    public function createRewardNotification(int $userId, string $rewardName): void
    {
        $this->create($userId, 'reward', 'Reward Achieved',
            "Congratulations! You have earned the {$rewardName} reward.");
    }

    public function createWithdrawalNotification(int $userId, string $status, string $remarks = ''): void
    {
        $this->create($userId, 'withdrawal', "Withdrawal {$status}",
            "Your withdrawal request has been {$status}. {$remarks}");
    }

    public function createTicketNotification(int $userId, string $ticketNo, string $update): void
    {
        $this->create($userId, 'ticket', "Ticket #{$ticketNo} Updated",
            "Your grievance ticket #{$ticketNo} has been updated: {$update}");
    }
}
