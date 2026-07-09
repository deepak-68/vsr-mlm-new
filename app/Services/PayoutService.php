<?php
namespace App\Services;

use App\Models\CCSetting;
use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\PayoutBalance;
use App\Models\PayoutTransaction;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    public function __construct(
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
        private readonly BinaryMatchingService $binaryMatchingService,
    ) {}

    /**
     * Delegate to BinaryMatchingService.
     * The old pair-matching logic has been removed and replaced.
     */
    public function processPairMatching($downlineUser, $orderCC, ?int $orderId = null): array
    {
        $order = \App\Models\Order::find($orderId ?? $downlineUser?->id);
        if (!$order) {
            Log::warning('Order not found for matching', ['order_id' => $orderId]);
            return [];
        }
        return $this->binaryMatchingService->processOrderMatching($order, (float) $orderCC);
    }

    public function getUserPayoutSummary($userId)
    {
        $personalCC = \App\Models\OrderItem::whereHas('order', fn($q) =>
            $q->where('user_id', $userId)->where('status', 'COMPLETED')
        )->sum('cc_points');

        $totalProducts = \App\Models\OrderItem::whereHas('order', fn($q) =>
            $q->where('user_id', $userId)->where('status', 'COMPLETED')
        )->sum('quantity');

        $balance = PayoutBalance::where('mlm_user_id', $userId)->first();

        return [
            'personal_cc' => $personalCC,
            'left_team_cc' => $balance ? $balance->left_cc : 0,
            'right_team_cc' => $balance ? $balance->right_cc : 0,
            'available_balance' => $balance ? $balance->available_balance : 0,
            'locked_balance' => $balance ? $balance->locked_balance : 0,
            'total_earned' => $balance ? $balance->total_earned : 0,
            'total_matched_cc' => $balance ? $balance->total_matched_cc : 0,
            'is_eligible' => $totalProducts >= 2,
            'products_needed' => max(0, 2 - $totalProducts),
        ];
    }
}
