<?php

namespace App\Services;

use App\Models\CCSetting;
use App\Models\IncomeLog;
use App\Models\MLMTree;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncomeService
{
    public function __construct(
        private readonly BinaryMatchingService $binaryMatchingService,
        private readonly PayoutService $payoutService,
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
        private readonly LevelIncomeService $levelIncomeService,
        private readonly RepurchaseIncomeService $repurchaseIncomeService,
    ) {}

    public function processDirectIncome(Order $order, int $quantity, int $commission): array
    {
        $sponsorId = MLMTree::where('mlm_user_id', $order->user_id)->value('parent_id');

        if (!$sponsorId) {
            Log::info('No sponsor found for direct income', ['user_id' => $order->user_id]);
            return ['sponsor_id' => null, 'amount' => 0];
        }

        $orderCC = (float) ($order->total_cc_points ?? 0);
        $ccRate = CCSetting::getActiveRate();
        $totalAmount = $orderCC * $ccRate;

        $this->creditWallet(
            userId: $sponsorId,
            amount: $totalAmount,
            referenceId: $order->id,
            description: "Direct income from User {$order->user_id} (Order #{$order->id})"
        );

        $this->incomeLogService->logFromOrder(
            order: $order,
            earnerUserId: $sponsorId,
            incomeType: 'direct',
            ccAmount: $orderCC,
            currencyAmount: $totalAmount,
            fromUserId: $order->user_id,
            remarks: "Direct income from User #{$order->user_id} - {$quantity} product(s)"
        );

        $this->notificationService->createIncomeNotification($sponsorId, $totalAmount, 'Direct Income');

        Log::info('Direct income processed', [
            'order_id'      => $order->id,
            'sponsor_id'    => $sponsorId,
            'total_amount'  => $totalAmount,
            'order_cc'      => $orderCC,
            'cc_rate'       => $ccRate,
            'commission'    => $commission,
            'quantity'      => $quantity,
        ]);

        return [
            'sponsor_id' => $sponsorId,
            'amount'     => $totalAmount,
        ];
    }

    public function processBinaryIncome(Order $order, float $orderCC, int $userId): array
    {
        $results = $this->binaryMatchingService->processOrderMatching($order, $orderCC);

        Log::info('Binary income processed', [
            'order_id' => $order->id,
            'user_id'  => $userId,
            'order_cc' => $orderCC,
        ]);

        return [
            'matched' => !empty($results),
            'result'  => $results,
        ];
    }

    public function processLevelIncome(Order $order, float $orderCC): array
    {
        return $this->levelIncomeService->processLevelIncome($order, $orderCC);
    }

    public function processRepurchaseIncome(Order $order, float $orderCC): array
    {
        if (!$this->repurchaseIncomeService->isRepurchase($order->user_id)) {
            return ['is_repurchase' => false];
        }

        return $this->repurchaseIncomeService->processRepurchaseIncome($order, $orderCC);
    }

    public function getTotalDirectIncome(int $userId): float
    {
        return (float) IncomeLog::where('user_id', $userId)
            ->where('income_type', 'direct')
            ->sum('currency_amount');
    }

    public function getTotalMatchingIncome(int $userId): float
    {
        return (float) IncomeLog::where('user_id', $userId)
            ->where('income_type', 'matching')
            ->sum('currency_amount');
    }

    public function getIncomeSummary(int $userId): array
    {
        $direct = $this->getTotalDirectIncome($userId);
        $matching = $this->getTotalMatchingIncome($userId);
        $level = $this->levelIncomeService->getTotalLevelIncome($userId);
        $repurchase = $this->repurchaseIncomeService->getTotalRepurchaseIncome($userId);

        return [
            'direct_income' => $direct,
            'matching_income' => $matching,
            'level_income' => $level,
            'repurchase_income' => $repurchase,
            'total_income' => $direct + $matching + $level + $repurchase,
        ];
    }

    public function creditWallet(int $userId, float $amount, int $referenceId, string $description): void
    {
        $wallet = WalletBalance::firstOrCreate(
            ['user_id' => $userId],
            [
                'wallet_id'    => 1,
                'balance'      => 0,
                'total_earned' => 0,
            ]
        );

        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);

        $wallet->refresh();

        WalletTransaction::create([
            'wallet_id'       => $wallet->wallet_id,
            'user_id'         => $userId,
            'type'            => 'credit',
            'amount'          => $amount,
            'balance_after'   => $wallet->balance,
            'reference_type'  => Order::class,
            'reference_id'    => $referenceId,
            'status'          => 'completed',
            'description'     => $description,
        ]);
    }
}
