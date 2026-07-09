<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\PayoutBalance;
use App\Models\UserRank;
use App\Models\WalletBalance;
use App\Models\Kyc;
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

        // Direct Income CC from income_logs
        $directIncomeCC = \App\Models\IncomeLog::where('user_id', $userId)
            ->where('income_type', 'direct')
            ->sum('cc_amount');

        // User rank
        $userRank = UserRank::where('mlm_user_id', $userId)->where('is_current', true)
            ->with('rank')->first();
        $currentRankName = $userRank?->rank?->name ?? 'Fresh';

        // Wallet balance
        $walletBalance = WalletBalance::where('user_id', $userId)->where('wallet_id', 1)->first();
        $fundWallet = $walletBalance?->balance ?? 0;

        // ===== 6 INCOME KPIs from income_logs =====

        $dirLogs = \App\Models\IncomeLog::where('user_id', $userId)->where('income_type', 'direct');
        $directIncomeCC = (clone $dirLogs)->sum('cc_amount');
        $directIncomeAmount = (clone $dirLogs)->sum('currency_amount');
        $directIncomeLifetime = $directIncomeCC;

        $matLogs = IncomeLog::where('user_id', $userId)->where('income_type', 'matching');
        $matchingIncomeCC = (clone $matLogs)->sum('cc_amount');
        $matchingIncomeAmount = (clone $matLogs)->sum('currency_amount');
        $matchingIncomeLifetime = $matchingIncomeCC;

        $levLogs = \App\Models\IncomeLog::where('user_id', $userId)->where('income_type', 'level');
        $levelIncomeCC = (clone $levLogs)->sum('cc_amount');
        $levelIncomeAmount = (clone $levLogs)->sum('currency_amount');
        $levelIncomeLifetime = $levelIncomeCC;

        $repLogs = \App\Models\IncomeLog::where('user_id', $userId)->where('income_type', 'repurchase');
        $repurchaseIncomeCC = (clone $repLogs)->sum('cc_amount');
        $repurchaseIncomeAmount = (clone $repLogs)->sum('currency_amount');
        $repurchaseIncomeLifetime = $repurchaseIncomeCC;

        $rankLogs = \App\Models\IncomeLog::where('user_id', $userId)->where('income_type', 'rank');
        $rankIncomeCC = (clone $rankLogs)->sum('cc_amount');
        $rankIncomeAmount = (clone $rankLogs)->sum('currency_amount');
        $rankIncomeLifetime = $rankIncomeCC;

        // Total across all income types
        $totalIncomeCC = $directIncomeCC + $matchingIncomeCC + $levelIncomeCC
            + $repurchaseIncomeCC + $rankIncomeCC;
        $totalIncomeAmount = $directIncomeAmount + $matchingIncomeAmount + $levelIncomeAmount
            + $repurchaseIncomeAmount + $rankIncomeAmount;

        // Current left/right CC (from payout balance)
        $payoutBalance = PayoutBalance::where('mlm_user_id', $userId)->first();
        $currentLeftCC = $payoutBalance?->left_cc ?? 0;
        $currentRightCC = $payoutBalance?->right_cc ?? 0;

        // KYC Status
        $kycRecord = Kyc::where('user_id', $userId)->latest()->first();
        $kycStatus = $kycRecord?->status ?? 'not_submitted';

        // Order history (last 10)
        $orderHistory = Order::with(['items', 'invoice', 'purchasedForUser'])
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
                'direct_cc' => $directIncomeCC,
                'fund_wallet' => $fundWallet,
                'current_left_cc' => $currentLeftCC,
                'current_right_cc' => $currentRightCC,
                'order_history' => $orderHistory,

                // 7 Income KPIs
                'income_kpis' => [ 
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

                'kyc_status' => $kycStatus,

                'total_income_cc' => $totalIncomeCC,
                'total_income_amount' => $totalIncomeAmount,
            ]
        ]);
    }
}
