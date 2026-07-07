<?php
namespace App\Services;

use App\Models\Rank;
use App\Models\UserRank;
use App\Models\MlmUser;
use App\Models\OrderItem;

class RankService
{
    public function __construct(
        private readonly SelfCCService $selfCCService,
    ) {}

    /**
     * Get total CC for a user including CC from orders where
     * this user is the sponsor of the purchased_for_user.
     */
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
                'progress_percent' => $rank->required_self_cc > 0
                    ? min(100, ($totalCC / $rank->required_self_cc) * 100)
                    : 0,
            ];
        }

        return $progress;
    }
}
