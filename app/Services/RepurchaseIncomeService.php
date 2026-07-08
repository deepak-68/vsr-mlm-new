<?php

namespace App\Services;

use App\Models\CCSetting;
use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepurchaseIncomeService
{
    public function __construct(
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function isRepurchase(int $userId): bool
    {
        $completedOrdersCount = Order::where('user_id', $userId)
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        return $completedOrdersCount > 0;
    }

    public function getRepurchaseCommissionRate(int $userId): float
    {
        $user = MlmUser::find($userId);
        if (!$user) {
            return 0;
        }

        $totalQuantity = OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', Order::STATUS_COMPLETED);
        })->sum('quantity');

        $percentage = match (true) {
            $totalQuantity >= 40 => 20,
            $totalQuantity >= 20 => 18,
            $totalQuantity >= 12 => 16,
            $totalQuantity >= 6  => 14,
            $totalQuantity >= 2  => 12,
            default              => 10,
        };

        return $percentage;
    }

    public function processRepurchaseIncome(Order $order, float $orderCC): array
    {
        $buyerUserId = $order->user_id;
        $results = [];

        $sponsor = MlmUser::find($buyerUserId)?->sponsor;
        if (!$sponsor) {
            Log::info('No sponsor found for repurchase income', ['user_id' => $buyerUserId]);
            return $results;
        }

        $commissionPct = $this->getRepurchaseCommissionRate($buyerUserId);
        $ccRate = CCSetting::getActiveRate();
        $commissionAmount = ($orderCC * $ccRate) * ($commissionPct / 100);

        if ($commissionAmount <= 0) {
            return $results;
        }

        try {
            DB::transaction(function () use ($sponsor, $commissionAmount, $order, $buyerUserId, $orderCC, $commissionPct, &$results) {
                $wallet = WalletBalance::firstOrCreate(
                    ['user_id' => $sponsor->id, 'wallet_id' => 1],
                    ['balance' => 0, 'total_earned' => 0]
                );

                $wallet->increment('balance', $commissionAmount);
                $wallet->increment('total_earned', $commissionAmount);
                $wallet->refresh();

                WalletTransaction::create([
                    'wallet_id' => 1,
                    'user_id' => $sponsor->id,
                    'type' => 'credit',
                    'amount' => $commissionAmount,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'repurchase_income',
                    'reference_id' => $order->id,
                    'status' => 'completed',
                    'description' => "Repurchase income from User #{$buyerUserId} (Order #{$order->id})",
                ]);

                $this->incomeLogService->logFromOrder(
                    order: $order,
                    earnerUserId: $sponsor->id,
                    incomeType: 'repurchase',
                    ccAmount: $orderCC,
                    currencyAmount: $commissionAmount,
                    fromUserId: $buyerUserId,
                    remarks: "Repurchase income - {$commissionPct}% commission on {$orderCC} CC",
                );

                $this->notificationService->createIncomeNotification(
                    $sponsor->id,
                    $commissionAmount,
                    'Repurchase Income'
                );

                $results[] = [
                    'sponsor_id' => $sponsor->id,
                    'amount' => $commissionAmount,
                    'commission_pct' => $commissionPct,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Repurchase income failed', [
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Repurchase income processed', [
            'order_id' => $order->id,
            'buyer_user_id' => $buyerUserId,
            'sponsor_id' => $sponsor->id,
            'amount' => $commissionAmount,
        ]);

        return $results;
    }

    public function getTotalRepurchaseIncome(int $userId): float
    {
        return (float) IncomeLog::where('user_id', $userId)
            ->where('income_type', 'repurchase')
            ->sum('currency_amount');
    }
}
