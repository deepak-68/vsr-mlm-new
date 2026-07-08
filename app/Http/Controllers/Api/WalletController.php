<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\CcPointSetting;
use App\Models\CCSetting;
use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\OrderItem;
use App\Models\PayoutTransaction;
use App\Models\Product;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function directIncome(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);
        $user = MlmUser::findOrFail($request->user_id);
        $userId = $user->id;

        $directIncome = WalletTransaction::with('user')->where('user_id', $userId)->where('wallet_id', 1)->get();
        $totalAmount = $directIncome->sum('amount');
        
        return response()->json([
            'success' => true,
            'data' => $directIncome,
            'total' => $totalAmount,
        ]);
    }

    public function matchingIncome(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);
        $user = MlmUser::findOrFail($request->user_id);
        $userId = $user->id;

        $directIncome = WalletTransaction::with('user')->where('user_id', $userId)->where('wallet_id', 2)->get();
        $totalAmount = $directIncome->sum('amount');
        
        return response()->json([
            'success' => true,
            'data' => $directIncome,
            'total' => $totalAmount,
        ]);
    }

    public function userWallet(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:mlm_users,id',
        ]);

        $userId = $request->user_id;

        // Total CC earned from all income logs
        $directIncomeCC = IncomeLog::where('user_id', $userId)
            ->where('income_type', 'direct')
            ->sum('cc_amount');

        $payoutCC = PayoutTransaction::where('mlm_user_id', $userId)
            ->sum('cc_amount');

        // Total CC from fund wallet balance (wallet_id = 1)
        $walletBalance = WalletBalance::where('user_id', $userId)->where('wallet_id', 1)->first();
        $fundWalletBalance = $walletBalance?->balance ?? 0;

        $totalCC = $directIncomeCC + $payoutCC;

        // Get conversion rate
        $conversionRate = CCSetting::getActiveRate();
        $convertedAmount = round($totalCC * $conversionRate, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'total_cc' => $totalCC,
                'converted_amount' => $convertedAmount,
                'conversion_rate' => $conversionRate,
                'direct_cc' => $directIncomeCC,
                'payout_cc' => $payoutCC,
                'fund_wallet_balance' => $fundWalletBalance,
            ],
        ]);
    }
}
