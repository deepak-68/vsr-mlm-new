<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\PayoutBalance;
use App\Models\PayoutTransaction;
use App\Models\Rank;
use App\Models\UserRank;
use App\Models\UserReward;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Services\IncomeService;
use App\Services\LevelIncomeService;
use App\Services\PayoutService;
use App\Services\RankService;
use App\Services\RepurchaseIncomeService;
use App\Services\RewardTourService;
use Illuminate\Http\Request;

class WalletIncomeApiController extends Controller
{
    public function __construct(
        private readonly IncomeService $incomeService,
        private readonly LevelIncomeService $levelIncomeService,
        private readonly RepurchaseIncomeService $repurchaseIncomeService,
        private readonly PayoutService $payoutService,
        private readonly RankService $rankService,
        private readonly RewardTourService $rewardTourService,
    ) {}

    public function getWalletTransactions(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = WalletTransaction::where('user_id', $request->user_id);

            if ($request->filled('reference_type') && $request->reference_type !== 'all') {
                $query->where('reference_type', $request->reference_type);
            }
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->orderBy('created_at', 'desc')->get();
            $totalCredit = $transactions->where('type', 'credit')->sum('amount');
            $totalDebit = $transactions->where('type', 'debit')->sum('amount');

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'totals' => ['credit' => $totalCredit, 'debit' => $totalDebit],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDirectIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'direct');
    }

    public function getMatchingIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'matching');
    }

    public function getLevelIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'level');
    }

    public function getRepurchaseIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'repurchase');
    }

    public function getRankIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'rank');
    }

    public function getRewardTourIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'reward_tour');
    }

    private function getIncomeByType(Request $request, string $incomeType)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = IncomeLog::where('user_id', $request->user_id)
                ->where('income_type', $incomeType)
                ->with('fromUser:id,user_name,first_name,last_name');

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->input('per_page', 50);
            $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $totalCurrency = $logs->sum('currency_amount');
            $totalCc = $logs->sum('cc_amount');

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'totals' => [
                    'currency_amount' => $totalCurrency,
                    'cc_amount' => $totalCc,
                ],
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getGenerationIncome(Request $request)
    {
        return $this->getIncomeByType($request, 'level');
    }

    public function getIncomeSummary(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $summary = $this->incomeService->getIncomeSummary($request->user_id);

            $rankIncome = $this->rankService->getTotalRankIncome($request->user_id);
            $rewardTourIncome = $this->rewardTourService->getTotalRewardIncome($request->user_id);

            $summary['rank_income'] = $rankIncome;
            $summary['reward_tour_income'] = $rewardTourIncome;
            $summary['total_income'] += $rankIncome + $rewardTourIncome;

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getAccountSummary(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = WalletTransaction::where('user_id', $request->user_id);

            $type = $request->get('type', 'all');
            switch ($type) {
                case 'current_business':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'date_calendar':
                    if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
                    if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
                    break;
                case 'closing_wise':
                    $query->whereDate('created_at', '>=', now()->subDays(30));
                    break;
            }

            $transactions = $query->orderBy('created_at', 'desc')->get();
            $totalCredit = $transactions->where('type', 'credit')->sum('amount');
            $totalDebit = $transactions->where('type', 'debit')->sum('amount');

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'totals' => ['credit' => $totalCredit, 'debit' => $totalDebit],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDownlineRank(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $progress = $this->rankService->getRankProgress($request->user_id);

            $userRanks = UserRank::where('mlm_user_id', $request->user_id)
                ->with('rank')
                ->orderBy('achieved_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_rank_progress' => $progress,
                    'rank_history' => $userRanks,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getWeeklyPayout(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $payoutSummary = $this->payoutService->getUserPayoutSummary($request->user_id);

            $payoutTransactions = PayoutTransaction::where('mlm_user_id', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $payoutSummary,
                    'transactions' => $payoutTransactions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getAwardsRewards(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $userRewards = $this->rewardTourService->getUserRewards($request->user_id);
            $qualifiedRewards = $this->rewardTourService->getQualifiedRewards($request->user_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'claimed_rewards' => $userRewards,
                    'qualified_rewards' => $qualifiedRewards,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRetreatTours(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $qualified = $this->rewardTourService->getQualifiedRewards($request->user_id);
            $claimed = $this->rewardTourService->getUserRewards($request->user_id);

            $payoutBalance = PayoutBalance::where('mlm_user_id', $request->user_id)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'qualified_rewards' => $qualified,
                    'claimed_rewards' => $claimed,
                    'current_left_cc' => $payoutBalance?->left_cc ?? 0,
                    'current_right_cc' => $payoutBalance?->right_cc ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCashBonusRequests(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = \App\Models\CashBonusRequest::where('user_id', $request->user_id);
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('status', $request->type);
            }
            $data = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getClaimCashRequests(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = \App\Models\ClaimCashRequest::where('user_id', $request->user_id);
            if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
            if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
            $data = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCashBonusHistory(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $query = \App\Models\CashBonusHistory::where('user_id', $request->user_id);
            if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
            if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
            $data = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getOrderHistory(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:mlm_users,id']);

        try {
            $orders = \App\Models\Order::where('user_id', $request->user_id)
                ->with(['items.product', 'invoice'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $orders, 'count' => $orders->count()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getByHandDelivery(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getCourierDelivery(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getByHandAward(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getByCourierAward(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getOtherProducts(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }
}
