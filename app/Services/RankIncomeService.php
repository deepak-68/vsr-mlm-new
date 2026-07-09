<?php

namespace App\Services;

use App\Models\IncomeLog;
use App\Models\MLMTree;
use App\Models\Order;
use App\Models\UserRank;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankIncomeService
{
    private const RANK_INCOME_PERCENTAGES = [
        '1-star'  => 1.0,
        'bronze'  => 2.0,
        'silver'  => 3.0,
        'gold'    => 4.0,
        'ruby'    => 5.0,
        'diamond' => 6.0,
        'crown'   => 7.0,
    ];

    public function __construct(
        private readonly IncomeBaseProvider $incomeBaseProvider,
        private readonly IncomeLogService $incomeLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function processRankIncome(Order $order): array
    {
        $results = [];
        $buyerUserId = $order->user_id;

        $sponsorTree = MLMTree::where('mlm_user_id', $buyerUserId)->first();
        if (!$sponsorTree || !$sponsorTree->parent_id) {
            Log::info('No sponsor found for rank income', ['user_id' => $buyerUserId]);
            return $results;
        }

        $sponsorUserId = $sponsorTree->parent_id;

        $userRank = UserRank::where('mlm_user_id', $sponsorUserId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        if (!$userRank || !$userRank->rank) {
            Log::info('Sponsor has no rank', ['sponsor_id' => $sponsorUserId, 'buyer_id' => $buyerUserId]);
            return $results;
        }

        $rankSlug = $userRank->rank->slug;
        $rankPct = self::RANK_INCOME_PERCENTAGES[$rankSlug] ?? 0;

        if ($rankPct <= 0) {
            Log::info('No rank income percentage configured for slug', [
                'slug' => $rankSlug,
                'sponsor_id' => $sponsorUserId,
            ]);
            return $results;
        }

        $baseAmount = $this->incomeBaseProvider->getBaseAmount($order);
        $incomeAmount = $baseAmount * ($rankPct / 100);

        if ($incomeAmount <= 0) {
            return $results;
        }

        try {
            DB::transaction(function () use ($sponsorUserId, $incomeAmount, $order, $buyerUserId, $rankSlug, $rankPct, $userRank, &$results) {
                $wallet = WalletBalance::firstOrCreate(
                    ['user_id' => $sponsorUserId, 'wallet_id' => 1],
                    ['balance' => 0, 'total_earned' => 0]
                );

                $wallet->increment('balance', $incomeAmount);
                $wallet->increment('total_earned', $incomeAmount);
                $wallet->refresh();

                WalletTransaction::create([
                    'wallet_id' => 1,
                    'user_id' => $sponsorUserId,
                    'type' => 'credit',
                    'amount' => $incomeAmount,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'rank_income',
                    'reference_id' => $order->id,
                    'status' => 'completed',
                    'description' => "Rank income ({$userRank->rank->name}) from User #{$buyerUserId} (Order #{$order->id})",
                ]);

                $ccPoints = (float) ($order->total_cc_points ?? 0);

                $this->incomeLogService->logFromOrder(
                    order: $order,
                    earnerUserId: $sponsorUserId,
                    incomeType: 'rank',
                    ccAmount: $ccPoints,
                    currencyAmount: $incomeAmount,
                    fromUserId: $buyerUserId,
                    remarks: "Rank income ({$userRank->rank->name}) - {$rankPct}% of {$this->incomeBaseProvider->getLabel()}",
                );

                $this->notificationService->createIncomeNotification(
                    $sponsorUserId,
                    $incomeAmount,
                    "Rank Income - {$userRank->rank->name}"
                );

                $results[] = [
                    'user_id' => $sponsorUserId,
                    'rank' => $userRank->rank->name,
                    'rank_slug' => $rankSlug,
                    'percentage' => $rankPct,
                    'amount' => $incomeAmount,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Rank income failed', [
                'sponsor_id' => $sponsorUserId,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    public static function getRankIncomeConfig(): array
    {
        return self::RANK_INCOME_PERCENTAGES;
    }
}
