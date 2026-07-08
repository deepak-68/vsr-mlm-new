<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Reward;
use App\Models\UserReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardTourService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function checkAndGenerateRewardIncome(int $userId, ?int $rankId = null): ?UserReward
    {
        $reward = null;
        if ($rankId) {
            $reward = Reward::where('rank_id', $rankId)->first();
        }

        if (!$reward) {
            $userRank = \App\Models\UserRank::where('mlm_user_id', $userId)
                ->where('is_current', true)
                ->with('rank.reward')
                ->first();

            if (!$userRank || !$userRank->rank || !$userRank->rank->reward) {
                return null;
            }
            $reward = $userRank->rank->reward;
        }

        $existing = UserReward::where('mlm_user_id', $userId)
            ->where('reward_id', $reward->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($userId, $reward) {
                $userReward = UserReward::create([
                    'mlm_user_id' => $userId,
                    'reward_id' => $reward->id,
                    'rank_id' => $reward->rank_id,
                    'achieved_at' => now(),
                    'status' => 'PENDING',
                ]);

                $rewardValue = (float) ($reward->value_cc ?? 0);

                $this->notificationService->createRewardNotification($userId, $reward->name);

                Log::info('Reward achieved', [
                    'user_id' => $userId,
                    'reward_id' => $reward->id,
                    'reward_name' => $reward->name,
                    'value_cc' => $rewardValue,
                ]);

                return $userReward;
            });
        } catch (\Throwable $e) {
            Log::error('Reward achievement failed', [
                'user_id' => $userId,
                'reward_id' => $reward->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getQualifiedRewards(int $userId): array
    {
        $totalCC = $this->getUserTotalCC($userId);

        $allRewards = Reward::where('is_active', true)
            ->with('rank')
            ->get();

        $qualified = [];
        foreach ($allRewards as $reward) {
            $alreadyClaimed = UserReward::where('mlm_user_id', $userId)
                ->where('reward_id', $reward->id)
                ->exists();

            if ($alreadyClaimed) {
                continue;
            }

            $requiredCC = $reward->rank?->required_self_cc ?? 0;
            $isQualified = $totalCC >= $requiredCC;

            $qualified[] = [
                'reward' => $reward,
                'rank' => $reward->rank,
                'required_cc' => $requiredCC,
                'current_cc' => $totalCC,
                'qualified' => $isQualified,
                'progress_percent' => $requiredCC > 0
                    ? min(100, ($totalCC / $requiredCC) * 100)
                    : 0,
            ];
        }

        return $qualified;
    }

    public function getUserRewards(int $userId): array
    {
        $userRewards = UserReward::where('mlm_user_id', $userId)
            ->with(['reward.rank'])
            ->orderBy('achieved_at', 'desc')
            ->get()
            ->toArray();

        return $userRewards;
    }

    private function getUserTotalCC(int $userId): float
    {
        return (float) OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', 'COMPLETED');
        })->sum('cc_points');
    }
}
