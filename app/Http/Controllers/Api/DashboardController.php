<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MlmUser;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\PayoutBalance;
use App\Models\PayoutTransaction;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\UserReward;
use App\Models\UserRank;
use App\Models\Rank;
use App\Http\Resources\MlmUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $user = MlmUser::with('detail:id,user_id,profile_image')
            ->where('id', $userId)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Left/Right team counts
        $leftTeam = DB::table('mlm_trees')->where('parent_id', $userId)->where('position', 'left')->count();
        $rightTeam = DB::table('mlm_trees')->where('parent_id', $userId)->where('position', 'right')->count();

        // Direct business
        $directBusiness = MlmUser::where('sponsor_id', $userId)->where('is_deleted', 0)->count();

        // Self CC (total CC from completed orders)
        $selfCC = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $userId)->where('status', 'COMPLETED'))
            ->sum('cc_points');

        // User rank
        $userRank = UserRank::where('mlm_user_id', $userId)->where('is_current', true)
            ->with('rank')->first();
        $currentRankName = $userRank?->rank?->name ?? 'Fresh';

        // Wallet balance
        $walletBalance = WalletBalance::where('user_id', $userId)->where('wallet_id', 1)->first();
        $fundWallet = $walletBalance?->balance ?? 0;

        // ===== 7 INCOME KPIs =====

        // 1. Retail Income — direct sales commission from own product purchases (self-commission)
        $retailIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'retail_income')->sum('cc_amount');
        $retailIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'retail_income')->sum('currency_amount');
        $retailIncomeLifetime = $retailIncomeCC;

        // 2. Direct Income — sponsor commission
        $directIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'direct_income')->orWhere('type', 'direct_income')->sum('cc_amount');
        $directIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'direct_income')->sum('currency_amount');
        $directIncomeLifetime = $directIncomeCC;

        // 3. Matching Income — binary pair commission
        $matchingIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'matching_income')->sum('cc_amount');
        $matchingIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'matching_income')->sum('currency_amount');
        $matchingIncomeLifetime = $matchingIncomeCC;

        // 4. Level Income — generation/level commission
        $levelIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'level_income')->sum('cc_amount');
        $levelIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'level_income')->sum('currency_amount');
        $levelIncomeLifetime = $levelIncomeCC;

        // 5. Reward & Tour Income
        $rewardIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'reward_income')->sum('cc_amount');
        $rewardIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'reward_income')->sum('currency_amount');
        $rewardIncomeLifetime = $rewardIncomeCC;

        // 6. Repurchase Income — commission from own repurchases
        $repurchaseIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'repurchase_income')->sum('cc_amount');
        $repurchaseIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'repurchase_income')->sum('currency_amount');
        $repurchaseIncomeLifetime = $repurchaseIncomeCC;

        // 7. Rank Income — rank-based bonuses
        $rankIncomeCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'rank_bonus')->sum('cc_amount');
        $rankIncomeAmount = PayoutTransaction::where('mlm_user_id', $userId)
            ->where('type', 'rank_bonus')->sum('currency_amount');
        $rankIncomeLifetime = $rankIncomeCC;

        // Total across all income types
        $totalIncomeCC = $retailIncomeCC + $directIncomeCC + $matchingIncomeCC + $levelIncomeCC
            + $rewardIncomeCC + $repurchaseIncomeCC + $rankIncomeCC;

        // Current left/right CC (from payout balance)
        $payoutBalance = PayoutBalance::where('mlm_user_id', $userId)->first();
        $currentLeftCC = $payoutBalance?->left_cc ?? 0;
        $currentRightCC = $payoutBalance?->right_cc ?? 0;

        // Order history (last 10)
        $orderHistory = Order::with('items')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new MlmUserResource($user),
                'user_rank' => $currentRankName,
                'left_team' => $leftTeam,
                'right_team' => $rightTeam,
                'direct_business' => $directBusiness,
                'self_cc' => $selfCC,
                'fund_wallet' => $fundWallet,
                'current_left_cc' => $currentLeftCC,
                'current_right_cc' => $currentRightCC,
                'order_history' => $orderHistory,

                // 7 Income KPIs
                'income_kpis' => [
                    'retail_income' => [
                        'label' => 'Retail Income',
                        'current_cc' => $retailIncomeCC,
                        'current_amount' => $retailIncomeAmount,
                        'lifetime_total' => $retailIncomeLifetime,
                    ],
                    'direct_income' => [
                        'label' => 'Direct Income',
                        'current_cc' => $directIncomeCC,
                        'current_amount' => $directIncomeAmount,
                        'lifetime_total' => $directIncomeLifetime,
                    ],
                    'matching_income' => [
                        'label' => 'Matching Income',
                        'current_cc' => $matchingIncomeCC,
                        'current_amount' => $matchingIncomeAmount,
                        'lifetime_total' => $matchingIncomeLifetime,
                    ],
                    'level_income' => [
                        'label' => 'Level Income',
                        'current_cc' => $levelIncomeCC,
                        'current_amount' => $levelIncomeAmount,
                        'lifetime_total' => $levelIncomeLifetime,
                    ],
                    'reward_tour_income' => [
                        'label' => 'Reward & Tour Income',
                        'current_cc' => $rewardIncomeCC,
                        'current_amount' => $rewardIncomeAmount,
                        'lifetime_total' => $rewardIncomeLifetime,
                    ],
                    'repurchase_income' => [
                        'label' => 'Repurchase Income',
                        'current_cc' => $repurchaseIncomeCC,
                        'current_amount' => $repurchaseIncomeAmount,
                        'lifetime_total' => $repurchaseIncomeLifetime,
                    ],
                    'rank_income' => [
                        'label' => 'Rank Income',
                        'current_cc' => $rankIncomeCC,
                        'current_amount' => $rankIncomeAmount,
                        'lifetime_total' => $rankIncomeLifetime,
                    ],
                ],

                'total_income_cc' => $totalIncomeCC,
            ]
        ]);
    }
}
