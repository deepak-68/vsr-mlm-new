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
    ) {}

    public function processPairMatching($downlineUser, $orderCC, ?int $orderId = null)
    {
        $ccRate = CCSetting::getActiveRate();
        $currentNode = $downlineUser;
        $maxLevels = 10;
        $results = [];

        for ($level = 1; $level <= $maxLevels; $level++) {
            $sponsor = $currentNode->sponsor;
            if (!$sponsor || !$sponsor->is_active || $sponsor->is_deleted) break;

            $tree = \App\Models\MLMTree::where('mlm_user_id', $currentNode->id)->first();
            if (!$tree || in_array($tree->position, ['none', null])) break;

            $position = $tree->position;
            $balance = PayoutBalance::firstOrNew(['mlm_user_id' => $sponsor->id]);

            if (!$balance->exists) {
                $balance->fill([
                    'personal_cc' => 0,
                    'left_team_cc' => 0,
                    'right_team_cc' => 0,
                    'available_balance' => 0,
                    'locked_balance' => 0,
                    'total_earned' => 0,
                    'total_withdrawn' => 0,
                    'cc_balance' => 0,
                    'left_cc' => 0,
                    'right_cc' => 0,
                    'total_matched_cc' => 0,
                    'is_payout_eligible' => false,
                ]);
            }

            if ($position === 'left') {
                $balance->left_cc += $orderCC;
                $balance->left_team_cc += $orderCC;
            } else {
                $balance->right_cc += $orderCC;
                $balance->right_team_cc += $orderCC;
            }
            $balance->save();

            $matched = min($balance->left_cc, $balance->right_cc);
            if ($matched > 0) {
                $pairCC = $matched * 2;
                $pairValue = $pairCC * $ccRate;
                $commissionPct = $sponsor->commission_percentage ?? 10;
                $grossCommission = $pairValue * ($commissionPct / 100);
                $netIncome = $grossCommission / 2;

                $balance->available_balance += $netIncome;
                $balance->total_earned += $netIncome;
                $balance->total_matched_cc += $matched;
                $balance->save();

                PayoutTransaction::create([
                    'mlm_user_id' => $sponsor->id,
                    'type' => 'matching_income',
                    'cc_amount' => $pairCC,
                    'currency_amount' => $netIncome,
                    'status' => 'credited',
                    'description' => "Pair match: {$matched} CC × 2 at Level {$level}",
                    'meta' => [
                        'matched_cc' => $matched,
                        'cc_rate' => $ccRate,
                        'commission_pct' => $commissionPct,
                        'level' => $level,
                        'order_id' => $orderId,
                        'downline_user_id' => $downlineUser->id,
                    ],
                ]);

                $this->creditMatchingIncomeToWallet($sponsor->id, $netIncome, $matched, $orderId, $downlineUser->id, $level);

                $this->incomeLogService->log(
                    userId: $sponsor->id,
                    incomeType: 'matching',
                    ccAmount: $pairCC,
                    currencyAmount: $netIncome,
                    fromUserId: $downlineUser->id,
                    referenceType: 'order',
                    referenceId: $orderId,
                    remarks: "Matching income - Pair match {$matched} CC at Level {$level}",
                );

                $this->notificationService->createIncomeNotification(
                    $sponsor->id,
                    $netIncome,
                    "Matching Income (Level {$level})"
                );

                $results[] = [
                    'sponsor_id' => $sponsor->id,
                    'level' => $level,
                    'matched_cc' => $matched,
                    'amount' => $netIncome,
                ];

                $balance->left_cc -= $matched;
                $balance->right_cc -= $matched;
                $balance->save();
            }

            $currentNode = $sponsor;
        }

        return $results;
    }

    private function creditMatchingIncomeToWallet(
        int $userId,
        float $amount,
        float $matchedCc,
        ?int $orderId,
        ?int $fromUserId,
        int $level
    ): void {
        try {
            DB::transaction(function () use ($userId, $amount, $orderId, $matchedCc, $fromUserId, $level) {
                $wallet = WalletBalance::firstOrCreate(
                    ['user_id' => $userId, 'wallet_id' => 2],
                    ['balance' => 0, 'total_earned' => 0]
                );

                $wallet->increment('balance', $amount);
                $wallet->increment('total_earned', $amount);
                $wallet->refresh();

                WalletTransaction::create([
                    'wallet_id' => 2,
                    'user_id' => $userId,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'matching_income',
                    'reference_id' => $orderId,
                    'status' => 'completed',
                    'description' => "Matching income from User #{$fromUserId} - {$matchedCc} CC matched at Level {$level}",
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to credit matching income to wallet', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getUserPayoutSummary($userId)
    {
        $config = \App\Models\PayoutConfig::first();
        $thresholdCC = $config ? $config->getThresholdCC() : 800;

        $personalCC = \App\Models\OrderItem::whereHas('order', fn($q) =>
            $q->where('user_id', $userId)->where('status', 'COMPLETED')
        )->sum('cc_points');

        $totalProducts = \App\Models\OrderItem::whereHas('order', fn($q) =>
            $q->where('user_id', $userId)->where('status', 'COMPLETED')
        )->sum('quantity');

        $productsForPayout = $config ? $config->products_for_payout : 40;
        $balance = PayoutBalance::where('mlm_user_id', $userId)->first();

        return [
            'personal_cc' => $personalCC,
            'left_team_cc' => $balance ? $balance->left_cc : 0,
            'right_team_cc' => $balance ? $balance->right_cc : 0,
            'available_balance' => $balance ? $balance->available_balance : 0,
            'locked_balance' => $balance ? $balance->locked_balance : 0,
            'total_earned' => $balance ? $balance->total_earned : 0,
            'total_matched_cc' => $balance ? $balance->total_matched_cc : 0,
            'is_eligible' => $totalProducts >= $productsForPayout,
            'threshold_cc' => $thresholdCC,
            'progress_percent' => min(100, ($totalProducts / $productsForPayout) * 100),
            'products_needed' => max(0, $productsForPayout - $totalProducts),
        ];
    }
}
