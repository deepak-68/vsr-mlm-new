<?php
namespace App\Services;

use App\Models\Rank;
use App\Models\UserRank;
use App\Models\MlmUser;
use App\Models\OrderItem;

class RankService
{
    public function checkAndUpgradeRank(int $userId): ?UserRank
    {
        $selfCC = OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', 'COMPLETED');
        })->sum('cc_points');

        $currentRank = UserRank::where('mlm_user_id', $userId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        $currentSortOrder = $currentRank ? $currentRank->rank->sort_order : 0;

        $nextRank = Rank::where('is_active', true)
            ->where('sort_order', '>', $currentSortOrder)
            ->where('required_self_cc', '<=', $selfCC)
            ->orderBy('sort_order', 'desc')
            ->first();

        if (!$nextRank) {
            return null;
        }

        if ($currentRank && $currentRank->rank_id === $nextRank->id) {
            return $currentRank;
        }

        if ($currentRank) {
            $currentRank->update(['is_current' => false]);
        }

        $userRank = UserRank::create([
            'mlm_user_id' => $userId,
            'rank_id' => $nextRank->id,
            'current_cc_at_time' => $selfCC,
            'is_current' => true,
            'achieved_at' => now(),
        ]);

        // Set the user's rank on MLMTree if applicable
        $tree = \App\Models\MLMTree::where('mlm_user_id', $userId)->first();
        if ($tree) {
            $tree->update(['rank' => $nextRank->slug]);
        }

        return $userRank;
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
        $selfCC = OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', 'COMPLETED');
        })->sum('cc_points');

        $currentRank = UserRank::where('mlm_user_id', $userId)
            ->where('is_current', true)
            ->with('rank')
            ->first();

        $allRanks = Rank::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $progress = [];
        foreach ($allRanks as $rank) {
            $achieved = $selfCC >= $rank->required_self_cc;
            $progress[] = [
                'rank' => $rank,
                'required_cc' => $rank->required_self_cc,
                'current_cc' => $selfCC,
                'achieved' => $achieved,
                'is_current' => $currentRank && $currentRank->rank_id === $rank->id,
                'progress_percent' => $rank->required_self_cc > 0
                    ? min(100, ($selfCC / $rank->required_self_cc) * 100)
                    : 0,
            ];
        }

        return $progress;
    }
}
