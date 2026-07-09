<?php

namespace App\Services;

use App\Models\IncomeLog;
use App\Models\MLMTreeClosure;
use App\Models\Order;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LevelIncomeService
{
    private const MAX_LEVELS = 10;
    private const LEVEL_PERCENTAGES = [
        1 => 6.0,
        2 => 4.0,
        3 => 3.0,
        4 => 2.0,
        5 => 2.0,
    ];

    public function __construct(
        private readonly IncomeBaseProvider $incomeBaseProvider,
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function processLevelIncome(Order $order, float $orderCC): array
    {
        $results = [];
        $buyerUserId = $order->user_id;

        $ancestors = MLMTreeClosure::where('descendant_id', function ($q) use ($buyerUserId) {
            $q->select('id')->from('mlm_trees')->where('mlm_user_id', $buyerUserId);
        })
        ->where('depth', '>', 0)
        ->where('depth', '<=', self::MAX_LEVELS)
        ->orderBy('depth')
        ->get();

        if ($ancestors->isEmpty()) {
            Log::info('No ancestors found for level income', ['user_id' => $buyerUserId]);
            return $results;
        }

        $baseAmount = $this->incomeBaseProvider->getBaseAmount($order);

        foreach ($ancestors as $closure) {
            $level = (int) $closure->depth;
            $ancestorTreeId = $closure->ancestor_id;

            $ancestorUserId = DB::table('mlm_trees')
                ->where('id', $ancestorTreeId)
                ->value('mlm_user_id');

            if (!$ancestorUserId) {
                continue;
            }

            $levelPct = $this->getLevelPercentage($level);
            if ($levelPct <= 0) {
                continue;
            }

            $levelAmount = $baseAmount * ($levelPct / 100);

            if ($levelAmount <= 0) {
                continue;
            }

            try {
                DB::transaction(function () use ($ancestorUserId, $levelAmount, $order, $buyerUserId, $level, $orderCC, $levelPct, &$results) {
                    $wallet = WalletBalance::firstOrCreate(
                        ['user_id' => $ancestorUserId, 'wallet_id' => 1],
                        ['balance' => 0, 'total_earned' => 0]
                    );

                    $wallet->increment('balance', $levelAmount);
                    $wallet->increment('total_earned', $levelAmount);
                    $wallet->refresh();

                    WalletTransaction::create([
                        'wallet_id' => 1,
                        'user_id' => $ancestorUserId,
                        'type' => 'credit',
                        'amount' => $levelAmount,
                        'balance_after' => $wallet->balance,
                        'reference_type' => 'level_income',
                        'reference_id' => $order->id,
                        'status' => 'completed',
                        'description' => "Level {$level} income from User #{$buyerUserId} (Order #{$order->id})",
                    ]);

                    $this->incomeLogService->logFromOrder(
                        order: $order,
                        earnerUserId: $ancestorUserId,
                        incomeType: 'level',
                        ccAmount: $orderCC,
                        currencyAmount: $levelAmount,
                        fromUserId: $buyerUserId,
                        remarks: "Level {$level} income - {$levelPct}% of {$this->incomeBaseProvider->getLabel()}",
                    );

                    $this->notificationService->createIncomeNotification(
                        $ancestorUserId,
                        $levelAmount,
                        "Level {$level} Income"
                    );

                    $results[] = [
                        'user_id' => $ancestorUserId,
                        'level' => $level,
                        'amount' => $levelAmount,
                    ];
                });
            } catch (\Throwable $e) {
                Log::error("Level income failed for ancestor {$ancestorUserId}", [
                    'error' => $e->getMessage(),
                    'level' => $level,
                ]);
            }
        }

        Log::info('Level income processed', [
            'order_id' => $order->id,
            'buyer_user_id' => $buyerUserId,
            'base_amount' => $baseAmount,
            'levels_distributed' => count($results),
        ]);

        return $results;
    }

    public function getLevelPercentage(int $level): float
    {
        return self::LEVEL_PERCENTAGES[$level] ?? 0;
    }

    public static function getLevelConfig(): array
    {
        return self::LEVEL_PERCENTAGES;
    }

    public function getTotalLevelIncome(int $userId): float
    {
        return (float) IncomeLog::where('user_id', $userId)
            ->where('income_type', 'level')
            ->sum('currency_amount');
    }
}
