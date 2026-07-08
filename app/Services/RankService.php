<?php
namespace App\Services;

use App\Models\CCSetting;
use App\Models\IncomeLog;
use App\Models\Rank;
use App\Models\UserRank;
use App\Models\MlmUser;
use App\Models\OrderItem;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankService
{
    public function __construct(
        private readonly SelfCCService $selfCCService,
        private readonly NotificationService $notificationService,
        private readonly IncomeLogService $incomeLogService,
    ) {}

    private function getTotalCc(int $userId): float
    {
        return $this->selfCCService->getTotalCcAsSponsor($userId);
    }

    public function checkAndUpgradeRank(int $userId): ?UserRank
    {
        $totalCC = $this->getTotalCc($userId);

        $currentRank = UserRank::where('mlm_user_id', $userId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        $currentSortOrder = $currentRank ? $currentRank->rank->sort_order : 0;

        $nextRank = Rank::where('is_active', true)
            ->where('sort_order', '>', $currentSortOrder)
            ->where('required_self_cc', '<=', $totalCC)
            ->orderBy('sort_order', 'desc')
            ->first();

        if (!$nextRank) {
            return null;
        }

        if ($currentRank && $currentRank->rank_id === $nextRank->id) {
            return $currentRank;
        }

        try {
            return DB::transaction(function () use ($userId, $nextRank, $currentRank, $totalCC) {
                if ($currentRank) {
                    $currentRank->update(['is_current' => false]);
                }

                $userRank = UserRank::create([
                    'mlm_user_id' => $userId,
                    'rank_id' => $nextRank->id,
                    'current_cc_at_time' => $totalCC,
                    'is_current' => true,
                    'achieved_at' => now(),
                ]);

                $tree = \App\Models\MLMTree::where('mlm_user_id', $userId)->first();
                if ($tree) {
                    $tree->update(['rank' => $nextRank->slug]);
                }

                $this->generateRankIncome($userId, $nextRank, $userRank);

                return $userRank;
            });
        } catch (\Throwable $e) {
            Log::error('Rank upgrade failed', [
                'user_id' => $userId,
                'rank_id' => $nextRank->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function generateRankIncome(int $userId, Rank $rank, UserRank $userRank): void
    {
        $reward = $rank->reward;
        if (!$reward) {
            Log::info('No reward configured for rank', ['rank_id' => $rank->id, 'rank' => $rank->name]);
            return;
        }

        $rewardValueCc = (float) ($reward->value_cc ?? 0);
        if ($rewardValueCc <= 0) {
            return;
        }

        $ccRate = CCSetting::getActiveRate();
        $currencyAmount = $rewardValueCc * $ccRate;

        $wallet = WalletBalance::firstOrCreate(
            ['user_id' => $userId, 'wallet_id' => 1],
            ['balance' => 0, 'total_earned' => 0]
        );

        $wallet->increment('balance', $currencyAmount);
        $wallet->increment('total_earned', $currencyAmount);
        $wallet->refresh();

        WalletTransaction::create([
            'wallet_id' => 1,
            'user_id' => $userId,
            'type' => 'credit',
            'amount' => $currencyAmount,
            'balance_after' => $wallet->balance,
            'reference_type' => 'rank_income',
            'reference_id' => $userRank->id,
            'status' => 'completed',
            'description' => "Rank income for achieving {$rank->name}",
        ]);

        $this->incomeLogService->log(
            userId: $userId,
            incomeType: 'rank',
            ccAmount: $rewardValueCc,
            currencyAmount: $currencyAmount,
            referenceType: 'rank',
            referenceId: $userRank->id,
            remarks: "Rank income for achieving {$rank->name} - {$rewardValueCc} CC",
        );

        $this->notificationService->createIncomeNotification(
            $userId,
            $currencyAmount,
            "Rank Income - {$rank->name}"
        );

        Log::info('Rank income generated', [
            'user_id' => $userId,
            'rank' => $rank->name,
            'value_cc' => $rewardValueCc,
            'currency_amount' => $currencyAmount,
        ]);
    }

    public function getUserCurrentRank(int $userId): ?Rank
    {
        $userRank = UserRank::where('mlm_user_id', $userId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        return $userRank ? $userRank->rank : null;
    }

    public function getRankProgress(int $userId): array
    {
        $totalCC = $this->getTotalCc($userId);

        $currentRank = UserRank::where('mlm_user_id', $userId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        $allRanks = Rank::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $progress = [];
        foreach ($allRanks as $rank) {
            $achieved = $totalCC >= $rank->required_self_cc;
            $progress[] = [
                'rank' => $rank,
                'required_cc' => $rank->required_self_cc,
                'current_cc' => $totalCC,
                'achieved' => $achieved,
                'is_current' => $currentRank && $currentRank->rank_id === $rank->id,
                'reward_cc' => $rank->reward?->value_cc ?? 0,
                'progress_percent' => $rank->required_self_cc > 0
                    ? min(100, ($totalCC / $rank->required_self_cc) * 100)
                    : 0,
            ];
        }

        return $progress;
    }

    public function getTotalRankIncome(int $userId): float
    {
        return (float) IncomeLog::where('user_id', $userId)
            ->where('income_type', 'rank')
            ->sum('currency_amount');
    }
}
