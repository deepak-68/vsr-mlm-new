<?php
namespace App\Services;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\MlmUser;

class RewardService
{
    public function checkAndAssignReward(int $userId, int $rankId): ?UserReward
    {
        $reward = Reward::where('rank_id', $rankId)->first();
        if (!$reward) return null;

        $existing = UserReward::where('mlm_user_id', $userId)
            ->where('reward_id', $reward->id)
            ->first();

        if ($existing) return $existing;

        $userReward = UserReward::create([
            'mlm_user_id' => $userId,
            'reward_id' => $reward->id,
            'rank_id' => $rankId,
            'achieved_at' => now(),
            'status' => 'PENDING',
        ]);

        return $userReward;
    }

    public function claimReward(int $userRewardId): UserReward
    {
        $userReward = UserReward::findOrFail($userRewardId);
        $userReward->update([
            'claimed_at' => now(),
            'status' => 'CLAIMED',
        ]);
        return $userReward;
    }

    public function getUserRewards(int $userId): array
    {
        $userRewards = UserReward::where('mlm_user_id', $userId)
            ->with(['reward', 'rank'])
            ->orderBy('achieved_at', 'desc')
            ->get();

        return $userRewards->toArray();
    }
}
