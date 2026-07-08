<?php

namespace App\Services;

use App\Models\MLMTree;
use App\Models\Order;
use App\Models\PairMatchingLog;
use App\Models\PayoutBalance;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinaryMatchingService
{
    const CC_PER_PAIR = 360;
    const INCOME_PER_PAIR = 100;
    const WALLET_ID = 2;

    public function __construct(
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Process binary matching for a completed purchase order.
     * Idempotent: if pair_matching_logs exist for this order, skips entirely.
     */
    public function processOrderMatching(Order $order, float $ccPoints): array
    {
        if ($ccPoints <= 0) {
            return [];
        }

        if (PairMatchingLog::where('order_id', $order->id)->exists()) {
            Log::info('Order already processed for matching', ['order_id' => $order->id]);
            return [];
        }

        $userId = $order->user_id;
        $ancestors = $this->getBinaryAncestors($userId);

        if (empty($ancestors)) {
            return [];
        }

        $results = [];

        DB::transaction(function () use ($ancestors, $ccPoints, $order, $userId, &$results) {
            foreach ($ancestors as $ancestor) {
                $result = $this->processSingleAncestor(
                    userId: $ancestor['user_id'],
                    orderCc: $ccPoints,
                    position: $ancestor['position'],
                    orderId: $order->id,
                    fromUserId: $userId,
                );

                if ($result !== null) {
                    $results[] = $result;
                }
            }
        });

        Log::info('Binary matching processed', [
            'order_id' => $order->id,
            'cc_points' => $ccPoints,
            'ancestors_matched' => count($results),
        ]);

        return $results;
    }

    /**
     * Walk up the MLM binary tree via parent_id chain.
     * Returns array of [user_id, position (left/right), level].
     */
    public function getBinaryAncestors(int $userId): array
    {
        $ancestors = [];
        $currentTree = MLMTree::where('mlm_user_id', $userId)->first();

        if (!$currentTree) {
            return [];
        }

        while ($currentTree && $currentTree->parent_id) {
            $parentTree = MLMTree::find($currentTree->parent_id);

            if (!$parentTree || !$parentTree->mlmUser) {
                break;
            }

            if ($currentTree->position === null || $currentTree->position === 'none') {
                break;
            }

            $ancestors[] = [
                'user_id' => $parentTree->mlm_user_id,
                'position' => $currentTree->position,
                'level' => $currentTree->level,
            ];

            $currentTree = $parentTree;
        }

        return $ancestors;
    }

    /**
     * Process matching for one ancestor:
     * 1. Add CC to the correct leg
     * 2. Calculate new pairs
     * 3. If pairs > 0: deduct CC, create log, credit wallet
     */
    private function processSingleAncestor(
        int $userId,
        float $orderCc,
        string $position,
        int $orderId,
        int $fromUserId,
    ): ?array {
        $balance = PayoutBalance::firstOrCreate(
            ['mlm_user_id' => $userId],
            [
                'left_cc' => 0,
                'right_cc' => 0,
                'left_team_cc' => 0,
                'right_team_cc' => 0,
                'cc_balance' => 0,
                'available_balance' => 0,
                'total_earned' => 0,
                'total_matched_cc' => 0,
            ]
        );

        $leftBefore = (float) $balance->left_cc;
        $rightBefore = (float) $balance->right_cc;

        if ($position === 'left') {
            $balance->increment('left_cc', $orderCc);
            $balance->increment('left_team_cc', $orderCc);
        } else {
            $balance->increment('right_cc', $orderCc);
            $balance->increment('right_team_cc', $orderCc);
        }

        $balance->refresh();

        $leftAfterAdd = (float) $balance->left_cc;
        $rightAfterAdd = (float) $balance->right_cc;

        $pairs = $this->calculatePairs($leftAfterAdd, $rightAfterAdd);

        if ($pairs <= 0) {
            return null;
        }

        $income = $pairs * self::INCOME_PER_PAIR;
        $usedCc = $pairs * self::CC_PER_PAIR;

        $balance->decrement('left_cc', $usedCc);
        $balance->decrement('right_cc', $usedCc);
        $balance->increment('total_matched_cc', $usedCc);
        $balance->increment('total_earned', $income);

        $balance->refresh();

        $leftAfter = (float) $balance->left_cc;
        $rightAfter = (float) $balance->right_cc;

        PairMatchingLog::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'from_user_id' => $fromUserId,
            'pairs_count' => $pairs,
            'left_cc_used' => $usedCc,
            'right_cc_used' => $usedCc,
            'income_amount' => $income,
            'left_cc_before' => $leftBefore,
            'left_cc_after' => $leftAfter,
            'right_cc_before' => $rightBefore,
            'right_cc_after' => $rightAfter,
        ]);

        $this->creditMatchingIncome($userId, $income, $usedCc, $orderId, $fromUserId);

        return [
            'user_id' => $userId,
            'pairs' => $pairs,
            'income' => $income,
            'left_used' => $usedCc,
            'right_used' => $usedCc,
            'left_cc_before' => $leftBefore,
            'left_cc_after' => $leftAfter,
            'right_cc_before' => $rightBefore,
            'right_cc_after' => $rightAfter,
        ];
    }

    /**
     * Pure function: calculate pairs from left and right CC.
     */
    public function calculatePairs(float $leftCc, float $rightCc): int
    {
        return (int) floor(min($leftCc, $rightCc) / self::CC_PER_PAIR);
    }

    /**
     * Credit matching income to user's commission wallet and create logs.
     */
    private function creditMatchingIncome(
        int $userId,
        float $amount,
        float $matchedCc,
        int $orderId,
        int $fromUserId,
    ): void {
        $wallet = WalletBalance::firstOrCreate(
            ['user_id' => $userId, 'wallet_id' => self::WALLET_ID],
            ['balance' => 0, 'total_earned' => 0]
        );

        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);

        $wallet->refresh();

        WalletTransaction::create([
            'wallet_id' => self::WALLET_ID,
            'user_id' => $userId,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'reference_type' => 'matching_income',
            'reference_id' => $orderId,
            'status' => 'completed',
            'description' => "Binary matching income: {$matchedCc} CC matched, ₹{$amount} credited",
        ]);

        $this->incomeLogService->log(
            userId: $userId,
            incomeType: 'matching',
            ccAmount: $matchedCc,
            currencyAmount: $amount,
            fromUserId: $fromUserId,
            referenceType: 'order',
            referenceId: $orderId,
            remarks: "Binary matching - {$matchedCc} CC matched, {$amount} pairs, ₹{$amount} credited",
        );

        try {
            $this->notificationService->createIncomeNotification(
                $userId,
                $amount,
                'Matching Income'
            );
        } catch (\Throwable $e) {
            Log::warning('Matching income notification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
