<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $user = DB::table('mlm_users')
            ->where('id', $userId)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found');
        }

        // Get Left Team - Direct left referrals from mlm_trees
        $leftTeam = DB::table('mlm_trees')
            ->where('parent_id', $userId)
            ->where('position', 'left')
            ->count();

        // Get Right Team - Direct right referrals from mlm_trees
        $rightTeam = DB::table('mlm_trees')
            ->where('parent_id', $userId)
            ->where('position', 'right')
            ->count();

        // Get Total Downline Left using closure table
        $totalDownlineLeft = DB::table('mlm_tree_closures')
            ->whereIn('descendant_id', function($query) use ($userId) {
                $query->select('descendant_id')
                      ->from('mlm_tree_closures')
                      ->join('mlm_trees', 'mlm_tree_closures.descendant_id', '=', 'mlm_trees.mlm_user_id')
                      ->where('mlm_tree_closures.ancestor_id', $userId)
                      ->where('mlm_tree_closures.descendant_id', '!=', $userId)
                      ->where('mlm_trees.position', 'left');
            })
            ->count();

        // Get Total Downline Right using closure table
        $totalDownlineRight = DB::table('mlm_tree_closures')
            ->whereIn('descendant_id', function($query) use ($userId) {
                $query->select('descendant_id')
                      ->from('mlm_tree_closures')
                      ->join('mlm_trees', 'mlm_tree_closures.descendant_id', '=', 'mlm_trees.mlm_user_id')
                      ->where('mlm_tree_closures.ancestor_id', $userId)
                      ->where('mlm_tree_closures.descendant_id', '!=', $userId)
                      ->where('mlm_trees.position', 'right');
            })
            ->count();

        // Get Direct Business Count (direct referrals from sponsor_id)
        $directBusiness = DB::table('mlm_users')
            ->where('sponsor_id', $userId)
            ->where('is_deleted', 0)
            ->count();

        // Get Total Direct Business Value
        $totalDirectBusiness = DB::table('mlm_users')
            ->join('mlm_trees', 'mlm_users.id', '=', 'mlm_trees.mlm_user_id')
            ->where('mlm_users.sponsor_id', $userId)
            ->where('mlm_users.is_deleted', 0)
            ->sum('mlm_trees.business_volume');

        // Get User's own business volume from mlm_trees
        $userTree = DB::table('mlm_trees')
            ->where('mlm_user_id', $userId)
            ->first();

        // Get Incomes from mlm_trees
        $totalIncome = $userTree->earned_amount ?? 0;
        $directIncome = 0;
        $matchingIncome = 0;
        $generationIncome = 0;

        // Try to get from income table if exists
        if (DB::getSchemaBuilder()->hasTable('mlm_income')) {
            $directIncome = DB::table('mlm_income')
                ->where('user_id', $userId)
                ->where('income_type', 'direct')
                ->sum('amount');

            $matchingIncome = DB::table('mlm_income')
                ->where('user_id', $userId)
                ->where('income_type', 'matching')
                ->sum('amount');

            $generationIncome = DB::table('mlm_income')
                ->where('user_id', $userId)
                ->where('income_type', 'generation')
                ->sum('amount');
        }

        // Get Order History
        $orderHistory = [];
        if (DB::getSchemaBuilder()->hasTable('orders')) {
            $orderHistory = DB::table('orders')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // Get User Rank from mlm_trees
        $userRank = $userTree->rank ?? 'Fresh';

        // Self CC (Personal Business Volume from mlm_trees)
        $selfCC = $userTree->business_volume ?? 0;

        // Current Left CC / Right CC from mlm_trees
        $currentLeftCC = DB::table('mlm_trees')
            ->where('parent_id', $userId)
            ->where('position', 'left')
            ->sum('business_volume');

        $currentRightCC = DB::table('mlm_trees')
            ->where('parent_id', $userId)
            ->where('position', 'right')
            ->sum('business_volume');

        // Fund Wallet
        $fundWallet = 0;
        if (DB::getSchemaBuilder()->hasTable('mlm_wallet')) {
            $fundWallet = DB::table('mlm_wallet')
                ->where('user_id', $userId)
                ->where('wallet_type', 'fund')
                ->sum('amount');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'userTree' => $userTree,
                'leftTeam' => $leftTeam,
                'rightTeam' => $rightTeam,
                'totalDownlineLeft' => $totalDownlineLeft,
                'totalDownlineRight' => $totalDownlineRight,
                'directBusiness' => $directBusiness,
                'totalDirectBusiness' => $totalDirectBusiness,
                'totalIncome' => $totalIncome,
                'directIncome' => $directIncome,
                'matchingIncome' => $matchingIncome,
                'generationIncome' => $generationIncome,
                'orderHistory' => $orderHistory,
                'userRank' => $userRank,
                'selfCC' => $selfCC,
                'currentLeftCC' => $currentLeftCC,
                'currentRightCC' => $currentRightCC,
                'fundWallet' => $fundWallet,
            ]
        ]);
    }
}
